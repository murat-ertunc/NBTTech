<?php
/**
 * Payment Controller için HTTP isteklerini yönetir.
 * Gelen talepleri doğrular ve yanıt akışını oluşturur.
 */

namespace App\Controllers;

use App\Core\Context;
use App\Core\Response;
use App\Core\Transaction;
use App\Core\UploadValidator;
use App\Core\DownloadHelper;
use App\Repositories\PaymentRepository;
use App\Services\Logger\ActionLogger;
use App\Services\CalendarService;

class PaymentController
{
    public static function index(): void
    {
        $Repo = new PaymentRepository();
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
                $Sonuc = $Repo->musteriyeGorePaginated($MusteriId, $Sayfa, $Limit);
                Response::json($Sonuc);
            } else {
                $Satirlar = $Repo->musteriyeGore($MusteriId);
                Response::json(['data' => $Satirlar]);
            }
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

        $Repo = new PaymentRepository();
        $Odeme = $Repo->bul($Id);

        if (!$Odeme) {
            Response::error('Odeme bulunamadi.', 404);
            return;
        }

        Response::json(['data' => $Odeme]);
    }

    public static function store(): void
    {
        $IcerikTipi = $_SERVER['CONTENT_TYPE'] ?? '';
        if (strpos($IcerikTipi, 'application/json') !== false) {
            $Girdi = json_decode(file_get_contents('php://input'), true) ?: [];
        } else {
            $Girdi = $_POST;
        }
        $Zorunlu = ['MusteriId', 'ProjeId', 'FaturaId', 'Tarih', 'Tutar'];
        foreach ($Zorunlu as $Alan) {
            if (empty($Girdi[$Alan])) {
                Response::error("$Alan alani zorunludur.", 422);
                return;
            }
        }

        $MusteriId = (int)$Girdi['MusteriId'];
        $ProjeId = (int)$Girdi['ProjeId'];
        $FaturaId = (int)$Girdi['FaturaId'];
        $Tutar = (float)$Girdi['Tutar'];
        $Tarih = trim((string)$Girdi['Tarih']);
        $Aciklama = isset($Girdi['Aciklama']) ? trim((string)$Girdi['Aciklama']) : null;
        $OdemeTuru = isset($Girdi['OdemeTuru']) ? trim((string)$Girdi['OdemeTuru']) : null;
        $BankaHesap = isset($Girdi['BankaHesap']) ? trim((string)$Girdi['BankaHesap']) : null;
        $Notlar = isset($Girdi['Notlar']) ? trim((string)$Girdi['Notlar']) : null;

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

        $Repo = new PaymentRepository();
        $Id = Transaction::wrap(function () use ($Repo, $MusteriId, $ProjeId, $FaturaId, $Tarih, $Tutar, $Aciklama, $OdemeTuru, $BankaHesap, $Notlar, $DosyaAdi, $DosyaYolu, $KullaniciId) {
            return $Repo->ekle([
                'MusteriId' => $MusteriId,
                'ProjeId' => $ProjeId,
                'FaturaId' => $FaturaId,
                'Tarih' => $Tarih,
                'Tutar' => $Tutar,
                'Aciklama' => $Aciklama,
                'OdemeTuru' => $OdemeTuru,
                'BankaHesap' => $BankaHesap,
                'Notlar' => $Notlar,
                'DosyaAdi' => $DosyaAdi,
                'DosyaYolu' => $DosyaYolu
            ], $KullaniciId);
        });

        if (!empty($Tarih)) {
            $OdemeAciklama = !empty($Aciklama) ? $Aciklama : 'Ödeme';
            CalendarService::createOrUpdateReminder(
                $MusteriId,
                'odeme',
                $Id,
                $Tarih,
                'Ödeme: ' . $OdemeAciklama,
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
        $Repo = new PaymentRepository();
        $KullaniciId = Context::kullaniciId();
        if (!$KullaniciId) {
            Response::error('Oturum gecersiz veya suresi dolmus.', 401);
            return;
        }

        $Mevcut = $Repo->bul($Id);
        if (!$Mevcut) {
            Response::error('Odeme bulunamadi.', 404);
            return;
        }

        $DosyaGuncellemesi = [];
        if (!empty($Girdi['removeFile'])) {
            $Mevcut = $Repo->bul($Id);
            if ($Mevcut && !empty($Mevcut['DosyaYolu'])) {
                $EskiDosyaYolu = SRC_PATH . $Mevcut['DosyaYolu'];
                if (file_exists($EskiDosyaYolu)) {
                    unlink($EskiDosyaYolu);
                }
            }
            $DosyaGuncellemesi['DosyaAdi'] = null;
            $DosyaGuncellemesi['DosyaYolu'] = null;
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
                $DosyaGuncellemesi['DosyaAdi'] = $OrijinalAd;
                $DosyaGuncellemesi['DosyaYolu'] = 'storage/uploads/' . $GuvenliAd;
            }
        }

        Transaction::wrap(function () use ($Repo, $Id, $Girdi, $KullaniciId, $DosyaGuncellemesi) {
            $Guncellenecek = [];
            if (isset($Girdi['ProjeId'])) $Guncellenecek['ProjeId'] = (int)$Girdi['ProjeId'];
            if (isset($Girdi['Tarih'])) $Guncellenecek['Tarih'] = $Girdi['Tarih'];
            if (isset($Girdi['Tutar'])) $Guncellenecek['Tutar'] = (float)$Girdi['Tutar'];
            if (isset($Girdi['FaturaId'])) $Guncellenecek['FaturaId'] = !empty($Girdi['FaturaId']) ? (int)$Girdi['FaturaId'] : null;
            if (isset($Girdi['Aciklama'])) $Guncellenecek['Aciklama'] = $Girdi['Aciklama'];
            if (isset($Girdi['OdemeTuru'])) $Guncellenecek['OdemeTuru'] = trim((string)$Girdi['OdemeTuru']);
            if (isset($Girdi['BankaHesap'])) $Guncellenecek['BankaHesap'] = trim((string)$Girdi['BankaHesap']);
            if (array_key_exists('Notlar', $Girdi)) $Guncellenecek['Notlar'] = trim((string)$Girdi['Notlar']);

            $Guncellenecek = array_merge($Guncellenecek, $DosyaGuncellemesi);

            if (!empty($Guncellenecek)) {
                $Repo->guncelle($Id, $Guncellenecek, $KullaniciId);
            }
        });

        if (isset($Girdi['Tarih'])) {
            $Mevcut = $Repo->bul($Id);
            if ($Mevcut) {
                $Aciklama = isset($Girdi['Aciklama']) ? $Girdi['Aciklama'] : ($Mevcut['Aciklama'] ?? 'Ödeme');
                CalendarService::createOrUpdateReminder(
                    (int)$Mevcut['MusteriId'],
                    'odeme',
                    $Id,
                    $Girdi['Tarih'],
                    'Ödeme: ' . $Aciklama,
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

        $Repo = new PaymentRepository();
        $KullaniciId = Context::kullaniciId();
        if (!$KullaniciId) {
            Response::error('Oturum gecersiz veya suresi dolmus.', 401);
            return;
        }

        $Mevcut = $Repo->bul($Id);
        if (!$Mevcut) {
            Response::error('Odeme bulunamadi.', 404);
            return;
        }

        Transaction::wrap(function () use ($Repo, $Id, $KullaniciId) {
            $Repo->yedekle($Id, 'bck_tbl_odeme', $KullaniciId);
            $Repo->softSil($Id, $KullaniciId);
            ActionLogger::delete('tbl_odeme', ['Id' => $Id]);
        });

        CalendarService::deleteReminder('odeme', $Id);

        Response::json(['status' => 'success']);
    }

    public static function download(array $Parametreler): void
    {
        $Id = isset($Parametreler['id']) ? (int) $Parametreler['id'] : 0;
        if ($Id <= 0) {
            Response::error('Gecersiz kayit.', 422);
            return;
        }

        $Repo = new PaymentRepository();
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
