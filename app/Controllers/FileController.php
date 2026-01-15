<?php

namespace App\Controllers;

use App\Core\Context;
use App\Core\Response;
use App\Core\Transaction;
use App\Repositories\FileRepository;

class FileController
{
    private static string $uploadDir = __DIR__ . '/../../storage/uploads/';

    public static function index(): void
    {
        $Repo = new FileRepository();
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
                $Sonuc = $Repo->musteriDosyalariPaginated($MusteriId, $Sayfa, $Limit);
                Response::json($Sonuc);
            } else {
                $Satirlar = $Repo->musteriDosyalari($MusteriId);
                Response::json(['data' => $Satirlar]);
            }
        } else {
            $Satirlar = $Repo->tumAktifler();
            Response::json(['data' => $Satirlar]);
        }
    }

    public static function store(): void
    {
        $KullaniciId = Context::kullaniciId();
        if (!$KullaniciId) {
            Response::error('Oturum gecersiz veya suresi dolmus.', 401);
            return;
        }

        // Hem 'file' hem 'dosya' adini kabul et
        $fileKey = isset($_FILES['file']) ? 'file' : (isset($_FILES['dosya']) ? 'dosya' : null);
        if (!$fileKey || $_FILES[$fileKey]['error'] !== UPLOAD_ERR_OK) {
            $errorMessages = [
                UPLOAD_ERR_INI_SIZE => 'Dosya boyutu sunucu limitini asiyor.',
                UPLOAD_ERR_FORM_SIZE => 'Dosya boyutu form limitini asiyor.',
                UPLOAD_ERR_PARTIAL => 'Dosya kismen yuklendi.',
                UPLOAD_ERR_NO_FILE => 'Dosya secilmedi.',
                UPLOAD_ERR_NO_TMP_DIR => 'Gecici klasor bulunamadi.',
                UPLOAD_ERR_CANT_WRITE => 'Dosya yazilamadi.',
                UPLOAD_ERR_EXTENSION => 'Dosya uzantisi engellendi.'
            ];
            $errorCode = $_FILES[$fileKey]['error'] ?? UPLOAD_ERR_NO_FILE;
            $errorMsg = $errorMessages[$errorCode] ?? 'Dosya yuklenemedi.';
            Response::error($errorMsg, 422);
            return;
        }

        // FaturaId varsa MusteriId zorunlu degil (fatura uzerinden musteri bulunur)
        $FaturaId = isset($_POST['FaturaId']) ? (int)$_POST['FaturaId'] : null;
        $MusteriId = isset($_POST['MusteriId']) ? (int)$_POST['MusteriId'] : 0;
        
        // FaturaId varsa faturadan MusteriId al
        if ($FaturaId && $MusteriId <= 0) {
            $invoiceRepo = new \App\Repositories\InvoiceRepository();
            $fatura = $invoiceRepo->bul($FaturaId);
            if ($fatura) {
                $MusteriId = (int)$fatura['MusteriId'];
            }
        }
        
        if ($MusteriId <= 0) {
            Response::error('MusteriId alani zorunludur.', 422);
            return;
        }

        $File = $_FILES[$fileKey];
        $OriginalName = $File['name'];
        $FileSize = $File['size'];
        $FileType = $File['type'];
        $TempPath = $File['tmp_name'];

        $maxSize = 10 * 1024 * 1024;
        if ($FileSize > $maxSize) {
            $sizeMB = round($FileSize / (1024 * 1024), 2);
            Response::error("Dosya boyutu cok buyuk ({$sizeMB}MB). Maksimum 10MB yuklenebilir.", 422);
            return;
        }

        $allowedTypes = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'image/jpeg',
            'image/png',
            'image/gif',
            'text/plain',
            'application/zip',
            'application/x-rar-compressed',
            'application/octet-stream'
        ];
        
        $allowedExtensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'gif', 'txt', 'zip', 'rar'];
        $Extension = strtolower(pathinfo($OriginalName, PATHINFO_EXTENSION));
        
        if (!in_array($Extension, $allowedExtensions)) {
            Response::error('Bu dosya turu desteklenmiyor. Izin verilen turler: PDF, Word, Excel, Resimler, TXT, ZIP, RAR', 422);
            return;
        }

        $SafeName = uniqid() . '_' . time() . '.' . $Extension;
        
        if (!is_dir(self::$uploadDir)) {
            mkdir(self::$uploadDir, 0755, true);
        }

        $DestPath = self::$uploadDir . $SafeName;
        
        if (!move_uploaded_file($TempPath, $DestPath)) {
            Response::error('Dosya kaydedilemedi.', 500);
            return;
        }

        $Repo = new FileRepository();
        $YuklenecekVeri = [
            'MusteriId' => $MusteriId,
            'ProjeId' => isset($_POST['ProjeId']) && $_POST['ProjeId'] ? (int)$_POST['ProjeId'] : null,
            'FaturaId' => $FaturaId,
            'DosyaAdi' => $OriginalName,
            'DosyaYolu' => 'storage/uploads/' . $SafeName,
            'DosyaTipi' => $FileType,
            'DosyaBoyutu' => $FileSize,
            'Aciklama' => isset($_POST['Aciklama']) ? trim((string)$_POST['Aciklama']) : null
        ];

        try {
            $Id = $Repo->ekle($YuklenecekVeri, $KullaniciId);
            Response::json(['id' => $Id, 'path' => $YuklenecekVeri['DosyaYolu']], 201);
        } catch (\Exception $e) {
            if (file_exists($DestPath)) {
                unlink($DestPath);
            }
            Response::error('Dosya kaydedilemedi: ' . $e->getMessage(), 500);
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
        $Repo = new FileRepository();
        $Dosya = $Repo->bul($Id);
        if (!$Dosya) {
            Response::error('Dosya bulunamadi.', 404);
            return;
        }
        Response::json(['data' => $Dosya]);
    }

    public static function update(array $Parametreler): void
    {
        $Id = isset($Parametreler['id']) ? (int) $Parametreler['id'] : 0;
        if ($Id <= 0) {
            Response::error('Gecersiz kayit.', 422);
            return;
        }

        $Girdi = json_decode(file_get_contents('php://input'), true) ?: [];
        $Repo = new FileRepository();
        $KullaniciId = Context::kullaniciId();
        if (!$KullaniciId) {
            Response::error('Oturum gecersiz veya suresi dolmus.', 401);
            return;
        }

        Transaction::wrap(function () use ($Repo, $Id, $Girdi, $KullaniciId) {
            $Guncellenecek = [];
            if (isset($Girdi['Aciklama'])) $Guncellenecek['Aciklama'] = trim((string)$Girdi['Aciklama']);

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
            Response::error('Gecersiz kayit.', 422);
            return;
        }

        $Repo = new FileRepository();
        $KullaniciId = Context::kullaniciId();
        if (!$KullaniciId) {
            Response::error('Oturum gecersiz veya suresi dolmus.', 401);
            return;
        }

        // Dosyayi fiziksel olarak silmiyoruz, sadece soft delete
        Transaction::wrap(function () use ($Repo, $Id, $KullaniciId) {
            $Repo->softSil($Id, $KullaniciId);
        });

        Response::json(['status' => 'success']);
    }

    public static function download(array $Parametreler): void
    {
        $Id = isset($Parametreler['id']) ? (int) $Parametreler['id'] : 0;
        if ($Id <= 0) {
            Response::error('Gecersiz kayit.', 422);
            return;
        }

        $Repo = new FileRepository();
        $Dosya = $Repo->bul($Id);
        
        if (!$Dosya) {
            Response::error('Dosya bulunamadi.', 404);
            return;
        }

        $FilePath = __DIR__ . '/../../' . $Dosya['DosyaYolu'];
        
        if (!file_exists($FilePath)) {
            Response::error('Dosya bulunamadi.', 404);
            return;
        }

        header('Content-Type: ' . ($Dosya['DosyaTipi'] ?: 'application/octet-stream'));
        header('Content-Disposition: attachment; filename="' . $Dosya['DosyaAdi'] . '"');
        header('Content-Length: ' . filesize($FilePath));
        readfile($FilePath);
        exit;
    }
}
