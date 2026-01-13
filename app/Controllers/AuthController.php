<?php

namespace App\Controllers;

use App\Core\Context;
use App\Core\Response;
use App\Core\Transaction;
use App\Core\Token;
use App\Repositories\UserRepository;
use App\Services\Logger\ActionLogger;

class AuthController
{
    public static function login(): void
    {
        $Girdi = json_decode(file_get_contents('php://input'), true) ?: [];
        $KullaniciAdi = trim((string) ($Girdi['username'] ?? ''));
        $Parola = trim((string) ($Girdi['password'] ?? ''));
        if ($KullaniciAdi === '' || $Parola === '') {
            Response::error('Kullanıcı adı ve parola gereklidir.', 422);
            return;
        }
        if (strlen($KullaniciAdi) < 3) {
            Response::error('Kullanıcı adı en az 3 karakter olmalıdır.', 422);
            return;
        }
        if (strlen($Parola) < 6) {
            Response::error('Parola en az 6 karakter olmalıdır.', 422);
            return;
        }

        $Repo = new UserRepository();
        $Kullanici = $Repo->kullaniciAdiIleBul($KullaniciAdi);
        if (!$Kullanici) {
            Response::error('Kullanıcı adı veya parola hatalı.', 401);
            return;
        }
        if ((int) ($Kullanici['Aktif'] ?? 0) !== 1) {
            Response::error('Kullanıcı pasif durumda.', 403);
            return;
        }
        if (!password_verify($Parola, $Kullanici['Parola'] ?? '')) {
            Response::error('Kullanıcı adı veya parola hatalı.', 401);
            return;
        }

        $TokenStr = Token::sign([
            'userId' => (int) $Kullanici['Id'],
            'role' => $Kullanici['Rol'] ?? 'user',
        ]);
        Context::setKullaniciId((int) $Kullanici['Id']);
        Context::setRol($Kullanici['Rol'] ?? 'user');
        ActionLogger::logla('login', 'tnm_user', ['KullaniciId' => (int) $Kullanici['Id'], 'KullaniciAdi' => $KullaniciAdi], 'ok');
        Response::json([
            'token' => $TokenStr,
            'user' => [
                'id' => (int) $Kullanici['Id'],
                'name' => $Kullanici['AdSoyad'] ?? $KullaniciAdi,
                'username' => $Kullanici['KullaniciAdi'],
                'role' => $Kullanici['Rol'] ?? 'user',
            ],
        ]);
    }

    public static function register(): void
    {
        $Girdi = json_decode(file_get_contents('php://input'), true) ?: [];
        $KullaniciAdi = trim((string) ($Girdi['username'] ?? ''));
        $Parola = trim((string) ($Girdi['password'] ?? ''));
        $AdSoyad = trim((string) ($Girdi['name'] ?? ''));
        if ($KullaniciAdi === '' || $Parola === '' || $AdSoyad === '') {
            Response::error('Kullanıcı adı, parola ve ad soyad zorunludur.', 422);
            return;
        }
        if (strlen($AdSoyad) < 2) {
            Response::error('Ad Soyad en az 2 karakter olmalıdır.', 422);
            return;
        }
        if (strlen($KullaniciAdi) < 3) {
            Response::error('Kullanıcı adı en az 3 karakter olmalıdır.', 422);
            return;
        }
        if (strlen($Parola) < 6) {
            Response::error('Parola en az 6 karakter olmalıdır.', 422);
            return;
        }
        $Repo = new UserRepository();
        if ($Repo->kullaniciAdiIleBul($KullaniciAdi)) {
            Response::error('Kullanıcı adı zaten kayıtlı.', 409);
            return;
        }
        $Hash = password_hash($Parola, PASSWORD_BCRYPT);
        [$Id, $Kayit] = Transaction::wrap(function () use ($Repo, $KullaniciAdi, $Hash, $AdSoyad) {
            $YeniId = $Repo->olustur($KullaniciAdi, $Hash, $AdSoyad, 'user');
            $Kayit = $Repo->bul($YeniId);
            ActionLogger::insert('tnm_user', ['Id' => $YeniId], ['KullaniciAdi' => $KullaniciAdi, 'AdSoyad' => $AdSoyad, 'Rol' => 'user']);
            return [$YeniId, $Kayit];
        });

        $TokenStr = Token::sign([
            'userId' => $Id,
            'role' => $Kayit['Rol'] ?? 'user',
        ]);
        Response::json([
            'token' => $TokenStr,
            'user' => [
                'id' => $Id,
                'name' => $Kayit['AdSoyad'] ?? $AdSoyad,
                'username' => $Kayit['KullaniciAdi'] ?? $KullaniciAdi,
                'role' => $Kayit['Rol'] ?? 'user',
            ],
        ], 201);
    }

    public static function logout(): void
    {
        $Header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (stripos($Header, 'Bearer ') === 0) {
            $TokenStr = trim(substr($Header, 7));
            $Yukleme = Token::verify($TokenStr);
            if ($Yukleme && isset($Yukleme['userId'])) {
                Context::setKullaniciId((int) $Yukleme['userId']);
                ActionLogger::logla('logout', 'tnm_user', ['KullaniciId' => (int) $Yukleme['userId']], 'ok');
            }
        }
        Response::json(['status' => 'ok']);
    }

    public static function refresh(): void
    {
        $Header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (stripos($Header, 'Bearer ') !== 0) {
            Response::error('Yetkisiz erişim.', 401);
            return;
        }
        $TokenStr = trim(substr($Header, 7));
        $Yukleme = Token::verify($TokenStr);
        if (!$Yukleme || !isset($Yukleme['userId'])) {
            Response::error('Yetkisiz erişim.', 401);
            return;
        }
        $YeniToken = Token::sign(['userId' => (int) $Yukleme['userId']]);
        Response::json(['token' => $YeniToken]);
    }
}
