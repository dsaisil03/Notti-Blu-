
<?php

require '../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $title = $_POST['title'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $category_id = $_POST['category_id'];
    $visa_status = $_POST['visa_status'];
$duration = $_POST['duration'];

    $image = $_FILES['image'];

    $imageName = time() . '_' . basename($image['name']);

    $uploadPath = '../images/' . $imageName;

    move_uploaded_file($image['tmp_name'], $uploadPath);

  $sql = "INSERT INTO product
        (title, description, price, category_id, image, visa_status, duration)
        VALUES
        (:title, :description, :price, :category_id, :image, :visa_status, :duration)";
    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        'title' => $title,
        'description' => $description,
        'price' => $price,
        'category_id' => $category_id,
        'image' => $imageName,
        'visa_status' => $visa_status,
    'duration' => $duration
    ]);

    header("Location: index.php");
    exit;
}

$categories = $pdo->query(
    "SELECT * FROM category ORDER BY name"
)->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="tr">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Yeni Rota | Notti Blu</title>

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
            min-height: 100vh;
            padding: 50px 20px;
        }

        .page {
            max-width: 760px;
            margin: auto;
        }

    .back {
    display: inline-block;
    margin-bottom: 20px;
    color: #527BAE;
    text-decoration: none;
    font-size: 14px;
    font-weight: 700;
}

        .back:hover {
            color: #173B68;
        }

        .card {
            background: white;
            border-radius: 24px;
            padding: 42px;
            box-shadow: 0 12px 35px rgba(23, 59, 104, 0.08);
        }

        .heading {
            margin-bottom: 35px;
        }
        .eyebrow {
    text-transform: uppercase;
    letter-spacing: 2.5px;
    font-size: 15px;
    font-weight: 700;
    color: #527BAE;
    margin-bottom: 12px;
}

        h1 {
            font-family: Georgia, "Times New Roman", serif;
            font-size: 38px;
            font-weight: 500;
            color: #0A1833;
            margin-bottom: 10px;
        }

        .subtitle {
            color: #7890A8;
            font-size: 14px;
            line-height: 1.6;
        }

        .form-group {
            margin-bottom: 23px;
        }

        label {
            display: block;
            margin-bottom: 9px;
            color: #243B64;
            font-size: 13px;
            font-weight: 600;
        }

        input,
        textarea,
        select {
            width: 100%;
            border: 1px solid #D8E0EA;
            background: #FBFCFD;
            color: #17233C;
            border-radius: 12px;
            padding: 14px 15px;
            font-family: inherit;
            font-size: 14px;
            outline: none;
            transition: 0.2s ease;
        }

        input:focus,
        textarea:focus,
        select:focus {
            border-color: #527BAE;
            background: white;
            box-shadow: 0 0 0 3px rgba(82, 123, 174, 0.08);
        }

        textarea {
            min-height: 140px;
            resize: vertical;
            line-height: 1.6;
        }

        .two-columns {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
        }

        .file-box {
            border: 1px dashed #B9C8D9;
            background: #F8FAFC;
            border-radius: 14px;
            padding: 18px;
        }

        .file-box input {
            border: none;
            background: transparent;
            padding: 5px 0;
            box-shadow: none;
        }

        .file-hint {
            margin-top: 7px;
            color: #8A96A6;
            font-size: 12px;
        }

        .bottom {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            margin-top: 32px;
            padding-top: 25px;
            border-top: 1px solid #E7EAF0;
        }

        .cancel {
            color: #687386;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
        }

        .cancel:hover {
            color: #173B68;
        }

        .submit {
            border: none;
            background: #173B68;
            color: white;
            padding: 14px 24px;
            border-radius: 12px;
            font-family: inherit;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s ease;
        }

        .submit:hover {
            background: #527BAE;
            transform: translateY(-1px);
        }

        @media (max-width: 650px) {

            body {
                padding: 30px 15px;
            }

            .card {
                padding: 28px 22px;
            }

            h1 {
                font-size: 32px;
            }

            .two-columns {
                grid-template-columns: 1fr;
                gap: 0;
            }

            .bottom {
                align-items: stretch;
                flex-direction: column-reverse;
            }

            .submit {
                width: 100%;
            }

        }

    </style>

</head>

<body>

<div class="page">

    <a href="index.php" class="back">
        ← Notti Blu Studio
    </a>

    <div class="card">

        <div class="heading">

            <div class="eyebrow">
                Notti Blu Studio
            </div>

            <h1>Yeni rota oluştur.</h1>

            <p class="subtitle">
                Notti Blu koleksiyonuna yeni bir yolculuk ekle.
            </p>

        </div>


        <form method="POST" enctype="multipart/form-data">

            <div class="form-group">

                <label>Rota Adı</label>

                <input
                    type="text"
                    name="title"
                    placeholder="Örneğin: Santorini Escape"
                    required
                >

            </div>


            <div class="form-group">

                <label>Rota Hikâyesi</label>

                <textarea
                    name="description"
                    placeholder="Bu rotayı özel yapan şeyleri anlat..."
                    required
                ></textarea>

            </div>


            <div class="two-columns">

                <div class="form-group">

                    <label>Fiyat (€)</label>

                    <input
                        type="number"
                        name="price"
                        step="0.01"
                        placeholder="349"
                        required
                    >

                </div>


                <div class="form-group">

                    <label>Kategori</label>

                    <select name="category_id" required>

                        <option value="">
                            Kategori seç
                        </option>

                        <?php foreach ($categories as $category): ?>

                            <option value="<?= $category['id'] ?>">
                                <?= htmlspecialchars($category['name']) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

            </div>
        

<div class="two-columns">

    <div class="form-group">

        <label>Vize Durumu</label>

        <select name="visa_status" required>

            <option value="">
                Vize durumu seç
            </option>

            <option value="vizesiz">
                Vizesiz
            </option>

            <option value="vizeli">
                Vizeli
            </option>

        </select>

    </div>

    <div class="form-group">

        <label>Seyahat Süresi</label>

        <select name="duration" required>

            <option value="">
                Süre seç
            </option>

            <option value="3">
                2-3 gün
            </option>

            <option value="5">
                4-5 gün
            </option>

            <option value="7">
                1 hafta
            </option>

            <option value="10">
                1 haftadan uzun
            </option>

        </select>

    </div>

</div>




   


            <div class="form-group">

                <label>Rota Fotoğrafı</label>

                <div class="file-box">

                    <input
                        type="file"
                        name="image"
                        accept="image/*"
                        required
                    >

                    <p class="file-hint">
                        JPG, JPEG, PNG veya WebP formatında bir görsel seç.
                    </p>

                </div>

            </div>


            <div class="bottom">

                <a href="index.php" class="cancel">
                    Vazgeç
                </a>

                <button type="submit" class="submit">
                    Rotayı Koleksiyona Ekle
                </button>

            </div>

        </form>

    </div>



</body>

</html>

