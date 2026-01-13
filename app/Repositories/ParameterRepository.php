<?php

namespace App\Repositories;

use App\Core\Transaction;
use App\Models\BaseModel;
use App\Services\Logger\ActionLogger;

class ParameterRepository extends BaseRepository
{
    protected string $Tablo = 'tbl_parametre';

    /**
     * Tüm aktif parametreleri grup bazında getirir
     */
    public function tumAktifler(): array
    {
        $Stmt = $this->Db->query("SELECT * FROM {$this->Tablo} WHERE Sil = 0 AND Aktif = 1 ORDER BY Grup, Sira");
        $Sonuclar = $Stmt->fetchAll();
        $this->logSelect(['Sil' => 0, 'Aktif' => 1], $Sonuclar);
        return $Sonuclar;
    }

    /**
     * Grup bazında parametreleri getirir
     */
    public function grubaGore(string $Grup): array
    {
        $Stmt = $this->Db->prepare("SELECT * FROM {$this->Tablo} WHERE Sil = 0 AND Aktif = 1 AND Grup = :Grup ORDER BY Sira");
        $Stmt->execute(['Grup' => $Grup]);
        $Sonuclar = $Stmt->fetchAll();
        $this->logSelect(['Sil' => 0, 'Aktif' => 1, 'Grup' => $Grup], $Sonuclar);
        return $Sonuclar;
    }

    /**
     * Tüm parametreleri getirir (admin için - aktif/pasif dahil)
     */
    public function tumParametreler(): array
    {
        $Stmt = $this->Db->query("SELECT * FROM {$this->Tablo} WHERE Sil = 0 ORDER BY Grup, Sira");
        $Sonuclar = $Stmt->fetchAll();
        $this->logSelect(['Sil' => 0], $Sonuclar);
        return $Sonuclar;
    }

    /**
     * Grup bazında gruplanmış parametreleri getirir
     */
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

    /**
     * Varsayılan dövizi getirir
     */
    public function varsayilanDoviz(): ?array
    {
        $Stmt = $this->Db->prepare("SELECT * FROM {$this->Tablo} WHERE Sil = 0 AND Aktif = 1 AND Grup = 'doviz' AND Varsayilan = 1");
        $Stmt->execute();
        $Sonuc = $Stmt->fetch();
        return $Sonuc ?: null;
    }

    /**
     * Aktif dövizleri getirir
     */
    public function aktifDovizler(): array
    {
        return $this->grubaGore('doviz');
    }

    /**
     * Genel parametre değerini getirir
     */
    public function genelParametre(string $Kod): ?string
    {
        $Stmt = $this->Db->prepare("SELECT Deger FROM {$this->Tablo} WHERE Sil = 0 AND Grup = 'genel' AND Kod = :Kod");
        $Stmt->execute(['Kod' => $Kod]);
        $Sonuc = $Stmt->fetch();
        return $Sonuc ? $Sonuc['Deger'] : null;
    }

    /**
     * Pagination default değerini getirir
     */
    public function paginationDefault(): int
    {
        $Deger = $this->genelParametre('pagination_default');
        return $Deger ? (int)$Deger : (int)env('PAGINATION_DEFAULT', 10);
    }

