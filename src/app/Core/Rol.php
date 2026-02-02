<?php

namespace App\Core;







class Rol
{
    
    public const SUPERADMIN = 'superadmin';
    
    
    public const USER = 'user';
    
    
    public const TUMU = [
        self::SUPERADMIN,
        self::USER,
    ];
    
    


    public static function gecerliMi(string $Rol): bool
    {
        return in_array($Rol, self::TUMU, true);
    }
    
}
