<?php
// get_seanslar.php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/auth.php';

header('Content-Type: application/json');

if (isset($_GET['saha_id']) && isset($_GET['tarih'])) {
    $saha_id = (int)$_GET['saha_id'];
    $tarih = $_GET['tarih'];

    // Dolu seanslar
    $doluSeansStmt = $pdo->prepare("SELECT seans_id FROM rezervasyonlar WHERE saha_id = :saha_id AND tarih = :tarih");
    $doluSeansStmt->execute(['saha_id' => $saha_id, 'tarih' => $tarih]);
    $doluSeanslar = $doluSeansStmt->fetchAll(PDO::FETCH_COLUMN);

    // Seansları çek
    $seanslarStmt = $pdo->prepare("SELECT id, baslangic_saati, bitis_saati FROM seanslar WHERE saha_id = :saha_id ORDER BY baslangic_saati ASC");
    $seanslarStmt->execute(['saha_id' => $saha_id]);
    $seanslar = $seanslarStmt->fetchAll(PDO::FETCH_ASSOC);

    $response = [];
    foreach ($seanslar as $seans) {
        $seans_zaman = substr($seans['baslangic_saati'],0,5) . " - " . substr($seans['bitis_saati'],0,5);
        $is_dolu = in_array($seans['id'], $doluSeanslar);
        $response[] = [
            'id' => $seans['id'],
            'text' => "Seans: $seans_zaman" . ($is_dolu ? " (DOLU)" : ""),
            'disabled' => $is_dolu
        ];
    }

    echo json_encode($response);
    exit;
}

// Eğer gerekli parametreler yoksa boş JSON döndür
echo json_encode([]);
?>
