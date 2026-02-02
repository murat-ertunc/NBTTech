<?php
/**
 * Authorization Service iş kurallarını uygular.
 * Servis seviyesinde işlem akışlarını sağlar.
 */

namespace App\Services\Authorization;

use App\Core\Redis;
use App\Core\Database;
use App\Core\Transaction;

class AuthorizationService
{

    private static ?AuthorizationService $Instance = null;

    private Redis $Redis;

    private $Db;

    private int $CacheTtl;

    private const CACHE_PREFIX_PERMISSION = 'user:%d:permissions';

    private const CACHE_PREFIX_ROLE_PERMISSION = 'role:%d:permissions';

    private const CACHE_PREFIX_ROLE = 'user:%d:roles';

    private const CACHE_KEY_ALL_PERMISSIONS = 'all:permissions';

    private const CACHE_KEY_ALL_ROLES = 'all:roles';

    private function __construct()
    {
        $this->Redis = Redis::getInstance();
        $this->Db = Database::getInstance()->getConnection();

        $RedisConfig = require CONFIG_PATH . 'redis.php';
        $this->CacheTtl = $RedisConfig['ttl']['permissions'] ?? 3600;
    }

    public static function getInstance(): self
    {
        if (self::$Instance === null) {
            self::$Instance = new self();
        }
        return self::$Instance;
    }

    public function can(int $UserId, string $PermissionKodu): bool
    {
        $Permissionlar = $this->kullaniciPermissionlariGetir($UserId);
        return in_array($PermissionKodu, $Permissionlar, true);
    }

    public function izinVarMi(int $UserId, string $PermissionKodu): bool
    {
        return $this->can($UserId, $PermissionKodu);
    }

    public function izinlerdenBiriVarMi(int $UserId, array $PermissionKodlari): bool
    {
        foreach ($PermissionKodlari as $PermissionKodu) {
            if ($this->can($UserId, $PermissionKodu)) {
                return true;
            }
        }
        return false;
    }

    public function tumIzinlerVarMi(int $UserId, array $PermissionKodlari): bool
    {
        foreach ($PermissionKodlari as $PermissionKodu) {
            if (!$this->can($UserId, $PermissionKodu)) {
                return false;
            }
        }
        return true;
    }

    public function modulErisimVarMi(int $UserId, string $ModulAdi): bool
    {
        $Permissionlar = $this->kullaniciPermissionlariGetir($UserId);

        foreach ($Permissionlar as $Permission) {
            if (strpos($Permission, $ModulAdi . '.') === 0) {
                return true;
            }
        }

        return false;
    }

    public function tumunuGorebilirMi(int $UserId, string $ModulAdi): bool
    {
        $PermissionKodu = $ModulAdi . '.read_all';
        return $this->can($UserId, $PermissionKodu);
    }

    public function tumunuDuzenleyebilirMi(int $UserId, string $ModulAdi): bool
    {

        $PermissionKodu = $ModulAdi . '.read_all';
        return $this->can($UserId, $PermissionKodu);
    }

    public function kullaniciPermissionlariGetir(int $UserId): array
    {
        $this->superadminPermissionlariniTamamla($UserId);
        $SuperadminMi = $this->kullaniciSuperadminMi($UserId);
        $CacheKey = sprintf(self::CACHE_PREFIX_PERMISSION, $UserId);

        $CachedData = $SuperadminMi ? [] : $this->Redis->setAl($CacheKey);
        if (!$SuperadminMi && !empty($CachedData)) {
            return $CachedData;
        }

        $Sql = "
            SELECT DISTINCT p.PermissionKodu
            FROM tnm_permission p
            INNER JOIN tnm_rol_permission rp ON rp.PermissionId = p.Id AND rp.Sil = 0
            INNER JOIN tnm_user_rol ur ON ur.RolId = rp.RolId AND ur.Sil = 0
            WHERE ur.UserId = :UserId
              AND p.Sil = 0
              AND p.Aktif = 1
            ORDER BY p.PermissionKodu
        ";

        $Stmt = $this->Db->prepare($Sql);
        $Stmt->execute([':UserId' => $UserId]);
        $Sonuc = $Stmt->fetchAll(\PDO::FETCH_COLUMN);

        $Permissionlar = $Sonuc ?: [];

        if (!empty($Permissionlar)) {
            $this->Redis->sil($CacheKey);
            $this->Redis->setEkle($CacheKey, ...$Permissionlar);

            $this->Redis->kaydet($CacheKey . ':ttl', '1', $this->CacheTtl);
        }

        return $Permissionlar;
    }

