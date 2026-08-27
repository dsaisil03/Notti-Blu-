<?php
require 'db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Aynı e-posta daha önce kayıtlı mı?
    $check = $pdo->prepare("SELECT id FROM user WHERE email = ?");
    $check->execute([$email]);

    if ($check->fetch()) {
        $error = "Bu e-posta adresi zaten kayıtlı.";
    } else {

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare(
            "INSERT INTO user (name, email, password, role) VALUES (?, ?, ?, 'user')"
        );

        $stmt->execute([
            $name,
            $email,
            $hashedPassword
        ]);

        header("Location: login.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Kayıt Ol | Notti Blu</title>

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

        .register-card {
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

            font-size: 32px;
            color: #173B68;
        }

        .subtitle {
            text-align: center;

            color: #718096;

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

            font-size: 14px;
        }

        input[type="text"],
        input[type="email"],
        input[type="password"] {

            width: 100%;

            padding: 14px;

            border: 1px solid #D8E0EA;

            border-radius: 12px;

            margin-bottom: 20px;

            font-size: 15px;

            outline: none;

            transition: 0.2s;
        }

        input:focus {
            border-color: #527BAE;

            box-shadow: 0 0 0 3px rgba(82,123,174,0.08);
        }

        .password-wrapper {
            position: relative;
        }

        .password-wrapper input {
            padding-right: 50px;
        }

        .eye-btn {
            position: absolute;

            right: 15px;
            top: 12px;

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

        .terms {
            display: flex;

            align-items: flex-start;

            gap: 8px;

            margin-top: -3px;

            margin-bottom: 22px;

            font-size: 13px;

            line-height: 1.5;

            color: #718096;
        }

        .terms input {
            margin-top: 3px;
        }

        .terms a {
            color: #527BAE;

            text-decoration: none;
        }

        .terms a:hover {
            text-decoration: underline;
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

<div class="register-card">

    <h1>Notti Blu</h1>

    <div class="subtitle">
        Yolculuğun burada başlıyor.
    </div>

    <?php if ($error): ?>

        <div class="error">
            <?= htmlspecialchars($error) ?>
        </div>

    <?php endif; ?>


    <form method="POST">

        <label>Ad Soyad</label>

        <input
            type="text"
            name="name"
            placeholder="Ad Soyad"
            required
        >


        <label>E-posta</label>

        <input
            type="email"
            name="email"
            placeholder="ornek@mail.com"
            required
        >


        <label>Şifre</label>

        <div class="password-wrapper">

            <input
                type="password"
                name="password"
                id="password"
                placeholder="Şifreni oluştur"
                required
            >

            <button
                type="button"
                onclick="togglePassword()"
                class="eye-btn"
                aria-label="Şifreyi göster"
            >

                <svg viewBox="0 0 24 24" aria-hidden="true">

                    <path d="M2.5 12s3.5-5.5 9.5-5.5S21.5 12 21.5 12 18 17.5 12 17.5 2.5 12 2.5 12Z"></path>

                    <circle cx="12" cy="12" r="2.5"></circle>

                </svg>

            </button>

        </div>


        <label class="terms">

            <input
                type="checkbox"
                name="kvkk"
                required
            >

            <span>
                <a href="kvkk.php" target="_blank">
                    KVKK Aydınlatma Metni
                </a>

                ve

                <a href="terms.php" target="_blank">
                    Kullanım Koşulları
                </a>'nı okudum, kabul ediyorum.
            </span>

        </label>


        <button type="submit" class="btn">
            Notti Blu'ya Katıl
        </button>

    </form>


    <div class="links">

        <a href="login.php">
            Zaten hesabın var mı? Giriş Yap
        </a>

    </div>

</div>


<script>

function togglePassword() {

    const input = document.getElementById('password');

    input.type =
        input.type === 'password'
            ? 'text'
            : 'password';

}

</script>

</body>
</html>