<?php

namespace App\Repositories;

use App\Core\Transaction;
use App\Services\Authorization\AuthorizationService;









class PermissionRepository extends BaseRepository
{
    protected string $Tablo = 'tnm_permission';
    
    




    public function tumPermissionlar(): array
    {
        $Sql = "
            SELECT 
                Id,
                Guid,
                PermissionKodu,
                ModulAdi,
                Aksiyon,
                Aciklama,
                Aktif,
                EklemeZamani,
                DegisiklikZamani
            FROM tnm_permission
            WHERE Sil = 0
            ORDER BY ModulAdi, Aksiyon
        ";
        
        $Stmt = $this->Db->query($Sql);
        $Sonuc = $Stmt->fetchAll();
        
        $this->logSelect(['Sil' => 0], $Sonuc);
        
        return $Sonuc;
    }
    
    




    public function modulBazindaGetir(): array
    {
        $Permissionlar = $this->tumPermissionlar();
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
    
    





    public function koduIleBul(string $PermissionKodu): ?array
    {
        $Sql = "SELECT * FROM tnm_permission WHERE PermissionKodu = :Kod AND Sil = 0";
        $Stmt = $this->Db->prepare($Sql);
        $Stmt->execute([':Kod' => $PermissionKodu]);
        return $Stmt->fetch() ?: null;
    }
    
    





    public function moduldekiPermissionlar(string $ModulAdi): array
    {
        $Sql = "
            SELECT * FROM tnm_permission 
            WHERE ModulAdi = :ModulAdi AND Sil = 0 AND Aktif = 1
            ORDER BY Aksiyon
        ";
        
        $Stmt = $this->Db->prepare($Sql);
        $Stmt->execute([':ModulAdi' => $ModulAdi]);
        
        return $Stmt->fetchAll();
    }
    
    






    public function permissionEkle(array $Veri, ?int $KullaniciId = null): int
    {
        if (empty($Veri['PermissionKodu']) || empty($Veri['ModulAdi']) || empty($Veri['Aksiyon'])) {
            throw new \InvalidArgumentException('PermissionKodu, ModulAdi ve Aksiyon zorunludur.');
        }
        
        
        $Mevcut = $this->koduIleBul($Veri['PermissionKodu']);
        if ($Mevcut) {
            throw new \InvalidArgumentException('Bu permission kodu zaten kullaniliyor.');
        }
        
        $EklenenId = $this->ekle([
            'PermissionKodu' => $Veri['PermissionKodu'],
            'ModulAdi'       => $Veri['ModulAdi'],
            'Aksiyon'        => $Veri['Aksiyon'],
            'Aciklama'       => $Veri['Aciklama'] ?? null,
            'Aktif'          => 1
        ], $KullaniciId);
        
        
        $SuperadminRol = $this->Db->query("SELECT Id FROM tnm_rol WHERE RolKodu = 'superadmin' AND Sil = 0")
            ->fetch(\PDO::FETCH_ASSOC);
        if ($SuperadminRol && isset($SuperadminRol['Id'])) {
            $RolId = (int) $SuperadminRol['Id'];
            $VarMiSql = "SELECT 1 FROM tnm_rol_permission WHERE RolId = :RolId AND PermissionId = :PermissionId AND Sil = 0";
            $VarMiStmt = $this->Db->prepare($VarMiSql);
            $VarMiStmt->execute([':RolId' => $RolId, ':PermissionId' => $EklenenId]);
            if (!$VarMiStmt->fetch()) {
                Transaction::wrap(function () use ($RolId, $EklenenId, $KullaniciId) {
                    $EkleSql = "
                        INSERT INTO tnm_rol_permission (Guid, EklemeZamani, EkleyenUserId, DegisiklikZamani, DegistirenUserId, Sil, RolId, PermissionId)
                        VALUES (NEWID(), SYSUTCDATETIME(), :EkleyenUserId, SYSUTCDATETIME(), :DegistirenUserId, 0, :RolId, :PermissionId)
                    ";
                    $Stmt = $this->Db->prepare($EkleSql);
                    $Stmt->execute([
                        ':RolId' => $RolId,
                        ':PermissionId' => $EklenenId,
                        ':EkleyenUserId' => $KullaniciId,
                        ':DegistirenUserId' => $KullaniciId
                    ]);
                });
                AuthorizationService::getInstance()->rolKullanicilarininCacheTemizle($RolId);
            }
        }
        
        
        AuthorizationService::getInstance()->tumCacheTemizle();
        
        return $EklenenId;
    }
    
    







    public function permissionGuncelle(int $Id, array $Veri, ?int $KullaniciId = null): void
    {
        $GuncellenecekAlanlar = [];
        $IzinliAlanlar = ['Aciklama', 'Aktif'];
        
        foreach ($IzinliAlanlar as $Alan) {
            if (array_key_exists($Alan, $Veri)) {
                $GuncellenecekAlanlar[$Alan] = $Veri[$Alan];
            }
        }
        
        if (!empty($GuncellenecekAlanlar)) {
            $this->guncelle($Id, $GuncellenecekAlanlar, $KullaniciId);
        }
        
        
        AuthorizationService::getInstance()->tumCacheTemizle();
    }
    
    




    public function modulListesi(): array
    {
        $Sql = "
            SELECT DISTINCT ModulAdi 
            FROM tnm_permission 
            WHERE Sil = 0 AND Aktif = 1
            ORDER BY ModulAdi
        ";
        
        $Stmt = $this->Db->query($Sql);
        return $Stmt->fetchAll(\PDO::FETCH_COLUMN);
    }
    
    





    public function idlerdenKodlariGetir(array $PermissionIdler): array
    {
        if (empty($PermissionIdler)) {
            return [];
        }
        
        $Placeholders = implode(',', array_fill(0, count($PermissionIdler), '?'));
        $Sql = "SELECT Id, PermissionKodu FROM tnm_permission WHERE Id IN ({$Placeholders}) AND Sil = 0";
        
        $Stmt = $this->Db->prepare($Sql);
        $Stmt->execute(array_values($PermissionIdler));
        $Sonuc = $Stmt->fetchAll();
        
        $Map = [];
        foreach ($Sonuc as $Row) {
            $Map[$Row['Id']] = $Row['PermissionKodu'];
        }
        
        return $Map;
    }
    
    





    public function kodlardanIdleriGetir(array $PermissionKodlari): array
    {
        if (empty($PermissionKodlari)) {
            return [];
        }
        
        $Placeholders = implode(',', array_fill(0, count($PermissionKodlari), '?'));
        $Sql = "SELECT Id, PermissionKodu FROM tnm_permission WHERE PermissionKodu IN ({$Placeholders}) AND Sil = 0";
        
        $Stmt = $this->Db->prepare($Sql);
        $Stmt->execute(array_values($PermissionKodlari));
        $Sonuc = $Stmt->fetchAll();
        
        $Map = [];
        foreach ($Sonuc as $Row) {
            $Map[$Row['PermissionKodu']] = $Row['Id'];
        }
        
        return $Map;
    }
}
