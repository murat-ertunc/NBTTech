<?php

namespace App\Controllers;

use App\Core\Context;
use App\Core\Response;
use App\Core\Transaction;
use App\Repositories\StampTaxRepository;
use App\Services\CalendarService;

class StampTaxController
{
    public static function index(): void
    {
        $Repo = new StampTaxRepository();
        $KullaniciId = Context::kullaniciId();
        if (!$KullaniciId) {
            Response::error('Oturum gecersiz veya suresi dolmus.', 401);
            return;
        }
        
        $MusteriId = isset($_GET['musteri_id']) ? (int)$_GET['musteri_id'] : 0;
        $Sayfa = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $Limit = isset($_GET['limit']) ? max(1, min(100, (int)$_GET['limit'])) : (int)env('PAGINATION_DEFAULT', 10);

        if ($MusteriId > 0) {
            if (isset($_GET['page']) || isset($_GET['limit'])) {
                $Sonuc = $Repo->musteriDamgaVergileriPaginated($MusteriId, $Sayfa, $Limit);
                Response::json($Sonuc);
            } else {
                $Satirlar = $Repo->musteriDamgaVergileri($MusteriId);
                Response::json(['data' => $Satirlar]);
            }
        } else {
            $Satirlar = $Repo->tumAktifler();
            Response::json(['data' => $Satirlar]);
        }
    }

    /**
     * Tek Damga Vergisi Detayi Getir
     */
    public static function show(array $Parametreler): void
    {
        $Id = isset($Parametreler['id']) ? (int) $Parametreler['id'] : 0;
        if ($Id <= 0) {
            Response::error('Gecersiz kayit.', 404);
            return;
        }

        $KullaniciId = Context::kullaniciId();
        if (!$KullaniciId) {
            Response::error('Oturum gecersiz veya suresi dolmus.', 401);
            return;
        }

        $Repo = new StampTaxRepository();
        $DamgaVergisi = $Repo->bul($Id);

        if (!$DamgaVergisi) {
            Response::error('Damga vergisi bulunamadi.', 404);
            return;
        }

        Response::json(['data' => $DamgaVergisi]);
    }

    public static function store(): void
    {
        // Hem JSON hem FormData destegi
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (strpos($contentType, 'application/json') !== false) {
            $Girdi = json_decode(file_get_contents('php://input'), true) ?: [];
        } else {
            // multipart/form-data
            $Girdi = $_POST;
        }
        
        $Zorunlu = ['MusteriId', 'Tarih', 'Tutar'];
        foreach ($Zorunlu as $Alan) {
            if (empty($Girdi[$Alan]) && $Girdi[$Alan] !== 0) {
                Response::error("$Alan alani zorunludur.", 422);
                return;
            }
        }

        $KullaniciId = Context::kullaniciId();
        if (!$KullaniciId) {
            Response::error('Oturum gecersiz veya suresi dolmus.', 401);
            return;
        }

        // Dosya yukleme islemi - varsa dosya yolunu ve adini kaydediyoruz
        $DosyaAdi = null;
        $DosyaYolu = null;
        if (isset($_FILES['dosya']) && $_FILES['dosya']['error'] === UPLOAD_ERR_OK) {
            $YuklemeKlasoru = __DIR__ . '/../../storage/uploads/';
            if (!is_dir($YuklemeKlasoru)) {
                mkdir($YuklemeKlasoru, 0755, true);
            }
            
            $OrijinalAd = $_FILES['dosya']['name'];
            $Uzanti = strtolower(pathinfo($OrijinalAd, PATHINFO_EXTENSION));
            $GuvenliAd = uniqid() . '_' . time() . '.' . $Uzanti;
            $HedefYol = $YuklemeKlasoru . $GuvenliAd;
            
            if (move_uploaded_file($_FILES['dosya']['tmp_name'], $HedefYol)) {
                $DosyaAdi = $OrijinalAd;
                $DosyaYolu = 'storage/uploads/' . $GuvenliAd;
            }
        }

        $Repo = new StampTaxRepository();
        $YuklenecekVeri = [
            'MusteriId' => (int)$Girdi['MusteriId'],
            'ProjeId' => isset($Girdi['ProjeId']) && $Girdi['ProjeId'] ? (int)$Girdi['ProjeId'] : null,
            'Tarih' => $Girdi['Tarih'],
            'Tutar' => (float)$Girdi['Tutar'],
            'DovizCinsi' => isset($Girdi['DovizCinsi']) ? trim((string)$Girdi['DovizCinsi']) : 'TRY',
            'Aciklama' => isset($Girdi['Aciklama']) ? trim((string)$Girdi['Aciklama']) : null,
            'BelgeNo' => isset($Girdi['BelgeNo']) ? trim((string)$Girdi['BelgeNo']) : null,
            'DosyaAdi' => $DosyaAdi,
            'DosyaYolu' => $DosyaYolu
        ];

        $Id = $Repo->ekle($YuklenecekVeri, $KullaniciId);

        // Takvim hatirlatmasi olustur - tarih varsa
        if (!empty($YuklenecekVeri['Tarih'])) {
            $Aciklama = !empty($YuklenecekVeri['Aciklama']) ? $YuklenecekVeri['Aciklama'] : 'Damga Vergisi';
            CalendarService::createOrUpdateReminder(
                (int)$YuklenecekVeri['MusteriId'],
                'damgavergisi',
                $Id,
                $YuklenecekVeri['Tarih'],
                'Damga Vergisi: ' . $Aciklama,
                $KullaniciId
            );
        }

        Response::json(['id' => $Id], 201);
    }

