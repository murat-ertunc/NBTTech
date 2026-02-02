<?php
/**
 * Role Repository için veri erişim işlemlerini yürütür.
 * Sorgu ve kalıcılık katmanını soyutlar.
 */

namespace App\Repositories;

use App\Core\Transaction;
use App\Services\Authorization\AuthorizationService;
use App\Services\Logger\ActionLogger;

class RoleRepository extends BaseRepository
{
    protected string $Tablo = 'tnm_rol';

    private const PIVOT_TABLO = 'tnm_rol_permission';

    private const USER_ROL_TABLO = 'tnm_user_rol';

    public function tumRoller(): array
    {
        $Sql = "
            SELECT
                r.Id,
                r.Guid,
                r.RolKodu,
                r.RolAdi,
                r.Aciklama,
                r.Seviye,
                r.SistemRolu,
                r.Aktif,
                r.EklemeZamani,
                r.DegisiklikZamani,
                (SELECT COUNT(*) FROM tnm_user_rol ur WHERE ur.RolId = r.Id AND ur.Sil = 0) as KullaniciSayisi,
                (SELECT COUNT(*) FROM tnm_rol_permission rp WHERE rp.RolId = r.Id AND rp.Sil = 0) as PermissionSayisi
            FROM tnm_rol r
            WHERE r.Sil = 0
            ORDER BY r.Seviye DESC, r.RolAdi
        ";

        $Stmt = $this->Db->query($Sql);
        $Sonuc = $Stmt->fetchAll();

        $this->logSelect(['Sil' => 0], $Sonuc);

        return $Sonuc;
    }

    public function rolDetay(int $Id): ?array
    {

        $Sql = "
            SELECT
                r.Id,
                r.Guid,
                r.RolKodu,
                r.RolAdi,
                r.Aciklama,
                r.Seviye,
                r.SistemRolu,
                r.Aktif,
                r.EklemeZamani,
                r.EkleyenUserId,
                r.DegisiklikZamani,
                r.DegistirenUserId
            FROM tnm_rol r
            WHERE r.Id = :Id AND r.Sil = 0
        ";

        $Stmt = $this->Db->prepare($Sql);
        $Stmt->execute([':Id' => $Id]);
        $Rol = $Stmt->fetch();

        if (!$Rol) {
            return null;
        }

        $Rol['Permissionlar'] = $this->rolPermissionlariGetir($Id);

        $CountSql = "SELECT COUNT(*) as Sayi FROM tnm_user_rol WHERE RolId = :RolId AND Sil = 0";
        $CountStmt = $this->Db->prepare($CountSql);
        $CountStmt->execute([':RolId' => $Id]);
        $Rol['KullaniciSayisi'] = (int) $CountStmt->fetch()['Sayi'];

        $this->logSelect(['Id' => $Id], [$Rol]);

        return $Rol;
    }

    public function rolPermissionlariGetir(int $RolId): array
    {
        $Sql = "
            SELECT
                p.Id,
                p.PermissionKodu,
                p.ModulAdi,
                p.Aksiyon,
                p.Aciklama
            FROM tnm_permission p
            INNER JOIN tnm_rol_permission rp ON rp.PermissionId = p.Id AND rp.Sil = 0
            WHERE rp.RolId = :RolId
              AND p.Sil = 0
              AND p.Aktif = 1
            ORDER BY p.ModulAdi, p.Aksiyon
        ";

        $Stmt = $this->Db->prepare($Sql);
        $Stmt->execute([':RolId' => $RolId]);

        return $Stmt->fetchAll();
    }

    public function rolKoduIleBul(string $RolKodu): ?array
    {
        $Sql = "SELECT * FROM tnm_rol WHERE RolKodu = :RolKodu AND Sil = 0";
        $Stmt = $this->Db->prepare($Sql);
        $Stmt->execute([':RolKodu' => $RolKodu]);
        return $Stmt->fetch() ?: null;
    }

    public function rolEkle(array $Veri, ?int $KullaniciId = null): int
    {

        if (empty($Veri['RolKodu']) || empty($Veri['RolAdi'])) {
            throw new \InvalidArgumentException('RolKodu ve RolAdi zorunludur.');
        }

        $Mevcut = $this->rolKoduIleBul($Veri['RolKodu']);
        if ($Mevcut) {
            throw new \InvalidArgumentException('Bu rol kodu zaten kullaniliyor.');
        }

        $EklenenId = $this->ekle([
            'RolKodu'    => $Veri['RolKodu'],
            'RolAdi'     => $Veri['RolAdi'],
            'Aciklama'   => $Veri['Aciklama'] ?? null,
            'Seviye'     => $Veri['Seviye'] ?? 0,
            'SistemRolu' => 0,
            'Aktif'      => 1
        ], $KullaniciId);

        AuthorizationService::getInstance()->tumCacheTemizle();

        return $EklenenId;
    }

