<?php
/**
 * Logger Factory iş kurallarını uygular.
 * Servis seviyesinde işlem akışlarını sağlar.
 */

namespace App\Services\Logger;

use RuntimeException;

class LoggerFactory
{
    public static function make(): LoggerInterface
    {
        $Surucu = config('log.driver', 'db');
        switch ($Surucu) {
            case 'db':
                return new DbLogger();
            case 'file':
                return new FileLogger();
            default:
                throw new RuntimeException('Desteklenmeyen log surucusu: ' . $Surucu);
        }
    }
}
