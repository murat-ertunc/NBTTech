<?php

namespace App\Middleware;

use App\Core\Response;
use App\Core\Token;

class Role
{
    public static function rolGerekli(array $IzinliRoller): bool
    {
        $Header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        $Rol = null;
        if (stripos($Header, 'Bearer ') === 0) {
            $TokenStr = trim(substr($Header, 7));
            $Yukleme = Token::verify($TokenStr);
            if ($Yukleme && isset($Yukleme['role'])) {
                $Rol = $Yukleme['role'];
            }
        }
        if ($Rol === null) {
            Response::error('Yetkisiz erisim.', 403);
            return false;
        }
        if (!in_array($Rol, $IzinliRoller, true)) {
            Response::error('Yetkisiz erisim.', 403);
            return false;
        }
        return true;
    }
}
