<?php
require_once "db.php";

// Dil Ayarı
$lang = $_GET['lang'] ?? 'tr';
$languages = ['tr' => 'Türkçe', 'en' => 'English', 'mk' => 'Македонски'];

?>

<?php include "language_analiz.php" ?>

<?php
$current_texts = $texts[$lang];

// Kullanıcı Bilgileri
if (session_status() === PHP_SESSION_NONE) {
    session_start();
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

// Seçilen tesisin verilerini listele (Yıl bazlı filtreleme)
if ($selectedFacility) {
    $stmt = $pdo->prepare("
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
            nr.uploaded_at
        FROM numune_raporlari nr
        WHERE nr.facility_id = :facility_id 
        AND YEAR(nr.alim_tarihi) = :selected_year
    ");
    $stmt->execute([
        "facility_id" => $selectedFacility,
        "selected_year" => $selectedDb
    ]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $results = [];
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
</head>
<body>
<!-- Sayfa baş kısmına eklenecek CSS -->
<style>
    .filter-buttons {
        margin-bottom: 15px;
    }
    .filter-btn {
        margin-right: 5px;
    }
    .mb-20 {
        margin-bottom: 20px;
    }
    /* Aktif filtre butonu için stil */
    .filter-btn.active {
        background-color: #337ab7;
        color: white;
        border-color: #2e6da4;
    }
</style>

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
                            <li><a href="?lang=<?= $key ?>&db=<?= $selectedDb ?>"><?= $language ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </li>
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
                <li class="dropdown dropdown-usermenu">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                        <div class="user-avatar"><img src="imgs/a0.jpg" alt="..."></div>
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
                <form method="GET" action="numune_rapor.php">
                    <input type="hidden" name="lang" value="<?= htmlspecialchars($lang) ?>">
                    <input type="hidden" name="db" value="<?= htmlspecialchars($selectedDb) ?>">
                    <div class="form-group">
                        <label for="facilitySelect"><?= $current_texts['facility_select'] ?>:</label>
                        <select name="facility_id" id="facilitySelect" class="form-control" onchange="this.form.submit()">
                            <option value=""><?= $current_texts['facility_select'] ?></option>
                            <?php foreach ($facilities as $facility): ?>
                                <option value="<?= $facility['id'] ?>" <?= $facility['id'] == $selectedFacility ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($facility['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                </form>
                <div class="row mb-20">
                    <div class="col-md-9">
                        <div class="btn-group filter-buttons" role="group" aria-label="<?= $current_texts['sample_type_filter'] ?? 'Numune Türü Filtresi' ?>">
                            <button type="button" class="btn btn-default filter-btn active" data-filter="all">
                                <?= $current_texts['all'] ?? 'Tümü' ?>
                            </button>
                            <?php foreach ($current_texts['sample_types'] as $value => $text): ?>
                            <button type="button" class="btn btn-default filter-btn" data-filter="<?= htmlspecialchars($value) ?>">
                                <?= htmlspecialchars($text) ?>
                            </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <button type="button" class="btn btn-primary pull-right" data-toggle="modal" data-target="#addSampleModal">
                            <i class="fa fa-plus"></i> <?= $current_texts['add_sample'] ?? 'Numune Ekle' ?>
                        </button>
                    </div>
                </div>
                
                <div class="clearfix"></div>
                
                <table id="datatable" class="table table-striped table-bordered">
                    <thead>
                    <tr>
                        <th><?= $current_texts['no'] ?? 'NO' ?></th>
                        <th><?= $current_texts['facility_select'] ?? 'TESİS SEÇ' ?></th>
                        <th><?= $current_texts['sample_type'] ?? 'NUMUNE TÜRÜ' ?></th>
                        <th><?= $current_texts['sample_name'] ?? 'NUMUNE ADI' ?></th>
                        <th><?= $current_texts['location'] ?? 'ALINDIĞI YER/PARTİ' ?></th>
                        <th><?= $current_texts['sample_date'] ?? 'NUMUNE ALIM/TESLİM TARİHİ' ?></th>
                        <th><?= $current_texts['sample_person'] ?? 'NUMUNE ALAN/TESLİM EDEN' ?></th>
                        <th><?= $current_texts['sample_code'] ?? 'NUMUNE KODU' ?></th>
                        <th><?= $current_texts['actions'] ?? 'GÜNCELLE/SİLME' ?></th>
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
                        ?>
                        <?php 
                            // Numune türünün çevirisini al
                            $numuneTuru = $result['numune_turu'];
                            $numuneTuruCeviri = isset($current_texts['sample_types'][$numuneTuru]) ? 
                                                $current_texts['sample_types'][$numuneTuru] : $numuneTuru;
                        ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td><?= htmlspecialchars($facilityName) ?></td>
                            <td><?= htmlspecialchars($numuneTuruCeviri) ?></td>
                            <td><?= htmlspecialchars($result['numune_adi']) ?></td>
                            <td><?= htmlspecialchars($result['yer_adi']) ?></td>
                            <td><?= htmlspecialchars($result['alim_tarihi']) ?></td>
                            <td><?= htmlspecialchars($result['alan_kisi']) ?></td>
                            <td><?= htmlspecialchars($result['numune_kodu']) ?></td>
                            <td>
                                <button type="button" class="btn btn-xs btn-primary btn-edit" data-id="<?= $result['id'] ?>">
                                    <i class="fa fa-edit"></i>
                                </button>
                                <button type="button" class="btn btn-xs btn-danger btn-delete" data-id="<?= $result['id'] ?>">
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
    <footer id="footer" class="ui-footer">
        <?= $current_texts['footer'] ?>
    </footer>
</div>

<!-- Numune Ekle Modal -->
<div class="modal fade" id="addSampleModal" tabindex="-1" role="dialog" aria-labelledby="addSampleModalLabel">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="addSampleModalLabel"><?= $current_texts['add_sample'] ?? 'Numune Ekle' ?></h4>
            </div>
            <div class="modal-body">
                <form id="addSampleForm">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="inputFacility"><?= $current_texts['facility_select'] ?></label>
                                <select name="facility_id" id="inputFacility" class="form-control" required>
                                    <option value=""><?= $current_texts['select'] ?? 'Seçiniz' ?></option>
                                    <?php foreach ($facilities as $facility): ?>
                                        <option value="<?= $facility['id'] ?>"><?= htmlspecialchars($facility['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="inputNumuneTuru"><?= $current_texts['sample_type'] ?></label>
                                <select name="numune_turu" id="inputNumuneTuru" class="form-control" required>
                                    <option value=""><?= $current_texts['select'] ?? 'Seçiniz' ?></option>
                                    <?php foreach ($current_texts['sample_types'] as $value => $text): ?>
                                        <option value="<?= $value ?>"><?= $text ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="inputNumuneAdiTr"><?= $current_texts['sample_name'] ?> (TR)</label>
                                <input type="text" class="form-control" id="inputNumuneAdiTr" name="numune-tr" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="inputNumuneAdiEn"><?= $current_texts['sample_name'] ?> (EN)</label>
                                <input type="text" class="form-control" id="inputNumuneAdiEn" name="numune-en" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="inputNumuneAdiMk"><?= $current_texts['sample_name'] ?> (MK)</label>
                                <input type="text" class="form-control" id="inputNumuneAdiMk" name="numune-mk" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="inputNumuneKodu"><?= $current_texts['sample_code'] ?></label>
                                <input type="text" class="form-control" id="inputNumuneKodu" name="numune_kodu" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="inputYerTr"><?= $current_texts['location'] ?> (TR)</label>
                                <input type="text" class="form-control" id="inputYerTr" name="yer-tr" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="inputYerEn"><?= $current_texts['location'] ?> (EN)</label>
                                <input type="text" class="form-control" id="inputYerEn" name="yer-en" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="inputYerMk"><?= $current_texts['location'] ?> (MK)</label>
                                <input type="text" class="form-control" id="inputYerMk" name="yer-mk" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="inputAlimTarihi"><?= $current_texts['sample_date'] ?></label>
                                <input type="datetime-local" class="form-control" id="inputAlimTarihi" name="alim_tarihi" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="inputAlanKisi"><?= $current_texts['sample_person'] ?></label>
                                <input type="text" class="form-control" id="inputAlanKisi" name="alan_kisi" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="inputReportPath"><?= $current_texts['file'] ?></label>
                        <input type="text" class="form-control" id="inputReportPath" name="report_path" placeholder="uploads/reports/sample.pdf">
                    </div>
                </form>
                <div id="formMessage" class="alert" style="display: none;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?= $current_texts['cancel'] ?? 'İptal' ?></button>
                <button type="button" class="btn btn-primary" id="btnSaveSample"><?= $current_texts['save'] ?? 'Kaydet' ?></button>
            </div>
        </div>
    </div>
</div>

<!-- Numune Düzenleme Modal -->
<div class="modal fade" id="editSampleModal" tabindex="-1" role="dialog" aria-labelledby="editSampleModalLabel">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="editSampleModalLabel"><?= $current_texts['edit_sample'] ?? 'Numune Düzenle' ?></h4>
            </div>
            <div class="modal-body">
                <form id="editSampleForm">
                    <input type="hidden" id="editSampleId" name="id">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="editFacility"><?= $current_texts['facility_select'] ?></label>
                                <select name="facility_id" id="editFacility" class="form-control" required>
                                    <option value=""><?= $current_texts['select'] ?? 'Seçiniz' ?></option>
                                    <?php foreach ($facilities as $facility): ?>
                                        <option value="<?= $facility['id'] ?>"><?= htmlspecialchars($facility['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="editNumuneTuru"><?= $current_texts['sample_type'] ?></label>
                                <select name="numune_turu" id="editNumuneTuru" class="form-control" required>
                                    <option value=""><?= $current_texts['select'] ?? 'Seçiniz' ?></option>
                                    <?php foreach ($current_texts['sample_types'] as $value => $text): ?>
                                        <option value="<?= $value ?>"><?= $text ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="editNumuneAdiTr"><?= $current_texts['sample_name'] ?> (TR)</label>
                                <input type="text" class="form-control" id="editNumuneAdiTr" name="numune-tr" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="editNumuneAdiEn"><?= $current_texts['sample_name'] ?> (EN)</label>
                                <input type="text" class="form-control" id="editNumuneAdiEn" name="numune-en" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="editNumuneAdiMk"><?= $current_texts['sample_name'] ?> (MK)</label>
                                <input type="text" class="form-control" id="editNumuneAdiMk" name="numune-mk" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="editNumuneKodu"><?= $current_texts['sample_code'] ?></label>
                                <input type="text" class="form-control" id="editNumuneKodu" name="numune_kodu" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="editYerTr"><?= $current_texts['location'] ?> (TR)</label>
                                <input type="text" class="form-control" id="editYerTr" name="yer-tr" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="editYerEn"><?= $current_texts['location'] ?> (EN)</label>
                                <input type="text" class="form-control" id="editYerEn" name="yer-en" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="editYerMk"><?= $current_texts['location'] ?> (MK)</label>
                                <input type="text" class="form-control" id="editYerMk" name="yer-mk" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="editAlimTarihi"><?= $current_texts['sample_date'] ?></label>
                                <input type="datetime-local" class="form-control" id="editAlimTarihi" name="alim_tarihi" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="editAlanKisi"><?= $current_texts['sample_person'] ?></label>
                                <input type="text" class="form-control" id="editAlanKisi" name="alan_kisi" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="editReportPath"><?= $current_texts['file'] ?></label>
                        <input type="text" class="form-control" id="editReportPath" name="report_path" placeholder="uploads/reports/sample.pdf">
                    </div>
                </form>
                <div id="editFormMessage" class="alert" style="display: none;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?= $current_texts['cancel'] ?? 'İptal' ?></button>
                <button type="button" class="btn btn-primary" id="btnUpdateSample"><?= $current_texts['save'] ?? 'Kaydet' ?></button>
            </div>
        </div>
    </div>
</div>

<script src="bower_components/jquery/dist/jquery.min.js"></script>
<script src="bower_components/bootstrap/dist/js/bootstrap.min.js"></script>

<script src="bower_components/datatables.net/js/jquery.dataTables.min.js"></script>
<script src="bower_components/datatables.net-bs/js/dataTables.bootstrap.min.js"></script>
<script>
    $(document).ready(function () {
        // DataTable'ı başlat
        var dataTable = $('#datatable').DataTable();
        // Numune türlerine göre filtreleme
        $('.filter-btn').on('click', function() {
            var filterValue = $(this).data('filter');
            
            // Aktif buton sınıfını güncelle
            $('.filter-btn').removeClass('active');
            $(this).addClass('active');
            
            // Filtreleme işlemi
            if (filterValue === 'all') {
                // Tümünü göster
                dataTable.search('').columns().search('').draw();
            } else {
                // Seçilen numune türüne göre filtrele - dile göre çevirisini değil orijinal değerini kullan
                dataTable.column(2).search(filterValue, true, false).draw();
            }
        });
        // Numune Kaydet
        $('#btnSaveSample').on('click', function() {
            var formData = $('#addSampleForm').serialize();
            
            $.ajax({
                url: 'save_numune.php',
                type: 'POST',
                data: formData,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $('#formMessage')
                            .removeClass('alert-danger')
                            .addClass('alert-success')
                            .text(response.message)
                            .show();
                            
                        // 2 saniye sonra sayfayı yenile
                        setTimeout(function() {
                            location.reload();
                        }, 2000);
                    } else {
                        $('#formMessage')
                            .removeClass('alert-success')
                            .addClass('alert-danger')
                            .text(response.message)
                            .show();
                    }
                },
                error: function() {
                    $('#formMessage')
                        .removeClass('alert-success')
                        .addClass('alert-danger')
                        .text('Bir hata oluştu. Lütfen tekrar deneyiniz.')
                        .show();
                } }); 
                       
        }); 
             
        // Düzenle butonuna tıklandığında
        $('.btn-edit').on('click', function() {
            var sampleId = $(this).data('id');
            
            // AJAX ile numune verilerini getir
            $.ajax({
                url: 'get_numune.php',
                type: 'GET',
                data: { id: sampleId },
                dataType: 'json',
                success: function(data) {
                    if (data.success) {
                        var sample = data.sample;
                        
                        // Form alanlarını doldur
                        $('#editSampleId').val(sample.id);
                        $('#editFacility').val(sample.facility_id);
                        $('#editNumuneTuru').val(sample.numune_turu);
                        $('#editNumuneAdiTr').val(sample['numune-tr']);
                        $('#editNumuneAdiEn').val(sample['numune-en']);
                        $('#editNumuneAdiMk').val(sample['numune-mk']);
                        $('#editYerTr').val(sample['yer-tr']);
                        $('#editYerEn').val(sample['yer-en']);
                        $('#editYerMk').val(sample['yer-mk']);
                        
                        // Datetime-local formatı için tarihi düzenle
                        var datetime = sample.alim_tarihi;
                        if (datetime) {
                            datetime = datetime.replace(' ', 'T');
                            // Saniyeler varsa kaldır
                            datetime = datetime.replace(/:\d\d$/, '');
                        }
                        $('#editAlimTarihi').val(datetime);
                        
                        $('#editAlanKisi').val(sample.alan_kisi);
                        $('#editReportPath').val(sample.report_path);
                        
                        // Modalı göster
                        $('#editSampleModal').modal('show');
                    } else {
                        alert(data.message || 'Numune bilgileri alınamadı.');
                    }
                },
                error: function() {
                    alert('Veri alınırken bir hata oluştu.');
                }
            });
        });
        
        // Numune Güncelleme
        $('#btnUpdateSample').on('click', function() {
            var formData = $('#editSampleForm').serialize();
            
            $.ajax({
                url: 'update_numune.php',
                type: 'POST',
                data: formData,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $('#editFormMessage')
                            .removeClass('alert-danger')
                            .addClass('alert-success')
                            .text(response.message)
                            .show();
                            
                        // 2 saniye sonra sayfayı yenile
                        setTimeout(function() {
                            location.reload();
                        }, 2000);
                    } else {
                        $('#editFormMessage')
                            .removeClass('alert-success')
                            .addClass('alert-danger')
                            .text(response.message)
                            .show();
                    }
                },
                error: function() {
                    $('#editFormMessage')
                        .removeClass('alert-success')
                        .addClass('alert-danger')
                        .text('Bir hata oluştu. Lütfen tekrar deneyiniz.')
                        .show();
                }
            });
        });
        
        // Sil butonuna tıklandığında
        $('.btn-delete').on('click', function() {
            var sampleId = $(this).data('id');
            
            if (confirm('<?= $current_texts['confirm_delete'] ?>')) {
                // AJAX ile silme işlemi yap
                $.ajax({
                    url: 'delete_numune.php',
                    type: 'POST',
                    data: { id: sampleId },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            alert(response.message);
                            location.reload();
                        } else {
                            alert(response.message || 'Silme işlemi başarısız oldu.');
                        }
                    },
                    error: function() {
                        alert('Silme işlemi sırasında bir hata oluştu.');
                    }
                });
            }
        });
    });
</script>
<script src="bower_components/autosize/dist/autosize.min.js"></script>
<script src="dist/js/main.js"></script>
</body>
</html>