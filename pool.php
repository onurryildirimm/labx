<?php
require_once "db.php";
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// Dil Ayarı
$lang = $_GET['lang'] ?? 'tr';
$languages = ['tr' => 'Türkçe', 'en' => 'English', 'mk' => 'Македонски'];
?>
<?php include "language_analiz.php" ?>
<?php
// Dil ayarları
$current_texts = $texts[$lang];

// Sayfa başlığı için doğru dil anahtarı seçimi
$pageTitle = 'Havuz Suyu Analizleri'; // Default başlık
if ($lang === 'tr') {
    $pageTitle = 'Havuz Suyu Analizleri';
} elseif ($lang === 'en') {
    $pageTitle = 'Pool Water Analysis';
} elseif ($lang === 'mk') {
    $pageTitle = 'Базенска вода';
}

// Dili değiştirilmiş başlık için
$current_texts['page_title'] = $pageTitle;

// Eksik dil anahtar kontrolü
if (!isset($current_texts['no_data'])) {
    $current_texts['no_data'] = $lang === 'tr' ? 'Veri bulunamadı' : ($lang === 'en' ? 'No data found' : 'Не се пронајдени податоци');
}

if (!isset($current_texts['sample_type'])) {
    $current_texts['sample_type'] = $lang === 'tr' ? 'Numune Türü' : ($lang === 'en' ? 'Sample Type' : 'Вид на примерок');
}

if (!isset($current_texts['sample_code'])) {
    $current_texts['sample_code'] = $lang === 'tr' ? 'Numune Kodu' : ($lang === 'en' ? 'Sample Code' : 'Код на примерок');
}

if (!isset($current_texts['sample_date'])) {
    $current_texts['sample_date'] = $lang === 'tr' ? 'Numune Tarihi' : ($lang === 'en' ? 'Sample Date' : 'Датум на примерок');
}

if (!isset($current_texts['sample_person'])) {
    $current_texts['sample_person'] = $lang === 'tr' ? 'Numune Alan Kişi' : ($lang === 'en' ? 'Sample Person' : 'Лице за примерок');
}

if (!isset($current_texts['report'])) {
    $current_texts['report'] = $lang === 'tr' ? 'Rapor' : ($lang === 'en' ? 'Report' : 'Извештај');
}

