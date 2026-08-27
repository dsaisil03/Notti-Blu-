<?php
session_start();
require 'db.php';

$id = $_GET['id'] ?? null;

if (!$id) {
    die("Ürün bulunamadı.");
}

$sql = "SELECT product.*, category.name AS category_name
        FROM product
        LEFT JOIN category ON product.category_id = category.id
        WHERE product.id = :id";

$stmt = $pdo->prepare($sql);
$stmt->execute(['id' => $id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    die("Ürün bulunamadı.");
}

// Veritabanındaki gerçek metni bölümlere ayırma
$rawDesc = $product['description'] ?? '';
$intro = '';
$stayAndTransport = '';
$included = '';
$excluded = '';

if (strpos($rawDesc, 'Konaklama:') !== false) {
    $parts = explode('Konaklama:', $rawDesc);
    $intro = trim($parts[0]);
    
    $subParts = explode('Fiyata Dahil Olanlar:', $parts[1] ?? '');
    $stayAndTransport = 'Konaklama: ' . trim($subParts[0]);

    if (isset($subParts[1])) {
        $incParts = explode('Fiyata Dahil Olmayanlar:', $subParts[1]);
        $included = trim($incParts[0]);
        $excluded = trim($incParts[1] ?? '');
    }
} else {
    $intro = $rawDesc;
}

$user = $_SESSION['user'] ?? null;
$userId = $user['id'] ?? $_SESSION['user_id'] ?? null;
$isFav = false;

if ($userId && isset($product['id'])) {
    $favCheck = $pdo->prepare("SELECT id FROM favorites WHERE user_id = ? AND product_id = ?");
    $favCheck->execute([$userId, $product['id']]);
    $isFav = (bool)$favCheck->fetch();
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($product['title']) ?> | Notti Blu</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: "Helvetica Neue", Arial, sans-serif;
            background: #F8F6F3;
            color: #17233C;
            -webkit-font-smoothing: antialiased;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
            padding: 50px 24px 80px;
        }

        .back {
            display: inline-block;
            margin-bottom: 25px;
            color: #687386;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
        }

        .back:hover {
            color: #203A63;
        }

        .tour-detail {
            background: #FFFFFF;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.07);
        }

        .image-area {
            position: relative;
            height: 400px;
        }

        .image-area img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .image-area::after {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(
                to bottom,
                rgba(10, 24, 51, 0.1),
                rgba(10, 24, 51, 0.75)
            );
        }

        .image-overlay {
            position: absolute;
            z-index: 2;
            left: 35px;
            right: 35px;
            bottom: 35px;
            color: white;
        }

        .category {
            font-size: 11.5px;
            text-transform: uppercase;
            letter-spacing: 1.8px;
            color: rgba(255, 255, 255, 0.85);
            font-weight: 600;
            margin-bottom: 10px;
        }

        .image-overlay h1 {
            font-family: Georgia, "Times New Roman", serif;
            font-size: 38px;
            line-height: 1.2;
            font-weight: 600;
            color: white;
        }

        .tour-content {
            padding: 35px 40px 40px;
        }

        .meta-tags {
            display: flex;
            gap: 10px;
            margin-bottom: 25px;
        }

        .badge {
            background: #F1F5F8;
            color: #203A63;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12.5px;
            font-weight: 600;
            text-transform: capitalize;
        }

        .intro-text {
            font-size: 15.5px;
            line-height: 1.8;
            color: #4A5568;
            margin-bottom: 30px;
        }

        /* ---------- AKORDEON ---------- */
        .accordion-wrapper {
            margin-bottom: 35px;
            border-top: 1px solid #EAEBEF;
        }

        .accordion-item {
            border-bottom: 1px solid #EAEBEF;
        }

        .accordion-btn {
            width: 100%;
            background: none;
            border: none;
            padding: 16px 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-family: inherit;
            font-size: 14px;
            font-weight: 700;
            color: #17233C;
            cursor: pointer;
            text-align: left;
        }

        .accordion-btn:hover {
            color: #203A63;
        }

        .accordion-icon {
            font-size: 18px;
            font-weight: 400;
            transition: transform 0.25s ease;
            color: #687386;
        }

        .accordion-item.active .accordion-icon {
            transform: rotate(45deg);
        }

        .accordion-body {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s cubic-bezier(0, 1, 0, 1);
            font-size: 14px;
            line-height: 1.8;
            color: #5A6578;
            white-space: pre-line;
        }

        .accordion-item.active .accordion-body {
            max-height: 600px;
            padding-bottom: 18px;
            transition: max-height 0.3s ease-in-out;
        }

        /* ---------- FİYAT & BUTON ---------- */
        .booking-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 25px;
            border-top: 1px solid #E7E5E4;
        }

        .price-area {
            display: flex;
            flex-direction: column;
        }

        .price-label {
            font-size: 11.5px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #8892A2;
            font-weight: 600;
        }

        .price {
            font-size: 26px;
            font-weight: 700;
            color: #203A63;
        }

        .btn-reserve {
            background: #203A63;
            color: #FFFFFF;
            text-decoration: none;
            padding: 14px 30px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 600;
            letter-spacing: 0.2px;
            transition: background 0.25s ease;
        }

        .btn-reserve:hover {
            background: #35527E;
        }

        @media (max-width: 700px) {
            .container { padding: 30px 18px; }
            .image-area { height: 280px; }
            .image-overlay h1 { font-size: 28px; }
            .tour-content { padding: 25px 20px; }
            .booking-bar { flex-direction: column; gap: 16px; align-items: stretch; text-align: center; }
        }

        .fav-detail-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 18px;
    border-radius: 12px;
    border: 1px solid #EAE6DF;
    background: #FFFFFF;
    color: #64748B;
    font-size: 13.5px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
}

