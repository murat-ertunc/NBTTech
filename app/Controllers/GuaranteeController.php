<?php

namespace App\Controllers;

use App\Core\Context;
use App\Core\Response;
use App\Core\Transaction;
use App\Repositories\GuaranteeRepository;

class GuaranteeController
{
    public static function index(): void
    {
        $Repo = new GuaranteeRepository();
        $KullaniciId = Context::kullaniciId();
        if (!$KullaniciId) {
            Response::error('Oturum geçersiz veya süresi dolmuş.', 401);
            return;
        }
        
        $MusteriId = isset($_GET['musteri_id']) ? (int)$_GET['musteri_id'] : 0;
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $limit = isset($_GET['limit']) ? max(1, min(100, (int)$_GET['limit'])) : (int)env('PAGINATION_DEFAULT', 10);

        if ($MusteriId > 0) {
            if (isset($_GET['page']) || isset($_GET['limit'])) {
                $result = $Repo->musteriTeminatlariPaginated($MusteriId, $page, $limit);
                Response::json($result);
            } else {
                $Satirlar = $Repo->musteriTeminatlari($MusteriId);
                Response::json(['data' => $Satirlar]);
            }
        } else {
            // Standalone sayfa - pagination ile tüm teminatlar
            if (isset($_GET['page']) || isset($_GET['limit'])) {
                $result = $Repo->tumAktiflerPaginated($page, $limit);
                Response::json($result);
            } else {
                $Satirlar = $Repo->tumAktifler();
                Response::json(['data' => $Satirlar]);
            }
        }
    }

