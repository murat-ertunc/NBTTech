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
    
    /** Yonetici - Kullanici yonetimi haric tum yetkiler */
    public const ADMIN = 'admin';
    
    /** Normal kullanici - Sadece kendi kayitlarini yonetir */
    public const USER = 'user';
    
    /** Tum gecerli roller */
    public const TUMU = [
        self::SUPERADMIN,
        self::ADMIN,
        self::USER,
    ];
    
    /** Yonetici rolleri (superadmin ve admin) */
    public const YONETICILER = [
        self::SUPERADMIN,
        self::ADMIN,
    ];
    
    /**
     * Verilen rol gecerli mi kontrol et
     */
    public static function gecerliMi(string $Rol): bool
    {
        return in_array($Rol, self::TUMU, true);
    }
    
    /**
     * Verilen rol yonetici mi kontrol et
     */
    public static function yoneticiMi(string $Rol): bool
    {
        return in_array($Rol, self::YONETICILER, true);
    }
}
