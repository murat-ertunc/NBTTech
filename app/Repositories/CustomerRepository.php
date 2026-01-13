<?php

namespace App\Repositories;

use App\Core\Transaction;
use App\Models\BaseModel;

class CustomerRepository extends BaseRepository
{
    protected string $Tablo = 'tbl_musteri';

    public function kullaniciyaGoreAktifler(int $KullaniciId): array
    {
        $Stmt = $this->Db->prepare("SELECT * FROM {$this->Tablo} WHERE Sil = 0 AND EkleyenUserId = :Uid ORDER BY Id DESC");
        $Stmt->execute(['Uid' => $KullaniciId]);
        $Sonuclar = $Stmt->fetchAll();
        $this->logSelect(['Sil' => 0, 'EkleyenUserId' => $KullaniciId], $Sonuclar);
        return $Sonuclar;
    }

    public function tumAktiflerSiraliKullaniciBilgisiIle(): array
    {
        $Sql = "SELECT m.*, u.AdSoyad AS EkleyenAdSoyad, u.KullaniciAdi AS EkleyenKullaniciAdi 
                FROM {$this->Tablo} m 
                LEFT JOIN tnm_user u ON m.EkleyenUserId = u.Id 
                WHERE m.Sil = 0 
                ORDER BY m.Id DESC";
        $Stmt = $this->Db->query($Sql);
        $Sonuclar = $Stmt->fetchAll();
        $this->logSelect(['Sil' => 0], $Sonuclar);
        return $Sonuclar;
    }

    public function tumAktiflerSirali(): array
    {
        $Stmt = $this->Db->query("SELECT * FROM {$this->Tablo} WHERE Sil = 0 ORDER BY Id DESC");
        $Sonuclar = $Stmt->fetchAll();
        $this->logSelect(['Sil' => 0], $Sonuclar);
        return $Sonuclar;
    }

    public function sahipliKayitBul(int $Id, int $KullaniciId): ?array
    {
        $Stmt = $this->Db->prepare("SELECT * FROM {$this->Tablo} WHERE Id = :Id AND Sil = 0 AND EkleyenUserId = :Uid");
        $Stmt->execute(['Id' => $Id, 'Uid' => $KullaniciId]);
        $Kayit = $Stmt->fetch();
        $this->logSelect(['Id' => $Id, 'Sil' => 0, 'EkleyenUserId' => $KullaniciId], $Kayit ? [$Kayit] : []);
        return $Kayit ?: null;
    }

    /**
     * Kullaniciya ait tum musterileri soft delete yapar
     * Kullanici silindiginde cagrilir
     */
    public function kullanicininMusterileriniSil(int $KullaniciId, int $SilenKullaniciId): int
    {
        $Musteriler = $this->kullaniciyaGoreAktifler($KullaniciId);
        foreach ($Musteriler as $Musteri) {
            $this->yedekle((int) $Musteri['Id'], 'bck_tbl_musteri', $SilenKullaniciId);
        }
        
        // Toplu soft delete
        $StandartAlanlar = BaseModel::softDeleteIcinStandartAlanlar($SilenKullaniciId);
        $SetParcalari = [];
        $Yukleme = ['EkleyenUserId' => $KullaniciId];
        foreach ($StandartAlanlar as $Anahtar => $Deger) {
            $SetParcalari[] = "$Anahtar = :$Anahtar";
            $Yukleme[$Anahtar] = $Deger;
        }
        $Sql = "UPDATE {$this->Tablo} SET " . implode(', ', $SetParcalari) . " WHERE EkleyenUserId = :EkleyenUserId AND Sil = 0";
        
        $Etkilenen = 0;
        Transaction::wrap(function () use ($Sql, $Yukleme, &$Etkilenen) {
            $Stmt = $this->Db->prepare($Sql);
            $Stmt->execute($Yukleme);
            $Etkilenen = $Stmt->rowCount();
        });
        
        return $Etkilenen;
    }
}
