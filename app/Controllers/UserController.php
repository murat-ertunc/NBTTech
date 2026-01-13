<?php

namespace App\Controllers;

use App\Core\Context;
use App\Core\Response;
use App\Core\Transaction;
use App\Repositories\CustomerRepository;
use App\Repositories\UserRepository;
use App\Services\Logger\ActionLogger;

class UserController
{
    public static function index(): void
    {
        $Repo = new UserRepository();
        $Satirlar = $Repo->tumKullanicilar();
        Response::json(['data' => $Satirlar]);
    }

    public static function block(array $Parametreler): void
    {
        $Id = isset($Parametreler['id']) ? (int) $Parametreler['id'] : 0;
        if ($Id <= 0) {
            Response::error('Geçersiz kullanıcı.', 422);
            return;
        }
        $Girdi = json_decode(file_get_contents('php://input'), true) ?: [];
        if (!array_key_exists('Aktif', $Girdi)) {
            Response::error('Aktif alanı zorunludur.', 422);
            return;
        }
        $Aktif = (int) $Girdi['Aktif'] === 1 ? 1 : 0;
        $Repo = new UserRepository();
        $Mevcut = $Repo->bul($Id);
        if (!$Mevcut) {
            Response::error('Kullanıcı bulunamadı.', 404);
            return;
        }
        if (($Mevcut['Rol'] ?? '') === 'superadmin') {
            Response::error('Süper admin bloklanamaz.', 403);
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
        $Id = isset($Parametreler['id']) ? (int) $Parametreler['id'] : 0;
        if ($Id <= 0) {
            Response::error('Geçersiz kullanıcı.', 422);
            return;
        }
        $Repo = new UserRepository();
        $Mevcut = $Repo->bul($Id);
        if (!$Mevcut) {
            Response::error('Kullanıcı bulunamadı.', 404);
            return;
        }
        if (($Mevcut['Rol'] ?? '') === 'superadmin') {
            Response::error('Süper admin silinemez.', 403);
            return;
        }
        
        $SilenKullaniciId = Context::kullaniciId();
        
        Transaction::wrap(function () use ($Id, $SilenKullaniciId, $Repo) {
            $MusteriRepo = new CustomerRepository();
            $SilinenMusteriSayisi = $MusteriRepo->kullanicininMusterileriniSil($Id, $SilenKullaniciId);
            if ($SilinenMusteriSayisi > 0) {
                ActionLogger::delete('tbl_musteri', ['EkleyenUserId' => $Id, 'SilinenAdet' => $SilinenMusteriSayisi]);
            }

            // Delete oncesi yedekleme (ProjeYazimKurallari kurali)
            $Repo->yedekle($Id, 'bck_tnm_user', $SilenKullaniciId);
            $Repo->softSil($Id, $SilenKullaniciId);
            ActionLogger::delete('tnm_user', ['Id' => $Id, 'BagliSilinenMusteri' => $SilinenMusteriSayisi]);
        });

        Response::json(['status' => 'ok']);
    }

    public static function store(): void
    {
        $Girdi = json_decode(file_get_contents('php://input'), true) ?: [];
        $AdSoyad = trim($Girdi['AdSoyad'] ?? '');
        $KullaniciAdi = trim($Girdi['KullaniciAdi'] ?? '');
        $Sifre = $Girdi['Sifre'] ?? '';
        $Rol = $Girdi['Rol'] ?? 'user';

        if (!$AdSoyad || !$KullaniciAdi || !$Sifre) {
            Response::error('Tüm alanlar zorunludur.', 422);
            return;
        }
        if (strlen($Sifre) < 6) {
            Response::error('Şifre en az 6 karakter olmalıdır.', 422);
            return;
        }
        if (!in_array($Rol, ['user', 'admin'])) {
            $Rol = 'user';
        }

        $Repo = new UserRepository();
        
        $Mevcut = $Repo->kullaniciAdiylaAra($KullaniciAdi);
        if ($Mevcut) {
            Response::error('Bu kullanıcı adı zaten kullanılıyor.', 422);
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
            Response::error('Geçersiz kullanıcı.', 422);
            return;
        }

        $Girdi = json_decode(file_get_contents('php://input'), true) ?: [];
        $Repo = new UserRepository();
        $KullaniciId = Context::kullaniciId();
        if (!$KullaniciId) {
            Response::error('Oturum geçersiz veya süresi dolmuş.', 401);
            return;
        }

        $Mevcut = $Repo->bul($Id);
        if (!$Mevcut) {
            Response::error('Kullanıcı bulunamadı.', 404);
            return;
        }

        if (($Mevcut['Rol'] ?? '') === 'superadmin' && $Id !== $KullaniciId) {
            Response::error('Süper admin düzenlenemez.', 403);
            return;
        }

        Transaction::wrap(function () use ($Repo, $Id, $Girdi, $KullaniciId) {
            $Guncellenecek = [];
            if (isset($Girdi['AdSoyad']) && trim($Girdi['AdSoyad'])) {
                $Guncellenecek['AdSoyad'] = trim($Girdi['AdSoyad']);
            }
            if (isset($Girdi['Rol']) && in_array($Girdi['Rol'], ['user', 'admin'])) {
                $Guncellenecek['Rol'] = $Girdi['Rol'];
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
            Response::error('Oturum geçersiz.', 401);
            return;
        }

        $Girdi = json_decode(file_get_contents('php://input'), true) ?: [];
        $MevcutSifre = $Girdi['CurrentPassword'] ?? '';
        $YeniSifre = $Girdi['NewPassword'] ?? '';

        if (!$MevcutSifre || !$YeniSifre) {
            Response::error('Mevcut ve yeni şifre zorunludur.', 422);
            return;
        }
        if (strlen($YeniSifre) < 6) {
            Response::error('Yeni şifre en az 6 karakter olmalıdır.', 422);
            return;
        }

        $Repo = new UserRepository();
        $Kullanici = $Repo->bul($KullaniciId);
        
        if (!$Kullanici) {
            Response::error('Kullanıcı bulunamadı.', 404);
            return;
        }

        if (!password_verify($MevcutSifre, $Kullanici['Parola'])) {
            Response::error('Mevcut şifre yanlış.', 422);
            return;
        }

        $YeniHash = password_hash($YeniSifre, PASSWORD_BCRYPT);

        Transaction::wrap(function () use ($Repo, $KullaniciId, $YeniHash) {
            $Repo->yedekle($KullaniciId, 'bck_tnm_user', $KullaniciId);
            $Repo->guncelle($KullaniciId, ['Parola' => $YeniHash], $KullaniciId);
            ActionLogger::logla('password_change', 'tnm_user', ['Id' => $KullaniciId], 'ok');
        });

        Response::json(['status' => 'ok', 'message' => 'Şifre değiştirildi.']);
    }
}
