<?php
session_start();
require 'db.php';

header('Content-Type: application/json');

$user = $_SESSION['user'] ?? null;
$userId = $user['id'] ?? $_SESSION['user_id'] ?? null;
$productId = (int)($_POST['product_id'] ?? 0);

if (!$userId || !$productId) {
    echo json_encode(['status' => 'error', 'message' => 'Lütfen önce giriş yapın.']);
    exit;
}

// Favori kontrolü
$stmt = $pdo->prepare("SELECT id FROM favorites WHERE user_id = ? AND product_id = ?");
$stmt->execute([$userId, $productId]);
$fav = $stmt->fetch(PDO::FETCH_ASSOC);

if ($fav) {
    $del = $pdo->prepare("DELETE FROM favorites WHERE id = ?");
    $del->execute([$fav['id']]);
    echo json_encode(['status' => 'removed']);
} else {
    $ins = $pdo->prepare("INSERT INTO favorites (user_id, product_id) VALUES (?, ?)");
    $ins->execute([$userId, $productId]);
    echo json_encode(['status' => 'added']);
}