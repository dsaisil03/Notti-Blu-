<?php
session_start();
require '../db.php';

// Güvenlik & Basit Admin Kontrolü
$user = $_SESSION['user'] ?? null;
$role = $user['role'] ?? $_SESSION['role'] ?? null;

// Durum Güncelleme İşlemi
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_order_status') {
    $orderId = (int)$_POST['order_id'];
    $newStatus = $_POST['status'];
    if (in_array($newStatus, ['Beklemede', 'Onaylandı', 'İptal Edildi'])) {
        $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->execute([$newStatus, $orderId]);
        $message = "Sipariş #{$orderId} durumu güncellendi.";
    }
}

// Ayın Favorisi (Featured) Değiştirme
if (isset($_GET['toggle_featured'])) {
    $featId = (int)$_GET['toggle_featured'];
    $current = (int)$_GET['current'];
    $newFeat = $current ? 0 : 1;
    $stmt = $pdo->prepare("UPDATE product SET is_featured = ? WHERE id = ?");
    $stmt->execute([$newFeat, $featId]);
    header("Location: index.php?msg=featured_updated");
    exit;
}

// Ürün Silme İşlemi
if (isset($_GET['delete_product'])) {
    $delId = (int)$_GET['delete_product'];
    $stmt = $pdo->prepare("DELETE FROM product WHERE id = ?");
    $stmt->execute([$delId]);
    header("Location: index.php?msg=deleted");
    exit;
}

