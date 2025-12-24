<?php
require_once "db.php";
session_start();

$lang = $_GET['lang'] ?? 'tr';
$databases = ['2023', '2024', '2025'];
$selectedDb = $_GET['db'] ?? '2025';

$languages = ['tr' => 'Türkçe', 'en' => 'English', 'mk' => 'Македонски'];

$texts = [
    'tr' => [
        'page_title' => 'Labaravutar Ekleme',
        'lab_name' => 'Analiz Labaratuvarı',
        'add_lab' => 'Yeni Analiz Labaratuvarı Ekle',
        'edit_lab' => 'Labaratuvarı Düzenle',
        'submit' => 'Kaydet',
        'cancel' => 'İptal',
        'update' => 'Güncelle',
        'delete' => 'Sil',
        'delete_confirm' => 'Bu labaratuvar kaydını silmek istediğinizden emin misiniz?',
        'yes' => 'Evet',
        'no' => 'Hayır',
        'success_add' => 'Analiz Labaratuvarı başarıyla eklendi.',
        'success_update' => 'Analiz Labaratuvarı başarıyla güncellendi.',
        'success_delete' => 'Analiz Labaratuvarı başarıyla silindi.',
        'error_message' => 'İşlem sırasında bir hata oluştu.',
        'facility_list' => 'Analiz Labaratuvarı Listesi',
        'action' => 'İşlem',
        'main_menu' => 'Menü',
        'menu' => 'Analizler',
        'menu-admin' => 'Admin Menü',
        'home' => [
            ['name' => 'Ana Sayfa', 'link' => 'index.php'],
            ['name' => 'Kullanıcı Profili', 'link' => 'profile.php']
        ],
        'profile' => 'Kullanıcı Profili',
        'test_types' => [
            ['name' => 'Gıda Mikrobiyolojik', 'link' => 'food.php'],
            ['name' => 'Gıda Kimyasal', 'link' => 'food-chem.php'],
            ['name' => 'Gıda Enstrumental', 'link' => 'food-enst.php'],
            ['name' => 'Su Mikrobiyolojik', 'link' => 'water.php'],
            ['name' => 'Su Kimyasal', 'link' => 'water-chem.php'],
            ['name' => 'Yüzey Sürüntü(SWAB)', 'link' => 'swab.php'],
            ['name' => 'Ortam Havası', 'link' => 'air.php'],
            ['name' => 'Atık Su', 'link' => 'wastewater.php'],
            ['name' => 'Toprak', 'link' => 'soil.php'],
            ['name' => 'Trichinella', 'link' => 'trichinella.php'],
            ['name' => 'Tavuk Dışkısı', 'link' => 'chicken.php'],
            ['name' => 'Organoleptik', 'link' => 'organoleptik.php'],
            ['name' => 'Diğer', 'link' => 'other.php']
        ],
        'admin' => [
            ['name' => 'Yeni Tesis Kaydı', 'link' => 'facility-add.php'],
            ['name' => 'Numune Yükle', 'link' => 'numune_rapor'],
            ['name' => 'Rapor Yükle', 'link' => 'upload-report.php'],
            ['name' => 'Kullanıcı Ekle', 'link' => 'user-add.php']
        ],
        'settings' => 'Ayarlar',
        'logout' => 'Çıkış Yap',
        'select_db' => 'Veritabanı Seç',
        'footer' => '2025 &copy; Labx by Vektraweb.',
    ],
    'en' => [
        'page_title' => 'Facility Management',
        'facility_name' => 'Facility Name',
        'facility_address' => 'Facility Address',
        'facility_location' => 'Facility Location',
        'contact_name' => 'Contact Person Name',
        'contact_position' => 'Contact Person Position',
        'contact_phone' => 'Contact Person Phone',
        'contact_email' => 'Contact Person Email',
        'add_facility' => 'Add New Facility',
        'edit_facility' => 'Edit Facility',
        'submit' => 'Save',
        'cancel' => 'Cancel',
        'update' => 'Update',
        'delete' => 'Delete',
        'delete_confirm' => 'Are you sure you want to delete this facility?',
        'yes' => 'Yes',
        'no' => 'No',
        'success_add' => 'Facility successfully added.',
        'success_update' => 'Facility successfully updated.',
        'success_delete' => 'Facility successfully deleted.',
        'error_message' => 'An error occurred during the operation.',
        'facility_list' => 'Facility List',
        'action' => 'Action',
        'menu' => 'Menu',
        'menu-admin' => 'Admin Menu',
        'home' => [
            ['name' => 'Main Page', 'link' => 'index.php'],
            ['name' => 'User Profile', 'link' => 'profile.php']
        ],
        'profile' => 'User Profile',
        'test_types' => [
            ['name' => 'Food Microbiological', 'link' => 'food.php'],
    ['name' => 'Food Chemical', 'link' => 'food-chem.php'],
    ['name' => 'Food Instrumental', 'link' => 'food-enst.php'],
    ['name' => 'Water Microbiological', 'link' => 'water.php'],
    ['name' => 'Water Chemical', 'link' => 'water-chem.php'],
    ['name' => 'Surface Swab (SWAB)', 'link' => 'swab.php'],
    ['name' => 'Ambient Air', 'link' => 'air.php'],
    ['name' => 'Wastewater', 'link' => 'wastewater.php'],
    ['name' => 'Soil', 'link' => 'soil.php'],
    ['name' => 'Trichinella', 'link' => 'trichinella.php'],
    ['name' => 'Chicken Feces', 'link' => 'chicken.php'],
    ['name' => 'Organoleptic', 'link' => 'organoleptik.php'],
    ['name' => 'Other', 'link' => 'other.php']
        ],
        'admin' => [
            ['name' => 'Add New Facility', 'link' => 'facility-add.php'],
            ['name' => 'Upload Sample', 'link' => 'numune_rapor'],
            ['name' => 'Upload Report', 'link' => 'upload-report.php'],
            ['name' => 'Add New User', 'link' => 'user-add.php']
        ],
        'settings' => 'Settings',
        'logout' => 'Logout',
        'select_db' => 'Select Database',
        'footer' => '2025 &copy; Labx by Vektraweb.',
    ],
    'mk' => [
        'page_title' => 'Управување со објект',
        'facility_name' => 'Име на објектот',
        'facility_address' => 'Адреса на објектот',
        'facility_location' => 'Локација на објектот',
        'contact_name' => 'Име на лице за контакт',
        'contact_position' => 'Позиција на лице за контакт',
        'contact_phone' => 'Телефон на лице за контакт',
        'contact_email' => 'Е-пошта на лице за контакт',
        'add_facility' => 'Додадете нов објект',
        'edit_facility' => 'Уреди објект',
        'submit' => 'Зачувај',
        'cancel' => 'Откажи',
        'update' => 'Ажурирај',
        'delete' => 'Избриши',
        'delete_confirm' => 'Дали сте сигурни дека сакате да го избришете овој објект?',
        'yes' => 'Да',
        'no' => 'Не',
        'success_add' => 'Објектот е успешно додаден.',
        'success_update' => 'Објектот е успешно ажуриран.',
        'success_delete' => 'Објектот е успешно избришан.',
        'error_message' => 'Се случи грешка за време на операцијата.',
        'facility_list' => 'Листа на објекти',
        'action' => 'Акција',
        'menu' => 'Мени',
        'menu-admin' => 'Администраторско мени',
        'home' => [
            ['name' => 'Главна страница', 'link' => 'index.php'],
            ['name' => 'Кориснички профил', 'link' => 'profile.php']
        ],
        'profile' => 'Кориснички профил',
        'test_types' => [
            ['name' => 'Инструментална анализа на храна', 'link' => 'food-enst.php'],
    ['name' => 'Микробиологија на вода', 'link' => 'water.php'],
    ['name' => 'Хемија на вода', 'link' => 'water-chem.php'],
    ['name' => 'Површински брис (SWAB)', 'link' => 'swab.php'],
    ['name' => 'Воздух во околина', 'link' => 'air.php'],
    ['name' => 'Отпадна вода', 'link' => 'wastewater.php'],
    ['name' => 'Почва', 'link' => 'soil.php'],
    ['name' => 'Трихинела', 'link' => 'trichinella.php'],
    ['name' => 'Кокошки измет', 'link' => 'chicken.php'],
    ['name' => 'Органолептичка анализа', 'link' => 'organoleptik.php'],
    ['name' => 'Друго', 'link' => 'other.php']
        ],
        'admin' => [
           ['name' => 'Додадете нов објект', 'link' => 'facility-add.php'],
            ['name' => 'Поставете примерок', 'link' => 'numune_rapor'],
            ['name' => 'Поставете извештај', 'link' => 'upload-report.php'],
            ['name' => 'Додадете нов корисник', 'link' => 'user-add.php']
        ],
        'settings' => 'Подесувања',
        'logout' => 'Одјави се',
        'select_db' => 'Избери база',
        'footer' => '2025 &copy; Labx by Vektraweb.',
    ],
];

