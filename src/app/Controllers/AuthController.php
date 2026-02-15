<?php
/**
 * Auth Controller için HTTP isteklerini yönetir.
 * Gelen talepleri doğrular ve yanıt akışını oluşturur.
 */

namespace App\Controllers;

use App\Core\Context;
use App\Core\Database;
use App\Core\Redis;
use App\Core\Response;
use App\Core\Token;
use App\Repositories\UserRepository;
use App\Services\Logger\ActionLogger;

class AuthController
{
    public static function login(): void
    {
        try {
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
            if ($KullaniciAdi === 'demo') {
                self::ensureDeterministicDemoUser($Repo);
            }
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
            $Ttl = (int) env('APP_TOKEN_TTL', 7200);
            setcookie('nbt_token', $TokenStr, [
                'expires' => time() + $Ttl,
                'path' => '/',
                'samesite' => 'Lax',
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
        } catch (\Throwable $E) {
            $Mesaj = config('app.debug', false) ? $E->getMessage() : 'Sunucu hatasi';
            Response::error($Mesaj, 500);
        }
    }

    private static function ensureDeterministicDemoUser(UserRepository $Repo): void
    {
        $AppEnv = strtolower((string) env('APP_ENV', 'production'));
        if ($AppEnv === 'production') {
            return;
        }

        $Db = Database::connection();
        $Simdi = date('Y-m-d H:i:s');
        $DemoHash = password_hash('Demo123!', PASSWORD_BCRYPT);

        $Mevcut = $Repo->kullaniciAdiIleBul('demo');
        if ($Mevcut) {
            $Guncelle = $Db->prepare("\n                UPDATE tnm_user\n                SET Parola = :Parola,\n                    AdSoyad = :AdSoyad,\n                    Aktif = 1,\n                    Rol = 'viewer',\n                    Sil = 0,\n                    DegisiklikZamani = :DegisiklikZamani\n                WHERE Id = :Id\n            ");
            $Guncelle->execute([
                'Parola' => $DemoHash,
                'AdSoyad' => 'Demo Kullanıcı',
                'DegisiklikZamani' => $Simdi,
                'Id' => (int) $Mevcut['Id'],
            ]);
            $DemoUserId = (int) $Mevcut['Id'];
        } else {
            $Ekle = $Db->prepare("\n                INSERT INTO tnm_user (Guid, EklemeZamani, DegisiklikZamani, KullaniciAdi, Parola, AdSoyad, Aktif, Rol, Sil)\n                VALUES (NEWID(), :EklemeZamani, :DegisiklikZamani, 'demo', :Parola, :AdSoyad, 1, 'viewer', 0)\n            ");
            $Ekle->execute([
                'EklemeZamani' => $Simdi,
                'DegisiklikZamani' => $Simdi,
                'Parola' => $DemoHash,
                'AdSoyad' => 'Demo Kullanıcı',
            ]);

            $YeniDemo = $Repo->kullaniciAdiIleBul('demo');
            $DemoUserId = (int) ($YeniDemo['Id'] ?? 0);
        }

        if ($DemoUserId <= 0) {
            return;
        }

        $RolStmt = $Db->query("SELECT TOP 1 Id FROM tnm_rol WHERE RolKodu = 'viewer' AND Sil = 0 AND Aktif = 1");
        $ViewerRolId = (int) ($RolStmt->fetchColumn() ?: 0);
        if ($ViewerRolId <= 0) {
            return;
        }

        $Temizle = $Db->prepare("\n            DELETE FROM tnm_user_rol\n            WHERE UserId = :UserId AND Sil = 0 AND RolId <> :RolId\n        ");
        $Temizle->execute(['UserId' => $DemoUserId, 'RolId' => $ViewerRolId]);

        $EslesmeStmt = $Db->prepare("\n            SELECT TOP 1 Id\n            FROM tnm_user_rol\n            WHERE UserId = :UserId AND RolId = :RolId AND Sil = 0\n        ");
        $EslesmeStmt->execute(['UserId' => $DemoUserId, 'RolId' => $ViewerRolId]);
        $EslesmeVar = $EslesmeStmt->fetchColumn();

        if (!$EslesmeVar) {
            $Ata = $Db->prepare("\n                INSERT INTO tnm_user_rol (Guid, EklemeZamani, EkleyenUserId, DegisiklikZamani, DegistirenUserId, Sil, UserId, RolId)\n                VALUES (NEWID(), :Simdi, 1, :Simdi2, 1, 0, :UserId, :RolId)\n            ");
            $Ata->execute([
                'Simdi' => $Simdi,
                'Simdi2' => $Simdi,
                'UserId' => $DemoUserId,
                'RolId' => $ViewerRolId,
            ]);
        }

        $Redis = Redis::getInstance();
        $Redis->sil(sprintf('user:%d:permissions', $DemoUserId));
        $Redis->sil(sprintf('user:%d:permissions:ttl', $DemoUserId));
        $Redis->sil(sprintf('user:%d:roles', $DemoUserId));
        $Redis->sil(sprintf('role:%d:permissions', $ViewerRolId));
        $Redis->sil(sprintf('role:%d:permissions:ttl', $ViewerRolId));
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
        $YeniToken = Token::sign([
            'userId' => (int) $Yukleme['userId'],
            'role' => $Yukleme['role'] ?? 'user',
        ]);
        $Ttl = (int) env('APP_TOKEN_TTL', 7200);
        setcookie('nbt_token', $YeniToken, [
            'expires' => time() + $Ttl,
            'path' => '/',
            'samesite' => 'Lax',
        ]);
        Response::json(['token' => $YeniToken]);
    }
}