/* ---------- 1. METRİKLER ---------- */
$orderStats = $pdo->query("
    SELECT 
        COUNT(*) as total_orders,
        SUM(CASE WHEN status != 'İptal Edildi' THEN total_price ELSE 0 END) as total_revenue,
        SUM(CASE WHEN status = 'Beklemede' THEN 1 ELSE 0 END) as pending_orders
    FROM orders
")->fetch(PDO::FETCH_ASSOC);

$totalProducts = $pdo->query("SELECT COUNT(*) FROM product")->fetchColumn();

$totalWishlist = 0;
try {
    $totalWishlist = $pdo->query("SELECT COUNT(*) FROM favorites")->fetchColumn();
} catch (Exception $e) {}

/* ---------- 2. VERİLER ---------- */
$recentOrders = $pdo->query("
    SELECT orders.*, product.title as product_title, product.duration as product_duration
    FROM orders
    LEFT JOIN product ON orders.product_id = product.id
    ORDER BY orders.created_at DESC
    LIMIT 6
")->fetchAll(PDO::FETCH_ASSOC);

$topWishlisted = [];
try {
    $topWishlisted = $pdo->query("
        SELECT product.id, product.title, product.price, category.name as category_name, COUNT(favorites.id) as fav_count
        FROM favorites
        JOIN product ON favorites.product_id = product.id
        LEFT JOIN category ON product.category_id = category.id
        GROUP BY product.id
        ORDER BY fav_count DESC
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

$productsList = $pdo->query("
    SELECT product.*, category.name as category_name
    FROM product
    LEFT JOIN category ON product.category_id = category.id
    ORDER BY product.id DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stüdyo Yönetim Paneli | Notti Blu</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background: #F4F6F9;
            color: #17233C;
            -webkit-font-smoothing: antialiased;
        }

        .layout {
            display: flex;
            min-height: 100vh;
        }

        /* Sol Sidebar */
        .sidebar {
            width: 260px;
            background: #101D38;
            color: #FFFFFF;
            padding: 35px 22px;
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
        }

        .brand-logo {
            font-family: Georgia, serif;
            font-size: 22px;
            font-weight: 500;
            color: #FFFFFF;
            text-decoration: none;
            letter-spacing: 0.5px;
            margin-bottom: 45px;
            display: block;
        }

        .brand-logo span { color: #C5A880; }

        .nav-menu {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .nav-item a {
            display: flex;
            align-items: center;
            padding: 12px 16px;
            border-radius: 10px;
            color: #94A3B8;
            text-decoration: none;
            font-size: 13.5px;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .nav-item a:hover {
            background: rgba(255, 255, 255, 0.08);
            color: #FFFFFF;
        }

        .nav-item a.active {
            border-left: 3px solid #C5A880;
            background: rgba(255, 255, 255, 0.1);
            color: #FFFFFF;
        }

        .sidebar-bottom {
            margin-top: auto;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
        }

        .sidebar-bottom a {
            color: #94A3B8;
            font-size: 13px;
            text-decoration: none;
            transition: color 0.2s;
        }

        .sidebar-bottom a:hover { color: #FFFFFF; }

        /* Ana İçerik */
        .main {
            flex: 1;
            padding: 40px 45px;
            overflow-y: auto;
        }

        .header-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .header-title h1 {
            font-family: Georgia, serif;
            font-size: 28px;
            font-weight: 500;
            color: #0A1833;
        }

        .header-title p {
            font-size: 13px;
            color: #64748B;
            margin-top: 3px;
        }

        .btn-add-tour {
            background: #203A63;
            color: #FFFFFF;
            padding: 10px 20px;
            border-radius: 10px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            transition: background 0.2s;
        }

        .btn-add-tour:hover { background: #35527E; }

        /* KPI Metrik Kartları */
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 35px;
        }

        .kpi-card {
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            border-radius: 14px;
            padding: 22px;
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.02);
        }

        .kpi-label {
            font-size: 11.5px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #64748B;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .kpi-val {
            font-family: Georgia, serif;
            font-size: 26px;
            font-weight: bold;
            color: #101D38;
        }

        /* 2 Kolonlu Alan */
        .dashboard-grid {
            display: grid;
            grid-template-columns: 1.5fr 1fr;
            gap: 25px;
            margin-bottom: 35px;
        }

        .panel-card {
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            border-radius: 14px;
            padding: 24px;
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.02);
        }

        .panel-title {
            font-family: Georgia, serif;
            font-size: 18px;
            color: #0A1833;
            margin-bottom: 18px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        /* Rota Arama & Başlık Satırı */
        .catalog-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
            gap: 15px;
        }

        .search-box {
            position: relative;
            max-width: 280px;
            width: 100%;
        }

        .search-box input {
            width: 100%;
            padding: 9px 14px 9px 34px;
            border: 1px solid #CBD5E1;
            border-radius: 10px;
            font-size: 13px;
            color: #17233C;
            outline: none;
            background: #FAF9F6;
            font-family: inherit;
            transition: border-color 0.2s, background 0.2s;
        }

        .search-box input:focus {
            border-color: #203A63;
            background: #FFFFFF;
        }

        .search-icon {
            position: absolute;
            left: 11px;
            top: 50%;
            transform: translateY(-50%);
            color: #94A3B8;
            font-size: 13px;
            pointer-events: none;
        }

        /* İçten Kaydırmalı Sabit Tablo Kutusu */
        .scrollable-table {
            max-height: 420px;
            overflow-y: auto;
            border: 1px solid #F1F5F9;
            border-radius: 10px;
        }

        .scrollable-table::-webkit-scrollbar {
            width: 6px;
        }

        .scrollable-table::-webkit-scrollbar-track {
            background: #FAF9F6;
        }

        .scrollable-table::-webkit-scrollbar-thumb {
            background: #CBD5E1;
            border-radius: 10px;
        }

        .scrollable-table::-webkit-scrollbar-thumb:hover {
            background: #94A3B8;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        /* Sabit başlıklar (scroll esnasında üstte kalır) */
        thead th {
            position: sticky;
            top: 0;
            background: #FAF9F6;
            z-index: 2;
            padding: 12px 14px;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #64748B;
            font-weight: 600;
            border-bottom: 1px solid #E2E8F0;
        }

        td {
            padding: 14px 14px;
            font-size: 13px;
            color: #1E293B;
            border-bottom: 1px solid #F1F5F9;
            vertical-align: middle;
        }

        tr:last-child td { border-bottom: none; }

        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }

        .status-Onaylandı { background: #DCFCE7; color: #166534; }
        .status-Beklemede { background: #FEF3C7; color: #92400E; }
        .status-İptal { background: #FEE2E2; color: #991B1B; }

        .btn-featured {
            font-size: 11px;
            padding: 4px 10px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            display: inline-block;
            transition: all 0.2s;
        }

        .featured-active { background: #FEF3C7; color: #B45309; border: 1px solid #FDE68A; }
        .featured-inactive { background: #F1F5F9; color: #64748B; }

        .btn-del {
            color: #EF4444;
            text-decoration: none;
            font-size: 12px;
            font-weight: 500;
        }

        .btn-del:hover { text-decoration: underline; }

        @media (max-width: 1000px) {
            .layout { flex-direction: column; }
            .sidebar { width: 100%; }
            .dashboard-grid { grid-template-columns: 1fr; }
            .kpi-grid { grid-template-columns: repeat(2, 1fr); }
            .catalog-header { flex-direction: column; align-items: stretch; }
            .search-box { max-width: 100%; }
        }
    </style>
</head>
<body>

<div class="layout">
    <!-- Sol Sidebar -->
    <aside class="sidebar">
        <a href="../index.php" class="brand-logo">NOTTI <span>BLU</span></a>
        
        <ul class="nav-menu">
            <li class="nav-item"><a href="index.php" class="active">Genel Bakış</a></li>
            <li class="nav-item"><a href="orders.php">Rezervasyonlar</a></li>
            <li class="nav-item"><a href="add-product.php">Yeni Rota Ekle</a></li>
        </ul>

        <div class="sidebar-bottom">
            <a href="../index.php">← Siteye Dön</a>
        </div>
    </aside>

    <!-- Ana Panel -->
    <main class="main">
        <div class="header-bar">
            <div class="header-title">
                <h1>Stüdyo Yönetim Merkezi</h1>
                <p>Kürasyon, rezervasyonlar ve rota performans analizi</p>
            </div>
            <a href="add-product.php" class="btn-add-tour">+ Yeni Rota Ekle</a>
        </div>

        <!-- KPI Kartları -->
        <div class="kpi-grid">
            <div class="kpi-card">
                <div class="kpi-label">Toplam Rezervasyon</div>
                <div class="kpi-val"><?= (int)($orderStats['total_orders'] ?? 0) ?></div>
            </div>
            <div class="kpi-card">
                <div class="kpi-label">Toplam Hacim / Ciro</div>
                <div class="kpi-val">€<?= number_format((float)($orderStats['total_revenue'] ?? 0), 0, ',', '.') ?></div>
            </div>
            <div class="kpi-card">
                <div class="kpi-label">Bekleyen İşlemler</div>
                <div class="kpi-val" style="color: #D97706;"><?= (int)($orderStats['pending_orders'] ?? 0) ?></div>
            </div>
          
<div class="kpi-card">
    <div class="kpi-label">Wishlist Etkileşimi</div>
    <div class="kpi-val" style="color: #9A7B56;"><?= $totalWishlist ?></div>
</div>
        </div>

        <!-- İki Kolonlu Canlı Akış & Wishlist -->
        <div class="dashboard-grid">
            <!-- Sol: Son Rezervasyonlar -->
            <div class="panel-card">
                <div class="panel-title">
                    <span>Son Rezervasyonlar</span>
                    <a href="orders.php" style="font-size: 12px; color: #203A63; text-decoration: none;">Tümünü Gör →</a>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Misafir</th>
                            <th>Rota</th>
                            <th>Tutar</th>
                            <th>Durum</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentOrders)): ?>
                            <tr><td colspan="4" style="text-align: center; color: #94A3B8; padding: 25px;">Kayıtlı rezervasyon yok.</td></tr>
                        <?php else: ?>
                            <?php foreach ($recentOrders as $order): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($order['fullname']) ?></strong><br>
                                        <span style="font-size: 11px; color: #64748B;"><?= htmlspecialchars($order['phone']) ?></span>
                                    </td>
                                    <td><?= htmlspecialchars($order['product_title'] ?? 'Özel Rota') ?></td>
                                    <td><strong>€<?= number_format($order['total_price'], 0, ',', '.') ?></strong></td>
                                    <td>
                                        <span class="status-badge status-<?= $order['status'] === 'İptal Edildi' ? 'İptal' : $order['status'] ?>">
                                            <?= htmlspecialchars($order['status']) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Sağ: Wishlist Analitiği -->
            <div class="panel-card">
                <div class="panel-title">
                    <span>En Çok Kaydedilenler</span>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Rota</th>
                            <th>Fiyat</th>
                            <th>İlgi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($topWishlisted)): ?>
                            <tr><td colspan="3" style="text-align: center; color: #94A3B8; padding: 25px;">Henüz kaydedilen rota yok.</td></tr>
                        <?php else: ?>
                            <?php foreach ($topWishlisted as $fav): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($fav['title']) ?></strong><br>
                                        <span style="font-size: 11px; color: #64748B;"><?= htmlspecialchars($fav['category_name'] ?? 'Genel') ?></span>
                                    </td>
                                    <td>€<?= number_format($fav['price'], 0, ',', '.') ?></td>
                                 
<td>
    <span style="display: inline-block; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; background: #F5EFEB; color: #9A7B56;">
        <?= $fav['fav_count'] ?> kişi
    </span>
</td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 3. Rota Kataloğu: Arama + İçten Kaydırmalı Sabit Alan -->
        <div class="panel-card">
            <div class="catalog-header">
                <div class="panel-title" style="margin-bottom: 0;">
                    <span>Katalogdaki Rotalar (<span id="routeCount"><?= count($productsList) ?></span>)</span>
                </div>

                <!-- Canlı Arama Kutusu -->
                <div class="search-box">
                    <span class="search-icon">🔍</span>
                    <input type="text" id="routeSearchInput" placeholder="Rota veya kategori ara..." onkeyup="filterRoutesTable()">
                </div>
            </div>

            <!-- Scrollable Tablo Container -->
            <div class="scrollable-table">
                <table id="routesTable">
                    <thead>
                        <tr>
                            <th>Görsel</th>
                            <th>Rota Adı</th>
                            <th>Kategori</th>
                            <th>Süre</th>
                            <th>Fiyat</th>
                            <th>Ayın Favorisi</th>
                            <th style="text-align: right;">Aksiyon</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($productsList as $p): ?>
                            <tr class="route-row">
                                <td style="width: 50px;">
                                    <img src="../images/<?= htmlspecialchars($p['image']) ?>" alt="" style="width: 45px; height: 32px; object-fit: cover; border-radius: 6px;">
                                </td>
                                <td class="search-target-title"><strong><?= htmlspecialchars($p['title']) ?></strong></td>
                                <td class="search-target-cat" style="color: #64748B;"><?= htmlspecialchars($p['category_name'] ?? 'Genel') ?></td>
                                <td><?= (int)$p['duration'] ?> Gün</td>
                                <td><strong>€<?= number_format($p['price'], 0, ',', '.') ?></strong></td>
                                <td>
                                    <a href="index.php?toggle_featured=<?= $p['id'] ?>&current=<?= (int)($p['is_featured'] ?? 0) ?>" 
                                       class="btn-featured <?= !empty($p['is_featured']) ? 'featured-active' : 'featured-inactive' ?>">
                                        <?= !empty($p['is_featured']) ? '★ Ayın Favorisi' : '☆ Yap' ?>
                                    </a>
                                </td>
                                <td style="text-align: right; white-space: nowrap;">
    <a href="edit-product.php?id=<?= $p['id'] ?>" style="color: #203A63; text-decoration: none; font-size: 12px; font-weight: 600; margin-right: 12px;">Düzenle</a>
    <a href="index.php?delete_product=<?= $p['id'] ?>" class="btn-del" onclick="return confirm('Bu rotayı kaldırmak istediğinize emin misiniz?')">Sil</a>
</td>
                            </tr>
                        <?php endforeach; ?>
                        <tr id="noRoutesMatch" style="display: none;">
                            <td colspan="7" style="text-align: center; color: #94A3B8; padding: 30px;">
                                Aradığınız kriterde bir rota bulunamadı.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<script>
// Canlı Arama Fonksiyonu
function filterRoutesTable() {
    const input = document.getElementById("routeSearchInput");
    const filter = input.value.toLowerCase().trim();
    const rows = document.querySelectorAll("#routesTable tbody tr.route-row");
    let visibleCount = 0;

    rows.forEach(row => {
        const title = row.querySelector(".search-target-title").textContent.toLowerCase();
        const category = row.querySelector(".search-target-cat").textContent.toLowerCase();

        if (title.includes(filter) || category.includes(filter)) {
            row.style.display = "";
            visibleCount++;
        } else {
            row.style.display = "none";
        }
    });

    const noMatchRow = document.getElementById("noRoutesMatch");
    if (visibleCount === 0) {
        noMatchRow.style.display = "";
    } else {
        noMatchRow.style.display = "none";
    }

    document.getElementById("routeCount").innerText = visibleCount;
}
</script>

</body>
</html>