if (!isset($current_texts['view_report'])) {
    $current_texts['view_report'] = $lang === 'tr' ? 'Raporu Görüntüle' : ($lang === 'en' ? 'View Report' : 'Преглед на извештај');
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$userId = $_SESSION["user_id"] ?? 0;
$facilityId = $_SESSION["facility_id"] ?? 0;
$stmt = $pdo->prepare("SELECT username FROM users WHERE id = :id");
$stmt->execute(["id" => $userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Admin kontrolü
$isAdmin = ($facilityId == 0);

// Veritabanı Seçimi (Yıl bazlı filtreleme)
$databases = ['2023', '2024', '2025'];
$selectedDb = $_GET['db'] ?? date('Y'); // Varsayılan olarak güncel yıl

// Kullanıcının tesislerini al
if ($isAdmin) {
    $stmt = $pdo->prepare("SELECT id, name FROM facilities WHERE id != 0 ORDER BY name");
    $stmt->execute();
} else {
    $stmt = $pdo->prepare("
        SELECT f.id, f.name 
        FROM facilities f
        INNER JOIN user_facilities uf ON uf.facility_id = f.id
        WHERE uf.user_id = :user_id
    ");
    $stmt->execute(["user_id" => $userId]);
}
$facilities = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Seçilen tesisin ID'sini al
$selectedFacility = $_GET['facility_id'] ?? null;

// Hangi dil sütunu kullanılacak
$sampleColumn = "`numune-" . $lang . "`";
$locationColumn = "`yer-" . $lang . "`";

// Seçilen tesisin verilerini listele (Yıl bazlı filtreleme)
if ($selectedFacility) {
    $stmt = $pdo->prepare("
        SELECT 
            id, 
            numune_kodu, 
            numune_turu, 
            report_path, 
            $sampleColumn AS sample_name, 
            $locationColumn AS location_name,
            alim_tarihi,
            alan_kisi,
            analiz_tarihi,
            analiz_raporu_tarihi,
            uploaded_at,
            updated_at
        FROM numune_raporlari 
        WHERE facility_id = :facility_id AND
        numune_turu = 'Havuz Suyu'
        AND YEAR(uploaded_at) = :selected_year
        ORDER BY uploaded_at DESC
    ");
    $stmt->execute([
        "facility_id" => $selectedFacility,
        "selected_year" => $selectedDb
    ]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Her numune için rapor dosyalarını al
    foreach ($results as &$result) {
        // report_path'de birden fazla PDF olup olmadığını kontrol et
        $reportPaths = !empty($result['report_path']) ? explode('|', $result['report_path']) : [];
        $result['report_paths'] = $reportPaths;
    }
} else {
    $results = [];
}
?>


<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Labx - <?= $current_texts['page_title'] ?? 'Havuz Suyu Analizleri' ?></title>
    <link rel="stylesheet" href="bower_components/bootstrap/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="bower_components/font-awesome/css/font-awesome.min.css">
    <link rel="stylesheet" href="bower_components/datatables.net-bs/css/dataTables.bootstrap.min.css">
    <link rel="stylesheet" href="dist/css/main.css">
    <script src="assets/js/modernizr-custom.js"></script>
    <!-- Favicon -->
    <link rel="icon" type="image/png" sizes="32x32" href="/imgs/favicon.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/imgs/favicon.png">
    <link rel="shortcut icon" href="imgs/favicon.ico">
    <style>
        .reports-container {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
        }
        .pdf-icon {
            margin-right: 5px;
            display: inline-block;
        }
    </style>
</head>
<body>
<div id="ui" class="ui">
    <header id="header" class="ui-header">
        <div class="navbar-header">
            <a href="index.php" class="navbar-brand">
                <span class="logo"><img src="imgs/labx.png" width="100px"></span>
            </a>
        </div>
        <div class="navbar-collapse nav-responsive-disabled">
            <ul class="nav navbar-nav">
                <li>
                    <a class="toggle-btn" data-toggle="ui-nav" href="#">
                        <i class="fa fa-bars"></i>
                    </a>
                </li>
            </ul>
            <ul class="nav navbar-nav navbar-right">
                <li class="dropdown">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                        <i class="fa fa-globe"></i> <?= $languages[$lang] ?>
                    </a>
                    <ul class="dropdown-menu">
                        <?php foreach ($languages as $key => $language): ?>
                            <li><a href="?lang=<?= $key ?>&db=<?= $selectedDb ?>&facility_id=<?= $selectedFacility ?>"><?= $language ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </li>
                <li class="dropdown">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                        <i class="fa fa-database"></i> <?= $selectedDb ?>
                    </a>
                    <ul class="dropdown-menu">
                        <?php foreach ($databases as $db): ?>
                            <li><a href="?lang=<?= $lang ?>&db=<?= $db ?>&facility_id=<?= $selectedFacility ?>"><?= $db ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </li>
                <li class="dropdown dropdown-usermenu">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                        <div class="user-avatar"><img src="imgs/a0.jpg" alt="..."></div>
                        <span class="hidden-xs"><?= htmlspecialchars($user['username'] ?? '') ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-usermenu pull-right">
                        <li><a href="settings.php"><i class="fa fa-cogs"></i> <?= $current_texts['settings'] ?></a></li>
                        <li><a href="logout.php"><i class="fa fa-sign-out"></i> <?= $current_texts['logout'] ?></a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </header>
    <!--sidebar start-->
    <?php include "sidebar.php" ?>
    <!--sidebar end-->
    <div id="content" class="ui-content">
        <div class="ui-content-body">
            <div class="ui-container">
                <div class="row">
                    <div class="col-md-12">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <h3 class="panel-title"><?= $current_texts['page_title'] ?? 'Havuz Suyu Analizleri' ?></h3>
                            </div>
                            <div class="panel-body">
                                <form method="GET" action="pool.php" class="form-inline mb-4">
                                    <input type="hidden" name="lang" value="<?= htmlspecialchars($lang) ?>">
                                    <input type="hidden" name="db" value="<?= htmlspecialchars($selectedDb) ?>">
                                    <div class="form-group">
                                        <label for="facilitySelect" class="mr-2"><?= $current_texts['facility_select'] ?>:</label>
                                        <select name="facility_id" id="facilitySelect" class="form-control" onchange="this.form.submit()">
                                            <option value=""><?= $current_texts['facility_select'] ?></option>
                                            <?php foreach ($facilities as $facility): ?>
                                                <option value="<?= $facility['id'] ?>" <?= $selectedFacility == $facility['id'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($facility['name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </form>
                                <br>
                                <div class="table-responsive">
                                    <table id="datatable" class="table table-striped table-bordered">
                                        <thead>
                                            <tr>
                                                <th width="3%">#</th>
                                                <th width="10%"><?= $current_texts['sample_type'] ?? 'Numune Türü' ?></th>
                                                <th width="10%"><?= $current_texts['sample_code'] ?? 'Numune Kodu' ?></th>
                                                <th width="13%"><?= $current_texts['sample_name'] ?? 'Numune' ?></th>
                                                <th width="13%"><?= $current_texts['location'] ?? 'Lokasyon' ?></th>
                                                <th width="10%"><?= $current_texts['sample_date'] ?? 'Alım Tarihi' ?></th>
                                                <th width="10%"><?= $current_texts['sample_person'] ?? 'Alan Kişi' ?></th>
                                                <th width="10%"><?= $current_texts['analysis_report_date'] ?? 'Rapor Tarihi' ?></th>
                                                <th width="6%"><?= $current_texts['report'] ?? 'Rapor' ?></th>
                                                <th width="10%"><?= $current_texts['upload_date'] ?? 'Yükleme Tarihi' ?></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($results)): ?>
                                                <tr>
                                                    <td colspan="10" class="text-center"><?= $current_texts['no_data'] ?? 'Veri bulunamadı' ?></td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($results as $index => $result): ?>
                                                    <?php 
                                                        // Rapor sayısı
                                                        $reportCount = count($result['report_paths']);
                                                    ?>
                                                    <tr>
                                                        <td><?= $index + 1 ?></td>
                                                        <td><?= htmlspecialchars($result['numune_turu'] ?? '') ?></td>
                                                        <td><?= htmlspecialchars($result['numune_kodu'] ?? '') ?></td>
                                                        <td><?= htmlspecialchars($result['sample_name'] ?? '') ?></td>
                                                        <td><?= htmlspecialchars($result['location_name'] ?? '') ?></td>
                                                        <td><?= !empty($result['alim_tarihi']) ? date('d.m.Y', strtotime($result['alim_tarihi'])) : '-' ?></td>
                                                        <td><?= htmlspecialchars($result['alan_kisi'] ?? '') ?></td>
                                                        <td><?= !empty($result['analiz_raporu_tarihi']) ? date('d.m.Y', strtotime($result['analiz_raporu_tarihi'])) : '-' ?></td>
                                                        <td class="text-center">
                                                            <?php if ($reportCount > 0): ?>
                                                                <div class="reports-container">
                                                                    <?php foreach ($result['report_paths'] as $reportIndex => $path): ?>
                                                                        <a href="<?= htmlspecialchars($path) ?>" target="_blank" class="pdf-icon btn btn-xs btn-danger" title="<?= $current_texts['view_report'] ?? 'Raporu Görüntüle' ?> #<?= $reportIndex + 1 ?>">
                                                                            <i class="fa fa-file-pdf-o"></i> <?= $reportIndex + 1 ?>
                                                                        </a>
                                                                    <?php endforeach; ?>
                                                                </div>
                                                            <?php else: ?>
                                                                <span class="text-muted">-</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td><?= !empty($result['uploaded_at']) ? date('d.m.Y H:i', strtotime($result['uploaded_at'])) : '-' ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <footer id="footer" class="ui-footer">
        <?= $current_texts['footer'] ?>
    </footer>
</div>
<script src="bower_components/jquery/dist/jquery.min.js"></script>
<script src="bower_components/bootstrap/dist/js/bootstrap.min.js"></script>
<script src="bower_components/datatables.net/js/jquery.dataTables.min.js"></script>
<script src="bower_components/datatables.net-bs/js/dataTables.bootstrap.min.js"></script>
<script>
    $(document).ready(function () {
        $('#datatable').DataTable({
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.10.25/i18n/<?= $lang === 'tr' ? 'Turkish' : ($lang === 'mk' ? 'Macedonian' : 'English') ?>.json",
                "emptyTable": "<?= $current_texts['no_data'] ?? 'Veri bulunamadı' ?>",
                "info": "<?= $lang === 'tr' ? '_TOTAL_ kayıttan _START_ - _END_ arası gösteriliyor' : 
                           ($lang === 'en' ? 'Showing _START_ to _END_ of _TOTAL_ entries' : 
                           'Приказ _START_ до _END_ од вкупно _TOTAL_ записи') ?>",
                "infoEmpty": "<?= $lang === 'tr' ? 'Gösterilecek kayıt bulunamadı' : 
                               ($lang === 'en' ? 'No entries to show' : 
                               'Нема записи за приказ') ?>",
                "search": "<?= $lang === 'tr' ? 'Ara:' : 
                            ($lang === 'en' ? 'Search:' : 
                            'Пребарај:') ?>",
                "zeroRecords": "<?= $lang === 'tr' ? 'Eşleşen kayıt bulunamadı' : 
                                 ($lang === 'en' ? 'No matching records found' : 
                                 'Не се пронајдени записи што се совпаѓаат') ?>"
            },
            "order": [[9, "desc"]], // Yükleme tarihine göre sırala
            "pageLength": 25, // Sayfa başına 25 kayıt göster
            "responsive": true
        });
    });
</script>
<script src="bower_components/jquery.nicescroll/dist/jquery.nicescroll.min.js"></script>
<script src="bower_components/autosize/dist/autosize.min.js"></script>
<script src="dist/js/main.js"></script>
</body>
</html>