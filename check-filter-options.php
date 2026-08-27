<?php
require 'db.php';
header('Content-Type: application/json; charset=utf-8');

$category_id = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$visa_status = $_GET['visa_status'] ?? '';
$duration    = $_GET['duration'] ?? '';

// Mevcut seçili kriterlere uyan ürünleri çek
$sql = "SELECT price, visa_status, duration FROM product WHERE 1=1";
$params = [];

if ($category_id > 0) {
    $sql .= " AND category_id = ?";
    $params[] = $category_id;
}
if ($visa_status !== '' && $visa_status !== 'Tümü') {
    $sql .= " AND visa_status = ?";
    $params[] = $visa_status;
}
if ($duration !== '' && $duration !== 'Tümü') {
    if ($duration === '2-3 gün') $val = 3;
    elseif ($duration === '4-5 gün') $val = 5;
    elseif ($duration === '1 hafta') $val = 7;
    elseif ($duration === '1 haftadan uzun') $val = 10;
    
    if (isset($val)) {
        $sql .= " AND duration = ?";
        $params[] = $val;
    }
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Uygun aralıkları ve seçenekleri belirle
$hasPrice0_500   = false;
$hasPrice500_1000 = false;
$hasPrice1000_1500 = false;
$hasPrice1500Plus = false;

$hasVizesiz = false;
$hasVizeli  = false;

foreach ($rows as $row) {
    $p = (float)$row['price'];
    if ($p <= 500) $hasPrice0_500 = true;
    if ($p > 500 && $p <= 1000) $hasPrice500_1000 = true;
    if ($p > 1000 && $p <= 1500) $hasPrice1000_1500 = true;
    if ($p > 1500) $hasPrice1500Plus = true;

    if ($row['visa_status'] === 'Vizesiz') $hasVizesiz = true;
    if ($row['visa_status'] === 'Vizeli')  $hasVizeli = true;
}

echo json_encode([
    'prices' => [
        '0-500'     => $hasPrice0_500,
        '500-1000'  => $hasPrice500_1000,
        '1000-1500' => $hasPrice1000_1500,
        '1500+'     => $hasPrice1500Plus
    ],
    'visas' => [
        'Vizesiz' => $hasVizesiz,
        'Vizeli'  => $hasVizeli
    ]
]);