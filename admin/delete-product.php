<?php

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../admin-login.php');
    exit;
}

require '../db.php';

$id = $_GET['id'] ?? null;

if (!$id) {
    header('Location: index.php');
    exit;
}


/* Silinecek rotayı bul */
$stmt = $pdo->prepare("
    SELECT image
    FROM product
    WHERE id = ?
");

$stmt->execute([$id]);

$product = $stmt->fetch(PDO::FETCH_ASSOC);

if ($product) {

    /* Veritabanından sil */
    $stmt = $pdo->prepare("
        DELETE FROM product
        WHERE id = ?
    ");

    $stmt->execute([$id]);


    /* Fotoğraf dosyasını da sil */
    if (!empty($product['image'])) {

        $imagePath = '../images/' . $product['image'];

        if (file_exists($imagePath)) {
            unlink($imagePath);
        }
    }
}

header('Location: index.php');
exit;