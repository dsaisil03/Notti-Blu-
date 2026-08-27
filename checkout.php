<?php
session_start();
require 'db.php';

// Hata raporlamayı aç
error_reporting(E_ALL);
ini_set('display_errors', 1);

$productId = $_GET['product_id'] ?? $_POST['product_id'] ?? null;

if (!$productId) {
    header("Location: index.php");
    exit;
}

$stmt = $pdo->prepare("SELECT product.*, category.name AS category_name 
                       FROM product 
                       LEFT JOIN category ON product.category_id = category.id 
                       WHERE product.id = ?");
$stmt->execute([$productId]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    die("Seçilen tur rotası bulunamadı.");
}

$user = $_SESSION['user'] ?? null;
$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['fullname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    $userId = isset($user['id']) ? (int)$user['id'] : null;
    $totalPrice = (float)$product['price'];

    if (empty($fullName) || empty($email) || empty($phone)) {
        $error = "Lütfen zorunlu alanları (Ad Soyad, E-posta, Telefon) eksiksiz doldurun.";
    } else {
        try {
            $insertSql = "INSERT INTO `orders` (`user_id`, `product_id`, `fullname`, `email`, `phone`, `total_price`, `notes`, `status`, `created_at`) 
                          VALUES (:user_id, :product_id, :fullname, :email, :phone, :total_price, :notes, 'Onaylandı', NOW())";
            
            $insertStmt = $pdo->prepare($insertSql);
            $insertStmt->execute([
                ':user_id' => $userId,
                ':product_id' => (int)$productId,
                ':fullname' => $fullName,
                ':email' => $email,
                ':phone' => $phone,
                ':total_price' => $totalPrice,
                ':notes' => $notes
            ]);

            $success = true;
        } catch (PDOException $e) {
            $error = "Veritabanı Hatası: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rezervasyon & Ödeme | <?= htmlspecialchars($product['title']) ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: "Helvetica Neue", Arial, sans-serif; background: #F8F6F3; color: #17233C; }
        .container { max-width: 900px; margin: 0 auto; padding: 40px 20px 80px; }
        .back-link { display: inline-block; margin-bottom: 24px; color: #687386; text-decoration: none; font-size: 14px; font-weight: 600; }
        .checkout-grid { display: grid; grid-template-columns: 1.2fr 1fr; gap: 30px; }
        .card { background: #FFFFFF; border-radius: 20px; padding: 35px; box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06); }
        h2 { font-family: Georgia, serif; font-size: 24px; margin-bottom: 20px; color: #17233C; }
        .form-group { margin-bottom: 18px; }
        label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 8px; color: #4A5568; }
        input, textarea { width: 100%; padding: 13px 16px; border: 1.5px solid #E2E8F0; border-radius: 12px; font-size: 14px; outline: none; }
        .order-summary { background: #F8F9FA; border-radius: 16px; padding: 24px; margin-bottom: 24px; }
        .summary-title { font-size: 16px; font-weight: 700; margin-bottom: 6px; }
        .summary-sub { font-size: 13px; color: #718096; margin-bottom: 16px; }
        .price-row { display: flex; justify-content: space-between; align-items: center; padding-top: 14px; border-top: 1px solid #E2E8F0; font-size: 15px; font-weight: 700; }
        .price-val { font-size: 22px; color: #203A63; }
        .btn-submit { width: 100%; background: #203A63; color: #FFFFFF; border: none; padding: 16px; border-radius: 12px; font-size: 15px; font-weight: 600; cursor: pointer; }
        .alert { padding: 14px 18px; border-radius: 12px; font-size: 14px; margin-bottom: 20px; }
        .alert-error { background: #FEE2E2; color: #991B1B; }
        .alert-success { background: #DCFCE7; color: #166534; text-align: center; padding: 30px; }
        @media (max-width: 768px) { .checkout-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

<div class="container">
    <a href="products.php?id=<?= $product['id'] ?>" class="back-link">← Tura Geri Dön</a>

    <?php if ($success): ?>
        <div class="card alert-success">
            <h2 style="color: #166534; margin-bottom: 10px;">Rezervasyonunuz Alındı! ✨</h2>
            <p style="margin-bottom: 20px; line-height: 1.6;">
                <strong><?= htmlspecialchars($product['title']) ?></strong> için rezervasyon kaydınız oluşturuldu.<br>
                Detaylar <strong><?= htmlspecialchars($email) ?></strong> adresinize gönderildi.
            </p>
            <div style="display: flex; gap: 15px; justify-content: center;">
                <a href="index.php" style="background: #203A63; color: white; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-size: 14px; font-weight: 600;">Anasayfaya Dön</a>
                <a href="admin/orders.php" style="background: #E2E8F0; color: #17233C; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-size: 14px; font-weight: 600;">Siparişi Adminde Gör →</a>
            </div>
        </div>
    <?php else: ?>
        <div class="checkout-grid">
            <div class="card">
                <h2>Rezervasyon Bilgileri</h2>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form action="checkout.php?product_id=<?= $product['id'] ?>" method="POST">
                    <input type="hidden" name="product_id" value="<?= $product['id'] ?>">

                    <div class="form-group">
                        <label>Ad Soyad *</label>
                        <input type="text" name="fullname" required value="<?= htmlspecialchars($user['name'] ?? $user['fullname'] ?? '') ?>" placeholder="Örn: Ayşe Yılmaz">
                    </div>

                    <div class="form-group">
                        <label>E-posta Adresi *</label>
                        <input type="email" name="email" required value="<?= htmlspecialchars($user['email'] ?? '') ?>" placeholder="ornek@mail.com">
                    </div>

                    <div class="form-group">
                        <label>Telefon Numarası *</label>
                        <input type="tel" name="phone" required placeholder="+90 5XX XXX XX XX">
                    </div>

                    <div class="form-group">
                        <label>Özel Notlar</label>
                        <textarea name="notes" rows="3" placeholder="Varsa özel istekleriniz..."></textarea>
                    </div>

                    <button type="submit" class="btn-submit">Rezervasyonu Tamamla & Öde</button>
                </form>
            </div>

            <div class="card">
                <h2>Seçilen Rota</h2>
                <div class="order-summary">
                    <div class="summary-title"><?= htmlspecialchars($product['title']) ?></div>
                    <div class="summary-sub"><?= (int)($product['duration'] ?? 1) ?> Günlük Deneyim</div>
                    <div class="price-row">
                        <span>Toplam Tutar:</span>
                        <span class="price-val">€<?= number_format($product['price'], 0, ',', '.') ?></span>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

</body>
</html>