    public static function update(array $Parametreler): void
    {
        $Id = isset($Parametreler['id']) ? (int) $Parametreler['id'] : 0;
        if ($Id <= 0) {
            Response::error('Gecersiz kayit.', 422);
            return;
        }

        // Hem JSON hem FormData gelen istekleri destekliyoruz
        $IcerikTipi = $_SERVER['CONTENT_TYPE'] ?? '';
        if (strpos($IcerikTipi, 'application/json') !== false) {
            $Girdi = json_decode(file_get_contents('php://input'), true) ?: [];
        } elseif (strpos($IcerikTipi, 'multipart/form-data') !== false) {
            // PUT isteklerinde $_POST bos kalir, bu yuzden $_POST kullaniyoruz
            // Ancak PHP multipart/form-data'yi PUT icin otomatik parse etmez
            // Frontend POST gibi davranir ve $_POST dolar
            $Girdi = $_POST;
        } elseif (strpos($IcerikTipi, 'application/x-www-form-urlencoded') !== false) {
            // PUT icin urlencoded veri parse ediliyor
            parse_str(file_get_contents('php://input'), $Girdi);
        } else {
            // Diger durumlar icin $_POST kullan
            $Girdi = $_POST;
        }
        
        $Repo = new StampTaxRepository();
        $KullaniciId = Context::kullaniciId();
        if (!$KullaniciId) {
            Response::error('Oturum gecersiz veya suresi dolmus.', 401);
            return;
        }

        $Guncellenecek = [];
        if (isset($Girdi['Tarih'])) $Guncellenecek['Tarih'] = $Girdi['Tarih'];
        if (isset($Girdi['Tutar'])) $Guncellenecek['Tutar'] = (float)$Girdi['Tutar'];
        if (isset($Girdi['DovizCinsi'])) $Guncellenecek['DovizCinsi'] = trim((string)$Girdi['DovizCinsi']);
        if (isset($Girdi['Aciklama'])) $Guncellenecek['Aciklama'] = trim((string)$Girdi['Aciklama']);
        if (isset($Girdi['BelgeNo'])) $Guncellenecek['BelgeNo'] = trim((string)$Girdi['BelgeNo']);
        if (array_key_exists('ProjeId', $Girdi)) $Guncellenecek['ProjeId'] = $Girdi['ProjeId'] ? (int)$Girdi['ProjeId'] : null;

        // Dosya silme veya guncelleme islemi
        if (!empty($Girdi['removeFile'])) {
            // Mevcut dosyayi sil
            $Mevcut = $Repo->bul($Id);
            if ($Mevcut && !empty($Mevcut['DosyaYolu'])) {
                $EskiDosyaYolu = __DIR__ . '/../../' . $Mevcut['DosyaYolu'];
                if (file_exists($EskiDosyaYolu)) {
                    unlink($EskiDosyaYolu);
                }
            }
            $Guncellenecek['DosyaAdi'] = null;
            $Guncellenecek['DosyaYolu'] = null;
        }

        // Yeni dosya yuklendiyse eskisini silip yenisini kaydediyoruz
        if (isset($_FILES['dosya']) && $_FILES['dosya']['error'] === UPLOAD_ERR_OK) {
            // Eski dosyayi fiziksel olarak sil
            $Mevcut = $Repo->bul($Id);
            if ($Mevcut && !empty($Mevcut['DosyaYolu'])) {
                $EskiDosyaYolu = __DIR__ . '/../../' . $Mevcut['DosyaYolu'];
                if (file_exists($EskiDosyaYolu)) {
                    unlink($EskiDosyaYolu);
                }
            }

            $YuklemeKlasoru = __DIR__ . '/../../storage/uploads/';
            if (!is_dir($YuklemeKlasoru)) {
                mkdir($YuklemeKlasoru, 0755, true);
            }
            
            $OrijinalAd = $_FILES['dosya']['name'];
            $Uzanti = strtolower(pathinfo($OrijinalAd, PATHINFO_EXTENSION));
            $GuvenliAd = uniqid() . '_' . time() . '.' . $Uzanti;
            $HedefYol = $YuklemeKlasoru . $GuvenliAd;
            
            if (move_uploaded_file($_FILES['dosya']['tmp_name'], $HedefYol)) {
                $Guncellenecek['DosyaAdi'] = $OrijinalAd;
                $Guncellenecek['DosyaYolu'] = 'storage/uploads/' . $GuvenliAd;
            }
        }

        if (!empty($Guncellenecek)) {
            $Repo->guncelle($Id, $Guncellenecek, $KullaniciId);
            
            // Takvim hatirlatmasi guncelle - tarih varsa
            if (isset($Guncellenecek['Tarih'])) {
                $Mevcut = $Repo->bul($Id);
                if ($Mevcut) {
                    $Aciklama = isset($Guncellenecek['Aciklama']) ? $Guncellenecek['Aciklama'] : ($Mevcut['Aciklama'] ?? 'Damga Vergisi');
                    CalendarService::createOrUpdateReminder(
                        (int)$Mevcut['MusteriId'],
                        'damgavergisi',
                        $Id,
                        $Guncellenecek['Tarih'],
                        'Damga Vergisi: ' . $Aciklama,
                        $KullaniciId
                    );
                }
            }
        }

        Response::json(['status' => 'success']);
    }

