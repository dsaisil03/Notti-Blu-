<?php
session_start();
require 'db.php';

// Kullanıcı oturum kontrolü (farklı session yapılarına uyumlu)
$user = $_SESSION['user'] ?? null;
$userId = $user['id'] ?? $_SESSION['user_id'] ?? null;
$userEmail = $user['email'] ?? $_SESSION['user_email'] ?? '';
$userName = $user['fullname'] ?? $user['name'] ?? $_SESSION['user_name'] ?? 'Misafir';

if (!$userId && empty($userEmail)) {
    header("Location: login.php");
    exit;
}

// Siparişleri çek
$stmt = $pdo->prepare("
    SELECT orders.*, product.title AS product_title, product.image AS product_image, product.duration 
    FROM orders 
    LEFT JOIN product ON orders.product_id = product.id 
    WHERE orders.user_id = :uid OR orders.email = :email 
    ORDER BY orders.created_at DESC
");
$stmt->execute([':uid' => $userId, ':email' => $userEmail]);
$myOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rezervasyonlarım | Notti Blu</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: "Helvetica Neue", Arial, sans-serif; background: #F8F6F3; color: #17233C; -webkit-font-smoothing: antialiased; }
        .container { max-width: 850px; margin: 50px auto; padding: 0 24px; }
        .back-link { display: inline-block; margin-bottom: 25px; color: #687386; text-decoration: none; font-size: 14px; font-weight: 600; }
        .back-link:hover { color: #203A63; }
        h1 { font-family: Georgia, serif; font-size: 32px; margin-bottom: 25px; color: #0A1833; }
        .order-card { background: #FFFFFF; border-radius: 18px; padding: 24px; margin-bottom: 20px; box-shadow: 0 6px 20px rgba(15, 23, 42, 0.05); display: flex; justify-content: space-between; align-items: center; border: 1px solid #EAEBEF; }
        .order-info h3 { font-family: Georgia, serif; font-size: 19px; margin-bottom: 6px; color: #17233C; }
        .order-info p { font-size: 13.5px; color: #718096; line-height: 1.5; }
        .order-price { text-align: right; }
        .price { font-size: 20px; font-weight: 700; color: #203A63; margin-bottom: 6px; }
        .status { font-size: 12px; padding: 5px 12px; border-radius: 20px; font-weight: 700; display: inline-block; }
        .status-Onaylandı { background: #DCFCE7; color: #166534; }
        .status-Beklemede { background: #FEF3C7; color: #92400E; }
        .status-İptal { background: #FEE2E2; color: #991B1B; }
        .empty-state { background: #FFFFFF; padding: 40px; border-radius: 18px; text-align: center; color: #64748B; border: 1px solid #EAEBEF; }
    </style>
</head>
<body>
<div class="container">
    <a href="index.php" class="back-link">← Ana Sayfaya Dön</a>
    <h1>Rezervasyonlarım</h1>

    <?php if (empty($myOrders)): ?>
        <div class="empty-state">
            <p style="font-size: 15px; margin-bottom: 15px;">Henüz kayıtlı bir seyahat rezervasyonunuz bulunmuyor.</p>
            <a href="index.php" style="color: #203A63; font-weight: 600; text-decoration: none;">Rotaları Keşfet →</a>
        </div>
    <?php else: ?>
        <?php foreach ($myOrders as $order): ?>
            <div class="order-card">
                <div class="order-info">
                    <h3><?= htmlspecialchars($order['product_title'] ?? 'Özel Rota') ?></h3>
                    <p>
                        <?= (int)($order['duration'] ?? 1) ?> Günlük Rota • Rezervasyon Tarihi: <?= date('d.m.Y H:i', strtotime($order['created_at'])) ?><br>
                        İletişim: <?= htmlspecialchars($order['fullname']) ?> (<?= htmlspecialchars($order['phone']) ?>)
                    </p>
                </div>
                <div class="order-price">
                    <div class="price">€<?= number_format($order['total_price'], 0, ',', '.') ?></div>
                    <span class="status status-<?= $order['status'] === 'İptal Edildi' ? 'İptal' : $order['status'] ?>">
                        <?= htmlspecialchars($order['status']) ?>
                    </span>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
</body>
</html>