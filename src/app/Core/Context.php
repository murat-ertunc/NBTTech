<?php

namespace App\Core;




class Context
{
    private static ?int $KullaniciId = null;
    private static ?string $IpAdresi = null;
    private static ?string $SekmeId = null;
    private static ?string $Rol = null;

    public static function setKullaniciId(?int $KullaniciId): void
    {
        self::$KullaniciId = $KullaniciId;
    }

    public static function kullaniciId(?int $Varsayilan = null): ?int
    {
        if (self::$KullaniciId !== null) {
            return self::$KullaniciId;
        }
        if ($Varsayilan !== null) {
            return $Varsayilan;
        }
        $EnvVarsayilan = env('DEFAULT_USER_ID', null);
        return $EnvVarsayilan !== null ? (int) $EnvVarsayilan : null;
    }

    public static function setIpAdresi(?string $IpAdresi): void
    {
        self::$IpAdresi = $IpAdresi;
    }

    public static function ipAdresi(?string $Varsayilan = null): ?string
    {
        if (self::$IpAdresi !== null) {
            return self::$IpAdresi;
        }
        if ($Varsayilan !== null) {
            return $Varsayilan;
        }
        return $_SERVER['REMOTE_ADDR'] ?? env('DEFAULT_IP', null);
    }

    public static function setSekmeId(?string $SekmeId): void
    {
        self::$SekmeId = $SekmeId;
    }

    public static function sekmeId(): ?string
    {
        return self::$SekmeId;
    }

    




    public static function setRol(?string $Rol): void
    {
        self::$Rol = $Rol;
    }

    




    public static function rol(): ?string
    {
        return self::$Rol;
    }
}