    /**
     * Yeni parametre ekler (tbl_parametre özel alan adları ile)
     */
    public function ekle(array $Veri, ?int $KullaniciId = null): int
    {
        $Simdi = date('Y-m-d H:i:s');
        
        // tbl_parametre için özel standart alanlar (Guid yok, farklı kolon isimleri)
        $Yukleme = array_merge([
            'EklemeZamani' => $Simdi,
            'EkleyenUserId' => $KullaniciId,
            'GuncellemeZamani' => $Simdi,
            'GuncelleyenUserId' => $KullaniciId,
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

    /**
     * Parametre yedekleme (tbl_parametre özel kolon yapısı ile)
     */
    public function yedekle(int $Id, string $YedekTablo, ?int $KullaniciId = null): void
    {
        $Kayit = $this->bul($Id);
        if (!$Kayit) {
            return;
        }
        
        // bck_tbl_parametre için özel kolon isimleri: YedekZamani, YedekleyenUserId
        $Kayit['YedekZamani'] = date('Y-m-d H:i:s');
        $Kayit['YedekleyenUserId'] = $KullaniciId;

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

    /**
     * Parametre soft delete (tbl_parametre özel kolon yapısı ile)
     */
    public function softSil(int $Id, ?int $KullaniciId = null, array $EkKosul = []): void
    {
        $Simdi = date('Y-m-d H:i:s');
        $Sql = "UPDATE {$this->Tablo} SET Sil = 1, SilenUserId = :Uid, SilmeZamani = :Simdi WHERE Id = :Id";
        
        Transaction::wrap(function () use ($Sql, $Id, $KullaniciId, $Simdi) {
            $Stmt = $this->Db->prepare($Sql);
            $Stmt->execute(['Id' => $Id, 'Uid' => $KullaniciId, 'Simdi' => $Simdi]);
            ActionLogger::delete($this->Tablo, ['Id' => $Id]);
        });
    }

    /**
     * Varsayılan parametreyi değiştirir (aynı gruptaki diğerlerini sıfırlar)
     */
    public function varsayilanYap(int $Id, int $KullaniciId): void
    {
        // Önce mevcut parametreyi bul ve grubunu al
        $Mevcut = $this->bul($Id);
        if (!$Mevcut) return;

        $Grup = $Mevcut['Grup'];

        Transaction::wrap(function () use ($Id, $Grup, $KullaniciId) {
            // Aynı gruptaki tüm varsayılanları kaldır
            $Stmt = $this->Db->prepare("UPDATE {$this->Tablo} SET Varsayilan = 0, GuncelleyenUserId = :Uid, GuncellemeZamani = GETDATE() WHERE Grup = :Grup AND Sil = 0");
            $Stmt->execute(['Grup' => $Grup, 'Uid' => $KullaniciId]);

            // Seçilen parametreyi varsayılan yap
            $Stmt2 = $this->Db->prepare("UPDATE {$this->Tablo} SET Varsayilan = 1, GuncelleyenUserId = :Uid, GuncellemeZamani = GETDATE() WHERE Id = :Id");
            $Stmt2->execute(['Id' => $Id, 'Uid' => $KullaniciId]);
            
            ActionLogger::update($this->Tablo, ['Id' => $Id], ['Varsayilan' => 1, 'Grup' => $Grup]);
        });
    }

    /**
     * Aktif/Pasif durumunu değiştirir
     */
    public function aktiflikDegistir(int $Id, bool $Aktif, int $KullaniciId): void
    {
        Transaction::wrap(function () use ($Id, $Aktif, $KullaniciId) {
            // Önce yedekle
            $this->yedekle($Id, 'bck_tbl_parametre', $KullaniciId);
            
            $Stmt = $this->Db->prepare("UPDATE {$this->Tablo} SET Aktif = :Aktif, GuncelleyenUserId = :Uid, GuncellemeZamani = GETDATE() WHERE Id = :Id");
            $Stmt->execute(['Id' => $Id, 'Aktif' => $Aktif ? 1 : 0, 'Uid' => $KullaniciId]);
            
            ActionLogger::update($this->Tablo, ['Id' => $Id], ['Aktif' => $Aktif]);
        });
    }

    /**
     * Parametre güncelleme (tbl_parametre özel alan adları ile)
     */
    public function guncelle(int $Id, array $Veri, ?int $KullaniciId = null, array $EkKosul = []): void
    {
        // tbl_parametre farklı kolon isimleri kullandığı için özel güncelleme
        $Veri['GuncellemeZamani'] = date('Y-m-d H:i:s');
        $Veri['GuncelleyenUserId'] = $KullaniciId;

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
                // Yedekleme tablosu yoksa islem kesilmesin diye catch
            }

            $Stmt = $this->Db->prepare($Sql);
            $Stmt->execute($Yukleme);

            ActionLogger::update($this->Tablo, array_merge(['Id' => $Id], $EkKosul), $Veri);
        });
    }
}
