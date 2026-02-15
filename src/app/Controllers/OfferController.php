<?php
/**
 * Offer Controller için HTTP isteklerini yönetir.
 * Gelen talepleri doğrular ve yanıt akışını oluşturur.
 */

namespace App\Controllers;

use App\Core\Context;
use App\Core\Response;
use App\Core\Transaction;
use App\Core\UploadValidator;
use App\Core\DownloadHelper;
use App\Repositories\OfferRepository;
use App\Services\CalendarService;

class OfferController
{
    public static function index(): void
    {
        $Repo = new OfferRepository();
        $KullaniciId = Context::kullaniciId();
        if (!$KullaniciId) {
            Response::error('Oturum gecersiz veya suresi dolmus.', 401);
            return;
        }

        $MusteriId = isset($_GET['musteri_id']) ? (int)$_GET['musteri_id'] : 0;
        $ProjeId = isset($_GET['proje_id']) ? (int)$_GET['proje_id'] : 0;
        $Sayfa = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $Limit = isset($_GET['limit']) ? max(1, min(100, (int)$_GET['limit'])) : (int)env('PAGINATION_DEFAULT', 10);

        if ($MusteriId > 0) {
            if (isset($_GET['page']) || isset($_GET['limit'])) {
                $Sonuc = $Repo->musteriTeklifleriPaginated($MusteriId, $Sayfa, $Limit);
                Response::json($Sonuc);
            } else {
                $Satirlar = $Repo->musteriTeklifleri($MusteriId);
                Response::json(['data' => $Satirlar]);
            }
        } elseif ($ProjeId > 0) {
            $Satirlar = $Repo->projeTeklifleri($ProjeId);
            Response::json(['data' => $Satirlar]);
        } else {

            if (isset($_GET['page']) || isset($_GET['limit'])) {
                $Sonuc = $Repo->tumAktiflerPaginated($Sayfa, $Limit);
                Response::json($Sonuc);
            } else {
                $Satirlar = $Repo->tumAktifler();
                Response::json(['data' => $Satirlar]);
            }
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

        $Repo = new OfferRepository();
        $Teklif = $Repo->bul($Id);

        if (!$Teklif) {
            Response::error('Teklif bulunamadi.', 404);
            return;
        }

        Response::json(['data' => $Teklif]);
    }

    public static function store(): void
    {

        $IcerikTipi = $_SERVER['CONTENT_TYPE'] ?? '';
        if (strpos($IcerikTipi, 'application/json') !== false) {
            $Girdi = json_decode(file_get_contents('php://input'), true) ?: [];
        } else {

            $Girdi = $_POST;
        }

        $Zorunlu = ['MusteriId'];
        foreach ($Zorunlu as $Alan) {
            if (empty($Girdi[$Alan])) {
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
            $MaksimumBoyut = 10 * 1024 * 1024;
            $Hata = UploadValidator::validateDocument($_FILES['dosya'], $MaksimumBoyut);
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
            $GuvenliAd = bin2hex(random_bytes(16)) . '.' . $Uzanti;
            $HedefYol = $YuklemeKlasoru . $GuvenliAd;

            if (move_uploaded_file($_FILES['dosya']['tmp_name'], $HedefYol)) {
                $DosyaAdi = $OrijinalAd;
                $DosyaYolu = 'storage/uploads/' . $GuvenliAd;
            }
        }

        $Repo = new OfferRepository();
        $YuklenecekVeri = [
            'MusteriId' => (int)$Girdi['MusteriId'],
            'ProjeId' => !empty($Girdi['ProjeId']) ? (int)$Girdi['ProjeId'] : null,
            'Konu' => $Girdi['Konu'] ?? null,
            'Tutar' => isset($Girdi['Tutar']) ? (float)$Girdi['Tutar'] : 0.00,
            'ParaBirimi' => $Girdi['ParaBirimi'] ?? 'TRY',
            'TeklifTarihi' => $Girdi['TeklifTarihi'] ?? null,
            'GecerlilikTarihi' => $Girdi['GecerlilikTarihi'] ?? null,
            'Durum' => isset($Girdi['Durum']) ? (int)$Girdi['Durum'] : 0,
            'DosyaAdi' => $DosyaAdi,
            'DosyaYolu' => $DosyaYolu
        ];

        $Id = Transaction::wrap(function () use ($Repo, $YuklenecekVeri, $KullaniciId) {
            return $Repo->ekle($YuklenecekVeri, $KullaniciId);
        });

        if (!empty($YuklenecekVeri['GecerlilikTarihi'])) {
            CalendarService::createOrUpdateReminder(
                (int)$YuklenecekVeri['MusteriId'],
                'teklif',
                $Id,
                $YuklenecekVeri['GecerlilikTarihi'],
                'Teklif Geçerlilik: ' . ($YuklenecekVeri['Konu'] ?? 'Teklif'),
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

        $Repo = new OfferRepository();
        $KullaniciId = Context::kullaniciId();
        if (!$KullaniciId) {
            Response::error('Oturum gecersiz veya suresi dolmus.', 401);
            return;
        }

        $Guncellenecek = [];
        if (isset($Girdi['Konu'])) $Guncellenecek['Konu'] = $Girdi['Konu'];
        if (isset($Girdi['Tutar'])) $Guncellenecek['Tutar'] = (float)$Girdi['Tutar'];
        if (isset($Girdi['ParaBirimi'])) $Guncellenecek['ParaBirimi'] = $Girdi['ParaBirimi'];
        if (isset($Girdi['TeklifTarihi'])) $Guncellenecek['TeklifTarihi'] = $Girdi['TeklifTarihi'];
        if (isset($Girdi['GecerlilikTarihi'])) $Guncellenecek['GecerlilikTarihi'] = $Girdi['GecerlilikTarihi'];
        if (isset($Girdi['Durum'])) $Guncellenecek['Durum'] = (int)$Girdi['Durum'];
        if (isset($Girdi['ProjeId'])) $Guncellenecek['ProjeId'] = (int)$Girdi['ProjeId'];

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
            $MaksimumBoyut = 10 * 1024 * 1024;
            $Hata = UploadValidator::validateDocument($_FILES['dosya'], $MaksimumBoyut);
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
            $GuvenliAd = bin2hex(random_bytes(16)) . '.' . $Uzanti;
            $HedefYol = $YuklemeKlasoru . $GuvenliAd;

            if (move_uploaded_file($_FILES['dosya']['tmp_name'], $HedefYol)) {
                $Guncellenecek['DosyaAdi'] = $OrijinalAd;
                $Guncellenecek['DosyaYolu'] = 'storage/uploads/' . $GuvenliAd;
            }
        }

        if (!empty($Guncellenecek)) {
            Transaction::wrap(function () use ($Repo, $Id, $Guncellenecek, $KullaniciId) {
                $Repo->guncelle($Id, $Guncellenecek, $KullaniciId);
            });
        }

        if (isset($Girdi['GecerlilikTarihi'])) {
            $Mevcut = $Repo->bul($Id);
            if ($Mevcut) {
                $Konu = isset($Girdi['Konu']) ? $Girdi['Konu'] : ($Mevcut['Konu'] ?? 'Teklif');
                CalendarService::createOrUpdateReminder(
                    (int)$Mevcut['MusteriId'],
                    'teklif',
                    $Id,
                    $Girdi['GecerlilikTarihi'],
                    'Teklif Geçerlilik: ' . $Konu,
                    $KullaniciId
                );
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

        $Repo = new OfferRepository();
        $KullaniciId = Context::kullaniciId();
        if (!$KullaniciId) {
            Response::error('Oturum gecersiz veya suresi dolmus.', 401);
            return;
        }

        Transaction::wrap(function () use ($Repo, $Id, $KullaniciId) {
            $Repo->softSil($Id, $KullaniciId);
        });

        CalendarService::deleteReminder('teklif', $Id);

        Response::json(['status' => 'success']);
    }

    public static function download(array $Parametreler): void
    {
        $Id = isset($Parametreler['id']) ? (int) $Parametreler['id'] : 0;
        if ($Id <= 0) {
            Response::error('Gecersiz kayit.', 422);
            return;
        }

        $Repo = new OfferRepository();
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
