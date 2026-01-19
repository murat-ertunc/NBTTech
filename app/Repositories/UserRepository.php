<?php

namespace App\Repositories;

use PDO;

class UserRepository extends BaseRepository
{
    private const GUVENLI_KOLONLAR = 'Id, Guid, EklemeZamani, EkleyenUserId, DegisiklikZamani, DegistirenUserId, Sil, KullaniciAdi, AdSoyad, Aktif, Rol';

    protected string $Tablo = 'tnm_user';

    public function tumAktifler(): array
    {
        $Sql = "SELECT " . self::GUVENLI_KOLONLAR . " FROM {$this->Tablo} WHERE Sil = 0 ORDER BY Id DESC";
        $Stmt = $this->Db->query($Sql);
        $Sonuclar = $Stmt->fetchAll();
        $this->logSelect(['Sil' => 0], $Sonuclar);
        return $Sonuclar;
    }

    public function tumKullanicilar(): array
    {
        $Sql = "SELECT " . self::GUVENLI_KOLONLAR . " FROM {$this->Tablo} WHERE Sil = 0 ORDER BY Id DESC";
        $Stmt = $this->Db->query($Sql);
        $Sonuclar = $Stmt->fetchAll();
        $this->logSelect(['Sil' => 0], $Sonuclar);
        return $Sonuclar;
    }
    
    /**
     * Sayfalamali kullanici listesi
     */
    public function tumKullanicilarPaginated(int $Sayfa = 1, int $Limit = 10, array $Filtreler = []): array
    {
        $Offset = ($Sayfa - 1) * $Limit;
        
        $WhereClause = "WHERE Sil = 0";
        $Parametreler = [];
        
        // Filtre: Ad Soyad
        if (!empty($Filtreler['adsoyad'])) {
            $WhereClause .= " AND AdSoyad LIKE :AdSoyad";
            $Parametreler['AdSoyad'] = '%' . $Filtreler['adsoyad'] . '%';
        }
        
        // Filtre: Kullanici Adi
        if (!empty($Filtreler['kullaniciadi'])) {
            $WhereClause .= " AND KullaniciAdi LIKE :KullaniciAdi";
            $Parametreler['KullaniciAdi'] = '%' . $Filtreler['kullaniciadi'] . '%';
        }
        
        // Filtre: Rol
        if (!empty($Filtreler['rol'])) {
            $WhereClause .= " AND Rol = :Rol";
            $Parametreler['Rol'] = $Filtreler['rol'];
        }
        
        // Filtre: Aktif
        if (isset($Filtreler['aktif']) && $Filtreler['aktif'] !== '') {
            $WhereClause .= " AND Aktif = :Aktif";
            $Parametreler['Aktif'] = (int)$Filtreler['aktif'];
        }
        
        // Toplam kayit sayisi
        $CountSql = "SELECT COUNT(*) FROM {$this->Tablo} {$WhereClause}";
        $CountStmt = $this->Db->prepare($CountSql);
        $CountStmt->execute($Parametreler);
        $ToplamKayit = (int) $CountStmt->fetchColumn();
        $ToplamSayfa = ceil($ToplamKayit / $Limit);
        
        // Verileri cek
        $Sql = "SELECT " . self::GUVENLI_KOLONLAR . " 
                FROM {$this->Tablo} 
                {$WhereClause}
                ORDER BY Id DESC 
                OFFSET {$Offset} ROWS FETCH NEXT {$Limit} ROWS ONLY";
        
        $Stmt = $this->Db->prepare($Sql);
        $Stmt->execute($Parametreler);
        $Sonuclar = $Stmt->fetchAll();
        $this->logSelect($Filtreler, $Sonuclar);
        
        return [
            'data' => $Sonuclar,
            'pagination' => [
                'page' => $Sayfa,
                'limit' => $Limit,
                'total' => $ToplamKayit,
                'totalPages' => $ToplamSayfa,
                'hasNext' => $Sayfa < $ToplamSayfa,
                'hasPrev' => $Sayfa > 1
            ]
        ];
    }

    public function kullaniciAdiIleBul(string $KullaniciAdi): ?array
    {
        $Stmt = $this->Db->prepare("SELECT TOP 1 * FROM {$this->Tablo} WHERE KullaniciAdi = :KullaniciAdi AND Sil = 0");
        $Stmt->execute(['KullaniciAdi' => $KullaniciAdi]);
        $Kayit = $Stmt->fetch(PDO::FETCH_ASSOC);
        $this->logSelect(['KullaniciAdi' => $KullaniciAdi, 'Sil' => 0], $Kayit ? [$Kayit] : []);
        return $Kayit ?: null;
    }

    public function kullaniciAdiylaAra(string $KullaniciAdi): ?array
    {
        return $this->kullaniciAdiIleBul($KullaniciAdi);
    }

    public function bul(int $Id): ?array
    {
        $Stmt = $this->Db->prepare("SELECT TOP 1 * FROM {$this->Tablo} WHERE Id = :Id AND Sil = 0");
        $Stmt->execute(['Id' => $Id]);
        $Kayit = $Stmt->fetch(PDO::FETCH_ASSOC);
        $this->logSelect(['Id' => $Id, 'Sil' => 0], $Kayit ? [$Kayit] : []);
        return $Kayit ?: null;
    }

    public function olustur(string $KullaniciAdi, string $ParolaHash, string $AdSoyad, string $Rol = 'user'): int
    {
        $Yukleme = [
            'KullaniciAdi' => $KullaniciAdi,
            'Parola' => $ParolaHash,
            'AdSoyad' => $AdSoyad,
            'Rol' => $Rol,
            'Aktif' => 1,
        ];
        return $this->ekle($Yukleme, null);
    }

    protected function sanitizeRow(array $Satir): array
    {
        unset($Satir['Parola']);
        return $Satir;
    }
}