    private function superadminPermissionlariniTamamla(int $UserId): void
    {
        if (!$this->kullaniciSuperadminMi($UserId)) {
            return;
        }

        $SuperadminRolId = $this->superadminRolIdGetir();
        if (!$SuperadminRolId) {
            return;
        }

        $ToplamPermission = (int) $this->Db->query("SELECT COUNT(*) FROM tnm_permission WHERE Sil = 0 AND Aktif = 1")
            ->fetchColumn();
        $AtananStmt = $this->Db->prepare("\n            SELECT COUNT(DISTINCT p.Id)\n            FROM tnm_permission p\n            INNER JOIN tnm_rol_permission rp ON rp.PermissionId = p.Id AND rp.RolId = :RolId AND rp.Sil = 0\n            WHERE p.Sil = 0 AND p.Aktif = 1\n        ");
        $AtananStmt->execute([':RolId' => $SuperadminRolId]);
        $AtananPermission = (int) $AtananStmt->fetchColumn();

        if ($AtananPermission >= $ToplamPermission) {
            return;
        }

        Transaction::wrap(function () use ($SuperadminRolId) {
            $UpdateSql = "\n                UPDATE tnm_rol_permission\n                SET Sil = 0, DegisiklikZamani = SYSUTCDATETIME(), DegistirenUserId = 1\n                WHERE RolId = :RolId AND Sil = 1\n                  AND PermissionId IN (SELECT Id FROM tnm_permission WHERE Sil = 0 AND Aktif = 1)\n            ";
            $UpdateStmt = $this->Db->prepare($UpdateSql);
            $UpdateStmt->execute([':RolId' => $SuperadminRolId]);

            $InsertSql = "\n                INSERT INTO tnm_rol_permission (Guid, EklemeZamani, EkleyenUserId, DegisiklikZamani, DegistirenUserId, Sil, RolId, PermissionId)\n                SELECT NEWID(), SYSUTCDATETIME(), 1, SYSUTCDATETIME(), 1, 0, :RolIdInsert, p.Id\n                FROM tnm_permission p\n                WHERE p.Sil = 0 AND p.Aktif = 1\n                  AND NOT EXISTS (\n                      SELECT 1 FROM tnm_rol_permission rp\n                      WHERE rp.RolId = :RolIdCheck AND rp.PermissionId = p.Id AND rp.Sil = 0\n                  )\n            ";
            $InsertStmt = $this->Db->prepare($InsertSql);
            $InsertStmt->execute([
                ':RolIdInsert' => $SuperadminRolId,
                ':RolIdCheck' => $SuperadminRolId
            ]);
        });

        $this->rolKullanicilarininCacheTemizle($SuperadminRolId);
    }

    private function kullaniciSuperadminMi(int $UserId): bool
    {
        $Sql = "\n            SELECT 1\n            FROM tnm_user_rol ur\n            INNER JOIN tnm_rol r ON r.Id = ur.RolId AND r.Sil = 0\n            WHERE ur.UserId = :UserId AND ur.Sil = 0 AND r.RolKodu = 'superadmin'\n        ";
        $Stmt = $this->Db->prepare($Sql);
        $Stmt->execute([':UserId' => $UserId]);
        return (bool) $Stmt->fetchColumn();
    }

    private function superadminRolIdGetir(): ?int
    {
        $Row = $this->Db->query("SELECT Id FROM tnm_rol WHERE RolKodu = 'superadmin' AND Sil = 0")
            ->fetch(\PDO::FETCH_ASSOC);
        return $Row ? (int) $Row['Id'] : null;
    }

    public function kullaniciRolleriGetir(int $UserId): array
    {
        $CacheKey = sprintf(self::CACHE_PREFIX_ROLE, $UserId);

        $CachedData = $this->Redis->al($CacheKey);
        if (!empty($CachedData) && is_array($CachedData)) {
            return $CachedData;
        }

        $Sql = "
            SELECT r.Id, r.RolKodu, r.RolAdi, r.Seviye, r.SistemRolu
            FROM tnm_rol r
            INNER JOIN tnm_user_rol ur ON ur.RolId = r.Id AND ur.Sil = 0
            WHERE ur.UserId = :UserId
              AND r.Sil = 0
              AND r.Aktif = 1
            ORDER BY r.Seviye DESC
        ";

        $Stmt = $this->Db->prepare($Sql);
        $Stmt->execute([':UserId' => $UserId]);
        $Roller = $Stmt->fetchAll(\PDO::FETCH_ASSOC);

        if (!empty($Roller)) {
            $this->Redis->kaydet($CacheKey, $Roller, $this->CacheTtl);
        }

        return $Roller ?: [];
    }

