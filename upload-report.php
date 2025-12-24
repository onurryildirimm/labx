<?php
ob_start(); // Çıktı tamponlaması başlat
require_once "db.php";
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Debug bilgisi
echo "<!-- Debug başlangıcı -->";

// Dil Ayarı
$lang = $_GET['lang'] ?? 'tr';
$languages = ['tr' => 'Türkçe', 'en' => 'English', 'mk' => 'Македонски'];

?>

<?php include "language_analiz.php" ?>

<?php
$current_texts = $texts[$lang];

// Dil dosyası kontrolü
if (!isset($texts) || !isset($texts[$lang])) {
    die("Dil dosyası yüklenemedi: texts veya texts[$lang] tanımlı değil");
}

// sample_types kontrolü
if (!isset($current_texts['sample_types'])) {
    echo "<!-- sample_types anahtarı bulunamadı -->";
    $current_texts['sample_types'] = [
        'Su' => 'Su',
        'Gıda' => 'Gıda',
        'Swab' => 'Swab',
        'Çevresel' => 'Çevresel',
        'Legionella' => 'Legionella',
        'Havuz Suyu' => 'Havuz Suyu',
        'Atık Su' => 'Atık Su'
    ];
}

// Eksik dil tanımlarını ekle
if (!isset($current_texts['update_report'])) {
    $current_texts['update_report'] = $lang === 'tr' ? 'Güncelle' : ($lang === 'en' ? 'Update' : 'Ажурирај');
}

if (!isset($current_texts['update_success'])) {
    $current_texts['update_success'] = $lang === 'tr' ? 'Güncelleme başarılı' : ($lang === 'en' ? 'Update successful' : 'Ажурирањето е успешно');
}

if (!isset($current_texts['multiple_reports'])) {
    $current_texts['multiple_reports'] = $lang === 'tr' ? 'Raporlar' : ($lang === 'en' ? 'Reports' : 'Извештаи');
}

if (!isset($current_texts['add_report'])) {
    $current_texts['add_report'] = $lang === 'tr' ? 'Rapor Ekle' : ($lang === 'en' ? 'Add Report' : 'Додај извештај');
}

if (!isset($current_texts['report_uploaded'])) {
    $current_texts['report_uploaded'] = $lang === 'tr' ? 'Rapor Yüklendi' : ($lang === 'en' ? 'Report Uploaded' : 'Извештајот е поставен');
}

if (!isset($current_texts['no_samples'])) {
    $current_texts['no_samples'] = $lang === 'tr' ? 'Bu tesiste rapor eklenecek numune bulunmamaktadır.' : 
                             ($lang === 'en' ? 'There are no samples to add reports in this facility.' :
                              'Нема примероци за додавање извештаи во овој објект.');
}

// Kullanıcı Bilgileri
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION["user_id"])) {
    die("Oturum bilgisi bulunamadı. Lütfen giriş yapın.");
}