    public static function delete(array $Parametreler): void
    {
        $Id = isset($Parametreler['id']) ? (int) $Parametreler['id'] : 0;
        if ($Id <= 0) {
            Response::error('Gecersiz kayit.', 422);
            return;
        }

        $Repo = new StampTaxRepository();
        $KullaniciId = Context::kullaniciId();
        if (!$KullaniciId) {
            Response::error('Oturum gecersiz veya suresi dolmus.', 401);
            return;
        }

        $Repo->softSil($Id, $KullaniciId);

        // Takvim hatirlatmasini sil
        CalendarService::deleteReminder('damgavergisi', $Id);

        Response::json(['status' => 'success']);
    }

    public static function download(array $Parametreler): void
    {
        $Id = isset($Parametreler['id']) ? (int) $Parametreler['id'] : 0;
        if ($Id <= 0) {
            Response::error('Gecersiz kayit.', 422);
            return;
        }

        $Repo = new StampTaxRepository();
        $Kayit = $Repo->bul($Id);
        
        if (!$Kayit) {
            Response::error('Kayit bulunamadi.', 404);
            return;
        }

        if (empty($Kayit['DosyaYolu'])) {
            Response::error('Bu kayita ait dosya bulunamadi.', 404);
            return;
        }

        $FilePath = __DIR__ . '/../../' . $Kayit['DosyaYolu'];
        
        if (!file_exists($FilePath)) {
            Response::error('Dosya bulunamadi.', 404);
            return;
        }

        $DosyaAdi = $Kayit['DosyaAdi'] ?? basename($Kayit['DosyaYolu']);
        $MimeType = mime_content_type($FilePath) ?: 'application/octet-stream';

        header('Content-Type: ' . $MimeType);
        header('Content-Disposition: attachment; filename="' . $DosyaAdi . '"');
        header('Content-Length: ' . filesize($FilePath));
        readfile($FilePath);
        exit;
    }
}
