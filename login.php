<?php
session_start();
require 'db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
$stmt = $pdo->prepare("SELECT * FROM user WHERE email = ? AND role = 'user'");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['role'] = $user['role'];

        header('Location: index.php');
        exit;
    } else {
        $error = "E-posta veya şifre hatalı.";
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giriş Yap | Notti Blu</title>

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
            margin-bottom: 30px;
            font-size: 32px;
            color: #173B68;
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

        .password-wrapper {
            position: relative;
        }

   

.password-wrapper input {
    width: 100%;
    padding-right: 50px;
}

.eye-btn {
    position: absolute;
    right: 15px;
    top: 14px;
    width: 24px;
    height: 24px;
    padding: 0;
    background: none;
    border: none;
    cursor: pointer;
    color: #527BAE;
    display: flex;
    align-items: center;
    justify-content: center;
}

.eye-btn svg {
    width: 19px;
    height: 19px;
    fill: none;
    stroke: currentColor;
    stroke-width: 1.5;
    stroke-linecap: round;
    stroke-linejoin: round;
}

.eye-btn:hover {
    color: #173B68;
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
            transition: 0.2s;
        }

        .btn:hover {
            background: #527BAE;
        }

        .links {
            margin-top: 20px;
            text-align: center;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .links a {
            color: #527BAE;
            text-decoration: none;
            font-size: 14px;
        }

        .links a:hover {
            text-decoration: underline;
        }

    </style>
</head>
<body>

<div class="login-card">
    <h1>Giriş Yap</h1>

    <?php if ($error): ?>
        <div class="error"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST">
        <label>E-posta</label>
        <input type="email" name="email" required>

        <label>Şifre</label>
        <div class="password-wrapper">
            <input type="password" name="password" id="password" required>
      <button type="button" onclick="togglePassword()" class="eye-btn" aria-label="Şifreyi göster">
    <svg viewBox="0 0 24 24" aria-hidden="true">
        <path d="M2.5 12s3.5-5.5 9.5-5.5S21.5 12 21.5 12 18 17.5 12 17.5 2.5 12 2.5 12Z"></path>
        <circle cx="12" cy="12" r="2.5"></circle>
    </svg>
</button>
        </div>

        <button type="submit" class="btn">Yolculuğa Devam Et</button>
    </form>

    <div class="links">
        <a href="forgot-password.php">Şifreni yeniden oluştur</a>
        <a href="register.php">Notti Blu’ya Katıl</a>
    </div>
</div>

<script>
function togglePassword() {
    const input = document.getElementById('password');
    input.type = input.type === 'password' ? 'text' : 'password';
}
</script>

</body>
</html>