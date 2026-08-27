<?php
session_start();
require 'db.php';

$user = $_SESSION['user'] ?? null;
$userId = $user['id'] ?? $_SESSION['user_id'] ?? null;

if (!$userId) {
    header("Location: login.php");
    exit;
}

$stmt = $pdo->prepare("
    SELECT product.*, category.name AS category_name
    FROM favorites
    JOIN product ON favorites.product_id = product.id
    LEFT JOIN category ON product.category_id = category.id
    WHERE favorites.user_id = ?
    ORDER BY favorites.created_at DESC
");
$stmt->execute([$userId]);
$favProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wishlist | Notti Blu</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: "Helvetica Neue", Arial, sans-serif; background: #F8F6F3; color: #17233C; }
        .container { max-width: 1100px; margin: 0 auto; padding: 40px 30px 80px; }
        .back { display: inline-block; margin-bottom: 25px; color: #687386; text-decoration: none; font-size: 14px; font-weight: 600; }
        h1 { font-family: Georgia, serif; font-size: 36px; margin-bottom: 30px; color: #0A1833; }
        .tour-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 35px 25px; }
        .tour-card { background: #FFFFFF; border-radius: 18px; overflow: hidden; text-decoration: none; color: inherit; display: block; box-shadow: 0 6px 20px rgba(15,23,42,0.06); }
        .tour-image { width: 100%; height: 240px; object-fit: cover; display: block; }
        .tour-content { padding: 22px 24px; }
        .category { font-size: 11px; text-transform: uppercase; letter-spacing: 1.5px; color: #7B8494; font-weight: 600; margin-bottom: 8px; }
        .tour-title { font-family: Georgia, serif; font-size: 22px; font-weight: 600; margin-bottom: 12px; color: #101D38; }
        .price { font-size: 20px; font-weight: 700; color: #203A63; }
        @media (max-width: 700px) { .tour-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
<div class="container">
    <a href="index.php" class="back">← Koleksiyona Dön</a>
    <h1>Kaydedilen Rotalar (Wishlist)</h1>

    <?php if (empty($favProducts)): ?>
        <p style="color: #7B8494;">Henüz favorilerinize eklediğiniz bir rota bulunmuyor.</p>
    <?php else: ?>
        <div class="tour-grid">
            <?php foreach ($favProducts as $product): ?>
                <a class="tour-card" href="products.php?id=<?= $product['id'] ?>">
                    <img class="tour-image" src="images/<?= htmlspecialchars($product['image']) ?>" alt="">
                    <div class="tour-content">
                        <div class="category"><?= htmlspecialchars($product['category_name']) ?></div>
                        <div class="tour-title"><?= htmlspecialchars($product['title']) ?></div>
                        <div class="price">€<?= number_format($product['price'], 0, ',', '.') ?></div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
</body>
</html>