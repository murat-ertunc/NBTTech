<?php

namespace App\Repositories;

use App\Services\Authorization\AuthorizationService;

/**
 * Permission Repository
 * 
 * Permission tanimlarinin CRUD islemleri.
 * Genelde sadece okuma yapilir, permission tanimlari seed ile olusturulur.
 * 
 * @package App\Repositories
 */
class PermissionRepository extends BaseRepository
{
    protected string $Tablo = 'tnm_permission';
    
    /**
     * Tum aktif permissionlari getirir
     * 
     * @return array
     */
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
    
    /**
     * Modul bazinda gruplu permission listesi
     * 
     * @return array ['users' => [...], 'invoices' => [...]]
     */
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
    
    /**
     * Permission kodu ile bul
     * 
     * @param string $PermissionKodu
     * @return array|null
     */
    public function koduIleBul(string $PermissionKodu): ?array
    {
        $Sql = "SELECT * FROM tnm_permission WHERE PermissionKodu = :Kod AND Sil = 0";
        $Stmt = $this->Db->prepare($Sql);
        $Stmt->execute([':Kod' => $PermissionKodu]);
        return $Stmt->fetch() ?: null;
    }
    
    /**
     * Belirtilen moduldeki permissionlari getirir
     * 
     * @param string $ModulAdi
     * @return array
     */
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
    
    /**
     * Permission ekler (genelde seed ile yapilir)
     * 
     * @param array $Veri
     * @param int|null $KullaniciId
     * @return int
     */
    public function permissionEkle(array $Veri, ?int $KullaniciId = null): int
    {
        if (empty($Veri['PermissionKodu']) || empty($Veri['ModulAdi']) || empty($Veri['Aksiyon'])) {
            throw new \InvalidArgumentException('PermissionKodu, ModulAdi ve Aksiyon zorunludur.');
        }
        
        // Ayni kod var mi kontrol
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
        
        // Cache temizle
        AuthorizationService::getInstance()->tumCacheTemizle();
        
        return $EklenenId;
    }
    
    /**
     * Permission gunceller
     * 
     * @param int $Id
     * @param array $Veri
     * @param int|null $KullaniciId
     * @return void
     */
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
        
        // Cache temizle
        AuthorizationService::getInstance()->tumCacheTemizle();
    }
    
    /**
     * Modul listesi
     * 
     * @return array ['users', 'invoices', ...]
     */
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
    
    /**
     * Permission ID listesinden kodlari getirir
     * 
     * @param array $PermissionIdler
     * @return array [1 => 'users.create', 2 => 'users.read', ...]
     */
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
    
    /**
     * Permission kodlarindan ID'leri getirir
     * 
     * @param array $PermissionKodlari
     * @return array ['users.create' => 1, 'users.read' => 2, ...]
     */
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
