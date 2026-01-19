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
            Response::error('Kullanici adi ve parola gereklidir.', 422);
            return;
        }
        if (strlen($KullaniciAdi) < 3) {
            Response::error('Kullanici adi en az 3 karakter olmalidir.', 422);
            return;
        }
        if (strlen($Parola) < 6) {
            Response::error('Parola en az 6 karakter olmalidir.', 422);
            return;
        }

        $Repo = new UserRepository();
        $Kullanici = $Repo->kullaniciAdiIleBul($KullaniciAdi);
        if (!$Kullanici) {
            Response::error('Kullanici adi veya parola hatali.', 401);
            return;
        }
        if ((int) ($Kullanici['Aktif'] ?? 0) !== 1) {
            Response::error('Kullanici pasif durumda.', 403);
            return;
        }
        if (!password_verify($Parola, $Kullanici['Parola'] ?? '')) {
            Response::error('Kullanici adi veya parola hatali.', 401);
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
            Response::error('Yetkisiz erisim.', 401);
            return;
        }
        $TokenStr = trim(substr($Header, 7));
        $Yukleme = Token::verify($TokenStr);
        if (!$Yukleme || !isset($Yukleme['userId'])) {
            Response::error('Yetkisiz erisim.', 401);
            return;
        }
        $YeniToken = Token::sign(['userId' => (int) $Yukleme['userId']]);
        Response::json(['token' => $YeniToken]);
    }
}
