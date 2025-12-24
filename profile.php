<?php
require_once "db.php";
session_start();

/* ==== Ortak parametreler ==== */
$lang = $_GET['lang'] ?? 'tr';
$selectedDb = $_GET['db'] ?? '2025';
$languages = ['tr' => 'Türkçe', 'en' => 'English', 'mk' => 'Македонски'];

/* ==== Sidebar/menü metinleri ==== */

$texts2 = [
    'tr' => [
        'page_title' => 'Kullanıcı Profili',
        'username' => 'Kullanıcı Adı',
        'current_password' => 'Mevcut Şifre',
        'new_password' => 'Yeni Şifre',
        'confirm_password' => 'Yeni Şifre (Tekrar)',
        'facility' => 'Tesis',
        'submit' => 'Şifreyi Güncelle',
        'main_menu' => 'Menü',
        'menu' => 'Analizler',
        'menu-admin' => 'Admin Menü',
        
    ],
    'en' => [
        'page_title' => 'User Profile',
        'username' => 'Username',
        'current_password' => 'Current Password',
        'new_password' => 'New Password',
        'confirm_password' => 'Confirm New Password',
        'facility' => 'Facility',
        'submit' => 'Update Password',
        'menu' => 'Menu',
        'menu-admin' => 'Admin Menu',
        ],
    
    'mk' => [
        'page_title' => 'Кориснички профил',
        'username' => 'Корисничко име',
        'current_password' => 'Моментална лозинка',
        'new_password' => 'Нова лозинка',
        'confirm_password' => 'Потврдете нова лозинка',
        'facility' => 'Објект',
        'submit' => 'Ажурирај лозинка',
        'menu' => 'Мени',
        'menu-admin' => 'Администраторско мени',
        ],
        ];
require_once "language_analiz.php";

$current_texts = $texts[$lang] ?? $texts2['tr'];
$current_texts2 = $texts2['tr'];

$userId = $_SESSION["user_id"];

