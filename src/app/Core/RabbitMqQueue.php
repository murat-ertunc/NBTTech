<?php
/**
 * RabbitMQ kuyruk sürücüsü.
 * php-amqplib kütüphanesi gerektirmeden socket-based AMQP 0-9-1 implementasyonu.
 *
 * Not: Production'da php-amqplib veya ext-amqp kullanılması önerilir.
 * Bu implementasyon ext-amqp (PECL) kullanır.
 */

namespace App\Core;

class RabbitMqQueue implements QueueInterface
{
    private static ?RabbitMqQueue $Instance = null;

    private ?\AMQPConnection $Baglanti = null;
    private ?\AMQPChannel $Kanal = null;
    private bool $Bagli = false;

    private function __construct()
    {
        $this->baglan();
    }

    public static function getInstance(): self
    {
        if (self::$Instance === null) {
            self::$Instance = new self();
        }
        return self::$Instance;
    }

    private function baglan(): bool
    {
        if ($this->Bagli) {
            return true;
        }

        if (!class_exists('AMQPConnection')) {
            error_log('[RabbitMqQueue] ext-amqp PECL eklentisi bulunamadı. Kuyruk devre dışı.');
            return false;
        }

        try {
            $Config = require CONFIG_PATH . 'queue.php';

            $this->Baglanti = new \AMQPConnection([
                'host'     => $Config['host'],
                'port'     => $Config['port'],
                'login'    => $Config['user'],
                'password' => $Config['pass'],
            ]);
            $this->Baglanti->connect();

            $this->Kanal = new \AMQPChannel($this->Baglanti);
            $this->Bagli = true;

            return true;
        } catch (\Throwable $E) {
            error_log('[RabbitMqQueue] Bağlantı hatası: ' . $E->getMessage());
            $this->Bagli = false;
            return false;
        }
    }

    public function bagliMi(): bool
    {
        return $this->Bagli && $this->Baglanti !== null && $this->Baglanti->isConnected();
    }

    public function gonder(string $KuyrukAdi, array $Yukleme): bool
    {
        if (!$this->bagliMi()) {
            error_log("[RabbitMqQueue] Bağlantı yok, mesaj gönderilemedi: {$KuyrukAdi}");
            return false;
        }

        try {
            $Kuyruk = new \AMQPQueue($this->Kanal);
            $Kuyruk->setName($KuyrukAdi);
            $Kuyruk->setFlags(AMQP_DURABLE);
            $Kuyruk->declareQueue();

            $Exchange = new \AMQPExchange($this->Kanal);
            $Exchange->publish(
                json_encode($Yukleme, JSON_UNESCAPED_UNICODE),
                $KuyrukAdi,
                AMQP_NOPARAM,
                ['delivery_mode' => 2, 'content_type' => 'application/json']
            );

            return true;
        } catch (\Throwable $E) {
            error_log("[RabbitMqQueue] Gönderim hatası ({$KuyrukAdi}): " . $E->getMessage());
            return false;
        }
    }

    public function tuket(string $KuyrukAdi, callable $Isleyici): bool
    {
        if (!$this->bagliMi()) {
            return false;
        }

        try {
            $Kuyruk = new \AMQPQueue($this->Kanal);
            $Kuyruk->setName($KuyrukAdi);
            $Kuyruk->setFlags(AMQP_DURABLE);
            $Kuyruk->declareQueue();

            $Mesaj = $Kuyruk->get();
            if ($Mesaj === false) {
                return false;
            }

            $Yukleme = json_decode($Mesaj->getBody(), true) ?? [];
            $Isleyici($Yukleme);
            $Kuyruk->ack($Mesaj->getDeliveryTag());

            return true;
        } catch (\Throwable $E) {
            error_log("[RabbitMqQueue] Tüketim hatası ({$KuyrukAdi}): " . $E->getMessage());
            return false;
        }
    }

    public function __destruct()
    {
        if ($this->Baglanti !== null && $this->Baglanti->isConnected()) {
            $this->Baglanti->disconnect();
        }
    }
}
