<?php

namespace App\Core;







class Token
{
    


    private static function getKey(): string
    {
        $Anahtar = env('APP_KEY');
        if (empty($Anahtar) || $Anahtar === 'devkey') {
            if (env('APP_ENV', 'production') === 'production') {
                throw new \RuntimeException('APP_KEY ortam degiskeni production ortaminda zorunludur');
            }
            return 'development-only-key-not-secure';
        }
        return $Anahtar;
    }

    public static function sign(array $Veri, ?int $GecerlilikSuresi = null): string
    {
        $Anahtar = self::getKey();
        $OlusturmaZamani = time();
        $VarsayilanSure = (int) env('APP_TOKEN_TTL', 7200);
        $SonGecerlilik = $OlusturmaZamani + ($GecerlilikSuresi ?? $VarsayilanSure);
        $Govde = array_merge($Veri, [
            'iat' => $OlusturmaZamani,
            'exp' => $SonGecerlilik,
        ]);
        $Json = json_encode($Govde, JSON_UNESCAPED_UNICODE);
        $Taban = rtrim(strtr(base64_encode($Json), '+/', '-_'), '=');
        $Imza = rtrim(strtr(base64_encode(hash_hmac('sha256', $Taban, $Anahtar, true)), '+/', '-_'), '=');
        return $Taban . '.' . $Imza;
    }

    public static function verify(string $Token): ?array
    {
        $Anahtar = self::getKey();
        $Parcalar = explode('.', $Token);
        if (count($Parcalar) !== 2) {
            return null;
        }
        [$Taban, $Imza] = $Parcalar;
        $BeklenenImza = rtrim(strtr(base64_encode(hash_hmac('sha256', $Taban, $Anahtar, true)), '+/', '-_'), '=');
        if (!hash_equals($BeklenenImza, $Imza)) {
            return null;
        }
        $Json = base64_decode(strtr($Taban, '-_', '+/'));
        $Veri = json_decode($Json, true);
        if (!is_array($Veri)) {
            return null;
        }
        if (isset($Veri['exp']) && time() > (int) $Veri['exp']) {
            return null;
        }
        return $Veri;
    }
}
