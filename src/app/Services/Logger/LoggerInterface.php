<?php
/**
 * Logger Interface iş kurallarını uygular.
 * Servis seviyesinde işlem akışlarını sağlar.
 */

namespace App\Services\Logger;

interface LoggerInterface
{
    public function log(array $Yukleme): void;
}
