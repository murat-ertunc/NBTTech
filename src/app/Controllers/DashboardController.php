<?php

namespace App\Controllers;

use App\Core\Context;
use App\Core\Database;
use App\Core\Response;
use App\Services\Authorization\AuthorizationService;

class DashboardController
{
    public static function index(): void
    {
        if (!Context::kullaniciId()) {
            Response::error('Yetkisiz erisim.', 401);
            return;
        }
        $KullaniciId = (int) Context::kullaniciId();
        $Auth = AuthorizationService::getInstance();

        $TumMusteriler = $Auth->tumunuGorebilirMi($KullaniciId, 'customers');
        $TumProjeler = $Auth->tumunuGorebilirMi($KullaniciId, 'projects');
        $TumFaturalar = $Auth->tumunuGorebilirMi($KullaniciId, 'invoices');
        $TumOdemeler = $Auth->tumunuGorebilirMi($KullaniciId, 'payments');

        try {
            $Db = Database::connection();

            
            $CustomerWhere = 'Sil = 0';
            $CustomerParams = [];
            if (!$TumMusteriler) {
                $CustomerWhere .= ' AND EkleyenUserId = :userId';
                $CustomerParams['userId'] = $KullaniciId;
            }
            $Stmt = $Db->prepare("SELECT COUNT(*) FROM tbl_musteri WHERE {$CustomerWhere}");
            $Stmt->execute($CustomerParams);
            $CustomerCount = (int) $Stmt->fetchColumn();

            
            $ProjectWhere = 'Sil = 0 AND Durum = 1';
            $ProjectParams = [];
            if (!$TumProjeler) {
                $ProjectWhere .= ' AND EkleyenUserId = :userId';
                $ProjectParams['userId'] = $KullaniciId;
            }
            $Stmt = $Db->prepare("SELECT COUNT(*) FROM tbl_proje WHERE {$ProjectWhere}");
            $Stmt->execute($ProjectParams);
            $ProjectCount = (int) $Stmt->fetchColumn();

            
            $PendingSql = "
                SELECT SUM(f.Tutar - ISNULL(paid.Toplam, 0)) AS PendingAmount
                FROM tbl_fatura f
                OUTER APPLY (
                    SELECT SUM(o.Tutar) AS Toplam
                    FROM tbl_odeme o
                    WHERE o.FaturaId = f.Id AND o.Sil = 0
                ) paid
                WHERE f.Sil = 0
                  AND f.Tutar > ISNULL(paid.Toplam, 0)
            ";
            $PendingParams = [];
            if (!$TumFaturalar) {
                $PendingSql .= ' AND f.EkleyenUserId = :userId';
                $PendingParams['userId'] = $KullaniciId;
            }
            $Stmt = $Db->prepare($PendingSql);
            $Stmt->execute($PendingParams);
            $PendingAmount = (float) $Stmt->fetchColumn();

            
            $Month = (int) date('n');
            $Year = (int) date('Y');
            $CollectedSql = "
                SELECT SUM(o.Tutar) AS CollectedAmount
                FROM tbl_odeme o
                WHERE o.Sil = 0
                  AND MONTH(o.Tarih) = :month
                  AND YEAR(o.Tarih) = :year
            ";
            $CollectedParams = ['month' => $Month, 'year' => $Year];
            if (!$TumOdemeler) {
                $CollectedSql .= ' AND o.EkleyenUserId = :userId';
                $CollectedParams['userId'] = $KullaniciId;
            }
            $Stmt = $Db->prepare($CollectedSql);
            $Stmt->execute($CollectedParams);
            $CollectedAmount = (float) $Stmt->fetchColumn();

            Response::json([
                'customerCount' => $CustomerCount,
                'projectCount' => $ProjectCount,
                'pendingAmount' => $PendingAmount,
                'collectedAmount' => $CollectedAmount
            ]);
        } catch (\Throwable $E) {
            error_log('DashboardController::index error: ' . $E->getMessage());
            Response::json([
                'customerCount' => 0,
                'projectCount' => 0,
                'pendingAmount' => 0,
                'collectedAmount' => 0
            ]);
        }
    }
}
