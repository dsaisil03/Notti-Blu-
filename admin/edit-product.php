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

/* Mevcut rotayı getir */
$stmt = $pdo->prepare("
    SELECT *
    FROM product
    WHERE id = ?
");

$stmt->execute([$id]);

$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    header('Location: index.php');
    exit;
}


/* Kategorileri getir */
$categories = $pdo->query("
    SELECT *
    FROM category
    ORDER BY name
")->fetchAll(PDO::FETCH_ASSOC);


/* Form gönderildiyse */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $price = $_POST['price'];
    $category_id = $_POST['category_id'];

    /* Yeni fotoğraf seçilmiş mi? */
    if (
        isset($_FILES['image']) &&
        $_FILES['image']['error'] === UPLOAD_ERR_OK
    ) {

        $image = $_FILES['image'];

        $imageName = time() . '_' . basename($image['name']);

        $uploadPath = '../images/' . $imageName;

        move_uploaded_file(
            $image['tmp_name'],
            $uploadPath
        );

        $sql = "
            UPDATE product
            SET
                title = :title,
                description = :description,
                price = :price,
                category_id = :category_id,
                image = :image
            WHERE id = :id
        ";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            'title' => $title,
            'description' => $description,
            'price' => $price,
            'category_id' => $category_id,
            'image' => $imageName,
            'id' => $id
        ]);

    } else {

        /* Fotoğraf değiştirilmediyse eskisini koru */

        $sql = "
            UPDATE product
            SET
                title = :title,
                description = :description,
                price = :price,
                category_id = :category_id
            WHERE id = :id
        ";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            'title' => $title,
            'description' => $description,
            'price' => $price,
            'category_id' => $category_id,
            'id' => $id
        ]);
    }

    header('Location: index.php');
    exit;
}

?>

<!DOCTYPE html>
<html lang="tr">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Rotayı Düzenle | Notti Blu</title>

    <style>

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: "Helvetica Neue", Arial, sans-serif;
            background: #F8F6F3;
            color: #173B68;
            padding: 40px 20px;
        }

        .container {
            max-width: 700px;
            margin: auto;
        }

        .card {
            background: white;
            padding: 35px;
            border-radius: 22px;
            box-shadow: 0 10px 30px rgba(23,59,104,0.08);
        }

        h1 {
            margin-bottom: 8px;
            font-size: 32px;
        }

        .subtitle {
            color: #7890A8;
            margin-bottom: 30px;
            font-size: 14px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            font-size: 14px;
        }

        input,
        textarea,
        select {
            width: 100%;
            padding: 13px;
            border: 1px solid #D8E0EA;
            border-radius: 12px;
            font-size: 14px;
            margin-bottom: 22px;
            font-family: inherit;
        }

        textarea {
            resize: vertical;
        }

        .current-image {
            width: 180px;
            height: 110px;
            object-fit: cover;
            border-radius: 12px;
            margin-bottom: 12px;
            display: block;
        }

        .buttons {
            display: flex;
            gap: 12px;
            margin-top: 5px;
        }

        button,
        .back {
            padding: 13px 18px;
            border-radius: 12px;
            border: none;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
        }

        button {
            background: #173B68;
            color: white;
        }

        button:hover {
            background: #527BAE;
        }

        .back {
            background: #EEF3F8;
            color: #173B68;
        }

    </style>

</head>

<body>

<div class="container">

    <div class="card">

        <h1>Rotayı Düzenle</h1>

        <p class="subtitle">
            Notti Blu rotanı yeniden şekillendir.
        </p>

        <form method="POST" enctype="multipart/form-data">

            <label>Rota Adı</label>

            <input
                type="text"
                name="title"
                value="<?= htmlspecialchars($product['title']) ?>"
                required
            >

            <label>Açıklama</label>

            <textarea
                name="description"
                rows="6"
                required
            ><?= htmlspecialchars($product['description']) ?></textarea>

            <label>Fiyat (€)</label>

            <input
                type="number"
                name="price"
                step="0.01"
                value="<?= htmlspecialchars($product['price']) ?>"
                required
            >

            <label>Kategori</label>

            <select name="category_id" required>

                <?php foreach ($categories as $category): ?>

                    <option
                        value="<?= $category['id'] ?>"
                        <?= $category['id'] == $product['category_id'] ? 'selected' : '' ?>
                    >
                        <?= htmlspecialchars($category['name']) ?>
                    </option>

                <?php endforeach; ?>

            </select>

            <label>Mevcut Fotoğraf</label>

            <?php if (!empty($product['image'])): ?>

                <img
                    src="../images/<?= htmlspecialchars($product['image']) ?>"
                    class="current-image"
                    alt="Mevcut rota fotoğrafı"
                >

            <?php endif; ?>

            <label>Yeni Fotoğraf Seç</label>

            <input
                type="file"
                name="image"
                accept="image/*"
            >

            <div class="buttons">

                <button type="submit">
                    Değişiklikleri Kaydet
                </button>

                <a href="index.php" class="back">
                    Vazgeç
                </a>

            </div>

        </form>

    </div>

</div>

</body>

</html>