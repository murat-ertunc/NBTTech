<?php

namespace App\Controllers;

use App\Core\Context;
use App\Core\Response;
use App\Core\Transaction;
use App\Core\Rol;
use App\Repositories\CustomerRepository;
use App\Repositories\UserRepository;
use App\Services\Logger\ActionLogger;

class UserController
{
    public static function index(): void
    {
        // Authentication check
        $KullaniciId = Context::kullaniciId();
        if (!$KullaniciId) {
            Response::unauthorized('Oturum gerekli');
            return;
        }
        
        $Repo = new UserRepository();
        $Satirlar = $Repo->tumKullanicilar();
        Response::json(['data' => $Satirlar]);
    }

    public static function block(array $Parametreler): void
    {
        // Authentication check
        $AktifKullaniciId = Context::kullaniciId();
        if (!$AktifKullaniciId) {
            Response::unauthorized('Oturum gerekli');
            return;
        }
        
        // Admin or Superadmin role check
        $Rol = Context::rol();
        if ($Rol !== Rol::SUPERADMIN && $Rol !== Rol::ADMIN) {
            Response::forbidden('Bu islem icin yetkiniz yok');
            return;
        }
        
        $Id = isset($Parametreler['id']) ? (int) $Parametreler['id'] : 0;
        if ($Id <= 0) {
            Response::error('Gecersiz kullanici.', 422);
            return;
        }
        $Girdi = json_decode(file_get_contents('php://input'), true) ?: [];
        if (!array_key_exists('Aktif', $Girdi)) {
            Response::error('Aktif alani zorunludur.', 422);
            return;
        }
        $Aktif = (int) $Girdi['Aktif'] === 1 ? 1 : 0;
        $Repo = new UserRepository();
        $Mevcut = $Repo->bul($Id);
        if (!$Mevcut) {
            Response::error('Kullanici bulunamadi.', 404);
            return;
        }
        if (($Mevcut['Rol'] ?? '') === 'superadmin') {
            Response::error('Super admin bloklanamaz.', 403);
            return;
        }
        Transaction::wrap(function () use ($Repo, $Id, $Aktif) {
            $Repo->yedekle($Id, 'bck_tnm_user', Context::kullaniciId());
            $Repo->guncelle($Id, ['Aktif' => $Aktif], Context::kullaniciId());
            $Islem = $Aktif === 1 ? 'unblock' : 'block';
            ActionLogger::logla($Islem, 'tnm_user', ['Id' => $Id, 'Aktif' => $Aktif], 'ok');
        });

        Response::json(['status' => 'ok']);
    }

    public static function delete(array $Parametreler): void
    {
        // Authentication check
        $AktifKullaniciId = Context::kullaniciId();
        if (!$AktifKullaniciId) {
            Response::unauthorized('Oturum gerekli');
            return;
        }
        
        // Admin or Superadmin role check
        $Rol = Context::rol();
        if ($Rol !== Rol::SUPERADMIN && $Rol !== Rol::ADMIN) {
            Response::forbidden('Bu islem icin yetkiniz yok');
            return;
        }
        
        $Id = isset($Parametreler['id']) ? (int) $Parametreler['id'] : 0;
        if ($Id <= 0) {
            Response::error('Gecersiz kullanici.', 422);
            return;
        }
        $Repo = new UserRepository();
        $Mevcut = $Repo->bul($Id);
        if (!$Mevcut) {
            Response::error('Kullanici bulunamadi.', 404);
            return;
        }
        if (($Mevcut['Rol'] ?? '') === 'superadmin') {
            Response::error('Super admin silinemez.', 403);
            return;
        }
        
        $SilenKullaniciId = Context::kullaniciId();
        
        Transaction::wrap(function () use ($Id, $SilenKullaniciId, $Repo) {
            $MusteriRepo = new CustomerRepository();
            $SilinenMusteriSayisi = $MusteriRepo->kullanicininMusterileriniSil($Id, $SilenKullaniciId);
            if ($SilinenMusteriSayisi > 0) {
                ActionLogger::delete('tbl_musteri', ['EkleyenUserId' => $Id, 'SilinenAdet' => $SilinenMusteriSayisi]);
            }

            // Silme oncesi yedekleme islemi yapilir
            $Repo->yedekle($Id, 'bck_tnm_user', $SilenKullaniciId);
            $Repo->softSil($Id, $SilenKullaniciId);
            ActionLogger::delete('tnm_user', ['Id' => $Id, 'BagliSilinenMusteri' => $SilinenMusteriSayisi]);
        });

        Response::json(['status' => 'ok']);
    }

