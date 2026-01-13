<?php

namespace App\Middleware;

use App\Core\Context;
use App\Core\Response;
use App\Core\Token;

class Auth
{
    public static function yetkilendirmeGerekli(): bool
    {
        $Header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (stripos($Header, 'Bearer ') !== 0) {
            Response::error('Yetkisiz erisim.', 401);
            return false;
        }
        $TokenStr = trim(substr($Header, 7));
        $Yukleme = Token::verify($TokenStr);
        if (!$Yukleme || !isset($Yukleme['userId'])) {
            Response::error('Yetkisiz erisim.', 401);
            return false;
        }
        Context::setKullaniciId((int) $Yukleme['userId']);
        if (isset($Yukleme['role'])) {
            Context::setRol((string) $Yukleme['role']);
        }
        $SekmeId = $_SERVER['HTTP_X_TAB_ID'] ?? null;
        if ($SekmeId) {
            Context::setSekmeId($SekmeId);
        }
        return true;
    }
}
