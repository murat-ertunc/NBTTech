<?php
/**
 * Queue Factory - kuyruk sürücüsü oluşturma.
 * RabbitMQ bağlantısı başarısız olursa NullQueue döner.
 */

namespace App\Core;

class QueueFactory
{
    private static ?QueueInterface $Instance = null;

    public static function make(): QueueInterface
    {
        if (self::$Instance !== null) {
            return self::$Instance;
        }

        $Surucu = env('QUEUE_DRIVER', 'rabbitmq');

        switch ($Surucu) {
            case 'rabbitmq':
                try {
                    self::$Instance = RabbitMqQueue::getInstance();
                    if (!self::$Instance->bagliMi()) {
                        error_log('[QueueFactory] RabbitMQ bağlantısı kurulamadı, NullQueue kullanılıyor.');
                        self::$Instance = NullQueue::getInstance();
                    }
                } catch (\Throwable $E) {
                    error_log('[QueueFactory] RabbitMQ hatası: ' . $E->getMessage());
                    self::$Instance = NullQueue::getInstance();
                }
                break;

            case 'null':
            case 'sync':
                self::$Instance = NullQueue::getInstance();
                break;

            default:
                error_log('[QueueFactory] Bilinmeyen kuyruk sürücüsü: ' . $Surucu);
                self::$Instance = NullQueue::getInstance();
        }

        return self::$Instance;
    }
}
