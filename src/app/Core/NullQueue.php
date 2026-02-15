<?php
/**
 * Null Queue - Kuyruk servisi kullanılamadığında sessiz fallback.
 * Test ve geliştirme ortamlarında da kullanılabilir.
 */

namespace App\Core;

class NullQueue implements QueueInterface
{
    private static ?NullQueue $Instance = null;

    public static function getInstance(): self
    {
        if (self::$Instance === null) {
            self::$Instance = new self();
        }
        return self::$Instance;
    }

    public function gonder(string $KuyrukAdi, array $Yukleme): bool
    {
        // Sessizce başarılı dön — kuyruk servisi devre dışı
        return true;
    }

    public function tuket(string $KuyrukAdi, callable $Isleyici): bool
    {
        return false;
    }

    public function bagliMi(): bool
    {
        return false;
    }
}
