<?php

namespace App\Services\Authorization;

use App\Core\Redis;
use App\Core\Database;
use App\Core\Transaction;

/**
 * Yetkilendirme Servisi
 * 
 * Laravel Spatie benzeri permission yonetimi.
 * Redis cache ile yuksek performansli permission kontrolleri.
 * 
 * Ozellikler:
 * - Kullanici permission cache (Redis)
 * - Rol-permission hiyerarsisi
 * - Subset constraint (sadece sahip olunan permissionlar atanabilir)
 * - Otomatik cache invalidation
 * 
 * @package App\Services\Authorization
 */
class AuthorizationService
{
    /** @var AuthorizationService|null Singleton instance */
    private static ?AuthorizationService $Instance = null;
    
    /** @var Redis Redis instance */
    private Redis $Redis;
    
    /** @var Database Database instance */
    private $Db;
    
    /** @var int Cache TTL (saniye) */
    private int $CacheTtl;
    
    /** @var string User permission cache key prefix */
    private const CACHE_PREFIX_PERMISSION = 'user:%d:permissions';
    
    /** @var string Role permission cache key prefix */
    private const CACHE_PREFIX_ROLE_PERMISSION = 'role:%d:permissions';
    
    /** @var string Role cache key prefix */
    private const CACHE_PREFIX_ROLE = 'user:%d:roles';
    
    /** @var string All permissions cache key */
    private const CACHE_KEY_ALL_PERMISSIONS = 'all:permissions';
    
    /** @var string All roles cache key */
    private const CACHE_KEY_ALL_ROLES = 'all:roles';
    
    /**
     * Constructor
     */
    private function __construct()
    {
        $this->Redis = Redis::getInstance();
        $this->Db = Database::getInstance()->getConnection();
        
        $RedisConfig = require CONFIG_PATH . 'redis.php';
        $this->CacheTtl = $RedisConfig['ttl']['permissions'] ?? 3600;
    }
    
    /**
     * Singleton instance
     * 
     * @return self
     */
    public static function getInstance(): self
    {
        if (self::$Instance === null) {
            self::$Instance = new self();
        }
        return self::$Instance;
    }
    
    // =========================================================================
    // PERMISSION KONTROL METODLARI
    // =========================================================================
    
    /**
     * Kullanicinin belirtilen permission'a sahip olup olmadigini kontrol eder
     * 
     * @param int $UserId
     * @param string $PermissionKodu Ornek: "users.create", "invoices.read"
     * @return bool
     */
    public function can(int $UserId, string $PermissionKodu): bool
    {
        $Permissionlar = $this->kullaniciPermissionlariGetir($UserId);
        return in_array($PermissionKodu, $Permissionlar, true);
    }
    
    /**
     * @deprecated Use can() instead
     */
    public function izinVarMi(int $UserId, string $PermissionKodu): bool
    {
        return $this->can($UserId, $PermissionKodu);
    }
    