    public function rolGuncelle(int $Id, array $Veri, ?int $KullaniciId = null): void
    {
        $Rol = $this->bul($Id);

        if (!$Rol) {
            throw new \InvalidArgumentException('Rol bulunamadi.');
        }

        if ($Rol['SistemRolu']) {
            throw new \InvalidArgumentException('Sistem rolleri duzenlenemez.');
        }

        if (isset($Veri['RolKodu']) && $Veri['RolKodu'] !== $Rol['RolKodu']) {
            $Mevcut = $this->rolKoduIleBul($Veri['RolKodu']);
            if ($Mevcut) {
                throw new \InvalidArgumentException('Bu rol kodu zaten kullaniliyor.');
            }
        }

        $GuncellenecekAlanlar = [];
        $IzinliAlanlar = ['RolKodu', 'RolAdi', 'Aciklama', 'Seviye', 'Aktif'];

        foreach ($IzinliAlanlar as $Alan) {
            if (array_key_exists($Alan, $Veri)) {
                $GuncellenecekAlanlar[$Alan] = $Veri[$Alan];
            }
        }

        if (!empty($GuncellenecekAlanlar)) {
            $this->guncelle($Id, $GuncellenecekAlanlar, $KullaniciId);
        }

        AuthorizationService::getInstance()->rolKullanicilarininCacheTemizle($Id);
    }

    public function rolSil(int $Id, ?int $KullaniciId = null): void
    {
        $Rol = $this->bul($Id);

        if (!$Rol) {
            throw new \InvalidArgumentException('Rol bulunamadi.');
        }

        if ($Rol['SistemRolu']) {
            throw new \InvalidArgumentException('Sistem rolleri silinemez.');
        }

        $CountSql = "SELECT COUNT(*) as Sayi FROM tnm_user_rol WHERE RolId = :RolId AND Sil = 0";
        $CountStmt = $this->Db->prepare($CountSql);
        $CountStmt->execute([':RolId' => $Id]);
        $KullaniciSayisi = (int) $CountStmt->fetch()['Sayi'];

        if ($KullaniciSayisi > 0) {
            throw new \InvalidArgumentException("Bu role atanmis {$KullaniciSayisi} kullanici var. Once kullanicilari baska role aktarin.");
        }

        Transaction::wrap(function () use ($Id, $KullaniciId) {

            $PivotSql = "UPDATE tnm_rol_permission SET Sil = 1, DegisiklikZamani = SYSUTCDATETIME(), DegistirenUserId = :UserId WHERE RolId = :RolId AND Sil = 0";
            $PivotStmt = $this->Db->prepare($PivotSql);
            $PivotStmt->execute([':RolId' => $Id, ':UserId' => $KullaniciId]);

            $this->softSil($Id, $KullaniciId);
        });

        AuthorizationService::getInstance()->tumCacheTemizle();
    }

    public function rolePermissionAta(int $RolId, array $PermissionIdler, ?int $KullaniciId = null): void
    {
        $Rol = $this->bul($RolId);

        if (!$Rol) {
            throw new \InvalidArgumentException('Rol bulunamadi.');
        }

        Transaction::wrap(function () use ($RolId, $PermissionIdler, $KullaniciId) {

            $SilSql = "UPDATE tnm_rol_permission SET Sil = 1, DegisiklikZamani = SYSUTCDATETIME(), DegistirenUserId = :UserId WHERE RolId = :RolId AND Sil = 0";
            $SilStmt = $this->Db->prepare($SilSql);
            $SilStmt->execute([':RolId' => $RolId, ':UserId' => $KullaniciId]);

            foreach ($PermissionIdler as $PermissionId) {
                $EkleSql = "
                    INSERT INTO tnm_rol_permission (Guid, EklemeZamani, EkleyenUserId, DegisiklikZamani, DegistirenUserId, Sil, RolId, PermissionId)
                    VALUES (NEWID(), SYSUTCDATETIME(), :EkleyenUserId, SYSUTCDATETIME(), :DegistirenUserId, 0, :RolId, :PermissionId)
                ";
                $EkleStmt = $this->Db->prepare($EkleSql);
                $EkleStmt->execute([
                    ':RolId'            => $RolId,
                    ':PermissionId'     => $PermissionId,
                    ':EkleyenUserId'    => $KullaniciId,
                    ':DegistirenUserId' => $KullaniciId
                ]);
            }

            ActionLogger::update(self::PIVOT_TABLO, ['RolId' => $RolId], ['PermissionIds' => $PermissionIdler]);
        });

        AuthorizationService::getInstance()->rolKullanicilarininCacheTemizle($RolId);
    }