    public static function store(): void
    {
        // Authentication check
        $AktifKullaniciId = Context::kullaniciId();
        if (!$AktifKullaniciId) {
            Response::unauthorized('Oturum gerekli');
            return;
        }
        
        // Admin or Superadmin role check
        $AktifRol = Context::rol();
        if ($AktifRol !== Rol::SUPERADMIN && $AktifRol !== Rol::ADMIN) {
            Response::forbidden('Bu islem icin yetkiniz yok');
            return;
        }
        
        $Girdi = json_decode(file_get_contents('php://input'), true) ?: [];
        $AdSoyad = trim($Girdi['AdSoyad'] ?? '');
        $KullaniciAdi = trim($Girdi['KullaniciAdi'] ?? '');
        $Sifre = $Girdi['Sifre'] ?? '';
        $Rol = $Girdi['Rol'] ?? 'user';

        if (!$AdSoyad || !$KullaniciAdi || !$Sifre) {
            Response::error('Tum alanlar zorunludur.', 422);
            return;
        }
        if (strlen($Sifre) < 6) {
            Response::error('Sifre en az 6 karakter olmalidir.', 422);
            return;
        }
        if ($Rol !== 'user') {
            $Rol = 'user';
        }

        $Repo = new UserRepository();
        
        $Mevcut = $Repo->kullaniciAdiylaAra($KullaniciAdi);
        if ($Mevcut) {
            Response::error('Bu kullanici adi zaten kullaniliyor.', 422);
            return;
        }

        $SifreHash = password_hash($Sifre, PASSWORD_BCRYPT);
        
        Transaction::wrap(function () use ($Repo, $AdSoyad, $KullaniciAdi, $SifreHash, $Rol) {
            $YeniId = $Repo->ekle([
                'AdSoyad' => $AdSoyad,
                'KullaniciAdi' => $KullaniciAdi,
                'Parola' => $SifreHash,
                'Rol' => $Rol,
                'Aktif' => 1
            ], Context::kullaniciId());
            ActionLogger::create('tnm_user', ['Id' => $YeniId, 'KullaniciAdi' => $KullaniciAdi, 'Rol' => $Rol]);
        });

        Response::json(['status' => 'ok']);
    }

    public static function update(array $Parametreler): void
    {
        $Id = isset($Parametreler['id']) ? (int) $Parametreler['id'] : 0;
        if ($Id <= 0) {
            Response::error('Gecersiz kullanici.', 422);
            return;
        }

        $Girdi = json_decode(file_get_contents('php://input'), true) ?: [];
        $Repo = new UserRepository();
        $KullaniciId = Context::kullaniciId();
        if (!$KullaniciId) {
            Response::error('Oturum gecersiz veya suresi dolmus.', 401);
            return;
        }

        $Mevcut = $Repo->bul($Id);
        if (!$Mevcut) {
            Response::error('Kullanici bulunamadi.', 404);
            return;
        }

        if (($Mevcut['Rol'] ?? '') === 'superadmin' && $Id !== $KullaniciId) {
            Response::error('Super admin duzenlenemez.', 403);
            return;
        }

        Transaction::wrap(function () use ($Repo, $Id, $Girdi, $KullaniciId) {
            $Guncellenecek = [];
            if (isset($Girdi['AdSoyad']) && trim($Girdi['AdSoyad'])) {
                $Guncellenecek['AdSoyad'] = trim($Girdi['AdSoyad']);
            }
            if (isset($Girdi['Rol']) && $Girdi['Rol'] === 'user') {
                $Guncellenecek['Rol'] = 'user';
            }
            if (isset($Girdi['Sifre']) && strlen($Girdi['Sifre']) >= 6) {
                $Guncellenecek['Parola'] = password_hash($Girdi['Sifre'], PASSWORD_BCRYPT);
            }

            if (!empty($Guncellenecek)) {
                $Repo->yedekle($Id, 'bck_tnm_user', $KullaniciId);
                $Repo->guncelle($Id, $Guncellenecek, $KullaniciId);
                ActionLogger::update('tnm_user', ['Id' => $Id], array_keys($Guncellenecek));
            }
        });

        Response::json(['status' => 'success']);
    }

    public static function changePassword(): void
    {
        $KullaniciId = Context::kullaniciId();
        if (!$KullaniciId) {
            Response::error('Oturum gecersiz.', 401);
            return;
        }

        $Girdi = json_decode(file_get_contents('php://input'), true) ?: [];
        $MevcutSifre = $Girdi['CurrentPassword'] ?? '';
        $YeniSifre = $Girdi['NewPassword'] ?? '';

        if (!$MevcutSifre || !$YeniSifre) {
            Response::error('Mevcut ve yeni sifre zorunludur.', 422);
            return;
        }
        if (strlen($YeniSifre) < 6) {
            Response::error('Yeni sifre en az 6 karakter olmalidir.', 422);
            return;
        }

        $Repo = new UserRepository();
        $Kullanici = $Repo->bul($KullaniciId);
        
        if (!$Kullanici) {
            Response::error('Kullanici bulunamadi.', 404);
            return;
        }

        if (!password_verify($MevcutSifre, $Kullanici['Parola'])) {
            Response::error('Mevcut sifre yanlis.', 422);
            return;
        }

        $YeniHash = password_hash($YeniSifre, PASSWORD_BCRYPT);

        Transaction::wrap(function () use ($Repo, $KullaniciId, $YeniHash) {
            $Repo->yedekle($KullaniciId, 'bck_tnm_user', $KullaniciId);
            $Repo->guncelle($KullaniciId, ['Parola' => $YeniHash], $KullaniciId);
            ActionLogger::logla('password_change', 'tnm_user', ['Id' => $KullaniciId], 'ok');
        });

        Response::json(['status' => 'ok', 'message' => 'Sifre degistirildi.']);
    }
}