    public static function store(): void
    {
        // Hem JSON hem FormData desteği
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (strpos($contentType, 'application/json') !== false) {
            $Girdi = json_decode(file_get_contents('php://input'), true) ?: [];
        } else {
            // multipart/form-data
            $Girdi = $_POST;
        }
        
        $Zorunlu = ['MusteriId', 'Tur', 'Tutar'];
        foreach ($Zorunlu as $Alan) {
            if (empty($Girdi[$Alan])) {
                Response::error("$Alan alanı zorunludur.", 422);
                return;
            }
        }

        $KullaniciId = Context::kullaniciId();
        if (!$KullaniciId) {
            Response::error('Oturum geçersiz veya süresi dolmuş.', 401);
            return;
        }

        // Dosya yükleme işlemi
        $DosyaAdi = null;
        $DosyaYolu = null;
        if (isset($_FILES['dosya']) && $_FILES['dosya']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../../storage/uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $OriginalName = $_FILES['dosya']['name'];
            $Extension = strtolower(pathinfo($OriginalName, PATHINFO_EXTENSION));
            $SafeName = uniqid() . '_' . time() . '.' . $Extension;
            $DestPath = $uploadDir . $SafeName;
            
            if (move_uploaded_file($_FILES['dosya']['tmp_name'], $DestPath)) {
                $DosyaAdi = $OriginalName;
                $DosyaYolu = 'storage/uploads/' . $SafeName;
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
            'VadeTarihi' => $Girdi['VadeTarihi'] ?? null,
            'BelgeNo' => $Girdi['BelgeNo'] ?? null,
            'Durum' => isset($Girdi['Durum']) ? (int)$Girdi['Durum'] : 1,
            'DosyaAdi' => $DosyaAdi,
            'DosyaYolu' => $DosyaYolu
        ];

        $Id = Transaction::wrap(function () use ($Repo, $YuklenecekVeri, $KullaniciId) {
            return $Repo->ekle($YuklenecekVeri, $KullaniciId);
        });

        Response::json(['id' => $Id], 201);
    }

    public static function update(array $Parametreler): void
    {
        $Id = isset($Parametreler['id']) ? (int) $Parametreler['id'] : 0;
        if ($Id <= 0) {
            Response::error('Geçersiz kayıt.', 422);
            return;
        }

        // Hem JSON hem FormData desteği
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (strpos($contentType, 'application/json') !== false) {
            $Girdi = json_decode(file_get_contents('php://input'), true) ?: [];
        } else {
            // multipart/form-data
            $Girdi = $_POST;
        }
        
        $Repo = new GuaranteeRepository();
        $KullaniciId = Context::kullaniciId();
        if (!$KullaniciId) {
            Response::error('Oturum geçersiz veya süresi dolmuş.', 401);
            return;
        }

        // Dosya silme veya güncelleme işlemi (Transaction dışında)
        $DosyaGuncellemesi = [];
        if (!empty($Girdi['removeFile'])) {
            // Mevcut dosyayı sil
            $Mevcut = $Repo->bul($Id);
            if ($Mevcut && !empty($Mevcut['DosyaYolu'])) {
                $EskiDosyaYolu = __DIR__ . '/../../' . $Mevcut['DosyaYolu'];
                if (file_exists($EskiDosyaYolu)) {
                    unlink($EskiDosyaYolu);
                }
            }
            $DosyaGuncellemesi['DosyaAdi'] = null;
            $DosyaGuncellemesi['DosyaYolu'] = null;
        }

        // Yeni dosya yüklendiyse
        if (isset($_FILES['dosya']) && $_FILES['dosya']['error'] === UPLOAD_ERR_OK) {
            // Eski dosyayı sil
            $Mevcut = $Repo->bul($Id);
            if ($Mevcut && !empty($Mevcut['DosyaYolu'])) {
                $EskiDosyaYolu = __DIR__ . '/../../' . $Mevcut['DosyaYolu'];
                if (file_exists($EskiDosyaYolu)) {
                    unlink($EskiDosyaYolu);
                }
            }

            $uploadDir = __DIR__ . '/../../storage/uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $OriginalName = $_FILES['dosya']['name'];
            $Extension = strtolower(pathinfo($OriginalName, PATHINFO_EXTENSION));
            $SafeName = uniqid() . '_' . time() . '.' . $Extension;
            $DestPath = $uploadDir . $SafeName;
            
            if (move_uploaded_file($_FILES['dosya']['tmp_name'], $DestPath)) {
                $DosyaGuncellemesi['DosyaAdi'] = $OriginalName;
                $DosyaGuncellemesi['DosyaYolu'] = 'storage/uploads/' . $SafeName;
            }
        }

        Transaction::wrap(function () use ($Repo, $Id, $Girdi, $KullaniciId, $DosyaGuncellemesi) {
            $Guncellenecek = [];
            if (isset($Girdi['Tur'])) $Guncellenecek['Tur'] = trim((string)$Girdi['Tur']);
            if (isset($Girdi['Tutar'])) $Guncellenecek['Tutar'] = (float)$Girdi['Tutar'];
            if (isset($Girdi['ParaBirimi'])) $Guncellenecek['ParaBirimi'] = $Girdi['ParaBirimi'];
            if (isset($Girdi['BankaAdi'])) $Guncellenecek['BankaAdi'] = $Girdi['BankaAdi'];
            if (isset($Girdi['VadeTarihi'])) $Guncellenecek['VadeTarihi'] = $Girdi['VadeTarihi'];
            if (isset($Girdi['BelgeNo'])) $Guncellenecek['BelgeNo'] = $Girdi['BelgeNo'];
            if (isset($Girdi['Durum'])) $Guncellenecek['Durum'] = (int)$Girdi['Durum'];
            if (isset($Girdi['ProjeId'])) $Guncellenecek['ProjeId'] = !empty($Girdi['ProjeId']) ? (int)$Girdi['ProjeId'] : null;

            // Dosya güncellemesi varsa ekle
            $Guncellenecek = array_merge($Guncellenecek, $DosyaGuncellemesi);

            if (!empty($Guncellenecek)) {
                $Repo->guncelle($Id, $Guncellenecek, $KullaniciId);
            }
        });

        Response::json(['status' => 'success']);
    }

    public static function delete(array $Parametreler): void
    {
        $Id = isset($Parametreler['id']) ? (int) $Parametreler['id'] : 0;
        if ($Id <= 0) {
            Response::error('Geçersiz kayıt.', 422);
            return;
        }

        $Repo = new GuaranteeRepository();
        $KullaniciId = Context::kullaniciId();
        if (!$KullaniciId) {
            Response::error('Oturum geçersiz veya süresi dolmuş.', 401);
            return;
        }

        Transaction::wrap(function () use ($Repo, $Id, $KullaniciId) {
            $Repo->softSil($Id, $KullaniciId);
        });

        Response::json(['status' => 'success']);
    }

    public static function download(array $Parametreler): void
    {
        $Id = isset($Parametreler['id']) ? (int) $Parametreler['id'] : 0;
        if ($Id <= 0) {
            Response::error('Geçersiz kayıt.', 422);
            return;
        }

        $Repo = new GuaranteeRepository();
        $Kayit = $Repo->bul($Id);
        
        if (!$Kayit) {
            Response::error('Kayıt bulunamadı.', 404);
            return;
        }

        if (empty($Kayit['DosyaYolu'])) {
            Response::error('Bu kayıta ait dosya bulunamadı.', 404);
            return;
        }

        $FilePath = __DIR__ . '/../../' . $Kayit['DosyaYolu'];
        
        if (!file_exists($FilePath)) {
            Response::error('Dosya bulunamadı.', 404);
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
