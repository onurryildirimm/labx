<?php
// Session Kontrolü (Hata vermemesi için güvenli başlatma)
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// DB Bağlantısı (Tek sefer çağırılmalı)
require_once __DIR__ . '/db.php';

// ---- 1. DEĞİŞKEN TANIMLAMALARI (Eksik olan kısım burasıydı) ----
$languages = ['tr' => 'Türkçe', 'en' => 'English', 'mk' => 'Македонски'];
$selectedDb = $_GET['db'] ?? '2025'; // Linklerde hata vermemesi için varsayılan DB

// ---- 2. DİL MANTIĞI ----
// A. Linkten gelen dil var mı?
if (isset($_GET['lang'])) {
    $lang = $_GET['lang'];
} 
// B. Yoksa Session'a bak (Giriş yaparken kaydettiğimiz)
elseif (isset($_SESSION['ui_lang'])) {
    $lang = $_SESSION['ui_lang'];
} 
// C. Hiçbiri yoksa varsayılan TR
else {
    $lang = 'tr';
}

// Güvenlik (Sadece izinli diller)
if (!array_key_exists($lang, $languages)) {
    $lang = 'tr';
}

// ---- 3. DİL DOSYASI YÜKLEME ----
// language_analiz.php içinde $texts dizisi olduğu varsayılıyor.
if (file_exists(__DIR__.'/language_analiz.php')) {
    require_once __DIR__.'/language_analiz.php';
} else {
    $texts = []; // Dosya yoksa hata vermesin
}

$Lx = $texts[$lang] ?? ($texts['tr'] ?? []);
$current_texts = $Lx; // Geriye dönük uyumluluk için

// ---- 4. YARDIMCI FONKSİYONLAR ----
if (!function_exists('tableExists')) {
    function tableExists(PDO $pdo, string $t): bool {
        static $cache=null;
        if ($cache===null) $cache = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        return in_array($t, $cache, true);
    }
}
if (!function_exists('columnExists')) {
    function columnExists(PDO $pdo, string $table, string $col): bool {
        try {
            $cols = $pdo->query("SHOW COLUMNS FROM `$table`")->fetchAll(PDO::FETCH_COLUMN);
            return in_array($col, $cols, true);
        } catch(Exception $e) { return false; }
    }
}

// ---- 5. KULLANICI KONTROLÜ ----
// Eğer oturum yoksa login'e at (Header çağrıldığı an koruma sağlar)
if (empty($_SESSION['user_id'])) {
    header("Location: login.php"); 
    exit;
}
$userId = (int)$_SESSION['user_id'];

// Kullanıcı bilgilerini çek
$usersTable = 'users';
// Tablo kontrolü yapmıyoruz, users tablosu kesin olmalı. Sütun kontrolü yapalım:
$hasRole      = columnExists($pdo, $usersTable, 'role');
$hasIsAdmin   = columnExists($pdo, $usersTable, 'is_admin');
$hasFacility  = columnExists($pdo, $usersTable, 'facility_id');

$selCols = ['id','username'];
if ($hasRole)     $selCols[] = 'role';
if ($hasIsAdmin)  $selCols[] = 'is_admin';
if ($hasFacility) $selCols[] = 'facility_id';

// Kullanıcı adı ve soyadı varsa onu da çekelim (Hoşgeldin mesajı için)
if (columnExists($pdo, $usersTable, 'full_name')) $selCols[] = 'full_name';

$sel = implode(',', array_map(fn($c)=>"`$c`",$selCols));
$st  = $pdo->prepare("SELECT $sel FROM `$usersTable` WHERE id=:id");
$st->execute([':id'=>$userId]);
$user = $st->fetch(PDO::FETCH_ASSOC);

if (!$user) { 
    // Oturum var ama kullanıcı silinmişse çıkış yaptır
    session_destroy();
    header("Location: login.php"); 
    exit; 
}

// ---- 6. YETKİ (ADMIN) KONTROLÜ ----
$isAdmin = false;
if ($hasRole && !empty($user['role'])) {
  $roleVal = strtolower((string)$user['role']);
  if (in_array($roleVal, ['admin','administrator','root'], true)) $isAdmin = true;
}
if (!$isAdmin && $hasIsAdmin && (int)($user['is_admin'] ?? 0) === 1) $isAdmin = true;
// Facility ID 0 veya NULL ise Admin sayılabilir (iş mantığınıza göre)
if (!$isAdmin && $hasFacility && (int)($user['facility_id'] ?? -1) === 0) $isAdmin = true;