    /**
     * Kullanicinin birden fazla permission'dan herhangi birine sahip olup olmadigini kontrol eder
     * 
     * @param int $UserId
     * @param array $PermissionKodlari
     * @return bool Herhangi birine sahipse true
     */
    public function izinlerdenBiriVarMi(int $UserId, array $PermissionKodlari): bool
    {
        foreach ($PermissionKodlari as $PermissionKodu) {
            if ($this->can($UserId, $PermissionKodu)) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Kullanicinin tum belirtilen permission'lara sahip olup olmadigini kontrol eder
     * 
     * @param int $UserId
     * @param array $PermissionKodlari
     * @return bool Hepsine sahipse true
     */
    public function tumIzinlerVarMi(int $UserId, array $PermissionKodlari): bool
    {
        foreach ($PermissionKodlari as $PermissionKodu) {
            if (!$this->can($UserId, $PermissionKodu)) {
                return false;
            }
        }
        return true;
    }
    
    /**
     * Kullanicinin belirtilen moduldeki herhangi bir yetkiye sahip olup olmadigini kontrol eder
     * 
     * @param int $UserId
     * @param string $ModulAdi Ornek: "users", "invoices"
     * @return bool
     */
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
    
    /**
     * Kullanicinin belirtilen moduldeki tum kayitlari gorup goremeyecegini kontrol eder
     * Ornek: tumunuGorebilirMi($UserId, 'customers') => customers.read_all yetkisi var mi?
     * 
     * @param int $UserId
     * @param string $ModulAdi Ornek: "customers", "users"
     * @return bool
     */
    public function tumunuGorebilirMi(int $UserId, string $ModulAdi): bool
    {
        $PermissionKodu = $ModulAdi . '.read_all';
        return $this->can($UserId, $PermissionKodu);
    }
    
    /**
     * Kullanicinin belirtilen moduldeki tum kayitlari duzenleyip duzenleyemeyecegini kontrol eder
     * Ornek: tumunuDuzenleyebilirMi($UserId, 'customers') => customers.update_all yetkisi var mi?
     * 
     * @param int $UserId
     * @param string $ModulAdi Ornek: "customers", "users"
     * @return bool
     */
    public function tumunuDuzenleyebilirMi(int $UserId, string $ModulAdi): bool
    {
        // read_all varsa update icin de scope kalksin (veya ayri update_all tanimlanabilir)
        $PermissionKodu = $ModulAdi . '.read_all';
        return $this->can($UserId, $PermissionKodu);
    }
    
    // =========================================================================
    // PERMISSION LISTELEME METODLARI
    // =========================================================================
    
    /**
     * Kullanicinin tum permission kodlarini getirir (cached)
     * 
     * @param int $UserId
     * @return array Permission kodlari ["users.create", "users.read", ...]
     */
    public function kullaniciPermissionlariGetir(int $UserId): array
    {
        $this->superadminPermissionlariniTamamla($UserId);
        $SuperadminMi = $this->kullaniciSuperadminMi($UserId);
        $CacheKey = sprintf(self::CACHE_PREFIX_PERMISSION, $UserId);
        
        // Cache'den dene
        $CachedData = $SuperadminMi ? [] : $this->Redis->setAl($CacheKey);
        if (!$SuperadminMi && !empty($CachedData)) {
            return $CachedData;
        }
        
        // Veritabanindan cek
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
        
        // Cache'e kaydet
        if (!empty($Permissionlar)) {
            $this->Redis->sil($CacheKey); // Once temizle
            $this->Redis->setEkle($CacheKey, ...$Permissionlar);
            // TTL ayarla
            $this->Redis->kaydet($CacheKey . ':ttl', '1', $this->CacheTtl);
        }
        
        return $Permissionlar;
    }

    /**
     * Superadmin rolunun tum permissionlara sahip olmasini garanti eder
     * 
     * @param int $UserId
     * @return void
     */
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
    
    /**
     * Kullanici superadmin rolune sahip mi kontrol eder
     * 
     * @param int $UserId
     * @return bool
     */
    private function kullaniciSuperadminMi(int $UserId): bool
    {
        $Sql = "\n            SELECT 1\n            FROM tnm_user_rol ur\n            INNER JOIN tnm_rol r ON r.Id = ur.RolId AND r.Sil = 0\n            WHERE ur.UserId = :UserId AND ur.Sil = 0 AND r.RolKodu = 'superadmin'\n        ";
        $Stmt = $this->Db->prepare($Sql);
        $Stmt->execute([':UserId' => $UserId]);
        return (bool) $Stmt->fetchColumn();
    }
    
    /**
     * Superadmin rol Id getirir
     * 
     * @return int|null
     */
    private function superadminRolIdGetir(): ?int
    {
        $Row = $this->Db->query("SELECT Id FROM tnm_rol WHERE RolKodu = 'superadmin' AND Sil = 0")
            ->fetch(\PDO::FETCH_ASSOC);
        return $Row ? (int) $Row['Id'] : null;
    }
    
    /**
     * Kullanicinin tum rollerini getirir (cached)
     * 
     * @param int $UserId
     * @return array [['Id' => 1, 'RolKodu' => 'admin', 'RolAdi' => 'Admin', 'Seviye' => 80], ...]
     */
    public function kullaniciRolleriGetir(int $UserId): array
    {
        $CacheKey = sprintf(self::CACHE_PREFIX_ROLE, $UserId);
        
        // Cache'den dene
        $CachedData = $this->Redis->al($CacheKey);
        if (!empty($CachedData) && is_array($CachedData)) {
            return $CachedData;
        }
        
        // Veritabanindan cek
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
        
        // Cache'e kaydet
        if (!empty($Roller)) {
            $this->Redis->kaydet($CacheKey, $Roller, $this->CacheTtl);
        }
        
        return $Roller ?: [];
    }
    
    // =========================================================================
    // SUBSET CONSTRAINT METODLARI
    // =========================================================================
    
    /**
    * Bir kullanicinin baska bir kullaniciya rol atayip atayamayacagini kontrol eder
    * Kural: Atanan rolun permission seti, kullanicinin permission setinin alt kumesi olmalidir
     * 
     * @param int $AtayanUserId Rol atayan kullanici
     * @param int $RolId Atanacak rol
     * @return bool
     */
    public function rolAtayabilirMi(int $AtayanUserId, int $RolId): bool
    {
        $RolPermissionlari = $this->rolPermissionlariGetir($RolId);
        
        if (empty($RolPermissionlari)) {
            return true;
        }
        
        $KullaniciPermissionlari = $this->kullaniciPermissionlariGetir($AtayanUserId);
        return empty(array_diff($RolPermissionlari, $KullaniciPermissionlari));
    }
    
    /**
     * Bir kullanicinin bir role permission ekleyip ekleyemeyecegini kontrol eder
     * Kural: Sadece kendi sahip oldugu permissionlari ekleyebilir
     * 
     * @param int $EkleyenUserId Permission ekleyen kullanici
     * @param string $PermissionKodu Eklenecek permission
     * @return bool
     */
    public function rolePermissionEkleyebilirMi(int $EkleyenUserId, string $PermissionKodu): bool
    {
        // Kullanici bu permission'a sahip mi?
        return $this->can($EkleyenUserId, $PermissionKodu);
    }
    
    /**
     * Bir kullanicinin bir role permission listesi ekleyip ekleyemeyecegini kontrol eder
     * 
     * @param int $EkleyenUserId
     * @param array $PermissionKodlari
     * @return array ['izinVerilenler' => [...], 'izinVerilmeyenler' => [...]]
     */
    public function rolePermissionlarEkleyebilirMi(int $EkleyenUserId, array $PermissionKodlari): array
    {
        $KullaniciPermissionlari = $this->kullaniciPermissionlariGetir($EkleyenUserId);
        
        $IzinVerilenler = array_intersect($PermissionKodlari, $KullaniciPermissionlari);
        $IzinVerilmeyenler = array_diff($PermissionKodlari, $KullaniciPermissionlari);
        
        return [
            'izinVerilenler' => array_values($IzinVerilenler),
            'izinVerilmeyenler' => array_values($IzinVerilmeyenler)
        ];
    }
    
    /**
     * Kullanicinin atayabilecegi rolleri getirir
     * 
     * @param int $UserId
     * @return array
     */
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
    
    // =========================================================================
    // CACHE INVALIDATION METODLARI
    // =========================================================================
    
    /**
     * Kullanicinin permission cache'ini temizler
     * 
     * @param int $UserId
     * @return void
     */
    public function kullaniciCacheTemizle(int $UserId): void
    {
        $PermissionKey = sprintf(self::CACHE_PREFIX_PERMISSION, $UserId);
        $RoleKey = sprintf(self::CACHE_PREFIX_ROLE, $UserId);
        
        $this->Redis->sil($PermissionKey);
        $this->Redis->sil($PermissionKey . ':ttl');
        $this->Redis->sil($RoleKey);
    }
    
    /**
     * Rol permission cache'ini temizler
     * 
     * @param int $RolId
     * @return void
     */
    public function rolPermissionCacheTemizle(int $RolId): void
    {
        $RolePermissionKey = sprintf(self::CACHE_PREFIX_ROLE_PERMISSION, $RolId);
        $this->Redis->sil($RolePermissionKey);
        $this->Redis->sil($RolePermissionKey . ':ttl');
    }
    
    /**
     * Bir role sahip tum kullanicilarin cache'ini temizler
     * Rol guncellendikten sonra cagrilmali
     * 
     * @param int $RolId
     * @return int Temizlenen kullanici sayisi
     */
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
    
    /**
     * Tum permission cache'lerini temizler
     * Permission tanimi degistiginde cagrilmali
     * 
     * @return void
     */
    public function tumCacheTemizle(): void
    {
        $this->Redis->patternIleSil('user:*:permissions*');
        $this->Redis->patternIleSil('user:*:roles');
        $this->Redis->patternIleSil('role:*:permissions*');
        $this->Redis->sil(self::CACHE_KEY_ALL_PERMISSIONS);
        $this->Redis->sil(self::CACHE_KEY_ALL_ROLES);
    }
    
    // =========================================================================
    // YARDIMCI METODLAR
    // =========================================================================
    
    /**
     * Tum rolleri getirir (cached)
     * 
     * @return array
     */
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
    
    /**
     * Tum permissionlari getirir (cached)
     * 
     * @return array
     */
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
    
    /**
     * Permissionlari modul bazinda gruplar
     * 
     * @return array ['users' => [...], 'invoices' => [...], ...]
     */
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
    
    /**
     * Bir rolun permissionlarini getirir
     * 
     * @param int $RolId
     * @return array Permission kodlari
     */
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
    
    /**
     * Frontend icin kullanici yetki bilgilerini JSON formatinda dondurur
     * 
     * @param int $UserId
     * @return array
     */
    public function frontendIcinYetkiler(int $UserId): array
    {
        $Permissionlar = $this->kullaniciPermissionlariGetir($UserId);
        $Roller = $this->kullaniciRolleriGetir($UserId);
        
        // Permission'lari modul bazinda grupla
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
    
    // =========================================================================
    // SINGLETON KORUMALARI
    // =========================================================================
    
    private function __clone() {}
    
    public function __wakeup()
    {
        throw new \Exception("Singleton siniflari unserialize edilemez.");
    }
}
