<?php

namespace App\Core;

class Response
{
    public static function json($Veri, int $DurumKodu = 200): void
    {
        http_response_code($DurumKodu);
        header('Content-Type: application/json');
        echo json_encode($Veri, JSON_UNESCAPED_UNICODE);
    }

    public static function error(string $Mesaj, int $DurumKodu = 400, $Hatalar = null): void
    {
        $Govde = ['error' => $Mesaj];
        if ($Hatalar !== null) {
            $Govde['errors'] = $Hatalar;
        }
        self::json($Govde, $DurumKodu);
    }

    public static function validationError(array $AlanHatalari, string $Mesaj = 'Doğrulama hatası'): void
    {
        $Govde = [
            'ok' => false,
            'error' => [
                'code' => 'VALIDATION_ERROR',
                'message' => $Mesaj,
                'fields' => $AlanHatalari
            ]
        ];
        self::json($Govde, 422);
    }

    public static function forbidden(string $Mesaj = 'Bu işlem için yetkiniz yok.'): void
    {
        $Govde = [
            'ok' => false,
            'error' => [
                'code' => 'FORBIDDEN',
                'message' => $Mesaj
            ]
        ];
        self::json($Govde, 403);
    }
}
