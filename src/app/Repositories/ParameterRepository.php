<?php

namespace App\Repositories;

use App\Core\Transaction;
use App\Models\BaseModel;
use App\Services\Logger\ActionLogger;

class ParameterRepository extends BaseRepository
{
    protected string $Tablo = 'tbl_parametre';

    


    public function tumAktifler(): array
    {
        $Stmt = $this->Db->query("SELECT * FROM {$this->Tablo} WHERE Sil = 0 AND Aktif = 1 ORDER BY Grup, Sira");
        $Sonuclar = $Stmt->fetchAll();
        $this->logSelect(['Sil' => 0, 'Aktif' => 1], $Sonuclar);
        return $Sonuclar;
    }

    


    public function grubaGore(string $Grup): array
    {
        $Stmt = $this->Db->prepare("SELECT * FROM {$this->Tablo} WHERE Sil = 0 AND Aktif = 1 AND Grup = :Grup ORDER BY Sira");
        $Stmt->execute(['Grup' => $Grup]);
        $Sonuclar = $Stmt->fetchAll();
        $this->logSelect(['Sil' => 0, 'Aktif' => 1, 'Grup' => $Grup], $Sonuclar);
        return $Sonuclar;
    }

    


    public function tumParametreler(): array
    {
        $Stmt = $this->Db->query("SELECT * FROM {$this->Tablo} WHERE Sil = 0 ORDER BY Grup, Sira");
        $Sonuclar = $Stmt->fetchAll();
        $this->logSelect(['Sil' => 0], $Sonuclar);
        return $Sonuclar;
    }

    


    public function grupluGetir(): array
    {
        $Satirlar = $this->tumParametreler();
        $Gruplu = [];
        foreach ($Satirlar as $Satir) {
            $Grup = $Satir['Grup'];
            if (!isset($Gruplu[$Grup])) {
                $Gruplu[$Grup] = [];
            }
            $Gruplu[$Grup][] = $Satir;
        }
        return $Gruplu;
    }

    


    public function varsayilanDoviz(): ?array
    {
        $Stmt = $this->Db->prepare("SELECT * FROM {$this->Tablo} WHERE Sil = 0 AND Aktif = 1 AND Grup = 'doviz' AND Varsayilan = 1");
        $Stmt->execute();
        $Sonuc = $Stmt->fetch();
        return $Sonuc ?: null;
    }

    


    public function aktifDovizler(): array
    {
        return $this->grubaGore('doviz');
    }

    


    public function genelParametre(string $Kod): ?string
    {
        $Stmt = $this->Db->prepare("SELECT Deger FROM {$this->Tablo} WHERE Sil = 0 AND Grup = 'genel' AND Kod = :Kod");
        $Stmt->execute(['Kod' => $Kod]);
        $Sonuc = $Stmt->fetch();
        return $Sonuc ? $Sonuc['Deger'] : null;
    }

    


    public function paginationDefault(): int
    {
        $Deger = $this->genelParametre('pagination_default');
        return $Deger ? (int)$Deger : (int)env('PAGINATION_DEFAULT', 10);
    }

    


    public function ekle(array $Veri, ?int $KullaniciId = null): int
    {
        $Simdi = date('Y-m-d H:i:s');
        
        
        $Yukleme = array_merge([
            'EklemeZamani' => $Simdi,
            'EkleyenUserId' => $KullaniciId,
            'DegisiklikZamani' => $Simdi,
            'DegistirenUserId' => $KullaniciId,
            'Sil' => 0,
        ], $Veri);
        
        $Kolonlar = array_keys($Yukleme);
        $Tutucular = array_map(fn($K) => ':' . $K, $Kolonlar);
        $Sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $this->Tablo,
            implode(', ', $Kolonlar),
            implode(', ', $Tutucular)
        );