// Fetch user information
$stmt = $pdo->prepare("SELECT u.username, u.created_at, f.name as facility_name 
                       FROM users u 
                       LEFT JOIN facilities f ON u.facility_id = f.id 
                       WHERE u.id = :id");
$stmt->execute(["id" => $userId]);
$userInfo = $stmt->fetch(PDO::FETCH_ASSOC);

// Password change status messages
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // First verify current password
    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = :id");
    $stmt->execute(["id" => $userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (password_verify($current_password, $user['password'])) {
        // Check if new passwords match
        if ($new_password === $confirm_password) {
            // Hash the new password and update
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare("UPDATE users SET password = :password WHERE id = :id");
            $stmt->execute([
                "password" => $hashed_password,
                "id" => $userId
            ]);
            
            $success_message = $current_texts2['password_success'];
        } else {
            $error_message = $current_texts2['password_match_error'];
        }
    } else {
        $error_message = $current_texts2['password_error'];
    }
}
?>

<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Labx - <?= $current_texts2['page_title'] ?></title>
    <link rel="stylesheet" href="bower_components/bootstrap/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="bower_components/font-awesome/css/font-awesome.min.css">
    <link rel="stylesheet" href="dist/css/main.css">
    <!-- Favicon -->
        <link rel="icon" type="image/png" sizes="32x32" href="/imgs/favicon.png">
        <link rel="icon" type="image/png" sizes="16x16" href="/imgs/favicon.png">
        <link rel="shortcut icon" href="imgs/favicon.ico">
</head>
<body>
<div id="ui" class="ui">

    <!--header start-->
    <header id="header" class="ui-header">
        <div class="navbar-header">
            <a href="index.php" class="navbar-brand">
                <span class="logo"><img src="imgs/labx.png" width="100px"></span>
            </a>
        </div>
        <div class="navbar-collapse nav-responsive-disabled">

                    <!--toggle buttons start-->
                    <ul class="nav navbar-nav">
                        <li>
                            <a class="toggle-btn" data-toggle="ui-nav" href="#">
                                <i class="fa fa-bars"></i>
                            </a>
                        </li>
                    </ul>
                    <!-- toggle buttons end -->
        
        <ul class="nav navbar-nav navbar-right">
            <!-- Dil Seçimi -->
            <li class="dropdown">
                <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                    <i class="fa fa-globe"></i> <?= $languages[$lang] ?>
                </a>
                <ul class="dropdown-menu">
                    <?php foreach ($languages as $key => $language): ?>
                        <li><a href="?lang=<?= $key ?>&db=<?= $selectedDb ?>"><?= $language ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </li>

            <!-- Veritabanı Seçimi -->
            <li class="dropdown">
                <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                    <i class="fa fa-database"></i> <?= $current_texts2['select_db'] ?>
                </a>
                <ul class="dropdown-menu">
                    <?php foreach ($databases as $db): ?>
                        <li><a href="?lang=<?= $lang ?>&db=<?= $db ?>"><?= $db ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </li>

            <!-- Kullanıcı Menüsü -->
            <li class="dropdown dropdown-usermenu">
                <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                    <div class="user-avatar"><img src="imgs/a0.jpg" alt="..."></div>
                    
                </a>
                <ul class="dropdown-menu dropdown-menu-usermenu pull-right">
                    <li><a href="profile.php?lang=<?= $lang ?>&db=<?= $selectedDb ?>"><i class="fa fa-user"></i> <?= $current_texts['profile'] ?></a></li>
                    <li><a href="settings.php"><i class="fa fa-cogs"></i> <?= $current_texts['settings'] ?></a></li>
                    <li><a href="logout.php"><i class="fa fa-sign-out"></i> <?= $current_texts['logout'] ?></a></li>
                </ul>
            </li>
        </ul>
    </header>
    <!--header end-->

    <!--sidebar start-->
    <?php include "sidebar.php" ?>
    <!--sidebar end-->

<div id="content" class="ui-content">
    <div class="ui-content-body">
        <div class="ui-container">
            <?php if (!empty($success_message)): ?>
            <div class="alert alert-success">
                <?= $success_message ?>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger">
                <?= $error_message ?>
            </div>
            <?php endif; ?>
            
            <!-- User Information Panel -->
            <div class="panel">
                <header class="panel-heading">
                    <?= $current_texts2['user_info'] ?>
                </header>
                <div class="panel-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="profile-details">
                                <div class="profile-image">
                                    <img src="imgs/a0.jpg" alt="profile-image" class="img-circle">
                                </div>
                                <div class="profile-info">
                                    <h6><?= htmlspecialchars($userInfo['username']) ?></h6>
                                    <p><strong><?= $current_texts2['facility'] ?>:</strong> <?= htmlspecialchars($userInfo['facility_name'] ?? '-') ?></p>
                                    <p><strong><?= $current_texts2['created_at'] ?>:</strong> <?= $userInfo['created_at'] ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Password Change Panel -->
            <div class="panel">
                <header class="panel-heading">
                    <?= $current_texts2['change_password'] ?>
                </header>
                <div class="panel-body">
                    <form action="profile.php?lang=<?= $lang ?>&db=<?= $selectedDb ?>" method="post">
                        <div class="form-group">
                            <label><?= $current_texts2['current_password'] ?>:</label>
                            <input type="password" name="current_password" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label><?= $current_texts2['new_password'] ?>:</label>
                            <input type="password" name="new_password" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label><?= $current_texts2['confirm_password'] ?>:</label>
                            <input type="password" name="confirm_password" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <?= $current_texts2['submit'] ?>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<footer id="footer" class="ui-footer">
    <?= $current_texts['footer'] ?>
</footer>

<style>
.profile-details {
    display: flex;
    align-items: center;
}
.profile-image {
    margin-right: 20px;
}
.profile-image img {
    width: 100px;
    height: 100px;
}
.profile-info h4 {
    margin-top: 0;
    margin-bottom: 10px;
}
</style>

<script src="bower_components/jquery/dist/jquery.min.js"></script>
<script src="bower_components/bootstrap/dist/js/bootstrap.min.js"></script>
<script src="dist/js/main.js"></script>
</body>
</html>