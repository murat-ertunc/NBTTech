<?php
/**
 * Stamp Tax Controller için HTTP isteklerini yönetir.
 * Gelen talepleri doğrular ve yanıt akışını oluşturur.
 */

namespace App\Controllers;

use App\Core\Context;
use App\Core\Response;
use App\Core\Transaction;
use App\Core\UploadValidator;
use App\Core\DownloadHelper;
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

        $IcerikTipi = $_SERVER['CONTENT_TYPE'] ?? '';
        if (strpos($IcerikTipi, 'application/json') !== false) {
            $Girdi = json_decode(file_get_contents('php://input'), true) ?: [];
        } else {

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

        $DosyaAdi = null;
        $DosyaYolu = null;
        if (isset($_FILES['dosya']) && $_FILES['dosya']['error'] === UPLOAD_ERR_OK) {
            $Hata = UploadValidator::validateDocument($_FILES['dosya'], 10 * 1024 * 1024);
            if ($Hata !== null) {
                Response::json(['errors' => ['dosya' => $Hata], 'message' => $Hata], 422);
                return;
            }
            $YuklemeKlasoru = STORAGE_PATH . 'uploads' . DIRECTORY_SEPARATOR;
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
            'BelgeNo' => isset($Girdi['BelgeNo']) ? trim((string)$Girdi['BelgeNo']) : null,
            'OdemeDurumu' => isset($Girdi['OdemeDurumu']) ? trim((string)$Girdi['OdemeDurumu']) : null,
            'Notlar' => isset($Girdi['Notlar']) ? trim((string)$Girdi['Notlar']) : null,
            'DosyaAdi' => $DosyaAdi,
            'DosyaYolu' => $DosyaYolu
        ];

        $Id = $Repo->ekle($YuklenecekVeri, $KullaniciId);

        if (!empty($YuklenecekVeri['Tarih'])) {
            $Notlar = !empty($YuklenecekVeri['Notlar']) ? $YuklenecekVeri['Notlar'] : 'Damga Vergisi';
            CalendarService::createOrUpdateReminder(
                (int)$YuklenecekVeri['MusteriId'],
                'damgavergisi',
                $Id,
                $YuklenecekVeri['Tarih'],
                'Damga Vergisi: ' . $Notlar,
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

        $IcerikTipi = $_SERVER['CONTENT_TYPE'] ?? '';
        if (strpos($IcerikTipi, 'application/json') !== false) {
            $Girdi = json_decode(file_get_contents('php://input'), true) ?: [];
        } elseif (strpos($IcerikTipi, 'multipart/form-data') !== false) {

            $Girdi = $_POST;
        } elseif (strpos($IcerikTipi, 'application/x-www-form-urlencoded') !== false) {

            parse_str(file_get_contents('php://input'), $Girdi);
        } else {

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
        if (isset($Girdi['BelgeNo'])) $Guncellenecek['BelgeNo'] = trim((string)$Girdi['BelgeNo']);
        if (isset($Girdi['OdemeDurumu'])) $Guncellenecek['OdemeDurumu'] = trim((string)$Girdi['OdemeDurumu']);
        if (array_key_exists('Notlar', $Girdi)) $Guncellenecek['Notlar'] = trim((string)$Girdi['Notlar']);
        if (array_key_exists('ProjeId', $Girdi)) $Guncellenecek['ProjeId'] = $Girdi['ProjeId'] ? (int)$Girdi['ProjeId'] : null;

        if (!empty($Girdi['removeFile'])) {

            $Mevcut = $Repo->bul($Id);
            if ($Mevcut && !empty($Mevcut['DosyaYolu'])) {
                $EskiDosyaYolu = SRC_PATH . $Mevcut['DosyaYolu'];
                if (file_exists($EskiDosyaYolu)) {
                    unlink($EskiDosyaYolu);
                }
            }
            $Guncellenecek['DosyaAdi'] = null;
            $Guncellenecek['DosyaYolu'] = null;
        }

        if (isset($_FILES['dosya']) && $_FILES['dosya']['error'] === UPLOAD_ERR_OK) {
            $Hata = UploadValidator::validateDocument($_FILES['dosya'], 10 * 1024 * 1024);
            if ($Hata !== null) {
                Response::json(['errors' => ['dosya' => $Hata], 'message' => $Hata], 422);
                return;
            }

            $Mevcut = $Repo->bul($Id);
            if ($Mevcut && !empty($Mevcut['DosyaYolu'])) {
                $EskiDosyaYolu = SRC_PATH . $Mevcut['DosyaYolu'];
                if (file_exists($EskiDosyaYolu)) {
                    unlink($EskiDosyaYolu);
                }
            }

            $YuklemeKlasoru = STORAGE_PATH . 'uploads' . DIRECTORY_SEPARATOR;
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

            if (isset($Guncellenecek['Tarih'])) {
                $Mevcut = $Repo->bul($Id);
                if ($Mevcut) {
                    $Notlar = isset($Guncellenecek['Notlar']) ? $Guncellenecek['Notlar'] : ($Mevcut['Notlar'] ?? 'Damga Vergisi');
                    CalendarService::createOrUpdateReminder(
                        (int)$Mevcut['MusteriId'],
                        'damgavergisi',
                        $Id,
                        $Guncellenecek['Tarih'],
                        'Damga Vergisi: ' . $Notlar,
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

        $FilePath = SRC_PATH . $Kayit['DosyaYolu'];

        if (!file_exists($FilePath)) {
            Response::error('Dosya bulunamadi.', 404);
            return;
        }

        $DosyaAdi = $Kayit['DosyaAdi'] ?? basename($Kayit['DosyaYolu']);
        DownloadHelper::outputFile($FilePath, $DosyaAdi);
    }
}
