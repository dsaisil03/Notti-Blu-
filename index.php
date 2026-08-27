<?php
session_start();
require 'db.php';

$selectedCategory = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$selectedVisa     = $_GET['visa_status'] ?? '';
$selectedDuration = $_GET['duration'] ?? '';
$selectedPrice    = $_GET['price'] ?? '';

/* ---------- PAGINATION ---------- */
$perPage = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) {
    $page = 1;
}

$categories = $pdo->query("SELECT * FROM category ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

/* ---------- AYIN FAVORİSİ (FEATURED) ---------- */
$featuredRoute = null;
try {
    $featStmt = $pdo->query("
        SELECT product.*, category.name AS category_name 
        FROM product 
        LEFT JOIN category ON product.category_id = category.id 
        WHERE product.is_featured = 1 
        LIMIT 1
    ");
    $featuredRoute = $featStmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

/* ---------- KULLANICININ FAVORİ ID'LERİ ---------- */
$user = $_SESSION['user'] ?? null;
$userId = $user['id'] ?? $_SESSION['user_id'] ?? null;
$userFavIds = [];

if ($userId) {
    try {
        $favStmt = $pdo->prepare("SELECT product_id FROM favorites WHERE user_id = ?");
        $favStmt->execute([$userId]);
        $userFavIds = $favStmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (Exception $e) {}
}

/* ---------- FİLTRELER ---------- */
$where = [];
$params = [];

if ($selectedCategory > 0) {
    $where[] = "product.category_id = ?";
    $params[] = $selectedCategory;
}

if ($selectedVisa !== '' && $selectedVisa !== 'Tümü') {
    $where[] = "product.visa_status = ?";
    $params[] = $selectedVisa;
}

if ($selectedDuration !== '' && $selectedDuration !== 'Tümü') {
    if ($selectedDuration === '2-3 gün') {
        $where[] = "product.duration = ?";
        $params[] = 3;
    } elseif ($selectedDuration === '4-5 gün') {
        $where[] = "product.duration = ?";
        $params[] = 5;
    } elseif ($selectedDuration === '1 hafta') {
        $where[] = "product.duration = ?";
        $params[] = 7;
    } elseif ($selectedDuration === '1 haftadan uzun') {
        $where[] = "product.duration = ?";
        $params[] = 10;
    }
}

if ($selectedPrice === '0-500') {
    $where[] = "product.price <= 500";
} elseif ($selectedPrice === '500-1000') {
    $where[] = "product.price > 500 AND product.price <= 1000";
} elseif ($selectedPrice === '1000-1500') {
    $where[] = "product.price > 1000 AND product.price <= 1500";
} elseif ($selectedPrice === '1500+') {
    $where[] = "product.price > 1500";
}

/* ---------- TOPLAM ÜRÜN SAYISI ---------- */
$countSql = "SELECT COUNT(*) FROM product";
if (!empty($where)) {
    $countSql .= " WHERE " . implode(" AND ", $where);
}

$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalProducts = $countStmt->fetchColumn();

$totalPages = ceil($totalProducts / $perPage);
if ($page > $totalPages && $totalPages > 0) {
    $page = $totalPages;
}

$offset = ($page - 1) * $perPage;

/* ---------- ÜRÜNLER ---------- */
$sql = "
    SELECT product.*, category.name AS category_name
    FROM product
    LEFT JOIN category ON product.category_id = category.id
";

if (!empty($where)) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

$sql .= " ORDER BY product.id LIMIT " . (int)$perPage . " OFFSET " . (int)$offset;

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

$queryParams = $_GET;
unset($queryParams['page']);
$queryString = http_build_query($queryParams);
$paginationPrefix = '?' . ($queryString ? $queryString . '&' : '') . 'page=';

$userName = $user['fullname'] ?? $user['name'] ?? $_SESSION['user_name'] ?? null;
$userRole = $user['role'] ?? $_SESSION['role'] ?? null;
$isLoggedIn = !empty($userName) || isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notti Blu | Butik Rota Koleksiyonu</title>

    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: "Helvetica Neue", Arial, sans-serif;
            background: #F8F6F3;
            color: #17233C;
            -webkit-font-smoothing: antialiased;
        }

        .container {
            max-width: 1100px;
            margin: auto;
            padding: 30px 35px 70px;
        }

        /* Topbar */
        .topbar {
            max-width: 1100px;
            margin: 0 auto;
            display: flex;
            justify-content: flex-end;
            align-items: center;
            padding: 24px 35px 0;
            gap: 15px;
        }

        .user-nav-wrapper {
            position: relative;
            display: inline-flex;
            align-items: center;
        }

        .user-pill-btn {
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            padding: 8px 16px;
            border-radius: 30px;
            font-size: 13.5px;
            font-weight: 600;
            color: #17233C;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 9px;
            box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
            transition: all 0.2s ease;
        }

        .user-avatar-dot {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: #203A63;
            color: #FFFFFF;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: 700;
        }

        .user-dropdown-card {
            opacity: 0;
            visibility: hidden;
            transform: translateY(8px);
            transition: all 0.2s ease;
            position: absolute;
            right: 0;
            top: 100%;
            margin-top: 8px;
            background: #FFFFFF;
            min-width: 210px;
            border-radius: 14px;
            box-shadow: 0 12px 30px rgba(15, 23, 42, 0.12);
            border: 1px solid #EAEBEF;
            padding: 8px 0;
            z-index: 5000;
        }

        .user-nav-wrapper:hover .user-dropdown-card {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .user-dropdown-card a {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 18px;
            color: #4A5568;
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
            transition: background 0.15s ease;
        }

        .user-dropdown-card a:hover {
            background: #F8F9FB;
            color: #203A63;
        }

        .user-dropdown-card hr {
            border: none;
            border-top: 1px solid #F1F3F5;
            margin: 6px 0;
        }

        .btn-login {
            background: #203A63;
            color: #FFFFFF;
            padding: 8px 18px;
            border-radius: 20px;
            text-decoration: none;
            font-size: 13.5px;
            font-weight: 600;
        }

        .btn-admin-link {
            color: #203A63;
            text-decoration: none;
            font-size: 13.5px;
            font-weight: 600;
            padding: 8px 12px;
        }

        /* Hero */
        .hero {
            text-align: center;
            margin-bottom: 45px;
        }

        .hero h1 {
            font-family: Georgia, "Times New Roman", serif;
            font-size: 72px;
            font-weight: 500;
            letter-spacing: -2px;
            color: #0A1833;
            margin-bottom: 12px;
        }

        .hero p {
            font-size: 15px;
            font-style: italic;
            color: #6B7280;
        }

        /* Ayın Favorisi Banner'ı */
        .featured-banner {
            background: #101D38;
            border-radius: 20px;
            overflow: hidden;
            display: grid;
            grid-template-columns: 1.2fr 1fr;
            margin-bottom: 45px;
            color: #FFFFFF;
            text-decoration: none;
            box-shadow: 0 12px 35px rgba(15, 23, 42, 0.12);
            transition: transform 0.3s ease;
        }

        .featured-banner:hover {
            transform: translateY(-4px);
        }

        .featured-img {
            width: 100%;
            height: 100%;
            min-height: 280px;
            object-fit: cover;
        }

        .featured-content {
            padding: 35px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .featured-badge {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: #C5A880;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .featured-title {
            font-family: Georgia, serif;
            font-size: 28px;
            line-height: 1.25;
            margin-bottom: 12px;
            color: #FFFFFF;
        }

        .featured-price {
            font-size: 22px;
            font-weight: 700;
            color: #FFFFFF;
            margin-top: 15px;
        }

        /* Filtre Butonu */
        .filter-bar {
            margin-bottom: 35px;
            display: flex;
            justify-content: flex-start;
        }

        .filter-button {
            border: 1.5px solid #203A63;
            background: #F8F6F3;
            color: #203A63;
            padding: 11px 22px;
            border-radius: 12px;
            font-family: inherit;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.25s ease;
        }

        .filter-button:hover {
            background: #203A63;
            color: #FFFFFF;
        }

        /* Kartlar & Wishlist Kalbi */
        .tour-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 45px 30px;
        }

        .tour-card-wrap {
            position: relative;
        }

        .tour-card {
            background: #FFFFFF;
            border-radius: 18px;
            overflow: hidden;
            text-decoration: none;
            color: inherit;
            display: block;
            box-shadow: 0 8px 25px rgba(15, 23, 42, 0.08);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .tour-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 15px 35px rgba(15, 23, 42, 0.14);
        }

        .tour-image {
            width: 100%;
            height: 270px;
            object-fit: cover;
            display: block;
        }

     .fav-btn {
    position: absolute;
    top: 16px;
    right: 16px;
    width: 38px;
    height: 38px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.92);
    backdrop-filter: blur(4px);
    border: 1px solid rgba(234, 230, 223, 0.8);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    box-shadow: 0 4px 12px rgba(15, 23, 42, 0.06);
    transition: all 0.2s ease;
    z-index: 10;
    color: #8C96A5;
}

.fav-btn:hover {
    transform: scale(1.08);
    background: #FFFFFF;
    color: #101D38;
}

/* Seçildiğinde şampanya/altın tonu */
.fav-btn.active {
    color: #C5A880;
    border-color: #E2D3BE;
}

        .tour-content {
            padding: 24px 25px 27px;
        }

        .category {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1.8px;
            color: #7B8494;
            font-weight: 600;
            margin-bottom: 9px;
        }

        .tour-title {
            font-family: Georgia, serif;
            font-size: 25px;
            font-weight: 600;
            line-height: 1.25;
            color: #101D38;
            margin-bottom: 15px;
        }

        .tour-description {
            font-size: 14px;
            color: #687386;
            line-height: 1.6;
            margin-bottom: 20px;
        }

        .tour-bottom {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top: 1px solid #E7E5E4;
            padding-top: 17px;
        }

        .price {
            font-size: 20px;
            font-weight: 700;
            color: #203A63;
        }

        .view {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1.3px;
            color: #7B8494;
        }

        /* Filtre Paneli */
        .filter-panel {
            position: fixed;
            top: 0;
            left: -400px;
            width: 370px;
            height: 100vh;
            background: #FFFFFF;
            z-index: 4000;
            padding: 35px;
            box-shadow: 10px 0 35px rgba(15, 23, 42, 0.12);
            transition: left 0.35s ease;
            overflow-y: auto;
        }

        .filter-panel.active { left: 0; }
        .filter-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 35px; }
        .filter-header h2 { font-family: Georgia, serif; font-size: 28px; color: #0A1833; font-weight: 500; }
        .close-filter { border: none; background: #F3F5F8; color: #203A63; width: 38px; height: 38px; border-radius: 50%; font-size: 20px; cursor: pointer; }
        .filter-group { margin-bottom: 28px; }
        .filter-group label { display: block; margin-bottom: 10px; color: #203A63; font-size: 13px; font-weight: 700; }
        .filter-group select { width: 100%; padding: 13px; border: 1px solid #D8E0EA; border-radius: 12px; background: #FBFCFD; color: #203A63; font-family: inherit; font-size: 14px; }
        .filter-apply { width: 100%; border: none; background: #203A63; color: white; padding: 14px; border-radius: 12px; font-family: inherit; font-size: 14px; font-weight: 600; cursor: pointer; margin-top: 10px; }
        .filter-overlay { position: fixed; inset: 0; background: rgba(10, 24, 51, 0.25); z-index: 3000; opacity: 0; visibility: hidden; transition: 0.3s ease; }
        .filter-overlay.active { opacity: 1; visibility: visible; }

        /* Pagination */
        .pagination { display: flex; justify-content: center; align-items: center; gap: 8px; margin-top: 55px; }
        .pagination a { display: flex; align-items: center; justify-content: center; min-width: 40px; height: 40px; padding: 0 13px; text-decoration: none; color: #203A63; background: #FFFFFF; border: 1px solid #DCE3EA; border-radius: 12px; font-size: 13px; font-weight: 600; }
        .pagination a.active { background: #203A63; color: #FFFFFF; border-color: #203A63; }
        .pagination a.disabled { opacity: 0.4; pointer-events: none; }

        /* AI Chatbot */
        /* AI CHATBOT - Ferah & Geniş Tasarım */
#ai-chat-widget {
    position: fixed;
    bottom: 30px;
    right: 30px;
    z-index: 9999;
    font-family: inherit;
}

#ai-toggle-btn {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    background: #101D38;
    color: #FFFFFF;
    border: none;
    cursor: pointer;
    box-shadow: 0 6px 20px rgba(16, 29, 56, 0.25);
    display: flex;
    align-items: center;
    justify-content: center;
    transition: transform 0.2s ease;
}

#ai-toggle-btn:hover {
    transform: scale(1.06);
}

#ai-chat-box {
    position: absolute;
    bottom: 70px;
    right: 0;
    width: 390px; /* Genişletildi */
    height: 540px; /* Uzatıldı */
    background: #FFFFFF;
    border-radius: 20px;
    box-shadow: 0 16px 40px rgba(15, 23, 42, 0.16);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    border: 1px solid #EAE6DF;
}

.ai-header {
    background: #101D38;
    color: #FFFFFF;
    padding: 16px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-family: Georgia, serif;
    font-size: 15px;
    letter-spacing: 0.3px;
}

.ai-close-btn {
    background: none;
    border: none;
    color: #A0AEC0;
    font-size: 22px;
    cursor: pointer;
    line-height: 1;
}

.ai-close-btn:hover { color: #FFFFFF; }

#ai-messages {
    flex: 1;
    padding: 20px;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: 12px;
    background: #F8F6F3;
}

.ai-msg {
    max-width: 85%;
    padding: 11px 15px;
    border-radius: 14px;
    font-size: 13.5px;
    line-height: 1.55;
    word-break: break-word;
}

.ai-msg.bot {
    align-self: flex-start;
    background: #FFFFFF;
    color: #17233C;
    border: 1px solid #EAE6DF;
    border-bottom-left-radius: 4px;
}

.ai-msg.user {
    align-self: flex-end;
    background: #101D38;
    color: #FFFFFF;
    border-bottom-right-radius: 4px;
}

.ai-input-area {
    padding: 14px;
    background: #FFFFFF;
    border-top: 1px solid #EAE6DF;
    display: flex;
    gap: 10px;
}

#ai-input {
    flex: 1;
    border: 1px solid #D8D2C7;
    border-radius: 10px;
    padding: 10px 14px;
    font-size: 13.5px;
    outline: none;
    font-family: inherit;
    background: #FAF9F6;
}

#ai-input:focus {
    border-color: #101D38;
    background: #FFFFFF;
}

#ai-send-btn {
    background: #101D38;
    color: #FFFFFF;
    border: none;
    border-radius: 10px;
    padding: 0 16px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
}
        @media (max-width: 768px) {
            .container { padding: 30px 20px; }
            .hero h1 { font-size: 48px; }
            .tour-grid { grid-template-columns: 1fr; }
            .featured-banner { grid-template-columns: 1fr; }
            .filter-panel { width: 100%; left: -100%; }
        }
    </style>
</head>
<body>

<div class="topbar">
    <?php if ($isLoggedIn): 
        $initial = mb_substr($userName ?? 'M', 0, 1, 'UTF-8');
    ?>
        <div class="user-nav-wrapper">
            <button class="user-pill-btn" type="button">
                <span class="user-avatar-dot"><?= strtoupper($initial) ?></span>
                <span><?= htmlspecialchars($userName ?? 'Hesabım') ?></span>
                <span style="font-size: 10px; color: #8892A2;">▾</span>
            </button>

            <div class="user-dropdown-card">
    <a href="favorites.php">Favoriler</a>
    <a href="my-orders.php">Rezervasyonlarım</a>
    <hr>
    <a href="admin/index.php">Yönetici Paneli</a>
    <a href="admin/orders.php">Sipariş Yönetimi</a>
    <hr>
    <a href="logout.php" style="color: #C53030; font-weight: 600;">Çıkış Yap</a>
</div>
        </div>
    <?php else: ?>
        <a href="login.php" class="btn-login">Giriş Yap</a>
        <a href="admin-login.php" class="btn-admin-link">Yönetici Girişi</a>
    <?php endif; ?>
</div>

<div class="container">
    <section class="hero">
        <h1>Notti Blu</h1>
        <p>Collecting sunsets, chasing blue nights.</p>
    </section>

    <!-- AYIN FAVORİSİ VİTRİNİ -->
    <?php if ($featuredRoute && $page === 1 && $selectedCategory === 0 && empty($selectedVisa)): ?>
        <a href="products.php?id=<?= $featuredRoute['id'] ?>" class="featured-banner">
            <div class="featured-content">
                <div class="featured-badge">★ Ayın Kürasyon Favorisi</div>
                <div class="featured-title"><?= htmlspecialchars($featuredRoute['title']) ?></div>
                <p style="font-size: 13.5px; color: #CBD5E1; line-height: 1.6;">
                    <?= htmlspecialchars(mb_substr(explode('Konaklama:', $featuredRoute['description'])[0], 0, 140)) ?>...
                </p>
                <div class="featured-price">€<?= number_format($featuredRoute['price'], 0, ',', '.') ?></div>
            </div>
            <img src="images/<?= htmlspecialchars($featuredRoute['image']) ?>" alt="" class="featured-img">
        </a>
    <?php endif; ?>

    <div class="filter-bar">
        <button type="button" class="filter-button" onclick="openFilters()">
            Filtrele
        </button>
    </div>

    <!-- FİLTRE PANELİ -->
    <div class="filter-overlay" id="filterOverlay" onclick="closeFilters()"></div>
    <div class="filter-panel" id="filterPanel">
        <div class="filter-header">
            <h2>Rotanı Özelleştir</h2>
            <button class="close-filter" onclick="closeFilters()">×</button>
        </div>

        <div class="filter-group">
            <label>Kategori</label>
            <select id="filterCategory" onchange="updateFilterAvailability()">
                <option value="0">Tüm rotalar</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?= $category['id'] ?>" <?= $selectedCategory == $category['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($category['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="filter-group">
            <label>Vize Durumu</label>
            <select id="filterVisa" onchange="updateFilterAvailability()">
                <option value="">Tümü</option>
                <option value="Vizesiz" <?= $selectedVisa === 'Vizesiz' ? 'selected' : '' ?>>Vizesiz</option>
                <option value="Vizeli" <?= $selectedVisa === 'Vizeli' ? 'selected' : '' ?>>Vizeli</option>
            </select>
        </div>

        <div class="filter-group">
            <label>Seyahat Süresi</label>
            <select id="filterDuration" onchange="updateFilterAvailability()">
                <option value="">Tümü</option>
                <option value="2-3 gün" <?= $selectedDuration === '2-3 gün' ? 'selected' : '' ?>>2-3 gün</option>
                <option value="4-5 gün" <?= $selectedDuration === '4-5 gün' ? 'selected' : '' ?>>4-5 gün</option>
                <option value="1 hafta" <?= $selectedDuration === '1 hafta' ? 'selected' : '' ?>>1 hafta</option>
                <option value="1 haftadan uzun" <?= $selectedDuration === '1 haftadan uzun' ? 'selected' : '' ?>>1 haftadan uzun</option>
            </select>
        </div>

        <div class="filter-group">
            <label>Fiyat</label>
            <select id="filterPrice">
                <option value="">Tüm fiyatlar</option>
                <option value="0-500" <?= $selectedPrice === '0-500' ? 'selected' : '' ?>>€0 – €500</option>
                <option value="500-1000" <?= $selectedPrice === '500-1000' ? 'selected' : '' ?>>€500 – €1000</option>
                <option value="1000-1500" <?= $selectedPrice === '1000-1500' ? 'selected' : '' ?>>€1000 – €1500</option>
                <option value="1500+" <?= $selectedPrice === '1500+' ? 'selected' : '' ?>>€1500+</option>
            </select>
        </div>

        <button type="button" class="filter-apply" onclick="applyFilters()">
            Rotaları Göster
        </button>
    </div>

    <!-- KARTLAR -->
    <section class="tour-grid">
        <?php foreach ($products as $product): ?>
            <?php 
                $cardDesc = $product['description'] ?? '';
                if (strpos($cardDesc, 'Konaklama:') !== false) {
                    $cardDesc = trim(explode('Konaklama:', $cardDesc)[0]);
                }
                $isFav = in_array($product['id'], $userFavIds);
            ?>
            <div class="tour-card-wrap">
                <button type="button" class="fav-btn <?= $isFav ? 'active' : '' ?>" onclick="toggleFav(<?= $product['id'] ?>, this)">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="<?= $isFav ? 'currentColor' : 'none' ?>" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
        <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
    </svg>
</button>

                <a class="tour-card" href="products.php?id=<?= $product['id'] ?>">
                    <?php if (!empty($product['image'])): ?>
                        <img class="tour-image" src="images/<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['title']) ?>">
                    <?php endif; ?>

                    <div class="tour-content">
                        <div class="category">
                            <?= htmlspecialchars($product['category_name']) ?>
                        </div>

                        <div class="tour-title">
                            <?= htmlspecialchars($product['title']) ?>
                        </div>

                        <?php if (!empty($cardDesc)): ?>
                            <div class="tour-description">
                                <?= htmlspecialchars($cardDesc) ?>
                            </div>
                        <?php endif; ?>

                        <div class="tour-bottom">
                            <div class="price">
                                €<?= number_format($product['price'], 0, ',', '.') ?>
                            </div>

                            <div class="view">
                                Rotayı keşfet →
                            </div>
                        </div>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    </section>

    <!-- SAYFALAMA -->
    <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="<?= $paginationPrefix . ($page - 1) ?>">←</a>
            <?php else: ?>
                <a class="disabled">←</a>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="<?= $paginationPrefix . $i ?>" class="<?= $i == $page ? 'active' : '' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>

            <?php if ($page < $totalPages): ?>
                <a href="<?= $paginationPrefix . ($page + 1) ?>">→</a>
            <?php else: ?>
                <a class="disabled">→</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<!-- AI CHATBOT -->
<div id="ai-chat-widget">
    <button id="ai-toggle-btn" onclick="toggleAIChat()">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
        </svg>
    </button>

    <div id="ai-chat-box" style="display: none;">
        <div class="ai-header">
            <span>Notti Blu Asistan</span>
            <button class="ai-close-btn" onclick="toggleAIChat()">&times;</button>
        </div>

        <div id="ai-messages">
            <div class="ai-msg bot">Koleksiyonda aradığın özel bir rota var mı, yoksa eşlik etmemi ister misin?</div>
        </div>

        <div class="ai-input-area">
            <input type="text" id="ai-input" placeholder="Bir rota sorun..." onkeypress="handleAIKey(event)">
            <button id="ai-send-btn" onclick="sendAIMessage()">Gönder</button>
        </div>
    </div>
</div>

<script>
async function toggleFav(productId, btn) {
    const formData = new FormData();
    formData.append('product_id', productId);

    try {
        const res = await fetch('toggle-favorite.php', { method: 'POST', body: formData });
        const data = await res.json();
        const svg = btn.querySelector('svg');

        if (data.status === 'added') {
            btn.classList.add('active');
            if (svg) svg.setAttribute('fill', 'currentColor');
        } else if (data.status === 'removed') {
            btn.classList.remove('active');
            if (svg) svg.setAttribute('fill', 'none');
        } else {
            alert(data.message || 'Lütfen önce giriş yapın.');
        }
    } catch (e) {
        console.error("Favori işlemi hatası:", e);
    }
}

async function updateFilterAvailability() {
    const category = document.getElementById("filterCategory").value;
    const visa = document.getElementById("filterVisa").value;
    const duration = document.getElementById("filterDuration").value;

    const params = new URLSearchParams();
    if (category !== "0") params.append("category", category);
    if (visa !== "" && visa !== "Tümü") params.append("visa_status", visa);
    if (duration !== "" && duration !== "Tümü") params.append("duration", duration);

    try {
        const res = await fetch("check-filter-options.php?" + params.toString());
        const data = await res.json();

        const priceSelect = document.getElementById("filterPrice");
        Array.from(priceSelect.options).forEach(opt => {
            if (!opt.value) return;
            const originalText = opt.text.replace(" (Mevcut Değil)", "");
            if (!data.prices[opt.value]) {
                opt.disabled = true;
                opt.text = originalText + " (Mevcut Değil)";
            } else {
                opt.disabled = false;
                opt.text = originalText;
            }
        });

        const visaSelect = document.getElementById("filterVisa");
        Array.from(visaSelect.options).forEach(opt => {
            if (!opt.value) return;
            const originalText = opt.text.replace(" (Mevcut Değil)", "");
            if (!data.visas[opt.value]) {
                opt.disabled = true;
                opt.text = originalText + " (Mevcut Değil)";
            } else {
                opt.disabled = false;
                opt.text = originalText;
            }
        });
    } catch (e) {}
}

function openFilters() {
    document.getElementById("filterPanel").classList.add("active");
    document.getElementById("filterOverlay").classList.add("active");
    updateFilterAvailability();
}

function closeFilters() {
    document.getElementById("filterPanel").classList.remove("active");
    document.getElementById("filterOverlay").classList.remove("active");
}

function applyFilters() {
    const category = document.getElementById("filterCategory").value;
    const visa = document.getElementById("filterVisa").value;
    const duration = document.getElementById("filterDuration").value;
    const price = document.getElementById("filterPrice").value;

    let params = [];
    if (category !== "0") params.push("category=" + encodeURIComponent(category));
    if (visa !== "" && visa !== "Tümü") params.append ? params.push("visa_status=" + encodeURIComponent(visa)) : null;
    if (duration !== "" && duration !== "Tümü") params.push("duration=" + encodeURIComponent(duration));
    if (price !== "" && price !== "Tüm fiyatlar") params.push("price=" + encodeURIComponent(price));

    let url = "index.php";
    if (params.length > 0) url += "?" + params.join("&");
    window.location.href = url;
}

function toggleAIChat() {
    const box = document.getElementById("ai-chat-box");
    box.style.display = (box.style.display === "none" || box.style.display === "") ? "flex" : "none";
    if (box.style.display === "flex") document.getElementById("ai-input").focus();
}

function handleAIKey(e) { if (e.key === "Enter") sendAIMessage(); }

async function sendAIMessage() {
    const input = document.getElementById("ai-input");
    const msgContainer = document.getElementById("ai-messages");
    const text = input.value.trim();
    if (!text) return;

    const userDiv = document.createElement("div");
    userDiv.className = "ai-msg user";
    userDiv.textContent = text;
    msgContainer.appendChild(userDiv);
    input.value = "";
    msgContainer.scrollTop = msgContainer.scrollHeight;

    const loadingDiv = document.createElement("div");
    loadingDiv.className = "ai-msg bot";
    loadingDiv.innerHTML = "<i>Seçenekler hazırlanıyor...</i>";
    msgContainer.appendChild(loadingDiv);
    msgContainer.scrollTop = msgContainer.scrollHeight;

    try {
        const res = await fetch("ai-route.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ message: text })
        });
        const data = await res.json();
        loadingDiv.innerHTML = data.reply || "Bir yanıt oluşturulamadı.";
    } catch (err) {
        loadingDiv.innerText = "Bağlantı hatası oluştu.";
    }
    msgContainer.scrollTop = msgContainer.scrollHeight;
}
</script>

</body>
</html>