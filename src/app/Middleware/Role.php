<?php

namespace App\Middleware;

use App\Core\Response;

class Role
{

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
