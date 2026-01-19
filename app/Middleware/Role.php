<?php
/**
 * @deprecated Bu sinif artik kullanilmiyor.
 *             Yeni RBAC sisteminde Permission::izinGerekli() kullanin.
 * @see \App\Middleware\Permission
 * 
 * Eski kullanim:
 *   Role::rolGerekli(['superadmin', 'user'])
 * 
 * Yeni kullanim:
 *   Permission::izinGerekli('customers.read')
 */

namespace App\Middleware;

use App\Core\Response;
use App\Core\Token;

/**
 * @deprecated Use Permission::izinGerekli() instead
 */
class Role
{
    /**
     * @deprecated Use Permission::izinGerekli('module.action') instead
     * @param array $IzinliRoller
     * @return bool
     */
    public static function rolGerekli(array $IzinliRoller): bool
    {
        trigger_error(
            'Role::rolGerekli() is deprecated. Use Permission::izinGerekli() instead.',
            E_USER_DEPRECATED
        );
        
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