    public function rolAtayabilirMi(int $AtayanUserId, int $RolId): bool
    {

        if ($this->kullaniciSuperadminMi($AtayanUserId)) {
            return true;
        }

        $RolPermissionlari = $this->rolPermissionlariGetir($RolId);

        if (empty($RolPermissionlari)) {
            return true;
        }

        $KullaniciPermissionlari = $this->kullaniciPermissionlariGetir($AtayanUserId);
        return empty(array_diff($RolPermissionlari, $KullaniciPermissionlari));
    }

    public function rolDuzenleyebilirMi(int $UserId, int $RolId): bool
    {
        return $this->rolAtayabilirMi($UserId, $RolId);
    }

    public function kullaniciRolleriniDuzenleyebilirMi(int $DuzenleyenUserId, int $HedefUserId): bool
    {
        if ($this->kullaniciSuperadminMi($DuzenleyenUserId)) {
            return true;
        }

        $HedefRoller = $this->kullaniciRolleriGetir($HedefUserId);
        if (empty($HedefRoller)) {
            return true;
        }

        foreach ($HedefRoller as $Rol) {
            $RolId = (int) ($Rol['Id'] ?? 0);
            if ($RolId > 0 && !$this->rolAtayabilirMi($DuzenleyenUserId, $RolId)) {
                return false;
            }
        }

        return true;
    }

    public function rolePermissionEkleyebilirMi(int $EkleyenUserId, string $PermissionKodu): bool
    {

        return $this->can($EkleyenUserId, $PermissionKodu);
    }

    public function rolePermissionlarEkleyebilirMi(int $EkleyenUserId, array $PermissionKodlari): array
    {

        if ($this->kullaniciSuperadminMi($EkleyenUserId)) {
            return [
                'izinVerilenler' => $PermissionKodlari,
                'izinVerilmeyenler' => []
            ];
        }

        $KullaniciPermissionlari = $this->kullaniciPermissionlariGetir($EkleyenUserId);

        $IzinVerilenler = array_intersect($PermissionKodlari, $KullaniciPermissionlari);
        $IzinVerilmeyenler = array_diff($PermissionKodlari, $KullaniciPermissionlari);

        return [
            'izinVerilenler' => array_values($IzinVerilenler),
            'izinVerilmeyenler' => array_values($IzinVerilmeyenler)
        ];
    }

    public function atanabilirRolleriGetir(int $UserId): array
    {
        $Roller = $this->tumRolleriGetir();
        if (empty($Roller)) {
            return [];
        }

        $KullaniciPermissionlari = $this->kullaniciPermissionlariGetir($UserId);
        $Atanabilir = [];

        foreach ($Roller as $Rol) {
            $RolPermissionlari = $this->rolPermissionlariGetir((int)$Rol['Id']);
            if (empty($RolPermissionlari) || empty(array_diff($RolPermissionlari, $KullaniciPermissionlari))) {
                $Atanabilir[] = $Rol;
            }
        }

        return $Atanabilir;
    }

    public function kullaniciCacheTemizle(int $UserId): void
    {
        $PermissionKey = sprintf(self::CACHE_PREFIX_PERMISSION, $UserId);
        $RoleKey = sprintf(self::CACHE_PREFIX_ROLE, $UserId);

        $this->Redis->sil($PermissionKey);
        $this->Redis->sil($PermissionKey . ':ttl');
        $this->Redis->sil($RoleKey);
    }

    public function rolPermissionCacheTemizle(int $RolId): void
    {
        $RolePermissionKey = sprintf(self::CACHE_PREFIX_ROLE_PERMISSION, $RolId);
        $this->Redis->sil($RolePermissionKey);
        $this->Redis->sil($RolePermissionKey . ':ttl');
    }

    public function rolKullanicilarininCacheTemizle(int $RolId): int
    {
        $this->rolPermissionCacheTemizle($RolId);
        $Sql = "
            SELECT UserId FROM tnm_user_rol
            WHERE RolId = :RolId AND Sil = 0
        ";

        $Stmt = $this->Db->prepare($Sql);
        $Stmt->execute([':RolId' => $RolId]);
        $UserIds = $Stmt->fetchAll(\PDO::FETCH_COLUMN);

        foreach ($UserIds as $UserId) {
            $this->kullaniciCacheTemizle($UserId);
        }

        return count($UserIds);
    }

