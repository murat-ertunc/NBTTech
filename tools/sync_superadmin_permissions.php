<?php

/**
 * Superadmin Tum Permission Sync
 * 
 * Kullanim: php tools/sync_superadmin_permissions.php
 */

require_once __DIR__ . '/../app/Core/bootstrap.php';

use App\Core\Database;
use App\Core\Transaction;
use App\Services\Authorization\AuthorizationService;

$Db = Database::connection();

$SuperadminRol = $Db->query("SELECT Id FROM tnm_rol WHERE RolKodu = 'superadmin' AND Sil = 0")
    ->fetch(\PDO::FETCH_ASSOC);

if (!$SuperadminRol || empty($SuperadminRol['Id'])) {
    echo "Superadmin rolu bulunamadi.\n";
    exit(1);
}

$SuperadminRolId = (int) $SuperadminRol['Id'];

$Permissionler = $Db->query("SELECT Id FROM tnm_permission WHERE Sil = 0 AND Aktif = 1")
    ->fetchAll(\PDO::FETCH_COLUMN);

if (empty($Permissionler)) {
    echo "Permission listesi bulunamadi.\n";
    exit(1);
}

$Eklendi = 0;
$Guncellendi = 0;

Transaction::wrap(function () use ($Db, $SuperadminRolId, $Permissionler, &$Eklendi, &$Guncellendi) {
    foreach ($Permissionler as $PermissionId) {
        $PermissionId = (int) $PermissionId;
        $VarSql = "SELECT Id, Sil FROM tnm_rol_permission WHERE RolId = :RolId AND PermissionId = :PermissionId";
        $VarStmt = $Db->prepare($VarSql);
        $VarStmt->execute([':RolId' => $SuperadminRolId, ':PermissionId' => $PermissionId]);
        $VarMi = $VarStmt->fetch(\PDO::FETCH_ASSOC);
        
        if ($VarMi) {
            if ((int) $VarMi['Sil'] === 1) {
                $GuncelleSql = "UPDATE tnm_rol_permission SET Sil = 0, DegisiklikZamani = SYSUTCDATETIME(), DegistirenUserId = 1 WHERE Id = :Id";
                $GuncelleStmt = $Db->prepare($GuncelleSql);
                $GuncelleStmt->execute([':Id' => (int) $VarMi['Id']]);
                $Guncellendi++;
            }
        } else {
            $EkleSql = "
                INSERT INTO tnm_rol_permission (Guid, EklemeZamani, EkleyenUserId, DegisiklikZamani, DegistirenUserId, Sil, RolId, PermissionId)
                VALUES (NEWID(), SYSUTCDATETIME(), 1, SYSUTCDATETIME(), 1, 0, :RolId, :PermissionId)
            ";
            $EkleStmt = $Db->prepare($EkleSql);
            $EkleStmt->execute([
                ':RolId' => $SuperadminRolId,
                ':PermissionId' => $PermissionId
            ]);
            $Eklendi++;
        }
    }
});

$AuthService = AuthorizationService::getInstance();
$AuthService->rolKullanicilarininCacheTemizle($SuperadminRolId);
$AuthService->tumCacheTemizle();

echo "Superadmin rolu permission senkron tamamlandi.\n";
echo "Eklenen: {$Eklendi}\n";
echo "Guncellenen: {$Guncellendi}\n";