$_SESSION['is_admin']    = $isAdmin;
$_SESSION['facility_id'] = $hasFacility ? (int)$user['facility_id'] : null;

// ---- 7. TESİS LİSTESİ ----
$facilities = [];
if (tableExists($pdo, 'facilities')) {
  if ($isAdmin) {
    $facilities = $pdo->query("SELECT id,name FROM facilities WHERE id != 0 ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
  } else {
    // user_facilities tablosu varsa oradan
    if (tableExists($pdo,'user_facilities')) {
      $uf = $pdo->prepare("
        SELECT f.id,f.name
        FROM user_facilities uf
        JOIN facilities f ON f.id=uf.facility_id
        WHERE uf.user_id=:uid AND f.id!=0
        ORDER BY f.name
      ");
      $uf->execute([':uid'=>$userId]);
      $facilities = $uf->fetchAll(PDO::FETCH_ASSOC);
    } 
    // Yoksa users tablosundaki facility_id'den
    elseif ($hasFacility && !empty($user['facility_id'])) {
      $ff = $pdo->prepare("SELECT id,name FROM facilities WHERE id=:id AND id!=0");
      $ff->execute([':id'=>(int)$user['facility_id']]);
      $one = $ff->fetch(PDO::FETCH_ASSOC);
      if ($one) $facilities = [$one];
    }
  }
}
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang) ?>">
<head>
  <meta charset="utf-8">
  <title>Labx - <?= htmlspecialchars($Lx['page_title'] ?? 'Labx') ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="bower_components/bootstrap/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="bower_components/font-awesome/css/font-awesome.min.css">
  <link rel="stylesheet" href="dist/css/main.css">
  <link rel="icon" type="image/png" sizes="32x32" href="/imgs/favicon.png">
  <link rel="icon" type="image/png" sizes="16x16" href="/imgs/favicon.png">
  <link rel="shortcut icon" href="imgs/favicon.ico">
</head>
<body>
<div id="ui" class="ui">

<header id="header" class="ui-header">
  <div class="navbar-header">
    <a href="index.php" class="navbar-brand">
      <span class="logo"><img src="imgs/pro-logo.png" width="125px"></span>
    </a>
  </div>

  <div class="navbar-collapse nav-responsive-disabled">
    <ul class="nav navbar-nav">
      <li>
        <a class="toggle-btn" data-toggle="ui-nav" href="#"><i class="fa fa-bars"></i></a>
      </li>
    </ul>

    <ul class="nav navbar-nav navbar-right">
      <li class="dropdown">
        <a href="#" class="dropdown-toggle" data-toggle="dropdown">
          <i class="fa fa-globe"></i> <?= htmlspecialchars($languages[$lang] ?? 'Türkçe') ?>
        </a>
        <ul class="dropdown-menu">
          <?php foreach ($languages as $key=>$label): ?>
            <li><a href="?lang=<?= $key ?>&db=<?= htmlspecialchars($selectedDb) ?>"><?= htmlspecialchars($label) ?></a></li>
          <?php endforeach; ?>
        </ul>
      </li>

      <li class="dropdown">
        <a href="#" class="dropdown-toggle" data-toggle="dropdown">
          <i class="fa fa-database"></i> <?= htmlspecialchars($selectedDb) ?>
        </a>
        <ul class="dropdown-menu">
          <?php foreach (['2023','2024','2025'] as $db): ?>
            <li><a href="?lang=<?= htmlspecialchars($lang) ?>&db=<?= $db ?>"><?= $db ?></a></li>
          <?php endforeach; ?>
        </ul>
      </li>

      <li class="dropdown dropdown-usermenu">
        <a href="#" class="dropdown-toggle" data-toggle="dropdown">
          <div class="user-avatar"><img src="imgs/a0.jpg" alt=""></div>
        </a>
        <ul class="dropdown-menu dropdown-menu-usermenu pull-right">
          <li><a href="logout.php"><i class="fa fa-sign-out"></i> <?= htmlspecialchars($Lx['logout'] ?? 'Çıkış') ?></a></li>
        </ul>
      </li>
    </ul>
  </div>
</header>