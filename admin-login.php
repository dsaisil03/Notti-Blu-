<?php
session_start();
require 'db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM user WHERE email = ? AND role = 'admin'");
    $stmt->execute([$email]);

    $admin = $stmt->fetch();

 if ($admin && password_verify($password, $admin['password'])) {

    session_unset();
    session_destroy();

    session_start();

    $_SESSION['user_id'] = $admin['id'];
    $_SESSION['user_name'] = $admin['name'];
    $_SESSION['role'] = 'admin';

    header('Location: admin/');
    exit;

    } else {

        $error = "Yönetici e-posta adresi veya şifre hatalı.";

    }
}
?>

<!DOCTYPE html>
<html lang="tr">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Yönetici Girişi | Notti Blu</title>

    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: "Helvetica Neue", Arial, sans-serif;
            background: #F8F6F3;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #243B64;
        }

        .login-card {
            background: white;
            width: 100%;
            max-width: 420px;
            padding: 40px;
            border-radius: 24px;
            box-shadow: 0 10px 30px rgba(23,59,104,0.08);
        }

        h1 {
            text-align: center;
            margin-bottom: 10px;
            font-size: 30px;
            color: #173B68;
        }

        .subtitle {
            text-align: center;
            color: #7B8494;
            font-size: 14px;
            margin-bottom: 30px;
        }

        .error {
            background: #ffeaea;
            color: #c0392b;
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
        }

        input {
            width: 100%;
            padding: 14px;
            border: 1px solid #D8E0EA;
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 15px;
        }

        .btn {
            width: 100%;
            background: #173B68;
            color: white;
            border: none;
            padding: 14px;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
        }

        .btn:hover {
            background: #527BAE;
        }

        .back {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #527BAE;
            text-decoration: none;
            font-size: 14px;
        }

    </style>

</head>

<body>

<div class="login-card">

    <h1>Notti Blu Studio</h1>

    <p class="subtitle">
        Yönetici giriş alanı
    </p>

    <?php if ($error): ?>

        <div class="error">
            <?= htmlspecialchars($error) ?>
        </div>

    <?php endif; ?>

    <form method="POST">

        <label>E-posta</label>

        <input
            type="email"
            name="email"
            required
        >

        <label>Şifre</label>

        <input
            type="password"
            name="password"
            required
        >

        <button type="submit" class="btn">
            Yönetici Paneline Geç
        </button>

    </form>

    <a href="login.php" class="back">
        Kullanıcı girişine dön
    </a>

</div>

</body>

</html>