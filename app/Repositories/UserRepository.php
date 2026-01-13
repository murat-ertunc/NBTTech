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