.fav-detail-btn:hover {
    border-color: #C5A880;
    color: #17233C;
}

.fav-detail-btn.active {
    color: #9A7B56;
    background: #FAF7F2;
    border-color: #E2D3BE;
}
    </style>
</head>
<body>

<div class="container">
    <a href="index.php" class="back">
        ← Tüm rotalara dön
    </a>

    <div class="tour-detail">
        <div class="image-area">
            <img src="images/<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['title']) ?>">

            <div class="image-overlay">
                <div class="category">
                    <?= htmlspecialchars($product['category_name']) ?>
                </div>
                <h1><?= htmlspecialchars($product['title']) ?></h1>
            </div>
        </div>

        <div class="tour-content">
            <div class="meta-tags">
                <?php if (!empty($product['duration'])): ?>
                    <span class="badge"><?= (int)$product['duration'] ?> Gün</span>
                <?php endif; ?>
                <?php if (!empty($product['visa_status'])): ?>
                    <span class="badge"><?= htmlspecialchars($product['visa_status']) ?></span>
                <?php endif; ?>
            </div>

            <!-- Rotanın Gerçek Tanıtım Metni -->
            <div class="intro-text">
                <?= nl2br(htmlspecialchars($intro)) ?>
            </div>

            <!-- Akordeon Bölümleri -->
            <?php if (!empty($stayAndTransport) || !empty($included) || !empty($excluded)): ?>
                <div class="accordion-wrapper">
                    
                    <?php if (!empty($stayAndTransport)): ?>
                        <div class="accordion-item active">
                            <button class="accordion-btn" onclick="toggleAccordion(this)">
                                <span>Konaklama & Ulaşım Bilgileri</span>
                                <span class="accordion-icon">+</span>
                            </button>
                            <div class="accordion-body">
                                <?= htmlspecialchars($stayAndTransport) ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($included)): ?>
                        <div class="accordion-item">
                            <button class="accordion-btn" onclick="toggleAccordion(this)">
                                <span>Fiyata Dahil Olan Hizmetler</span>
                                <span class="accordion-icon">+</span>
                            </button>
                            <div class="accordion-body">
                                <?= htmlspecialchars($included) ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($excluded)): ?>
                        <div class="accordion-item">
                            <button class="accordion-btn" onclick="toggleAccordion(this)">
                                <span>Fiyata Dahil Olmayanlar</span>
                                <span class="accordion-icon">+</span>
                            </button>
                            <div class="accordion-body">
                                <?= htmlspecialchars($excluded) ?>
                            </div>
                        </div>
                    <?php endif; ?>

                </div>
            <?php endif; ?>

            <div class="booking-bar">
                <div class="price-area">
                    <span class="price-label">Kişi Başı</span>
                    <span class="price">€<?= number_format($product['price'], 0, ',', '.') ?></span>
                </div>

                <a href="checkout.php?product_id=<?= $product['id'] ?>" class="btn-reserve">
                    Rezervasyon Yap & Öde →

                </a>

                <button type="button" class="fav-detail-btn <?= $isFav ? 'active' : '' ?>" onclick="toggleFav(<?= $product['id'] ?>, this)">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="<?= $isFav ? 'currentColor' : 'none' ?>" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
        </svg>
        <span><?= $isFav ? 'Kaydedildi' : 'Wishlist\'e Ekle' ?></span>
    </button>
            </div>
        </div>
    </div>
</div>

<script>
    function toggleAccordion(btn) {
        const item = btn.parentElement;
        item.classList.toggle('active');
    }
    async function toggleFav(productId, btn) {
    const formData = new FormData();
    formData.append('product_id', productId);

    try {
        const res = await fetch('toggle-favorite.php', { method: 'POST', body: formData });
        const data = await res.json();
        const svg = btn.querySelector('svg');
        const span = btn.querySelector('span');

        if (data.status === 'added') {
            btn.classList.add('active');
            if (svg) svg.setAttribute('fill', 'currentColor');
            if (span) span.innerText = 'Kaydedildi';
        } else if (data.status === 'removed') {
            btn.classList.remove('active');
            if (svg) svg.setAttribute('fill', 'none');
            if (span) span.innerText = "Wishlist'e Ekle";
        } else {
            alert(data.message || 'Lütfen önce giriş yapın.');
        }
    } catch (e) {
        console.error("Favori işlemi hatası:", e);
    }
}
</script>

</body>
</html>