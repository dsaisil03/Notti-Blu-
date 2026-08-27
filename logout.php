<?php

session_start();

$wasAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

session_unset();
session_destroy();

if ($wasAdmin) {
    header("Location: admin-login.php");
} else {
    header("Location: login.php");
}

exit;