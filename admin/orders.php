<?php
session_start();
require '../db.php';

$message = '';

// Durum Güncelleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $orderId = (int)$_POST['order_id'];
    $newStatus = $_POST['status'];

    if (in_array($newStatus, ['Beklemede', 'Onaylandı', 'İptal Edildi'])) {
        $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->execute([$newStatus, $orderId]);
        $message = "Rezervasyon #{$orderId} güncellendi.";
    }
}

// Rezervasyonları Çek
$sql = "SELECT orders.*, 
               product.title AS product_title, 
               product.duration AS product_duration,
               product.image AS product_image
        FROM orders
        LEFT JOIN product ON orders.product_id = product.id
        ORDER BY orders.created_at DESC";

$orders = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

// İstatistikler
$totalOrders = count($orders);
$totalRevenue = 0;
$pendingCount = 0;

foreach ($orders as $o) {
    if ($o['status'] !== 'İptal Edildi') {
        $totalRevenue += (float)$o['total_price'];
    }
    if ($o['status'] === 'Beklemede') {
        $pendingCount++;
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rezervasyonlar | Notti Blu Studio</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background: #F8F6F3;
            color: #17233C;
            -webkit-font-smoothing: antialiased;
        }

        .layout {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
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

        /* Ana Alan */
        .main {
            flex: 1;
            padding: 40px 45px;
            overflow-y: auto;
        }

        .header-bar {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
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
            color: #7B8494;
            margin-top: 3px;
        }

        /* KPI Şeridi */
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 35px;
        }

        .kpi-card {
            background: #FFFFFF;
            border: 1px solid #EAE6DF;
            border-radius: 14px;
            padding: 22px;
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.02);
        }

        .kpi-label {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #8C96A5;
            font-weight: 600;
            margin-bottom: 6px;
        }

        .kpi-val {
            font-family: Georgia, serif;
            font-size: 26px;
            font-weight: 500;
            color: #101D38;
        }

        /* Tablo Alanı */
        .table-card {
            background: #FFFFFF;
            border: 1px solid #EAE6DF;
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.02);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        thead {
            background: #FAF9F6;
            border-bottom: 1px solid #EAE6DF;
        }

        th {
            padding: 14px 18px;
            font-size: 11px;
            font-weight: 600;
            color: #8C96A5;
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }

        td {
            padding: 18px 18px;
            font-size: 13.5px;
            color: #1E293B;
            border-bottom: 1px solid #F2EFE9;
            vertical-align: middle;
        }

        tr:last-child td { border-bottom: none; }

        .guest-name { font-weight: 600; color: #101D38; margin-bottom: 2px; }
        .guest-sub { font-size: 12px; color: #7B8494; }
        .tour-name { font-weight: 600; color: #101D38; }
        .tour-sub { font-size: 12px; color: #7B8494; margin-top: 2px; }

        .price-badge { font-weight: 600; color: #101D38; }

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

        .status-select {
            background: #FAF9F6;
            border: 1px solid #D8D2C7;
            padding: 6px 10px;
            border-radius: 8px;
            font-size: 12px;
            color: #17233C;
            outline: none;
            cursor: pointer;
            font-family: inherit;
        }

        @media (max-width: 900px) {
            .layout { flex-direction: column; }
            .sidebar { width: 100%; }
            .main { padding: 25px 20px; }
            .kpi-grid { grid-template-columns: 1fr; }
            .table-card { overflow-x: auto; }
        }
    </style>
</head>
<body>

<div class="layout">
    <!-- Sidebar -->
    <aside class="sidebar">
        <a href="index.php" class="brand-logo">NOTTI <span>BLU</span></a>
        
        <ul class="nav-menu">
            <li class="nav-item"><a href="index.php">Genel Bakış</a></li>
            <li class="nav-item"><a href="orders.php" class="active">Rezervasyonlar</a></li>
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
                <h1>Rezervasyon & Siparişler</h1>
                <p>Gelen tüm seyahat talepleri ve ödeme durumları</p>
            </div>
        </div>

        <?php if (!empty($message)): ?>
            <div style="background: #EBF3FA; color: #1E3A5F; padding: 12px 18px; border-radius: 8px; margin-bottom: 25px; font-size: 13px; border: 1px solid #D2E3F3;">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <!-- KPI Kartları -->
        <div class="kpi-grid">
            <div class="kpi-card">
                <div class="kpi-label">Toplam Rezervasyon</div>
                <div class="kpi-val"><?= $totalOrders ?></div>
            </div>
            <div class="kpi-card">
                <div class="kpi-label">Toplam Tutar / Hacim</div>
                <div class="kpi-val">€<?= number_format($totalRevenue, 0, ',', '.') ?></div>
            </div>
            <div class="kpi-card">
                <div class="kpi-label">Bekleyen İşlemler</div>
                <div class="kpi-val" style="color: #B45309;"><?= $pendingCount ?></div>
            </div>
        </div>

        <!-- Sipariş Tablosu -->
        <div class="table-card">
            <table>
                <thead>
                    <tr>
                        <th>#ID</th>
                        <th>Misafir</th>
                        <th>Seçilen Rota</th>
                        <th>Tutar</th>
                        <th>Tarih</th>
                        <th>Durum</th>
                        <th>İşlem</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($orders)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 40px; color: #8C96A5;">
                                Henüz kayıtlı bir rezervasyon bulunmuyor.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td><strong>#<?= $order['id'] ?></strong></td>
                                <td>
                                    <div class="guest-name"><?= htmlspecialchars($order['fullname']) ?></div>
                                    <div class="guest-sub"><?= htmlspecialchars($order['email']) ?> • <?= htmlspecialchars($order['phone']) ?></div>
                                    <?php if (!empty($order['notes'])): ?>
                                        <div class="guest-sub" style="margin-top: 4px; color: #4A5568;">Not: <?= htmlspecialchars($order['notes']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="tour-name"><?= htmlspecialchars($order['product_title'] ?? 'Özel Rota') ?></div>
                                    <div class="tour-sub"><?= (int)($order['product_duration'] ?? 1) ?> Günlük Kaçış</div>
                                </td>
                                <td>
                                    <span class="price-badge">€<?= number_format($order['total_price'], 0, ',', '.') ?></span>
                                </td>
                                <td style="color: #7B8494; font-size: 13px;">
                                    <?= date('d.m.Y H:i', strtotime($order['created_at'])) ?>
                                </td>
                                <td>
                                    <span class="status-badge status-<?= $order['status'] === 'İptal Edildi' ? 'İptal' : $order['status'] ?>">
                                        <?= htmlspecialchars($order['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <form method="POST" action="">
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                        <select name="status" class="status-select" onchange="this.form.submit()">
                                            <option value="Onaylandı" <?= $order['status'] === 'Onaylandı' ? 'selected' : '' ?>>Onaylandı</option>
                                            <option value="Beklemede" <?= $order['status'] === 'Beklemede' ? 'selected' : '' ?>>Beklemede</option>
                                            <option value="İptal Edildi" <?= $order['status'] === 'İptal Edildi' ? 'selected' : '' ?>>İptal Edildi</option>
                                        </select>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

</body>
</html>