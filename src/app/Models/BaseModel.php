<?php

namespace App\Models;

use App\Core\Context;

class BaseModel
{
    protected array $Ozellikler = [];

    public function __construct(array $Ozellikler = [])
    {
        $this->Ozellikler = $Ozellikler;
    }

    public function toArray(): array
    {
        return $this->Ozellikler;
    }

    public static function insertIcinStandartAlanlar(array $Veri, ?int $KullaniciId = null): array
    {
        $Simdi = date('Y-m-d H:i:s');
        $Uid = Context::kullaniciId($KullaniciId);
        return array_merge([
            'Guid' => self::yeniGuid(),
            'EklemeZamani' => $Simdi,
            'EkleyenUserId' => $Uid,
            'DegisiklikZamani' => $Simdi,
            'DegistirenUserId' => $Uid,
            'Sil' => 0,
        ], $Veri);
    }

    public static function updateIcinStandartAlanlar(array $Veri, ?int $KullaniciId = null): array
    {
        $Uid = Context::kullaniciId($KullaniciId);
        return array_merge([
            'DegisiklikZamani' => date('Y-m-d H:i:s'),
            'DegistirenUserId' => $Uid,
        ], $Veri);
    }

    public static function softDeleteIcinStandartAlanlar(?int $KullaniciId = null): array
    {
        $Uid = Context::kullaniciId($KullaniciId);
        return [
            'Sil' => 1,
            'DegisiklikZamani' => date('Y-m-d H:i:s'),
            'DegistirenUserId' => $Uid,
        ];
    }

    private static function yeniGuid(): string
    {
        $Veri = random_bytes(16);
        $Veri[6] = chr((ord($Veri[6]) & 0x0f) | 0x40);
        $Veri[8] = chr((ord($Veri[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($Veri), 4));
    }
}