$userId = $_SESSION["user_id"];
$facilityId = $_SESSION["facility_id"];
$stmt = $pdo->prepare("SELECT username FROM users WHERE id = :id");
$stmt->execute(["id" => $userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Admin kontrolü
$isAdmin = ($facilityId == 0);

// Veritabanı Seçimi (Yıl bazlı filtreleme)
$databases = ['2023', '2024', '2025'];
$selectedDb = $_GET['db'] ?? '2025';

// Seçilen numune türü
$selectedSampleType = $_GET['sample_type'] ?? '';

// Kullanıcının tesislerini al
if ($isAdmin) {
    $stmt = $pdo->prepare("SELECT id, name FROM facilities WHERE id != 0 ORDER BY name");
} else {
    $stmt = $pdo->prepare("
        SELECT f.id, f.name 
        FROM facilities f
        INNER JOIN user_facilities uf ON uf.facility_id = f.id
        WHERE uf.user_id = :user_id
    ");
    $stmt->execute(["user_id" => $userId]);
}
$stmt->execute();
$facilities = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Seçilen tesisin ID'sini al
$selectedFacility = $_GET['facility_id'] ?? null;

// Hangi dil sütunu kullanılacak
$sampleColumn = "`numune-" . $lang . "`";
$locationColumn = "`yer-" . $lang . "`";

// AJAX isteği ile rapor güncelleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_update'])) {
    header('Content-Type: application/json');
    
    $numune_id = $_POST['numune_id'] ?? null;
    $analiz_tarihi = $_POST['analiz_tarihi'] ?? null;
    $analiz_raporu_tarihi = $_POST['analiz_raporu_tarihi'] ?? null;
    
    if (!$numune_id || !$analiz_tarihi || !$analiz_raporu_tarihi) {
        echo json_encode(['success' => false, 'message' => 'Gerekli alanlar eksik']);
        exit;
    }
    
    try {
        $stmt = $pdo->prepare("
            UPDATE numune_raporlari 
            SET 
                analiz_tarihi = :analiz_tarihi,
                analiz_raporu_tarihi = :analiz_raporu_tarihi
            WHERE id = :id
        ");
        
        $stmt->execute([
            ':id' => $numune_id,
            ':analiz_tarihi' => $analiz_tarihi,
            ':analiz_raporu_tarihi' => $analiz_raporu_tarihi
        ]);
        
        echo json_encode(['success' => true, 'message' => $current_texts['update_success']]);
        exit;
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => "Veritabanı güncelleme hatası: " . $e->getMessage()]);
        exit;
    }
}

// Numuneleri listele
$results = [];
$sampleTypes = [];

if ($selectedFacility) {
    try {
        // Önce tesis için mevcut numune türlerini al
        $stmt = $pdo->prepare("
            SELECT DISTINCT numune_turu 
            FROM numune_raporlari 
            WHERE facility_id = :facility_id 
            AND YEAR(alim_tarihi) = :selected_year
            ORDER BY numune_turu
        ");
        
        $stmt->execute([
            "facility_id" => $selectedFacility,
            "selected_year" => $selectedDb
        ]);
        
        $sampleTypes = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Numune türü seçilmemişse, ilk türü varsayılan olarak seç
        if (empty($selectedSampleType) && !empty($sampleTypes)) {
            $selectedSampleType = $sampleTypes[0];
        }
        
        // Numuneleri al
        $sql = "
            SELECT 
                nr.id, 
                nr.facility_id, 
                nr.numune_turu,
                nr.numune_kodu, 
                $sampleColumn AS numune_adi, 
                $locationColumn AS yer_adi,
                nr.alim_tarihi, 
                nr.alan_kisi,
                nr.report_path,
                nr.analiz_tarihi,
                nr.analiz_raporu_tarihi
            FROM numune_raporlari nr
            WHERE nr.facility_id = :facility_id 
            AND YEAR(nr.alim_tarihi) = :selected_year
        ";
        
        // Eğer numune türü seçilmişse, filtreleme yap
        if (!empty($selectedSampleType)) {
            $sql .= " AND nr.numune_turu = :numune_turu";
        }
        
        $sql .= " ORDER BY nr.alim_tarihi DESC";
        
        $stmt = $pdo->prepare($sql);
        
        $params = [
            "facility_id" => $selectedFacility,
            "selected_year" => $selectedDb
        ];
        
        if (!empty($selectedSampleType)) {
            $params["numune_turu"] = $selectedSampleType;
        }
        
        $stmt->execute($params);
        
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Her numune için rapor dosyalarını al
        foreach ($results as &$result) {
            // report_path'de birden fazla PDF olup olmadığını kontrol et
            $reportPaths = !empty($result['report_path']) ? explode('|', $result['report_path']) : [];
            $result['report_paths'] = $reportPaths;
        }
        
        // Debug bilgisi
        echo "<!-- Sorgu çalıştı, " . count($results) . " kayıt bulundu -->";
        
    } catch (PDOException $e) {
        // Hata mesajını görünür yap
        die("Veritabanı hatası: " . $e->getMessage());
    }
}

// Rapor yükleme işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_report'])) {
    $numune_id = $_POST['numune_id'] ?? null;
    $analiz_tarihi = $_POST['analiz_tarihi'] ?? null;
    $analiz_raporu_tarihi = $_POST['analiz_raporu_tarihi'] ?? null;
    
    if ($numune_id && $analiz_tarihi && $analiz_raporu_tarihi && 
        isset($_FILES['report_file']) && $_FILES['report_file']['error'][0] === UPLOAD_ERR_OK) {
        
        $uploadDir = 'uploads/reports/';
        
        // Klasör yoksa oluştur
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        // Mevcut rapor yollarını al
        $stmt = $pdo->prepare("SELECT report_path FROM numune_raporlari WHERE id = :id");
        $stmt->execute([':id' => $numune_id]);
        $existingReport = $stmt->fetch(PDO::FETCH_ASSOC);
        $existingPaths = !empty($existingReport['report_path']) ? explode('|', $existingReport['report_path']) : [];
        
        $uploadedFiles = [];
        $fileCount = count($_FILES['report_file']['name']);
        
        for ($i = 0; $i < $fileCount; $i++) {
            if ($_FILES['report_file']['error'][$i] === UPLOAD_ERR_OK) {
                // Benzersiz dosya adı oluştur
                $fileName = uniqid('report_') . '_' . basename($_FILES['report_file']['name'][$i]);
                $filePath = $uploadDir . $fileName;
                
                // Dosyayı yükle
                if (move_uploaded_file($_FILES['report_file']['tmp_name'][$i], $filePath)) {
                    $uploadedFiles[] = $filePath;
                }
            }
        }
        
        if (!empty($uploadedFiles)) {
            // Yeni ve mevcut dosya yollarını birleştir
            $allPaths = array_merge($existingPaths, $uploadedFiles);
            $reportPathsStr = implode('|', $allPaths);
            
            // Veritabanını güncelle
            try {
                $stmt = $pdo->prepare("
                    UPDATE numune_raporlari 
                    SET 
                        report_path = :report_path,
                        analiz_tarihi = :analiz_tarihi,
                        analiz_raporu_tarihi = :analiz_raporu_tarihi
                    WHERE id = :id
                ");
                
                $stmt->execute([
                    ':id' => $numune_id,
                    ':report_path' => $reportPathsStr,
                    ':analiz_tarihi' => $analiz_tarihi,
                    ':analiz_raporu_tarihi' => $analiz_raporu_tarihi
                ]);
                
                $successMessage = $current_texts['report_upload_success'] ?? 'Rapor başarıyla yüklendi.';
                
                // Numune türünü koruyarak sayfayı yenile
                header("Location: upload-report.php?lang=$lang&db=$selectedDb&facility_id=$selectedFacility&sample_type=$selectedSampleType&success=1");
                exit;
                
            } catch (PDOException $e) {
                $errorMessage = "Veritabanı güncelleme hatası: " . $e->getMessage();
            }
        } else {
            $errorMessage = $current_texts['report_upload_error'] ?? 'Rapor yüklenirken bir hata oluştu.';
        }
    } else {
        $errorMessage = $current_texts['report_upload_error_fields'] ?? 'Lütfen tüm alanları doldurun ve bir dosya seçin.';
    }
}
?>

<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Labx - <?= $current_texts['report_upload_title'] ?? 'Rapor Yükleme' ?></title>
    <link rel="stylesheet" href="bower_components/bootstrap/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="bower_components/font-awesome/css/font-awesome.min.css">
    <link rel="stylesheet" href="bower_components/datatables.net-bs/css/dataTables.bootstrap.min.css">
    <link rel="stylesheet" href="dist/css/main.css">
    <!-- Favicon -->
    <link rel="icon" type="image/png" sizes="32x32" href="/imgs/favicon.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/imgs/favicon.png">
    <link rel="shortcut icon" href="imgs/favicon.ico">
    <style>
        .mb-20 {
            margin-bottom: 20px;
        }
        .pdf-icon {
            margin-right: 5px;
            display: inline-block;
        }
        .reports-container {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
        }
        .alert-fixed {
            position: fixed;
            top: 10px;
            right: 10px;
            width: 300px;
            z-index: 9999;
        }
        .sample-type-nav {
            margin-bottom: 20px;
        }
        .sample-type-nav .nav-pills > li.active > a {
            background-color: #337ab7;
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
                        <i class="fa fa-globe"></i> <?= $languages[$lang] ?? $lang ?>
                    </a>
                    <ul class="dropdown-menu">
                        <?php foreach ($languages as $key => $language): ?>
                            <li><a href="?lang=<?= $key ?>&db=<?= $selectedDb ?>&facility_id=<?= $selectedFacility ?>&sample_type=<?= $selectedSampleType ?>"><?= $language ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </li>
                <li class="dropdown">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                        <i class="fa fa-database"></i> <?= $current_texts['select_db'] ?? 'Veritabanı Seç' ?>
                    </a>
                    <ul class="dropdown-menu">
                        <?php foreach ($databases as $db): ?>
                            <li><a href="?lang=<?= $lang ?>&db=<?= $db ?>&facility_id=<?= $selectedFacility ?>&sample_type=<?= $selectedSampleType ?>"><?= $db ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </li>
                <li class="dropdown dropdown-usermenu">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                        <div class="user-avatar"><img src="imgs/a0.jpg" alt="..."></div>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-usermenu pull-right">
                        <li><a href="settings.php"><i class="fa fa-cogs"></i> <?= $current_texts['settings'] ?? 'Ayarlar' ?></a></li>
                        <li><a href="logout.php"><i class="fa fa-sign-out"></i> <?= $current_texts['logout'] ?? 'Çıkış Yap' ?></a></li>
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
                <div id="ajax-alert" style="display: none;" class="alert alert-fixed">
                    <span id="ajax-message"></span>
                </div>
                
                <div class="row">
                    <div class="col-md-12">
                        <?php if (isset($successMessage)): ?>
                            <div class="alert alert-success">
                                <?= $successMessage ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (isset($errorMessage)): ?>
                            <div class="alert alert-danger">
                                <?= $errorMessage ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
                            <div class="alert alert-success">
                                <?= $current_texts['report_upload_success'] ?? 'Rapor başarıyla yüklendi.' ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <form method="GET" action="upload-report.php">
                    <input type="hidden" name="lang" value="<?= htmlspecialchars($lang) ?>">
                    <input type="hidden" name="db" value="<?= htmlspecialchars($selectedDb) ?>">
                    <input type="hidden" name="sample_type" value="<?= htmlspecialchars($selectedSampleType) ?>">
                    <div class="form-group">
                        <label for="facilitySelect"><?= $current_texts['facility_select'] ?? 'Tesis Seç' ?>:</label>
                        <select name="facility_id" id="facilitySelect" class="form-control" onchange="this.form.submit()">
                            <option value=""><?= $current_texts['select'] ?? 'Seçiniz' ?></option>
                            <?php foreach ($facilities as $facility): ?>
                                <option value="<?= $facility['id'] ?>" <?= $facility['id'] == $selectedFacility ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($facility['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>
                
                <?php if ($selectedFacility && !empty($sampleTypes)): ?>
                    <!-- Numune türü sekmeleri -->
                    <div class="sample-type-nav">
                        <ul class="nav nav-pills">
                            <?php foreach ($sampleTypes as $sampleType): ?>
                                <?php 
                                    // Numune türünün çevirisini al
                                    $sampleTypeTr = $sampleType;
                                    if (isset($current_texts['sample_types']) && 
                                        is_array($current_texts['sample_types']) && 
                                        isset($current_texts['sample_types'][$sampleType])) {
                                        $sampleTypeTr = $current_texts['sample_types'][$sampleType];
                                    }
                                ?>
                                <li class="<?= $sampleType === $selectedSampleType ? 'active' : '' ?>">
                                    <a href="?lang=<?= $lang ?>&db=<?= $selectedDb ?>&facility_id=<?= $selectedFacility ?>&sample_type=<?= $sampleType ?>">
                                        <?= htmlspecialchars($sampleTypeTr) ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <?php if (empty($results)): ?>
                    <div class="alert alert-info">
                        <?= $current_texts['no_samples'] ?? 'Bu tesiste rapor eklenecek numune bulunmamaktadır.' ?>
                    </div>
                <?php else: ?>
                    <table id="datatable" class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th><?= $current_texts['no'] ?? 'No' ?></th>
                                <th><?= $current_texts['facility_select'] ?? 'Tesis Seç' ?></th>
                                <th><?= $current_texts['sample_type'] ?? 'Numune Türü' ?></th>
                                <th><?= $current_texts['sample_code'] ?? 'Numune Kodu' ?></th>
                                <th><?= $current_texts['sample_name'] ?? 'Numune Adı' ?></th>
                                <th><?= $current_texts['location'] ?? 'Alındığı Yer/Parti' ?></th>
                                <th><?= $current_texts['sample_date'] ?? 'Numune Alım/Teslim Tarihi' ?></th>
                                <th><?= $current_texts['sample_person'] ?? 'Numune Alan/Teslim Eden' ?></th>
                                <th><?= $current_texts['analysis_date'] ?? 'Analiz Tarihi' ?></th>
                                <th><?= $current_texts['analysis_report_date'] ?? 'Analiz Raporu Tarihi' ?></th>
                                <th><?= $current_texts['report'] ?? 'Rapor' ?></th>
                                <th><?= $current_texts['actions'] ?? 'İşlemler' ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($results as $index => $result): ?>
                                <?php 
                                    // Tesis adını bul
                                    $facilityName = "";
                                    foreach ($facilities as $facility) {
                                        if ($facility['id'] == $result['facility_id']) {
                                            $facilityName = $facility['name'];
                                            break;
                                        }
                                    }
                                    
                                    // Numune türünün çevirisini al
                                    $numuneTuru = $result['numune_turu'];
                                    $numuneTuruCeviri = $numuneTuru;
                                    if (isset($current_texts['sample_types']) && 
                                        is_array($current_texts['sample_types']) && 
                                        isset($current_texts['sample_types'][$numuneTuru])) {
                                        $numuneTuruCeviri = $current_texts['sample_types'][$numuneTuru];
                                    }
                                    
                                    // Rapor sayısı
                                    $reportCount = count($result['report_paths']);
                                ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td><?= htmlspecialchars($facilityName) ?></td>
                                    <td><?= htmlspecialchars($numuneTuruCeviri) ?></td>
                                    <td><?= htmlspecialchars($result['numune_kodu']) ?></td>
                                    <td><?= htmlspecialchars($result['numune_adi']) ?></td>
                                    <td><?= htmlspecialchars($result['yer_adi']) ?></td>
                                    <td><?= htmlspecialchars($result['alim_tarihi']) ?></td>
                                    <td><?= htmlspecialchars($result['alan_kisi']) ?></td>
                                    <td><?= htmlspecialchars($result['analiz_tarihi'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($result['analiz_raporu_tarihi'] ?? '') ?></td>
                                    <td>
                                        <?php if ($reportCount > 0): ?>
                                            <div class="reports-container">
                                                <?php foreach ($result['report_paths'] as $index => $path): ?>
                                                    <a href="<?= htmlspecialchars($path) ?>" target="_blank" class="pdf-icon btn btn-xs btn-info" title="<?= $current_texts['view_report'] ?? 'Raporu Görüntüle' ?> #<?= $index + 1 ?>">
                                                        <i class="fa fa-file-pdf-o"></i> <?= $index + 1 ?>
                                                    </a>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-xs btn-primary btn-upload <?= $reportCount > 0 ? 'has-reports' : '' ?>" data-id="<?= $result['id'] ?>" data-numune="<?= htmlspecialchars($result['numune_adi']) ?>" data-analiz-tarihi="<?= htmlspecialchars($result['analiz_tarihi'] ?? '') ?>" data-analiz-raporu-tarihi="<?= htmlspecialchars($result['analiz_raporu_tarihi'] ?? '') ?>">
                                                <i class="fa fa-upload"></i> <?= $reportCount > 0 ? ($current_texts['add_report'] ?? 'Rapor Ekle') : ($current_texts['upload_report'] ?? 'Rapor Yükle') ?>
                                            </button>
                                            
                                            <?php if ($reportCount > 0): ?>
                                                <button type="button" class="btn btn-xs btn-warning btn-update" data-id="<?= $result['id'] ?>" data-numune="<?= htmlspecialchars($result['numune_adi']) ?>" data-analiz-tarihi="<?= htmlspecialchars($result['analiz_tarihi'] ?? '') ?>" data-analiz-raporu-tarihi="<?= htmlspecialchars($result['analiz_raporu_tarihi'] ?? '') ?>">
                                                    <i class="fa fa-pencil"></i> <?= $current_texts['update_report'] ?? 'Güncelle' ?>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <footer id="footer" class="ui-footer">
        <?= $current_texts['footer'] ?? '2025 &copy; Labx by Vektraweb.' ?>
    </footer>
</div>

<!-- Rapor Yükleme Modal -->
<div class="modal fade" id="uploadReportModal" tabindex="-1" role="dialog" aria-labelledby="uploadReportModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="uploadReportModalLabel"><?= $current_texts['upload_report'] ?? 'Rapor Yükle' ?></h4>
            </div>
            <form action="upload-report.php?lang=<?= $lang ?>&db=<?= $selectedDb ?>&facility_id=<?= $selectedFacility ?>&sample_type=<?= $selectedSampleType ?>" method="post" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="numune_id" id="numune_id">
                    
                    <div class="form-group">
                        <label for="numune_info"><?= $current_texts['sample_info'] ?? 'Numune Bilgisi' ?>:</label>
                        <input type="text" class="form-control" id="numune_info" readonly>
                    </div>
                    
                    <div class="form-group">
                        <label for="analiz_tarihi"><?= $current_texts['analysis_date'] ?? 'Analiz Tarihi' ?>:</label>
                        <input type="date" name="analiz_tarihi" id="analiz_tarihi" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="analiz_raporu_tarihi"><?= $current_texts['analysis_report_date'] ?? 'Analiz Raporu Tarihi' ?>:</label>
                        <input type="date" name="analiz_raporu_tarihi" id="analiz_raporu_tarihi" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="report_file"><?= $current_texts['report_file'] ?? 'Rapor Dosyası' ?> (PDF):</label>
                        <input type="file" name="report_file[]" id="report_file" class="form-control" accept="application/pdf" required multiple>
                        <small class="text-muted"><?= $lang === 'tr' ? 'Birden fazla dosya seçmek için CTRL tuşuna basılı tutarak seçim yapabilirsiniz.' : ($lang === 'en' ? 'Hold the CTRL key to select multiple files.' : 'Држете го копчето CTRL за да изберете повеќе датотеки.') ?></small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal"><?= $current_texts['cancel'] ?? 'İptal' ?></button>
                    <button type="submit" name="upload_report" class="btn btn-primary"><?= $current_texts['upload'] ?? 'Yükle' ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Rapor Güncelleme Modal -->
<div class="modal fade" id="updateReportModal" tabindex="-1" role="dialog" aria-labelledby="updateReportModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="updateReportModalLabel"><?= $current_texts['update_report'] ?? 'Rapor Güncelle' ?></h4>
            </div>
            <form id="updateReportForm">
                <div class="modal-body">
                    <input type="hidden" name="numune_id" id="update_numune_id">
                    
                    <div class="form-group">
                        <label for="update_numune_info"><?= $current_texts['sample_info'] ?? 'Numune Bilgisi' ?>:</label>
                        <input type="text" class="form-control" id="update_numune_info" readonly>
                    </div>
                    
                    <div class="form-group">
                        <label for="update_analiz_tarihi"><?= $current_texts['analysis_date'] ?? 'Analiz Tarihi' ?>:</label>
                        <input type="date" name="analiz_tarihi" id="update_analiz_tarihi" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="update_analiz_raporu_tarihi"><?= $current_texts['analysis_report_date'] ?? 'Analiz Raporu Tarihi' ?>:</label>
                        <input type="date" name="analiz_raporu_tarihi" id="update_analiz_raporu_tarihi" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal"><?= $current_texts['cancel'] ?? 'İptal' ?></button>
                    <button type="submit" class="btn btn-warning"><?= $current_texts['update_report'] ?? 'Güncelle' ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="bower_components/jquery/dist/jquery.min.js"></script>
<script src="bower_components/bootstrap/dist/js/bootstrap.min.js"></script>
<script src="bower_components/datatables.net/js/jquery.dataTables.min.js"></script>
<script src="bower_components/datatables.net-bs/js/dataTables.bootstrap.min.js"></script>
<script>
    $(document).ready(function() {
        try {
            // DataTable başlat
            var dataTable = $('#datatable').DataTable({
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.10.25/i18n/<?= $lang === 'tr' ? 'Turkish' : ($lang === 'mk' ? 'Macedonian' : 'English') ?>.json",
                    "emptyTable": "<?= $current_texts['no_samples'] ?? 'Bu tesiste rapor eklenecek numune bulunmamaktadır.' ?>",
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
                "order": [[0, "desc"]],
                "pageLength": 25,
                "responsive": true
            });
            
            // Rapor yükleme butonu
            $('.btn-upload').on('click', function() {
                var numune_id = $(this).data('id');
                var numune_info = $(this).data('numune');
                var analiz_tarihi = $(this).data('analiz-tarihi');
                var analiz_raporu_tarihi = $(this).data('analiz-raporu-tarihi');
                
                $('#numune_id').val(numune_id);
                $('#numune_info').val(numune_info);
                
                // Tarih alanlarını doldur (eğer varsa)
                if (analiz_tarihi) {
                    $('#analiz_tarihi').val(formatDateForInput(analiz_tarihi));
                } else {
                    $('#analiz_tarihi').val('<?= date('Y-m-d') ?>');
                }
                
                if (analiz_raporu_tarihi) {
                    $('#analiz_raporu_tarihi').val(formatDateForInput(analiz_raporu_tarihi));
                } else {
                    $('#analiz_raporu_tarihi').val('<?= date('Y-m-d') ?>');
                }
                
                // Modal başlığını güncelle
                var hasReports = $(this).hasClass('has-reports');
                if (hasReports) {
                    $('#uploadReportModalLabel').text('<?= $current_texts['add_report'] ?? 'Rapor Ekle' ?>');
                } else {
                    $('#uploadReportModalLabel').text('<?= $current_texts['upload_report'] ?? 'Rapor Yükle' ?>');
                }
                
                $('#uploadReportModal').modal('show');
            });
            
            // Rapor güncelleme butonu
            $('.btn-update').on('click', function() {
                var numune_id = $(this).data('id');
                var numune_info = $(this).data('numune');
                var analiz_tarihi = $(this).data('analiz-tarihi');
                var analiz_raporu_tarihi = $(this).data('analiz-raporu-tarihi');
                
                $('#update_numune_id').val(numune_id);
                $('#update_numune_info').val(numune_info);
                
                // Tarih alanlarını doldur
                if (analiz_tarihi) {
                    $('#update_analiz_tarihi').val(formatDateForInput(analiz_tarihi));
                }
                
                if (analiz_raporu_tarihi) {
                    $('#update_analiz_raporu_tarihi').val(formatDateForInput(analiz_raporu_tarihi));
                }
                
                $('#updateReportModal').modal('show');
            });
            
            // AJAX ile güncelleme formunu gönder
            $('#updateReportForm').on('submit', function(e) {
                e.preventDefault();
                
                var numune_id = $('#update_numune_id').val();
                var analiz_tarihi = $('#update_analiz_tarihi').val();
                var analiz_raporu_tarihi = $('#update_analiz_raporu_tarihi').val();
                
                $.ajax({
                    url: 'upload-report.php?lang=<?= $lang ?>&db=<?= $selectedDb ?>&facility_id=<?= $selectedFacility ?>&sample_type=<?= $selectedSampleType ?>',
                    type: 'POST',
                    data: {
                        ajax_update: 1,
                        numune_id: numune_id,
                        analiz_tarihi: analiz_tarihi,
                        analiz_raporu_tarihi: analiz_raporu_tarihi
                    },
                    dataType: 'json',
                    success: function(response) {
                        $('#updateReportModal').modal('hide');
                        
                        // Başarı/hata mesajını göster
                        $('#ajax-message').text(response.message);
                        $('#ajax-alert').removeClass('alert-success alert-danger')
                            .addClass(response.success ? 'alert-success' : 'alert-danger')
                            .fadeIn();
                        
                        // 3 saniye sonra mesajı kapat
                        setTimeout(function() {
                            $('#ajax-alert').fadeOut();
                        }, 3000);
                        
                        // Başarılıysa sayfayı yenile
                        if (response.success) {
                            setTimeout(function() {
                                location.reload();
                            }, 1500);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error("AJAX hatası: ", error);
                        alert("İstek sırasında bir hata oluştu: " + error);
                    }
                });
            });
            
        } catch (error) {
            console.error("JavaScript hata: ", error);
            alert("Bir JavaScript hatası oluştu: " + error.message);
        }
    });
    
    // Tarih formatını input için düzenle (DD.MM.YYYY -> YYYY-MM-DD)
    function formatDateForInput(dateStr) {
        if (!dateStr) return '';
        
        // Tarih formatını kontrol et
        if (dateStr.includes('.')) {
            // DD.MM.YYYY formatı
            var parts = dateStr.split('.');
            if (parts.length === 3) {
                return parts[2] + '-' + parts[1] + '-' + parts[0];
            }
        } else if (dateStr.includes('-')) {
            // YYYY-MM-DD formatı zaten doğru
            return dateStr;
        }
        
        return dateStr;
    }
</script>
<script src="bower_components/autosize/dist/autosize.min.js"></script>
<script src="dist/js/main.js"></script>
</body>
</html>