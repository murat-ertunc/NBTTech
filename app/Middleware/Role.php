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
        
        Response::error('Rol tabanli yetkilendirme devre disi. Permission kontrolu kullanin.', 403);
        return false;
    }
}