    public function rolePermissionEkle(int $RolId, int $PermissionId, ?int $KullaniciId = null): bool
    {

        $VarMiSql = "SELECT Id FROM tnm_rol_permission WHERE RolId = :RolId AND PermissionId = :PermissionId AND Sil = 0";
        $VarMiStmt = $this->Db->prepare($VarMiSql);
        $VarMiStmt->execute([':RolId' => $RolId, ':PermissionId' => $PermissionId]);

        if ($VarMiStmt->fetch()) {
            return false;
        }

        Transaction::wrap(function () use ($RolId, $PermissionId, $KullaniciId) {
            $EkleSql = "
                INSERT INTO tnm_rol_permission (Guid, EklemeZamani, EkleyenUserId, DegisiklikZamani, DegistirenUserId, Sil, RolId, PermissionId)
                VALUES (NEWID(), SYSUTCDATETIME(), :EkleyenUserId, SYSUTCDATETIME(), :DegistirenUserId, 0, :RolId, :PermissionId)
            ";
            $EkleStmt = $this->Db->prepare($EkleSql);
            $EkleStmt->execute([
                ':RolId'            => $RolId,
                ':PermissionId'     => $PermissionId,
                ':EkleyenUserId'    => $KullaniciId,
                ':DegistirenUserId' => $KullaniciId
            ]);

            ActionLogger::insert(self::PIVOT_TABLO, ['RolId' => $RolId, 'PermissionId' => $PermissionId], []);
        });

        AuthorizationService::getInstance()->rolKullanicilarininCacheTemizle($RolId);

        return true;
    }

    public function roldenPermissionKaldir(int $RolId, int $PermissionId, ?int $KullaniciId = null): bool
    {
        Transaction::wrap(function () use ($RolId, $PermissionId, $KullaniciId) {
            $SilSql = "UPDATE tnm_rol_permission SET Sil = 1, DegisiklikZamani = SYSUTCDATETIME(), DegistirenUserId = :UserId WHERE RolId = :RolId AND PermissionId = :PermissionId AND Sil = 0";
            $SilStmt = $this->Db->prepare($SilSql);
            $SilStmt->execute([
                ':RolId'        => $RolId,
                ':PermissionId' => $PermissionId,
                ':UserId'       => $KullaniciId
            ]);

            ActionLogger::delete(self::PIVOT_TABLO, ['RolId' => $RolId, 'PermissionId' => $PermissionId], 'Permission Removed');
        });

        AuthorizationService::getInstance()->rolKullanicilarininCacheTemizle($RolId);

        return true;
    }

    public function kullaniciyaRolAta(int $UserId, int $RolId, ?int $AtayanUserId = null): bool
    {

        if ($AtayanUserId && $AtayanUserId !== $UserId) {
            $AuthService = AuthorizationService::getInstance();
            if (!$AuthService->rolAtayabilirMi($AtayanUserId, $RolId)) {
                throw new \InvalidArgumentException('Bu rolu atama yetkiniz yok. Sadece sahip oldugunuz permission setinin alt kumesi olan rolleri atayabilirsiniz.');
            }
        }

        $VarMiSql = "SELECT Id FROM tnm_user_rol WHERE UserId = :UserId AND RolId = :RolId AND Sil = 0";
        $VarMiStmt = $this->Db->prepare($VarMiSql);
        $VarMiStmt->execute([':UserId' => $UserId, ':RolId' => $RolId]);

        if ($VarMiStmt->fetch()) {
            return false;
        }

        Transaction::wrap(function () use ($UserId, $RolId, $AtayanUserId) {
            $EkleSql = "
                INSERT INTO tnm_user_rol (Guid, EklemeZamani, EkleyenUserId, DegisiklikZamani, DegistirenUserId, Sil, UserId, RolId)
                VALUES (NEWID(), SYSUTCDATETIME(), :EkleyenUserId, SYSUTCDATETIME(), :DegistirenUserId, 0, :UserId, :RolId)
            ";
            $EkleStmt = $this->Db->prepare($EkleSql);
            $EkleStmt->execute([
                ':UserId'           => $UserId,
                ':RolId'            => $RolId,
                ':EkleyenUserId'    => $AtayanUserId,
                ':DegistirenUserId' => $AtayanUserId
            ]);

            ActionLogger::insert(self::USER_ROL_TABLO, ['UserId' => $UserId, 'RolId' => $RolId], []);
        });

        AuthorizationService::getInstance()->kullaniciCacheTemizle($UserId);

        return true;
    }

