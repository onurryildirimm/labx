<?php
require_once "db.php";
session_start(); // Oturumu başlatmayı unutma

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $password = trim($_POST["password"]);
    $captcha = $_POST['g-recaptcha-response'];

    // reCAPTCHA boş mu kontrolü
    if (empty($captcha)) {
        header("Location: login.php?error=captcha");
        exit();
    }

    // reCAPTCHA doğrulama isteği
    $secretKey = "6LeZNKMUAAAAAH0NKkUVYcgWNEn_13r9x61nLXsK";
    $verifyResponse = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=$secretKey&response=$captcha");
    $responseData = json_decode($verifyResponse);

    if (!$responseData->success) {
        header("Location: login.php?error=captcha");
        exit();
    }

    // Kullanıcı adı ve şifre kontrolü
    if (!empty($username) && !empty($password)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username");
        $stmt->execute(["username" => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user["password"])) {
            $_SESSION["user_id"] = $user["id"];
            $_SESSION["facility_id"] = $user["facility_id"];
            $_SESSION["username"] = $user["username"];
            $_SESSION['ui_lang'] = $user['ui_lang'] ?? 'mk';

            header("Location: index.php");
            exit();
        } else {
            header("Location: login.php?error=invalid");
            exit();
        }
    }
}

header("Location: login.php");
exit();
?>
