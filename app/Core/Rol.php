<?php

namespace App\Core;

/**
 * Kullanici Rolleri
 * 
 * Sistemdeki tum kullanici rollerini tanimlar.
 * Yetki kontrolleri icin bu sabitler kullanilmalidir.
 */
class Rol
{
    /** Super yonetici - Tum yetkilere sahip */
    public const SUPERADMIN = 'superadmin';
    
    /** Normal kullanici - Sadece kendi kayitlarini yonetir */
    public const USER = 'user';
    
    /** Tum gecerli roller */
    public const TUMU = [
        self::SUPERADMIN,
        self::USER,
    ];
    
    /**
     * Verilen rol gecerli mi kontrol et
     */
    public static function gecerliMi(string $Rol): bool
    {
        return in_array($Rol, self::TUMU, true);
    }
    
    /**
     * Verilen rol superadmin mi kontrol et
     */
    public static function superadminMi(string $Rol): bool
    {
        return $Rol === self::SUPERADMIN;
    }
}
