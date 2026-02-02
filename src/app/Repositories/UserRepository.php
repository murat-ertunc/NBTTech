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
        
        
        return $this->kullanicilaraRollerEkle($Sonuclar);
    }
    
    






    private function kullanicilaraRollerEkle(array $Kullanicilar): array
    {
        if (empty($Kullanicilar)) {
            return $Kullanicilar;
        }
        
        
        $UserIds = array_column($Kullanicilar, 'Id');
        if (empty($UserIds)) {
            return $Kullanicilar;
        }
        
        
        $Placeholders = implode(',', array_fill(0, count($UserIds), '?'));
        $Sql = "
            SELECT 
                ur.UserId,
                r.Id as RolId,
                r.RolKodu,
                r.RolAdi,
                r.Seviye
            FROM tnm_user_rol ur
            INNER JOIN tnm_rol r ON r.Id = ur.RolId AND r.Sil = 0 AND r.Aktif = 1
            WHERE ur.UserId IN ({$Placeholders})
              AND ur.Sil = 0
            ORDER BY ur.UserId, r.Seviye DESC
        ";
        
        $Stmt = $this->Db->prepare($Sql);
        $Stmt->execute($UserIds);
        $TumRoller = $Stmt->fetchAll(PDO::FETCH_ASSOC);
        
        
        $RollerMap = [];
        foreach ($TumRoller as $Rol) {
            $UserId = $Rol['UserId'];
            if (!isset($RollerMap[$UserId])) {
                $RollerMap[$UserId] = [];
            }
            $RollerMap[$UserId][] = [
                'Id' => $Rol['RolId'],
                'RolKodu' => $Rol['RolKodu'],
                'RolAdi' => $Rol['RolAdi'],
                'Seviye' => $Rol['Seviye']
            ];
        }
        
        
        foreach ($Kullanicilar as &$Kullanici) {
            $UserId = $Kullanici['Id'];
            $Roller = $RollerMap[$UserId] ?? [];
            $Kullanici['Roller'] = $Roller;
            $Kullanici['RollerStr'] = implode(', ', array_column($Roller, 'RolAdi'));
            
            
            if (empty($Kullanici['Rol']) && !empty($Roller)) {
                
                $Kullanici['Rol'] = $Roller[0]['RolKodu'];
            }
        }
        
        return $Kullanicilar;
    }
    
    


    public function tumKullanicilarPaginated(int $Sayfa = 1, int $Limit = 10, array $Filtreler = []): array
    {
        $Offset = ($Sayfa - 1) * $Limit;
        
        $WhereClause = "WHERE Sil = 0";
        $Parametreler = [];
        
        
        if (!empty($Filtreler['adsoyad'])) {
            $WhereClause .= " AND AdSoyad LIKE :AdSoyad";
            $Parametreler['AdSoyad'] = '%' . $Filtreler['adsoyad'] . '%';
        }
        
        
        if (!empty($Filtreler['kullaniciadi'])) {
            $WhereClause .= " AND KullaniciAdi LIKE :KullaniciAdi";
            $Parametreler['KullaniciAdi'] = '%' . $Filtreler['kullaniciadi'] . '%';
        }
        
        
        if (!empty($Filtreler['roller_str'])) {
            $WhereClause .= " AND EXISTS (
                SELECT 1 FROM tnm_user_rol ur
                INNER JOIN tnm_rol r ON r.Id = ur.RolId AND r.Sil = 0 AND r.Aktif = 1
                WHERE ur.UserId = u.Id AND ur.Sil = 0 AND r.RolAdi LIKE :RollerStr
            )";
            $Parametreler['RollerStr'] = '%' . $Filtreler['roller_str'] . '%';
        }
        
        
        if (!empty($Filtreler['rol'])) {
            $WhereClause .= " AND Rol = :Rol";
            $Parametreler['Rol'] = $Filtreler['rol'];
        }
        
        
        if (isset($Filtreler['aktif']) && $Filtreler['aktif'] !== '') {
            $WhereClause .= " AND Aktif = :Aktif";
            $Parametreler['Aktif'] = (int)$Filtreler['aktif'];
        }
        
        
        if (!empty($Filtreler['ekleyen_user_id'])) {
            $WhereClause .= " AND EkleyenUserId = :EkleyenUserId";
            $Parametreler['EkleyenUserId'] = (int)$Filtreler['ekleyen_user_id'];
        }
        
        
        $CountSql = "SELECT COUNT(*) FROM {$this->Tablo} {$WhereClause}";
        $CountStmt = $this->Db->prepare($CountSql);
        $CountStmt->execute($Parametreler);
        $ToplamKayit = (int) $CountStmt->fetchColumn();
        $ToplamSayfa = ceil($ToplamKayit / $Limit);
        
        
        $Sql = "SELECT " . self::GUVENLI_KOLONLAR . " 
                FROM {$this->Tablo} 
                {$WhereClause}
                ORDER BY Id DESC 
                OFFSET {$Offset} ROWS FETCH NEXT {$Limit} ROWS ONLY";
        
        $Stmt = $this->Db->prepare($Sql);
        $Stmt->execute($Parametreler);
        $Sonuclar = $Stmt->fetchAll();
        $this->logSelect($Filtreler, $Sonuclar);
        
        
        $Sonuclar = $this->kullanicilaraRollerEkle($Sonuclar);
        
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
    
    


    public function kullaniciyaGoreKullanicilar(int $EkleyenUserId): array
    {
        $Sql = "SELECT " . self::GUVENLI_KOLONLAR . " FROM {$this->Tablo} WHERE Sil = 0 AND EkleyenUserId = :EkleyenUserId ORDER BY Id DESC";
        $Stmt = $this->Db->prepare($Sql);
        $Stmt->execute(['EkleyenUserId' => $EkleyenUserId]);
        $Sonuclar = $Stmt->fetchAll();
        $this->logSelect(['Sil' => 0, 'EkleyenUserId' => $EkleyenUserId], $Sonuclar);
        
        
        return $this->kullanicilaraRollerEkle($Sonuclar);
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