$current_texts = $texts[$lang];

// Kullanıcı bilgilerini al
$userId = $_SESSION["user_id"] ?? 0;
$stmt = $pdo->prepare("SELECT username FROM users WHERE id = :id");
$stmt->execute(["id" => $userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Tüm tesisleri getir
$stmt = $pdo->prepare("SELECT * FROM labaratuvar_tanim WHERE id != 0 ORDER BY analiz_lab");
$stmt->execute();
$facilities = $stmt->fetchAll(PDO::FETCH_ASSOC);

// AJAX isteklerini işle
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['ajax_action'])) {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => $current_texts['error_message']];
    
    try {
        // Tesis ekle
        if ($_POST['ajax_action'] == 'add') {
            $facilityName = $_POST['facility_name'] ?? '';
            $facilityAddress = $_POST['facility_address'] ?? '';
            $facilityLocation = $_POST['facility_location'] ?? '';
            $contactName = $_POST['contact_name'] ?? '';
            $contactPosition = $_POST['contact_position'] ?? '';
            $contactPhone = $_POST['contact_phone'] ?? '';
            $contactEmail = $_POST['contact_email'] ?? '';
            
            $stmt = $pdo->prepare("
                INSERT INTO facilities (
                    name, 
                    address, 
                    location, 
                    contact_name, 
                    contact_position, 
                    contact_phone, 
                    contact_email, 
                    created_at
                ) VALUES (
                    :name, 
                    :address, 
                    :location, 
                    :contact_name, 
                    :contact_position, 
                    :contact_phone, 
                    :contact_email, 
                    NOW()
                )
            ");
            
            $stmt->execute([
                "name" => $facilityName,
                "address" => $facilityAddress,
                "location" => $facilityLocation,
                "contact_name" => $contactName,
                "contact_position" => $contactPosition,
                "contact_phone" => $contactPhone,
                "contact_email" => $contactEmail
            ]);
            
            $response = ['success' => true, 'message' => $current_texts['success_add']];
        }
        // Tesis güncelle
        else if ($_POST['ajax_action'] == 'update' && isset($_POST['facility_id'])) {
            $facilityId = $_POST['facility_id'];
            $facilityName = $_POST['facility_name'] ?? '';
            $facilityAddress = $_POST['facility_address'] ?? '';
            $facilityLocation = $_POST['facility_location'] ?? '';
            $contactName = $_POST['contact_name'] ?? '';
            $contactPosition = $_POST['contact_position'] ?? '';
            $contactPhone = $_POST['contact_phone'] ?? '';
            $contactEmail = $_POST['contact_email'] ?? '';
            
            $stmt = $pdo->prepare("
                UPDATE facilities SET 
                    name = :name, 
                    address = :address, 
                    location = :location, 
                    contact_name = :contact_name, 
                    contact_position = :contact_position, 
                    contact_phone = :contact_phone, 
                    contact_email = :contact_email, 
                    updated_at = NOW()
                WHERE id = :id
            ");
            
            $stmt->execute([
                "id" => $facilityId,
                "name" => $facilityName,
                "address" => $facilityAddress,
                "location" => $facilityLocation,
                "contact_name" => $contactName,
                "contact_position" => $contactPosition,
                "contact_phone" => $contactPhone,
                "contact_email" => $contactEmail
            ]);
            
            $response = ['success' => true, 'message' => $current_texts['success_update']];
        }
        // Tesis sil
        else if ($_POST['ajax_action'] == 'delete' && isset($_POST['facility_id'])) {
            $facilityId = $_POST['facility_id'];
            
            $stmt = $pdo->prepare("DELETE FROM facilities WHERE id = :id");
            $stmt->execute(["id" => $facilityId]);
            
            $response = ['success' => true, 'message' => $current_texts['success_delete']];
        }
        // Tesis detaylarını getir
        else if ($_POST['ajax_action'] == 'get_facility' && isset($_POST['facility_id'])) {
            $facilityId = $_POST['facility_id'];
            
            $stmt = $pdo->prepare("SELECT * FROM facilities WHERE id = :id");
            $stmt->execute(["id" => $facilityId]);
            $facility = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($facility) {
                $response = ['success' => true, 'facility' => $facility];
            }
        }
    } catch (PDOException $e) {
        $response = ['success' => false, 'message' => $e->getMessage()];
    }
    
    echo json_encode($response);
    exit;
}
?>

<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Labx - <?= $current_texts['page_title'] ?></title>
    <link rel="stylesheet" href="bower_components/bootstrap/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="bower_components/font-awesome/css/font-awesome.min.css">
    <link rel="stylesheet" href="bower_components/datatables.net-bs/css/dataTables.bootstrap.min.css">
    <link rel="stylesheet" href="dist/css/main.css">
    <!-- Favicon -->
    <link rel="icon" type="image/png" sizes="32x32" href="/imgs/favicon.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/imgs/favicon.png">
    <link rel="shortcut icon" href="imgs/favicon.ico">
    <style>
        .action-buttons .btn {
            margin-right: 5px;
        }
        .alert-fixed {
            position: fixed;
            top: 10px;
            right: 10px;
            width: 300px;
            z-index: 9999;
        }
    </style>
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
                        <i class="fa fa-database"></i> <?= $current_texts['select_db'] ?>
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
    <!--header end-->

    <!--sidebar start-->
    <?php include "sidebar.php" ?>
    <!--sidebar end-->

    <!-- AJAX bildirimi -->
    <div id="ajax-alert" style="display: none;" class="alert alert-fixed">
        <span id="ajax-message"></span>
    </div>

    <div id="content" class="ui-content">
        <div class="ui-content-body">
            <div class="ui-container">
                <!-- Tesis Listesi -->
                <div class="panel">
                    <header class="panel-heading">
                        <?= $current_texts['facility_list'] ?>
                        <button class="btn btn-primary btn-sm pull-right" id="addFacilityBtn">
                            <i class="fa fa-plus"></i> <?= $current_texts['add_facility'] ?>
                        </button>
                    </header>
                    <div class="panel-body">
                        <div class="table-responsive">
                            <table id="facilitiesTable" class="table table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th><?= $current_texts['facility_name'] ?></th>
                                        <th><?= $current_texts['facility_address'] ?></th>
                                        <th><?= $current_texts['facility_location'] ?></th>
                                        <th><?= $current_texts['contact_name'] ?></th>
                                        <th><?= $current_texts['contact_position'] ?></th>
                                        <th><?= $current_texts['contact_phone'] ?></th>
                                        <th><?= $current_texts['contact_email'] ?></th>
                                        <th><?= $current_texts['action'] ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($facilities as $facility): ?>
                                    <tr>
                                        <td><?= $facility['id'] ?></td>
                                        <td><?= htmlspecialchars($facility['name']) ?></td>
                                        <td><?= htmlspecialchars($facility['address'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($facility['location'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($facility['contact_name'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($facility['contact_position'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($facility['contact_phone'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($facility['contact_email'] ?? '') ?></td>
                                        <td class="action-buttons">
                                            <button class="btn btn-sm btn-warning edit-facility" data-id="<?= $facility['id'] ?>">
                                                <i class="fa fa-pencil"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger delete-facility" data-id="<?= $facility['id'] ?>">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
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

<!-- Tesis Ekle/Düzenle Modal -->
<div class="modal fade" id="facilityModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="facilityModalTitle"><?= $current_texts['add_facility'] ?></h4>
            </div>
            <div class="modal-body">
                <form id="facilityForm">
                    <input type="hidden" id="facility_id" name="facility_id">
                    <input type="hidden" id="ajax_action" name="ajax_action" value="add">
                    
                    <div class="form-group">
                        <label for="facility_name"><?= $current_texts['facility_name'] ?>:</label>
                        <input type="text" class="form-control" id="facility_name" name="facility_name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="facility_address"><?= $current_texts['facility_address'] ?>:</label>
                        <input type="text" class="form-control" id="facility_address" name="facility_address">
                    </div>
                    
                    <div class="form-group">
                        <label for="facility_location"><?= $current_texts['facility_location'] ?>:</label>
                        <input type="text" class="form-control" id="facility_location" name="facility_location">
                    </div>
                    
                    <div class="form-group">
                        <label for="contact_name"><?= $current_texts['contact_name'] ?>:</label>
                        <input type="text" class="form-control" id="contact_name" name="contact_name">
                    </div>
                    
                    <div class="form-group">
                        <label for="contact_position"><?= $current_texts['contact_position'] ?>:</label>
                        <input type="text" class="form-control" id="contact_position" name="contact_position">
                    </div>
                    
                    <div class="form-group">
                        <label for="contact_phone"><?= $current_texts['contact_phone'] ?>:</label>
                        <input type="text" class="form-control" id="contact_phone" name="contact_phone">
                    </div>
                    
                    <div class="form-group">
                        <label for="contact_email"><?= $current_texts['contact_email'] ?>:</label>
                        <input type="email" class="form-control" id="contact_email" name="contact_email">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?= $current_texts['cancel'] ?></button>
                <button type="button" class="btn btn-primary" id="saveFacility"><?= $current_texts['submit'] ?></button>
            </div>
        </div>
    </div>
</div>

<!-- Silme Onay Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title"><?= $current_texts['delete'] ?></h4>
            </div>
            <div class="modal-body">
                <p><?= $current_texts['delete_confirm'] ?></p>
                <input type="hidden" id="delete_facility_id">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?= $current_texts['no'] ?></button>
                <button type="button" class="btn btn-danger" id="confirmDelete"><?= $current_texts['yes'] ?></button>
            </div>
        </div>
    </div>
</div>

<script src="bower_components/jquery/dist/jquery.min.js"></script>
<script src="bower_components/bootstrap/dist/js/bootstrap.min.js"></script>
<script src="bower_components/datatables.net/js/jquery.dataTables.min.js"></script>
<script src="bower_components/datatables.net-bs/js/dataTables.bootstrap.min.js"></script>
<script>
    $(document).ready(function() {
        // DataTables başlat
        var table = $('#facilitiesTable').DataTable({
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.10.25/i18n/<?= $lang === 'tr' ? 'Turkish' : ($lang === 'mk' ? 'Macedonian' : 'English') ?>.json"
            },
            "responsive": true,
            "order": [[0, "desc"]]
        });
        
        // Tesis Ekle butonuna tıklandığında
        $('#addFacilityBtn').click(function() {
            // Formu sıfırla
            $('#facilityForm')[0].reset();
            $('#facility_id').val('');
            $('#ajax_action').val('add');
            
            // Modal başlığını güncelle
            $('#facilityModalTitle').text('<?= $current_texts['add_facility'] ?>');
            
            // Submit butonunu güncelle
            $('#saveFacility').text('<?= $current_texts['submit'] ?>');
            
            // Modal'ı göster
            $('#facilityModal').modal('show');
        });
        
        // Düzenle butonuna tıklandığında
        $(document).on('click', '.edit-facility', function() {
            var facilityId = $(this).data('id');
            
            // AJAX ile tesis bilgilerini getir
            $.ajax({
                url: 'facility-add.php?lang=<?= $lang ?>&db=<?= $selectedDb ?>',
                type: 'POST',
                data: {
                    ajax_action: 'get_facility',
                    facility_id: facilityId
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        var facility = response.facility;
                        
                        // Form alanlarını doldur
                        $('#facility_id').val(facility.id);
                        $('#facility_name').val(facility.name);
                        $('#facility_address').val(facility.address);
                        $('#facility_location').val(facility.location);
                        $('#contact_name').val(facility.contact_name);
                        $('#contact_position').val(facility.contact_position);
                        $('#contact_phone').val(facility.contact_phone);
                        $('#contact_email').val(facility.contact_email);
                        
                        // Action'ı güncelle
                        $('#ajax_action').val('update');
                        
                        // Modal başlığını güncelle
                        $('#facilityModalTitle').text('<?= $current_texts['edit_facility'] ?>');
                        
                        // Submit butonunu güncelle
                        $('#saveFacility').text('<?= $current_texts['update'] ?>');
                        
                        // Modal'ı göster
                        $('#facilityModal').modal('show');
                    } else {
                        showAlert(false, response.message);
                    }
                },
                error: function(xhr, status, error) {
                    showAlert(false, "Error: " + error);
                }
            });
        });
        
        // Sil butonuna tıklandığında
        $(document).on('click', '.delete-facility', function() {
            var facilityId = $(this).data('id');
            
            // Silinecek tesis ID'sini kaydet
            $('#delete_facility_id').val(facilityId);
            
            // Silme onay modal'ını göster
            $('#deleteModal').modal('show');
        });
        
        // Silme işlemini onayla
        $('#confirmDelete').click(function() {
            var facilityId = $('#delete_facility_id').val();
            
            // AJAX ile tesisi sil
            $.ajax({
                url: 'facility-add.php?lang=<?= $lang ?>&db=<?= $selectedDb ?>',
                type: 'POST',
                data: {
                    ajax_action: 'delete',
                    facility_id: facilityId
                },
                dataType: 'json',
                success: function(response) {
                    // Modal'ı kapat
                    $('#deleteModal').modal('hide');
                    
                    // Başarı/hata mesajını göster
                    showAlert(response.success, response.message);
                    
                    // Başarılıysa sayfayı yenile
                    if (response.success) {
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    }
                },
                error: function(xhr, status, error) {
                    $('#deleteModal').modal('hide');
                    showAlert(false, "Error: " + error);
                }
            });
        });
        
        // Kaydet butonuna tıklandığında
        $('#saveFacility').click(function() {
            // Form verilerini al
            var formData = $('#facilityForm').serialize();
            
            // AJAX ile tesis ekle/güncelle
            $.ajax({
                url: 'facility-add.php?lang=<?= $lang ?>&db=<?= $selectedDb ?>',
                type: 'POST',
                data: formData,
                dataType: 'json',
                success: function(response) {
                    // Modal'ı kapat
                    $('#facilityModal').modal('hide');
                    
                    // Başarı/hata mesajını göster
                    showAlert(response.success, response.message);
                    
                    // Başarılıysa sayfayı yenile
                    if (response.success) {
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    }
                },
                error: function(xhr, status, error) {
                    $('#facilityModal').modal('hide');
                    showAlert(false, "Error: " + error);
                }
            });
        });
        
        // AJAX bildirimi göster
        function showAlert(success, message) {
            $('#ajax-message').text(message);
            $('#ajax-alert').removeClass('alert-success alert-danger')
                .addClass(success ? 'alert-success' : 'alert-danger')
                .fadeIn();
            
            // 3 saniye sonra bildirimi kapat
            setTimeout(function() {
                $('#ajax-alert').fadeOut();
            }, 3000);
        }
    });
</script>
<script src="dist/js/main.js"></script>
</body>
</html>