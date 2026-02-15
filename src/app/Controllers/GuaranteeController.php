<?php
/**
 * Guarantee Controller için HTTP isteklerini yönetir.
 * Gelen talepleri doğrular ve yanıt akışını oluşturur.
 */

namespace App\Controllers;

use App\Core\Context;
use App\Core\Response;
use App\Core\Transaction;
use App\Core\UploadValidator;
use App\Core\DownloadHelper;
use App\Repositories\GuaranteeRepository;
use App\Services\CalendarService;

class GuaranteeController
{
    public static function index(): void
    {
        $Repo = new GuaranteeRepository();
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
                $Sonuc = $Repo->musteriTeminatlariPaginated($MusteriId, $Sayfa, $Limit);
                Response::json($Sonuc);
            } else {
                $Satirlar = $Repo->musteriTeminatlari($MusteriId);
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

        $Repo = new GuaranteeRepository();
        $Teminat = $Repo->bul($Id);

        if (!$Teminat) {
            Response::error('Teminat bulunamadi.', 404);
            return;
        }

        Response::json(['data' => $Teminat]);
    }

    public static function store(): void
    {

        $IcerikTipi = $_SERVER['CONTENT_TYPE'] ?? '';
        if (strpos($IcerikTipi, 'application/json') !== false) {
            $Girdi = json_decode(file_get_contents('php://input'), true) ?: [];
        } else {

            $Girdi = $_POST;
        }

        $Zorunlu = ['MusteriId', 'Tur', 'Tutar'];
        foreach ($Zorunlu as $Alan) {
            if (!isset($Girdi[$Alan]) || (empty($Girdi[$Alan]) && $Girdi[$Alan] !== 0 && $Girdi[$Alan] !== '0')) {
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

            $OriginalName = $_FILES['dosya']['name'];
            $Uzanti = strtolower(pathinfo($OriginalName, PATHINFO_EXTENSION));
            $GuvenliAd = bin2hex(random_bytes(16)) . '.' . $Uzanti;
            $HedefYol = $YuklemeKlasoru . $GuvenliAd;

            if (move_uploaded_file($_FILES['dosya']['tmp_name'], $HedefYol)) {
                $DosyaAdi = $OriginalName;
                $DosyaYolu = 'storage/uploads/' . $GuvenliAd;
            }
        }

        $Repo = new GuaranteeRepository();
        $YuklenecekVeri = [
            'MusteriId' => (int)$Girdi['MusteriId'],
            'ProjeId' => !empty($Girdi['ProjeId']) ? (int)$Girdi['ProjeId'] : null,
            'Tur' => trim((string)$Girdi['Tur']),
            'Tutar' => (float)$Girdi['Tutar'],
            'ParaBirimi' => $Girdi['ParaBirimi'] ?? 'TRY',
            'BankaAdi' => $Girdi['BankaAdi'] ?? null,
            'TerminTarihi' => $Girdi['TerminTarihi'] ?? null,
            'Durum' => (isset($Girdi['Durum']) && trim((string)$Girdi['Durum']) !== '') ? (int)$Girdi['Durum'] : 1,
            'Notlar' => isset($Girdi['Notlar']) ? trim((string)$Girdi['Notlar']) : null,
            'DosyaAdi' => $DosyaAdi,
            'DosyaYolu' => $DosyaYolu
        ];

        $Id = Transaction::wrap(function () use ($Repo, $YuklenecekVeri, $KullaniciId) {
            return $Repo->ekle($YuklenecekVeri, $KullaniciId);
        });

        if (!empty($YuklenecekVeri['TerminTarihi'])) {
            $Tur = !empty($YuklenecekVeri['Tur']) ? $YuklenecekVeri['Tur'] : 'Teminat';
            CalendarService::createOrUpdateReminder(
                (int)$YuklenecekVeri['MusteriId'],
                'teminat',
                $Id,
                $YuklenecekVeri['TerminTarihi'],
                'Teminat Termin: ' . $Tur,
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

        $Repo = new GuaranteeRepository();
        $KullaniciId = Context::kullaniciId();
        if (!$KullaniciId) {
            Response::error('Oturum gecersiz veya suresi dolmus.', 401);
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

            $OriginalName = $_FILES['dosya']['name'];
            $Uzanti = strtolower(pathinfo($OriginalName, PATHINFO_EXTENSION));
            $GuvenliAd = bin2hex(random_bytes(16)) . '.' . $Uzanti;
            $HedefYol = $YuklemeKlasoru . $GuvenliAd;

            if (move_uploaded_file($_FILES['dosya']['tmp_name'], $HedefYol)) {
                $DosyaGuncellemesi['DosyaAdi'] = $OriginalName;
                $DosyaGuncellemesi['DosyaYolu'] = 'storage/uploads/' . $GuvenliAd;
            }
        }

        Transaction::wrap(function () use ($Repo, $Id, $Girdi, $KullaniciId, $DosyaGuncellemesi) {
            $Guncellenecek = [];
            if (isset($Girdi['Tur'])) $Guncellenecek['Tur'] = trim((string)$Girdi['Tur']);
            if (isset($Girdi['Tutar'])) $Guncellenecek['Tutar'] = (float)$Girdi['Tutar'];
            if (isset($Girdi['ParaBirimi'])) $Guncellenecek['ParaBirimi'] = $Girdi['ParaBirimi'];
            if (isset($Girdi['BankaAdi'])) $Guncellenecek['BankaAdi'] = $Girdi['BankaAdi'];
            if (isset($Girdi['TerminTarihi'])) $Guncellenecek['TerminTarihi'] = $Girdi['TerminTarihi'];
            if (isset($Girdi['Notlar'])) $Guncellenecek['Notlar'] = trim((string)$Girdi['Notlar']);
            if (array_key_exists('Durum', $Girdi)) {
                $DurumDeger = trim((string)$Girdi['Durum']);
                $Guncellenecek['Durum'] = $DurumDeger === '' ? 1 : (int)$DurumDeger;
            }
            if (isset($Girdi['ProjeId'])) $Guncellenecek['ProjeId'] = !empty($Girdi['ProjeId']) ? (int)$Girdi['ProjeId'] : null;

            $Guncellenecek = array_merge($Guncellenecek, $DosyaGuncellemesi);

            if (!empty($Guncellenecek)) {
                $Repo->guncelle($Id, $Guncellenecek, $KullaniciId);
            }
        });

        if (isset($Girdi['TerminTarihi'])) {
            $Mevcut = $Repo->bul($Id);
            if ($Mevcut) {
                $Tur = isset($Girdi['Tur']) ? $Girdi['Tur'] : ($Mevcut['Tur'] ?? 'Teminat');
                CalendarService::createOrUpdateReminder(
                    (int)$Mevcut['MusteriId'],
                    'teminat',
                    $Id,
                    $Girdi['TerminTarihi'],
                    'Teminat Termin: ' . $Tur,
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

        $Repo = new GuaranteeRepository();
        $KullaniciId = Context::kullaniciId();
        if (!$KullaniciId) {
            Response::error('Oturum gecersiz veya suresi dolmus.', 401);
            return;
        }

        Transaction::wrap(function () use ($Repo, $Id, $KullaniciId) {
            $Repo->softSil($Id, $KullaniciId);
        });

        CalendarService::deleteReminder('teminat', $Id);

        Response::json(['status' => 'success']);
    }

    public static function download(array $Parametreler): void
    {
        $Id = isset($Parametreler['id']) ? (int) $Parametreler['id'] : 0;
        if ($Id <= 0) {
            Response::error('Gecersiz kayit.', 422);
            return;
        }

        $Repo = new GuaranteeRepository();
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
