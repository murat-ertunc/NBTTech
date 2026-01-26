<?php
require dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'bootstrap' . DIRECTORY_SEPARATOR . 'app.php';

$db = \App\Core\Database::connection();

echo "=== PROJE DURUMLARI ===\n";
$stmt = $db->query("SELECT Kod, Deger, Etiket FROM tbl_parametre WHERE Grup = 'durum_proje' AND Aktif = 1 ORDER BY Sira");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $row) {
    echo "  Kod: {$row['Kod']}, Deger: {$row['Deger']}, Etiket: {$row['Etiket']}\n";
}

echo "\n=== TEKLIF DURUMLARI ===\n";
$stmt = $db->query("SELECT Kod, Deger, Etiket FROM tbl_parametre WHERE Grup = 'durum_teklif' AND Aktif = 1 ORDER BY Sira");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $row) {
    echo "  Kod: {$row['Kod']}, Deger: {$row['Deger']}, Etiket: {$row['Etiket']}\n";
}

echo "\n=== SOZLESME DURUMLARI ===\n";
$stmt = $db->query("SELECT Kod, Deger, Etiket FROM tbl_parametre WHERE Grup = 'durum_sozlesme' AND Aktif = 1 ORDER BY Sira");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $row) {
    echo "  Kod: {$row['Kod']}, Deger: {$row['Deger']}, Etiket: {$row['Etiket']}\n";
}

echo "\n=== TEMINAT DURUMLARI ===\n";
$stmt = $db->query("SELECT Kod, Deger, Etiket FROM tbl_parametre WHERE Grup = 'durum_teminat' AND Aktif = 1 ORDER BY Sira");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $row) {
    echo "  Kod: {$row['Kod']}, Deger: {$row['Deger']}, Etiket: {$row['Etiket']}\n";
}
