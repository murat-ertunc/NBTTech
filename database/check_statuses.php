<?php
require __DIR__ . '/../app/Core/bootstrap.php';

$db = \App\Core\Database::connection();

echo "=== PROJE DURUMLARI ===\n";
$stmt = $db->query("SELECT Kod, Deger, Etiket FROM tbl_parametre WHERE Grup = 'durum_proje' AND Aktif = 1 ORDER BY Sira");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $row) {
    echo "  Kod: {$row['Kod']}, Deger: {$row['Deger']}, Etiket: {$row['Etiket']}\n";
}

echo "\n=== TEKLİF DURUMLARI ===\n";
$stmt = $db->query("SELECT Kod, Deger, Etiket FROM tbl_parametre WHERE Grup = 'durum_teklif' AND Aktif = 1 ORDER BY Sira");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $row) {
    echo "  Kod: {$row['Kod']}, Deger: {$row['Deger']}, Etiket: {$row['Etiket']}\n";
}

echo "\n=== SÖZLEŞME DURUMLARI ===\n";
$stmt = $db->query("SELECT Kod, Deger, Etiket FROM tbl_parametre WHERE Grup = 'durum_sozlesme' AND Aktif = 1 ORDER BY Sira");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $row) {
    echo "  Kod: {$row['Kod']}, Deger: {$row['Deger']}, Etiket: {$row['Etiket']}\n";
}

echo "\n=== TEMİNAT DURUMLARI ===\n";
$stmt = $db->query("SELECT Kod, Deger, Etiket FROM tbl_parametre WHERE Grup = 'durum_teminat' AND Aktif = 1 ORDER BY Sira");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $row) {
    echo "  Kod: {$row['Kod']}, Deger: {$row['Deger']}, Etiket: {$row['Etiket']}\n";
}