    public function tumCacheTemizle(): void
    {
        $this->Redis->patternIleSil('user:*:permissions*');
        $this->Redis->patternIleSil('user:*:roles');
        $this->Redis->patternIleSil('role:*:permissions*');
        $this->Redis->sil(self::CACHE_KEY_ALL_PERMISSIONS);
        $this->Redis->sil(self::CACHE_KEY_ALL_ROLES);
    }

    public function tumRolleriGetir(): array
    {
        $CachedData = $this->Redis->al(self::CACHE_KEY_ALL_ROLES);
        if (!empty($CachedData) && is_array($CachedData)) {
            return $CachedData;
        }

        $Sql = "
            SELECT Id, RolKodu, RolAdi, Seviye, Aciklama, SistemRolu
            FROM tnm_rol
            WHERE Sil = 0 AND Aktif = 1
            ORDER BY Seviye DESC
        ";

        $Stmt = $this->Db->query($Sql);
        $Roller = $Stmt->fetchAll(\PDO::FETCH_ASSOC);

        $this->Redis->kaydet(self::CACHE_KEY_ALL_ROLES, $Roller, $this->CacheTtl);

        return $Roller ?: [];
    }

    public function tumPermissionlariGetir(): array
    {
        $CachedData = $this->Redis->al(self::CACHE_KEY_ALL_PERMISSIONS);
        if (!empty($CachedData) && is_array($CachedData)) {
            return $CachedData;
        }

        $Sql = "
            SELECT Id, PermissionKodu, ModulAdi, Aksiyon, Aciklama
            FROM tnm_permission
            WHERE Sil = 0 AND Aktif = 1
            ORDER BY ModulAdi, Aksiyon
        ";

        $Stmt = $this->Db->query($Sql);
        $Permissionlar = $Stmt->fetchAll(\PDO::FETCH_ASSOC);

        $this->Redis->kaydet(self::CACHE_KEY_ALL_PERMISSIONS, $Permissionlar, $this->CacheTtl);

        return $Permissionlar ?: [];
    }

    public function permissionlariModulBazindaGetir(): array
    {
        $Permissionlar = $this->tumPermissionlariGetir();
        $Gruplanmis = [];

        foreach ($Permissionlar as $Permission) {
            $Modul = $Permission['ModulAdi'];
            if (!isset($Gruplanmis[$Modul])) {
                $Gruplanmis[$Modul] = [];
            }
            $Gruplanmis[$Modul][] = $Permission;
        }

        return $Gruplanmis;
    }

    public function rolPermissionlariGetir(int $RolId): array
    {
        $RolePermissionKey = sprintf(self::CACHE_PREFIX_ROLE_PERMISSION, $RolId);
        $Cached = $this->Redis->setAl($RolePermissionKey);
        if (!empty($Cached)) {
            return $Cached;
        }

        $Sql = "
            SELECT p.PermissionKodu
            FROM tnm_permission p
            INNER JOIN tnm_rol_permission rp ON rp.PermissionId = p.Id AND rp.Sil = 0
            WHERE rp.RolId = :RolId
              AND p.Sil = 0
              AND p.Aktif = 1
            ORDER BY p.PermissionKodu
        ";

        $Stmt = $this->Db->prepare($Sql);
        $Stmt->execute([':RolId' => $RolId]);

        $Permissionlar = $Stmt->fetchAll(\PDO::FETCH_COLUMN) ?: [];
        if (!empty($Permissionlar)) {
            $this->Redis->sil($RolePermissionKey);
            $this->Redis->setEkle($RolePermissionKey, ...$Permissionlar);
            $this->Redis->kaydet($RolePermissionKey . ':ttl', '1', $this->CacheTtl);
        }

        return $Permissionlar;
    }

    public function frontendIcinYetkiler(int $UserId): array
    {
        $Permissionlar = $this->kullaniciPermissionlariGetir($UserId);
        $Roller = $this->kullaniciRolleriGetir($UserId);

        $ModulPermissionlari = [];
        foreach ($Permissionlar as $PermissionKodu) {
            $Parts = explode('.', $PermissionKodu, 2);
            if (count($Parts) === 2) {
                $Modul = $Parts[0];
                $Aksiyon = $Parts[1];

                if (!isset($ModulPermissionlari[$Modul])) {
                    $ModulPermissionlari[$Modul] = [];
                }
                $ModulPermissionlari[$Modul][] = $Aksiyon;
            }
        }

        return [
            'roller' => array_column($Roller, 'RolKodu'),
            'permissionlar' => $Permissionlar,
            'moduller' => $ModulPermissionlari
        ];
    }

    private function __clone() {}

    public function __wakeup()
    {
        throw new \Exception("Singleton siniflari unserialize edilemez.");
    }
}