    public function kullanicidanRolKaldir(int $UserId, int $RolId, ?int $KaldiranUserId = null): bool
    {
        Transaction::wrap(function () use ($UserId, $RolId, $KaldiranUserId) {
            $SilSql = "UPDATE tnm_user_rol SET Sil = 1, DegisiklikZamani = SYSUTCDATETIME(), DegistirenUserId = :KaldiranUserId WHERE UserId = :UserId AND RolId = :RolId AND Sil = 0";
            $SilStmt = $this->Db->prepare($SilSql);
            $SilStmt->execute([
                ':UserId'         => $UserId,
                ':RolId'          => $RolId,
                ':KaldiranUserId' => $KaldiranUserId
            ]);

            ActionLogger::delete(self::USER_ROL_TABLO, ['UserId' => $UserId, 'RolId' => $RolId], 'Role Removed');
        });

        AuthorizationService::getInstance()->kullaniciCacheTemizle($UserId);

        return true;
    }

    public function kullaniciRolleriniGuncelle(int $UserId, array $RolIdler, ?int $GuncelleyenUserId = null): void
    {

        if ($GuncelleyenUserId) {
            $AuthService = AuthorizationService::getInstance();
            foreach ($RolIdler as $RolId) {
                if (!$AuthService->rolAtayabilirMi($GuncelleyenUserId, $RolId)) {
                    $Rol = $this->bul($RolId);
                    $RolAdi = $Rol ? $Rol['RolAdi'] : $RolId;
                    throw new \InvalidArgumentException("'{$RolAdi}' rolunu atama yetkiniz yok.");
                }
            }
        }

        Transaction::wrap(function () use ($UserId, $RolIdler, $GuncelleyenUserId) {

            $SilSql = "UPDATE tnm_user_rol SET Sil = 1, DegisiklikZamani = SYSUTCDATETIME(), DegistirenUserId = :DegistirenUserId WHERE UserId = :HedefUserId AND Sil = 0";
            $SilStmt = $this->Db->prepare($SilSql);
            $SilStmt->execute([':HedefUserId' => $UserId, ':DegistirenUserId' => $GuncelleyenUserId]);

            foreach ($RolIdler as $RolId) {
                $EkleSql = "
                    INSERT INTO tnm_user_rol (Guid, EklemeZamani, EkleyenUserId, DegisiklikZamani, DegistirenUserId, Sil, UserId, RolId)
                    VALUES (NEWID(), SYSUTCDATETIME(), :EkleyenUserId, SYSUTCDATETIME(), :DegistirenUserId, 0, :UserId, :RolId)
                ";
                $EkleStmt = $this->Db->prepare($EkleSql);
                $EkleStmt->execute([
                    ':UserId'           => $UserId,
                    ':RolId'            => $RolId,
                    ':EkleyenUserId'    => $GuncelleyenUserId,
                    ':DegistirenUserId' => $GuncelleyenUserId
                ]);
            }

            ActionLogger::update(self::USER_ROL_TABLO, ['UserId' => $UserId], ['RolIds' => $RolIdler]);
        });

        AuthorizationService::getInstance()->kullaniciCacheTemizle($UserId);
    }

    public function kullaniciRolleriGetir(int $UserId): array
    {
        $Sql = "
            SELECT
                r.Id,
                r.RolKodu,
                r.RolAdi,
                r.Seviye,
                ur.EklemeZamani as AtanmaZamani
            FROM tnm_rol r
            INNER JOIN tnm_user_rol ur ON ur.RolId = r.Id AND ur.Sil = 0
            WHERE ur.UserId = :UserId
              AND r.Sil = 0
              AND r.Aktif = 1
            ORDER BY r.Seviye DESC
        ";

        $Stmt = $this->Db->prepare($Sql);
        $Stmt->execute([':UserId' => $UserId]);

        return $Stmt->fetchAll();
    }

    public function kullaniciRolleriniTemizle(int $UserId, ?int $TemizleyenUserId = null): int
    {
        $Sql = "UPDATE tnm_user_rol
                SET Sil = 1,
                    DegisiklikZamani = SYSUTCDATETIME(),
                    DegistirenUserId = :TemizleyenUserId
                WHERE UserId = :UserId AND Sil = 0";

        $Stmt = $this->Db->prepare($Sql);
        $Stmt->execute([
            ':UserId'           => $UserId,
            ':TemizleyenUserId' => $TemizleyenUserId
        ]);

        $EtkilenenSatir = $Stmt->rowCount();

        if ($EtkilenenSatir > 0) {
            ActionLogger::delete(self::USER_ROL_TABLO, ['UserId' => $UserId, 'TemizlenenRolSayisi' => $EtkilenenSatir], 'All roles cleared');
            AuthorizationService::getInstance()->kullaniciCacheTemizle($UserId);
        }

        return $EtkilenenSatir;
    }
}