        return Transaction::wrap(function () use ($Sql, $Yukleme, $Veri) {
            $Stmt = $this->Db->prepare($Sql);
            $Stmt->execute($Yukleme);
            $Id = (int) $this->Db->lastInsertId();
            ActionLogger::insert($this->Tablo, ['Id' => $Id], $Veri);
            return $Id;
        });
    }

    


    public function yedekle(int $Id, string $YedekTablo, ?int $KullaniciId = null): void
    {
        $Kayit = $this->bul($Id);
        if (!$Kayit) {
            return;
        }
        
        
        $Kayit['KaynakId'] = $Kayit['Id'];
        unset($Kayit['Id']);
        
        
        $Kayit['BackupZamani'] = date('Y-m-d H:i:s');
        $Kayit['BackupUserId'] = $KullaniciId;

        $Kolonlar = array_keys($Kayit);
        $Tutucular = array_map(fn($K) => ':' . $K, $Kolonlar);
        $Sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $YedekTablo,
            implode(', ', $Kolonlar),
            implode(', ', $Tutucular)
        );
        
        Transaction::wrap(function () use ($Sql, $Kayit) {
            $Stmt = $this->Db->prepare($Sql);
            $Stmt->execute($Kayit);
        });
    }

    


    public function softSil(int $Id, ?int $KullaniciId = null, array $EkKosul = []): void
    {
        $Sql = "UPDATE {$this->Tablo} SET Sil = 1, DegistirenUserId = :Uid, DegisiklikZamani = GETDATE() WHERE Id = :Id";
        
        Transaction::wrap(function () use ($Sql, $Id, $KullaniciId) {
            $Stmt = $this->Db->prepare($Sql);
            $Stmt->execute(['Id' => $Id, 'Uid' => $KullaniciId]);
            ActionLogger::delete($this->Tablo, ['Id' => $Id]);
        });
    }

    


    public function varsayilanYap(int $Id, int $KullaniciId): void
    {
        
        $Mevcut = $this->bul($Id);
        if (!$Mevcut) return;

        $Grup = $Mevcut['Grup'];

        Transaction::wrap(function () use ($Id, $Grup, $KullaniciId) {
            
            $Stmt = $this->Db->prepare("UPDATE {$this->Tablo} SET Varsayilan = 0, DegistirenUserId = :Uid, DegisiklikZamani = GETDATE() WHERE Grup = :Grup AND Sil = 0");
            $Stmt->execute(['Grup' => $Grup, 'Uid' => $KullaniciId]);

            
            $Stmt2 = $this->Db->prepare("UPDATE {$this->Tablo} SET Varsayilan = 1, DegistirenUserId = :Uid, DegisiklikZamani = GETDATE() WHERE Id = :Id");
            $Stmt2->execute(['Id' => $Id, 'Uid' => $KullaniciId]);
            
            ActionLogger::update($this->Tablo, ['Id' => $Id], ['Varsayilan' => 1, 'Grup' => $Grup]);
        });
    }

    


    public function aktiflikDegistir(int $Id, bool $Aktif, int $KullaniciId): void
    {
        Transaction::wrap(function () use ($Id, $Aktif, $KullaniciId) {
            
            $this->yedekle($Id, 'bck_tbl_parametre', $KullaniciId);
            
            $Stmt = $this->Db->prepare("UPDATE {$this->Tablo} SET Aktif = :Aktif, DegistirenUserId = :Uid, DegisiklikZamani = GETDATE() WHERE Id = :Id");
            $Stmt->execute(['Id' => $Id, 'Aktif' => $Aktif ? 1 : 0, 'Uid' => $KullaniciId]);
            
            ActionLogger::update($this->Tablo, ['Id' => $Id], ['Aktif' => $Aktif]);
        });
    }

    


    public function guncelle(int $Id, array $Veri, ?int $KullaniciId = null, array $EkKosul = []): void
    {
        
        $Veri['DegisiklikZamani'] = date('Y-m-d H:i:s');
        $Veri['DegistirenUserId'] = $KullaniciId;

        $Yukleme = $Veri;
        $Yukleme['Id'] = $Id;
        
        $SetParcalari = [];
        foreach (array_keys($Veri) as $K) {
            $SetParcalari[] = "$K = :$K";
        }
        $SetSql = implode(', ', $SetParcalari);
        
        $WhereParcalari = ['Id = :Id'];
        foreach ($EkKosul as $Anahtar => $Deger) {
            $Tutucu = 'w_' . $Anahtar;
            $Yukleme[$Tutucu] = $Deger;
            $WhereParcalari[] = "$Anahtar = :$Tutucu";
        }
        
        $Sql = "UPDATE {$this->Tablo} SET {$SetSql} WHERE " . implode(' AND ', $WhereParcalari);

        $BckTablo = 'bck_' . $this->Tablo;

        Transaction::wrap(function () use ($Sql, $Yukleme, $Id, $BckTablo, $KullaniciId, $Veri, $EkKosul) {
            try {
                $this->yedekle($Id, $BckTablo, $KullaniciId);
            } catch (\Throwable $Ignored) {
                
            }

            $Stmt = $this->Db->prepare($Sql);
            $Stmt->execute($Yukleme);

            ActionLogger::update($this->Tablo, array_merge(['Id' => $Id], $EkKosul), $Veri);
        });
    }

    


    public function degerGuncelle(string $ParametreKod, string $Deger, ?int $KullaniciId = null): void
    {
        
        $Stmt = $this->Db->prepare("SELECT Id FROM {$this->Tablo} WHERE Kod = :Kod AND Sil = 0");
        $Stmt->execute(['Kod' => $ParametreKod]);
        $Mevcut = $Stmt->fetch();

        if ($Mevcut) {
            
            $Sql = "UPDATE {$this->Tablo} SET Deger = :Deger, DegistirenUserId = :Uid, DegisiklikZamani = GETDATE() WHERE Id = :Id";
            $Stmt = $this->Db->prepare($Sql);
            $Stmt->execute(['Id' => $Mevcut['Id'], 'Deger' => $Deger, 'Uid' => $KullaniciId]);
            ActionLogger::update($this->Tablo, ['Kod' => $ParametreKod], ['Deger' => $Deger]);
        } else {
            
            $Grup = 'genel';
            
            $this->ekle([
                'Grup' => $Grup,
                'Kod' => $ParametreKod,
                'Deger' => $Deger,
                'Etiket' => $ParametreKod,
                'Aktif' => 1
            ], $KullaniciId);
        }
    }
}
