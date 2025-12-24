<?php
require_once "db.php";
if (session_status() === PHP_SESSION_NONE) { session_start(); }

/* ==== Ortak parametreler ==== */
$lang = $_GET['lang'] ?? 'tr';
$selectedDb = $_GET['db'] ?? '2025';
$languages = ['tr' => 'Türkçe', 'en' => 'English', 'mk' => 'Македонски'];

/* ==== Sidebar/menü metinleri ==== */
require_once "language_analiz.php";
$current_texts = $texts[$lang] ?? $texts['tr'];

/* ==== Oturumdaki lab kullanıcısı (kabul eden olarak kullanılacak) ==== */
$currentLabUserId = $_SESSION['lab_user_id'] ?? 1; // varsayılan 1

/* ==== Sayfa i18n ==== */
$page_i18n = [
  'tr' => [
    'page_title'   => 'Numune Kabul',
    'banner'       => 'NUMUNE KABUL',
    'filter_date'  => 'Tarih Seç',
    'filter_firm'  => 'Firma Seç',
    'all_dates'    => 'Tüm Tarihler',
    'all_firms'    => 'Tüm Firmalar',
    'pending_count'=> 'Kabul Bekleyen Numune Sayısı',
    'accepted_count'=> 'Kabulü Yapılmış Numune Sayısı',
    
    'no'           => 'No',
    'sample_date'  => 'Numune Alım Tarihi',
    'facility'     => 'Numune Alınan Firma',
    'producer'     => 'Numune Alınan Tesis/Üretici Adı',
    'address'      => 'Numune Alınan Adres',
    'sample_name'  => 'Numune Adı',
    'taken_place'  => 'Numune Alınan Yer',
    'pack_type'    => 'Numune Ambalaj Türü',
    'production_date' => 'Üretim Tarihi',
    'expiry_date'  => 'Son Kullanma Tarihi',
    'lot_no'       => 'Parti/Lot No',
    'degree'       => 'Numune Derecesi °C',
    'amount'       => 'Numune Miktarı gr',
    'reason'       => 'Analiz Nedeni',
    'reason_opt'   => ['OZEL_ISTEK'=>'Özel İstek','YASAL_ANALIZ'=>'Yasal Analiz'],
    'atype'        => 'Analiz Türü',
    'asubtype'     => 'Alt Analiz Türü',
    'details'      => 'Analiz Detayı',
    'lab'          => 'Analiz Laboratuvarı',
    'accepted_by'  => 'Numuneyi Kabul Eden',
    'accept_date'  => 'Numune Kabul Tarihi',
    'sample_code'  => 'Numune Kodu',
    'actions'      => 'Numune Kabul/Güncelle/Sil',
    
    'accept_btn'   => 'KABUL ET',
    'update_btn'   => 'GÜNCELLE',
    'delete_btn'   => 'SİL',
    'save'         => 'Kaydet',
    'cancel'       => 'İptal',
    
    'success_accept' => 'Numune kabul edildi.',
    'success_update' => 'Numune güncellendi.',
    'success_delete' => 'Numune silindi.',
    'confirm_delete' => 'Bu numune kaydı silinsin mi?',
    'no_data'      => 'Kayıt bulunamadı.',
    'select'       => 'Seçiniz',
  ],
  'en' => [
    'page_title'   => 'Sample Acceptance',
    'banner'       => 'SAMPLE ACCEPTANCE',
    'filter_date'  => 'Select Date',
    'filter_firm'  => 'Select Facility',
    'all_dates'    => 'All Dates',
    'all_firms'    => 'All Facilities',
    'pending_count'=> 'Pending Samples',
    'accepted_count'=> 'Accepted Samples',
    
    'no'           => 'No',
    'sample_date'  => 'Sample Date',
    'facility'     => 'Facility',
    'producer'     => 'Producer/Plant',
    'address'      => 'Address',
    'sample_name'  => 'Sample Name',
    'taken_place'  => 'Location',
    'pack_type'    => 'Package Type',
    'production_date' => 'Production Date',
    'expiry_date'  => 'Expiry Date',
    'lot_no'       => 'Lot No',
    'degree'       => 'Temperature °C',
    'amount'       => 'Amount (g)',
    'reason'       => 'Reason',
    'reason_opt'   => ['OZEL_ISTEK'=>'Special Request','YASAL_ANALIZ'=>'Legal'],
    'atype'        => 'Analysis Type',
    'asubtype'     => 'Sub Type',
    'details'      => 'Details',
    'lab'          => 'Laboratory',
    'accepted_by'  => 'Accepted By',
    'accept_date'  => 'Acceptance Date',
    'sample_code'  => 'Sample Code',
    'actions'      => 'Accept/Update/Delete',
    
    'accept_btn'   => 'ACCEPT',
    'update_btn'   => 'UPDATE',
    'delete_btn'   => 'DELETE',
    'save'         => 'Save',
    'cancel'       => 'Cancel',
    
    'success_accept' => 'Sample accepted.',
    'success_update' => 'Sample updated.',
    'success_delete' => 'Sample deleted.',
    'confirm_delete' => 'Delete this sample?',
    'no_data'      => 'No records.',
    'select'       => 'Select',
  ],
  'mk' => [
    'page_title'   => 'Прием на примерок',
    'banner'       => 'ПРИЕМ НА ПРИМЕРОК',
    'filter_date'  => 'Избери датум',
    'filter_firm'  => 'Избери фирма',
    'all_dates'    => 'Сите датуми',
    'all_firms'    => 'Сите фирми',
    'pending_count'=> 'Примероци на чекање',
    'accepted_count'=> 'Прифатени примероци',
    
    'no'           => 'Бр',
    'sample_date'  => 'Датум на земање',
    'facility'     => 'Објект',
    'producer'     => 'Производител',
    'address'      => 'Адреса',
    'sample_name'  => 'Име на примерок',
    'taken_place'  => 'Локација',
    'pack_type'    => 'Амбалажа',
    'production_date' => 'Датум на производство',
    'expiry_date'  => 'Рок на траење',
    'lot_no'       => 'Лот бр.',
    'degree'       => 'Температура °C',
    'amount'       => 'Количина (g)',
    'reason'       => 'Причина',
    'reason_opt'   => ['OZEL_ISTEK'=>'Посебно барање','YASAL_ANALIZ'=>'Законска'],
    'atype'        => 'Тип анализа',
    'asubtype'     => 'Подтип',
    'details'      => 'Детали',
    'lab'          => 'Лабораторија',
    'accepted_by'  => 'Прифатил',
    'accept_date'  => 'Датум на прием',
    'sample_code'  => 'Код',
    'actions'      => 'Прифати/Ажурирај/Избриши',
    
    'accept_btn'   => 'ПРИФАТИ',
    'update_btn'   => 'АЖУРИРАЈ',
    'delete_btn'   => 'ИЗБРИШИ',
    'save'         => 'Зачувај',
    'cancel'       => 'Откажи',
    
    'success_accept' => 'Примерокот е прифатен.',
    'success_update' => 'Примерокот е ажуриран.',
    'success_delete' => 'Примерокот е избришан.',
    'confirm_delete' => 'Да се избрише примерокот?',
    'no_data'      => 'Нема записи.',
    'select'       => 'Изберете',
  ],
];
$T = $page_i18n[$lang] ?? $page_i18n['tr'];

/* ==== Yardımcılar ==== */
function nm($r,$lang){ return $lang==='en'?$r['name_en'] : ($lang==='mk'?$r['name_mk'] : $r['name_tr']); }

/* ==== Sözlükler ==== */
$types    = $pdo->query("SELECT id,name_tr,name_en,name_mk FROM analysis_types ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
$subtypes = $pdo->query("SELECT id,type_id,name_tr,name_en,name_mk FROM analysis_subtypes ORDER BY type_id,id")->fetchAll(PDO::FETCH_ASSOC);
$labs     = $pdo->query("SELECT id,name FROM laboratories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$labUsers = $pdo->query("SELECT id, full_name FROM lab_users ORDER BY full_name")->fetchAll(PDO::FETCH_ASSOC);
$facilities = $pdo->query("SELECT id, name FROM facilities ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

/* ==== Ambalaj seçenekleri ==== */
$packagingOptions = ['ORJINAL AMBALAJ','STERIL KAP','STERIL SWAB','STERIL PETRI','STERIL ŞİŞE','POŞET','(KAYIT)'];

/* ==== Filtreler ==== */
$filterDate = $_GET['filter_date'] ?? '';
$filterFacility = (int)($_GET['filter_facility'] ?? 0);

/* ==== AJAX işlemleri ==== */
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json; charset=utf-8');
    $action = $_POST['action'] ?? '';
    
    try {
        if ($action === 'accept') {
            $itemId = (int)($_POST['item_id'] ?? 0);
            $reason = $_POST['reason'] ?? 'OZEL_ISTEK';
            $typeId = (int)($_POST['type_id'] ?? 0);
            $subtypeId = (int)($_POST['subtype_id'] ?? 0);
            $labId = (int)($_POST['lab_id'] ?? 0);
            $details = trim($_POST['details'] ?? '');
            $acceptedBy = (int)($_POST['accepted_by'] ?? $currentLabUserId);
            $acceptDate = date('Y-m-d');
            
            // Gizli alanlar (modal'dan gelen)
            $packType = $_POST['pack_type'] ?? '';
            $productionDate = $_POST['production_date'] ?? null;
            $expiryDate = $_POST['expiry_date'] ?? null;
            $lotNo = $_POST['lot_no'] ?? '';
            $degree = $_POST['degree'] ?? null;
            $amount = $_POST['amount'] ?? null;
            
            if (!$itemId) throw new Exception('Geçersiz kayıt');
            
            // sample_item'dan sample_id'yi al
            $stItem = $pdo->prepare("SELECT sample_id FROM sample_items WHERE id = :id");
            $stItem->execute([':id' => $itemId]);
            $sampleId = (int)$stItem->fetchColumn();
            if (!$sampleId) throw new Exception('Numune bulunamadı');
            
            // Numune kodunu oluştur
            $sampleCode = 'NK-' . date('Ymd') . '-' . str_pad($itemId, 4, '0', STR_PAD_LEFT);
            
            // sample_full_accepts tablosuna kaydet
            $ins = $pdo->prepare("
                INSERT INTO sample_full_accepts 
                    (sample_item_id, reason, types_json, subtypes_json, labs_json, details, accepted_at, accepted_by, sample_code)
                VALUES 
                    (:item_id, :reason, :types, :subs, :labs, :details, :accept_date, :accept_by, :code)
                ON DUPLICATE KEY UPDATE
                    reason = VALUES(reason),
                    types_json = VALUES(types_json),
                    subtypes_json = VALUES(subtypes_json),
                    labs_json = VALUES(labs_json),
                    details = VALUES(details),
                    accepted_at = VALUES(accepted_at),
                    accepted_by = VALUES(accepted_by),
                    sample_code = VALUES(sample_code)
            ");
            $ins->execute([
                ':item_id' => $itemId,
                ':reason' => $reason,
                ':types' => json_encode([$typeId]),
                ':subs' => json_encode([$subtypeId]),
                ':labs' => json_encode([$labId]),
                ':details' => $details,
                ':accept_date' => $acceptDate,
                ':accept_by' => $acceptedBy ?: null,
                ':code' => $sampleCode
            ]);
            
            // sample_accepts tablosuna da kaydet (ön kabul bilgileri)
            $insAccept = $pdo->prepare("
                INSERT INTO sample_accepts 
                    (sample_item_id, packaging_type, degree_c, amount_gr, accept_date, accepted_by)
                VALUES 
                    (:item_id, :pack, :deg, :amt, :acc_date, :acc_by)
                ON DUPLICATE KEY UPDATE
                    packaging_type = VALUES(packaging_type),
                    degree_c = VALUES(degree_c),
                    amount_gr = VALUES(amount_gr),
                    accept_date = VALUES(accept_date),
                    accepted_by = VALUES(accepted_by)
            ");
            $insAccept->execute([
                ':item_id' => $itemId,
                ':pack' => $packType ?: '(KAYIT)',
                ':deg' => $degree !== '' ? $degree : null,
                ':amt' => $amount !== '' ? $amount : null,
                ':acc_date' => $acceptDate,
                ':acc_by' => $acceptedBy ?: null
            ]);
            
            // *** ÖNEMLİ: samples tablosunu da güncelle (analysis-result.php için) ***
            // samples tablosundaki sütunları kontrol et
            $sCols = $pdo->query("SHOW COLUMNS FROM samples")->fetchAll(PDO::FETCH_COLUMN);
            
            // [DÜZELTME] Sütun adlarını küçük harfe çevir (Case Sensitivity sorunu için)
            $sCols = array_map('strtolower', $sCols);
            
            $updateParts = [];
            $updateParams = [':sid' => $sampleId];
            
            // sample_code güncelle
            if (in_array('sample_code', $sCols)) {
                $updateParts[] = "sample_code = :code";
                $updateParams[':code'] = $sampleCode;
            }
            
            // type_id güncelle
            if (in_array('type_id', $sCols) && $typeId > 0) {
                $updateParts[] = "type_id = :tid";
                $updateParams[':tid'] = $typeId;
            } elseif (in_array('analysis_type_id', $sCols) && $typeId > 0) {
                $updateParts[] = "analysis_type_id = :tid";
                $updateParams[':tid'] = $typeId;
            }
            
            // subtype_id güncelle
            if (in_array('subtype_id', $sCols) && $subtypeId > 0) {
                $updateParts[] = "subtype_id = :stid";
                $updateParams[':stid'] = $subtypeId;
            } elseif (in_array('analysis_subtype_id', $sCols) && $subtypeId > 0) {
                $updateParts[] = "analysis_subtype_id = :stid";
                $updateParams[':stid'] = $subtypeId;
            }
            
            // detail güncelle
            if (in_array('detail', $sCols) && $details !== '') {
                $updateParts[] = "detail = :det";
                $updateParams[':det'] = $details;
            } elseif (in_array('analysis_detail', $sCols) && $details !== '') {
                $updateParts[] = "analysis_detail = :det";
                $updateParams[':det'] = $details;
            }
            
            if ($updateParts) {
                $updateSql = "UPDATE samples SET " . implode(', ', $updateParts) . " WHERE id = :sid";
                $pdo->prepare($updateSql)->execute($updateParams);
            }
            
            echo json_encode(['success' => true, 'message' => $T['success_accept'], 'sample_code' => $sampleCode]);
            exit;
        }
        
        if ($action === 'update') {
            $itemId = (int)($_POST['item_id'] ?? 0);
            $reason = $_POST['reason'] ?? 'OZEL_ISTEK';
            $typeId = (int)($_POST['type_id'] ?? 0);
            $subtypeId = (int)($_POST['subtype_id'] ?? 0);
            $labId = (int)($_POST['lab_id'] ?? 0);
            $details = trim($_POST['details'] ?? '');
            
            // Gizli alanlar
            $packType = $_POST['pack_type'] ?? '';
            $productionDate = $_POST['production_date'] ?? null;
            $expiryDate = $_POST['expiry_date'] ?? null;
            $lotNo = $_POST['lot_no'] ?? '';
            $degree = $_POST['degree'] ?? null;
            $amount = $_POST['amount'] ?? null;
            
            if (!$itemId) throw new Exception('Geçersiz kayıt');
            
            // sample_item'dan sample_id'yi al
            $stItem = $pdo->prepare("SELECT sample_id FROM sample_items WHERE id = :id");
            $stItem->execute([':id' => $itemId]);
            $sampleId = (int)$stItem->fetchColumn();
            
            // sample_full_accepts güncelle
            $upd = $pdo->prepare("
                UPDATE sample_full_accepts SET
                    reason = :reason,
                    types_json = :types,
                    subtypes_json = :subs,
                    labs_json = :labs,
                    details = :details
                WHERE sample_item_id = :item_id
            ");
            $upd->execute([
                ':item_id' => $itemId,
                ':reason' => $reason,
                ':types' => json_encode([$typeId]),
                ':subs' => json_encode([$subtypeId]),
                ':labs' => json_encode([$labId]),
                ':details' => $details
            ]);
            
            // sample_accepts güncelle
            $updAccept = $pdo->prepare("
                UPDATE sample_accepts SET
                    packaging_type = :pack,
                    degree_c = :deg,
                    amount_gr = :amt
                WHERE sample_item_id = :item_id
            ");
            $updAccept->execute([
                ':item_id' => $itemId,
                ':pack' => $packType ?: '(KAYIT)',
                ':deg' => $degree !== '' ? $degree : null,
                ':amt' => $amount !== '' ? $amount : null
            ]);
            
            // *** ÖNEMLİ: samples tablosunu da güncelle (analysis-result.php için) ***
            if ($sampleId) {
                $sCols = $pdo->query("SHOW COLUMNS FROM samples")->fetchAll(PDO::FETCH_COLUMN);
                
                // [DÜZELTME] Sütun adlarını küçük harfe çevir (Case Sensitivity sorunu için)
                $sCols = array_map('strtolower', $sCols);
                
                $updateParts = [];
                $updateParams = [':sid' => $sampleId];
                
                // type_id güncelle
                if (in_array('type_id', $sCols) && $typeId > 0) {
                    $updateParts[] = "type_id = :tid";
                    $updateParams[':tid'] = $typeId;
                } elseif (in_array('analysis_type_id', $sCols) && $typeId > 0) {
                    $updateParts[] = "analysis_type_id = :tid";
                    $updateParams[':tid'] = $typeId;
                }
                
                // subtype_id güncelle
                if (in_array('subtype_id', $sCols) && $subtypeId > 0) {
                    $updateParts[] = "subtype_id = :stid";
                    $updateParams[':stid'] = $subtypeId;
                } elseif (in_array('analysis_subtype_id', $sCols) && $subtypeId > 0) {
                    $updateParts[] = "analysis_subtype_id = :stid";
                    $updateParams[':stid'] = $subtypeId;
                }
                
                // detail güncelle
                if (in_array('detail', $sCols) && $details !== '') {
                    $updateParts[] = "detail = :det";
                    $updateParams[':det'] = $details;
                } elseif (in_array('analysis_detail', $sCols) && $details !== '') {
                    $updateParts[] = "analysis_detail = :det";
                    $updateParams[':det'] = $details;
                }
                
                if ($updateParts) {
                    $updateSql = "UPDATE samples SET " . implode(', ', $updateParts) . " WHERE id = :sid";
                    $pdo->prepare($updateSql)->execute($updateParams);
                }
            }
            
            echo json_encode(['success' => true, 'message' => $T['success_update']]);
            exit;
        }
        
        if ($action === 'delete') {
            $itemId = (int)($_POST['item_id'] ?? 0);
            if (!$itemId) throw new Exception('Geçersiz kayıt');
            
            $pdo->prepare("DELETE FROM sample_full_accepts WHERE sample_item_id = :id")->execute([':id' => $itemId]);
            $pdo->prepare("DELETE FROM sample_accepts WHERE sample_item_id = :id")->execute([':id' => $itemId]);
            
            echo json_encode(['success' => true, 'message' => $T['success_delete']]);
            exit;
        }
        
        echo json_encode(['success' => false, 'message' => 'Unknown action']);
        exit;
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}

/* ==== Tarih listesi (mevcut numune tarihleri) ==== */
$dateList = $pdo->query("SELECT DISTINCT DATE(sample_date) as dt FROM samples ORDER BY dt DESC")->fetchAll(PDO::FETCH_COLUMN);

/* ==== sample_items sütun kontrolü ==== */
$siCols = $pdo->query("SHOW COLUMNS FROM sample_items")->fetchAll(PDO::FETCH_COLUMN);
$hasReason = in_array('reason', $siCols, true);
$hasAnalysisTypeId = in_array('analysis_type_id', $siCols, true);
$hasTakeDate = in_array('take_date', $siCols, true);
$hasTakenPlace = in_array('taken_place', $siCols, true);
$hasSiAddress = in_array('address', $siCols, true);
$hasProductionDate = in_array('production_date', $siCols, true);
$hasExpiryDate = in_array('expiry_date', $siCols, true);
$hasLotNo = in_array('lot_no', $siCols, true);

/* ==== Numuneleri getir ==== */
$sql = "
    SELECT 
        si.id AS item_id,
        si.item_no,
        si.sample_name,
        si.producer_name,
        " . ($hasReason ? "si.reason AS item_reason," : "") . "
        " . ($hasAnalysisTypeId ? "si.analysis_type_id," : "") . "
        " . ($hasTakeDate ? "si.take_date AS item_take_date," : "") . "
        " . ($hasTakenPlace ? "si.taken_place," : "") . "
        " . ($hasSiAddress ? "si.address AS item_address," : "") . "
        " . ($hasProductionDate ? "si.production_date," : "") . "
        " . ($hasExpiryDate ? "si.expiry_date," : "") . "
        " . ($hasLotNo ? "si.lot_no," : "") . "
        s.id AS sample_id,
        s.sample_code AS main_sample_code,
        s.sample_date,
        s.address,
        s.delivered_by,
        f.id AS facility_id,
        f.name AS facility_name,
        lu.full_name AS taker_name,
        
        at.name_tr AS analysis_type_name_tr,
        at.name_en AS analysis_type_name_en,
        at.name_mk AS analysis_type_name_mk,
        at.lab_id AS analysis_lab_id,
        lab.name AS analysis_lab_name,
        
        sa.packaging_type,
        sa.degree_c,
        sa.amount_gr,
        sa.accept_date AS pre_accept_date,
        
        sfa.id AS full_accept_id,
        sfa.reason,
        sfa.types_json,
        sfa.subtypes_json,
        sfa.labs_json,
        sfa.details,
        sfa.accepted_at,
        sfa.accepted_by,
        sfa.sample_code,
        
        lu2.full_name AS accepted_by_name
        
    FROM sample_items si
    INNER JOIN samples s ON s.id = si.sample_id
    INNER JOIN facilities f ON f.id = s.facility_id
    INNER JOIN lab_users lu ON lu.id = s.taker_id
    LEFT JOIN analysis_types at ON at.id = " . ($hasAnalysisTypeId ? "si.analysis_type_id" : "NULL") . "
    LEFT JOIN laboratories lab ON lab.id = at.lab_id
    LEFT JOIN sample_accepts sa ON sa.sample_item_id = si.id
    LEFT JOIN sample_full_accepts sfa ON sfa.sample_item_id = si.id
    LEFT JOIN lab_users lu2 ON lu2.id = sfa.accepted_by
    WHERE 1=1
";
$params = [];

if ($filterDate) {
    $sql .= " AND DATE(s.sample_date) = :fdate";
    $params[':fdate'] = $filterDate;
}
if ($filterFacility > 0) {
    $sql .= " AND f.id = :ffac";
    $params[':ffac'] = $filterFacility;
}

$sql .= " ORDER BY s.sample_date DESC, si.item_no ASC";

$st = $pdo->prepare($sql);
$st->execute($params);
$rows = $st->fetchAll(PDO::FETCH_ASSOC);

/* ==== Sayaçlar ==== */
$pendingCount = 0;
$acceptedCount = 0;
foreach ($rows as $r) {
    if ($r['full_accept_id']) {
        $acceptedCount++;
    } else {
        $pendingCount++;
    }
}
?>
<!DOCTYPE html>
<html lang="<?=htmlspecialchars($lang)?>">
<head>
  <meta charset="utf-8">
  <title>Labx - <?=htmlspecialchars($T['page_title'])?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="bower_components/bootstrap/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="bower_components/font-awesome/css/font-awesome.min.css">
  <link rel="stylesheet" href="dist/css/main.css">
  <link rel="icon" type="image/png" href="/imgs/favicon.png">
  <style>
    .blk-title{background:#000;color:#fff;padding:8px 12px;font-weight:600;text-align:center;}
    .filter-box{background:#f5f5f5;padding:15px;border-radius:5px;margin-bottom:20px;}
    .count-box{display:inline-block;padding:10px 20px;border-radius:5px;margin:5px;font-weight:bold;}
    .count-pending{background:#fff3cd;color:#856404;border:2px solid #ffc107;}
    .count-accepted{background:#d4edda;color:#155724;border:2px solid #28a745;}
    .row-pending{background:#fff9e6 !important;}
    .row-accepted{background:#e8f5e9 !important;}
    .btn-accept{background:#28a745;color:#fff;border:none;}
    .btn-accept:hover{background:#218838;color:#fff;}
    .hidden-col{display:none;}
  </style>
</head>
<body>
<div id="ui" class="ui">

  <header id="header" class="ui-header">
    <div class="navbar-header">
      <a href="index.php" class="navbar-brand"><span class="logo"><img src="imgs/labx.png" width="100"></span></a>
    </div>
    <ul class="nav navbar-nav navbar-right">
      <li class="dropdown">
        <a href="#" class="dropdown-toggle" data-toggle="dropdown"><i class="fa fa-globe"></i> <?=$languages[$lang]?></a>
        <ul class="dropdown-menu">
          <?php foreach ($languages as $k=>$v): ?>
            <li><a href="?lang=<?=$k?>&db=<?=$selectedDb?>&filter_date=<?=$filterDate?>&filter_facility=<?=$filterFacility?>"><?=$v?></a></li>
          <?php endforeach; ?>
        </ul>
      </li>
    </ul>
  </header>

  <?php include "sidebar.php"; ?>

  <div id="content" class="ui-content">
    <div class="ui-content-body">
      <div class="ui-container">

        <div class="panel">
          <header class="blk-title"><?=htmlspecialchars($T['banner'])?></header>
          <div class="panel-body">

            <div class="filter-box">
              <form method="get" class="form-inline">
                <input type="hidden" name="lang" value="<?=$lang?>">
                <input type="hidden" name="db" value="<?=$selectedDb?>">
                
                <div class="form-group" style="margin-right:20px;">
                  <label><?=$T['filter_date']?></label>
                  <input type="date" name="filter_date" class="form-control" value="<?=htmlspecialchars($filterDate)?>" onchange="this.form.submit()">
                </div>
                
                <div class="form-group" style="margin-right:20px;">
                  <label><?=$T['filter_firm']?></label>
                  <select name="filter_facility" class="form-control" onchange="this.form.submit()">
                    <option value="0"><?=$T['all_firms']?></option>
                    <?php foreach($facilities as $f): ?>
                      <option value="<?=$f['id']?>" <?=$filterFacility==$f['id']?'selected':''?>><?=htmlspecialchars($f['name'])?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                
                <div class="form-group">
                  <span class="count-box count-pending">
                    <i class="fa fa-clock-o"></i> <?=$T['pending_count']?>: <strong><?=$pendingCount?></strong>
                  </span>
                  <span class="count-box count-accepted">
                    <i class="fa fa-check-circle"></i> <?=$T['accepted_count']?>: <strong><?=$acceptedCount?></strong>
                  </span>
                </div>
              </form>
            </div>

            <div class="table-responsive">
              <table class="table table-bordered table-hover" id="mainTable">
                <thead>
                  <tr style="background:#343a40;color:#fff;">
                    <th><?=$T['no']?></th>
                    <th><?=$T['sample_date']?></th>
                    <th><?=$T['facility']?></th>
                    <th><?=$T['producer']?></th>
                    <th><?=$T['address']?></th>
                    <th><?=$T['sample_name']?></th>
                    <th><?=$T['reason']?></th>
                    <th><?=$T['atype']?></th>
                    <th><?=$T['lab']?></th>
                    <th><?=$T['accept_date']?></th>
                    <th><?=$T['sample_code']?></th>
                    <th style="width:200px;"><?=$T['actions']?></th>
                  </tr>
                </thead>
                <tbody>
                  <?php if(!$rows): ?>
                    <tr><td colspan="12" class="text-center text-muted"><?=$T['no_data']?></td></tr>
                  <?php endif; ?>
                  <?php $rowNum = 1; foreach($rows as $r): 
                    $isAccepted = !empty($r['full_accept_id']);
                    $rowClass = $isAccepted ? 'row-accepted' : 'row-pending';
                    
                    // JSON verilerini parse et
                    $typeIds = json_decode($r['types_json'] ?? '[]', true) ?: [];
                    $subIds = json_decode($r['subtypes_json'] ?? '[]', true) ?: [];
                    $labIds = json_decode($r['labs_json'] ?? '[]', true) ?: [];
                    
                    $typeNames = [];
                    foreach ($typeIds as $tid) {
                        foreach ($types as $t) {
                            if ($t['id'] == $tid) { $typeNames[] = nm($t, $lang); break; }
                        }
                    }
                    
                    $labNames = [];
                    foreach ($labIds as $lid) {
                        foreach ($labs as $l) {
                            if ($l['id'] == $lid) { $labNames[] = $l['name']; break; }
                        }
                    }
                    
                    // Analiz Nedeni: kabul edilmişse sfa.reason, edilmemişse sample_items.reason
                    $displayReason = $isAccepted ? ($r['reason'] ?? '') : ($r['item_reason'] ?? '');
                    
                    // Analiz Türü: kabul edilmişse sfa'dan, edilmemişse sample_items.analysis_type_id'den
                    $displayTypeNames = $typeNames;
                    if (!$isAccepted && isset($r['analysis_type_id']) && $r['analysis_type_id']) {
                        // sample_items'tan gelen analysis_type_id'yi göster
                        $displayTypeNames = [];
                        foreach ($types as $t) {
                            if ($t['id'] == $r['analysis_type_id']) {
                                $displayTypeNames[] = nm($t, $lang);
                                break;
                            }
                        }
                    }
                    
                    // Analiz Laboratuvarı: kabul edilmişse sfa'dan, edilmemişse analysis_types.lab_id'den
                    $displayLabNames = $labNames;
                    if (!$isAccepted && !empty($r['analysis_lab_name'])) {
                        $displayLabNames = [$r['analysis_lab_name']];
                    }
                  ?>
                  <tr class="<?=$rowClass?>" data-row='<?=json_encode($r, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP)?>'>
                    <td><?=$rowNum++?></td>
                    <td><?=htmlspecialchars($r['sample_date'])?></td>
                    <td><?=htmlspecialchars($r['facility_name'])?></td>
                    <td><?=htmlspecialchars($r['producer_name'] ?: $r['facility_name'] ?? '')?></td>
                    <td><?=htmlspecialchars($r['item_address'] ?? $r['address'] ?? '')?></td>
                    <td><strong><?=htmlspecialchars($r['sample_name'])?></strong></td>
                    <td><?= $displayReason ? ($T['reason_opt'][$displayReason] ?? $displayReason) : '-' ?></td>
                    <td><?= implode(', ', $displayTypeNames) ?: '-' ?></td>
                    <td><?= implode(', ', $displayLabNames) ?: '-' ?></td>
                    <td><?=htmlspecialchars($r['accepted_at'] ?? '-')?></td>
                    <td><strong><?=htmlspecialchars($r['sample_code'] ?? '-')?></strong></td>
                    <td class="text-center">
                      <?php if(!$isAccepted): ?>
                        <button class="btn btn-sm btn-accept accept-btn" data-item="<?=$r['item_id']?>">
                          <i class="fa fa-check"></i> <?=$T['accept_btn']?>
                        </button>
                      <?php else: ?>
                        <button class="btn btn-sm btn-warning update-btn" data-item="<?=$r['item_id']?>">
                          <i class="fa fa-edit"></i> <?=$T['update_btn']?>
                        </button>
                        <button class="btn btn-sm btn-danger delete-btn" data-item="<?=$r['item_id']?>">
                          <i class="fa fa-trash"></i>
                        </button>
                      <?php endif; ?>
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

  <footer id="footer" class="ui-footer"><?=$current_texts['footer'] ?? '2025 &copy; Labx by Vektraweb.'?></footer>
</div>

<div class="modal fade" id="acceptModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header" style="background:#28a745;color:#fff;">
        <button type="button" class="close" data-dismiss="modal" style="color:#fff;">&times;</button>
        <h4 class="modal-title"><i class="fa fa-check-circle"></i> <span id="modalTitle"><?=$T['accept_btn']?></span></h4>
      </div>
      <div class="modal-body">
        <form id="acceptForm">
          <input type="hidden" name="item_id" id="f_item_id">
          <input type="hidden" name="action" id="f_action" value="accept">
          
          <div class="row">
            <div class="col-sm-4 form-group">
              <label><?=$T['reason']?></label>
              <select class="form-control" name="reason" id="f_reason">
                <?php foreach($T['reason_opt'] as $k=>$v): ?>
                  <option value="<?=$k?>"><?=$v?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-sm-4 form-group">
              <label><?=$T['atype']?></label>
              <select class="form-control" name="type_id" id="f_type">
                <option value="0"><?=$T['select']?></option>
                <?php foreach($types as $t): ?>
                  <option value="<?=$t['id']?>"><?=htmlspecialchars(nm($t,$lang))?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-sm-4 form-group">
              <label><?=$T['lab']?></label>
              <select class="form-control" name="lab_id" id="f_lab">
                <option value="0"><?=$T['select']?></option>
                <?php foreach($labs as $l): ?>
                  <option value="<?=$l['id']?>"><?=htmlspecialchars($l['name'])?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          
          <div class="row">
            <div class="col-sm-6 form-group">
              <label><?=$T['asubtype']?></label>
              <select class="form-control" name="subtype_id" id="f_subtype">
                <option value="0"><?=$T['select']?></option>
                <?php foreach($subtypes as $s): ?>
                  <option value="<?=$s['id']?>" data-type="<?=$s['type_id']?>"><?=htmlspecialchars(nm($s,$lang))?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-sm-6 form-group">
              <label><?=$T['details']?></label>
              <input type="text" class="form-control" name="details" id="f_details">
            </div>
          </div>
          
          <div id="hiddenFields" style="display:none; border-top:1px dashed #ccc; padding-top:15px; margin-top:15px;">
            <h5><i class="fa fa-eye-slash"></i> Ek Bilgiler</h5>
            <div class="row">
              <div class="col-sm-3 form-group">
                <label><?=$T['pack_type']?></label>
                <select class="form-control" name="pack_type" id="f_pack">
                  <?php foreach($packagingOptions as $p): ?>
                    <option value="<?=$p?>"><?=$p?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-sm-3 form-group">
                <label><?=$T['production_date']?></label>
                <input type="date" class="form-control" name="production_date" id="f_prod_date">
              </div>
              <div class="col-sm-3 form-group">
                <label><?=$T['expiry_date']?></label>
                <input type="date" class="form-control" name="expiry_date" id="f_exp_date">
              </div>
              <div class="col-sm-3 form-group">
                <label><?=$T['lot_no']?></label>
                <input type="text" class="form-control" name="lot_no" id="f_lot">
              </div>
            </div>
            <div class="row">
              <div class="col-sm-3 form-group">
                <label><?=$T['degree']?></label>
                <input type="number" step="0.1" class="form-control" name="degree" id="f_degree">
              </div>
              <div class="col-sm-3 form-group">
                <label><?=$T['amount']?></label>
                <input type="number" step="0.01" class="form-control" name="amount" id="f_amount">
              </div>
            </div>
          </div>
          
          <div class="alert alert-info" style="margin-top:15px;">
            <i class="fa fa-info-circle"></i>
            <strong><?=$T['accepted_by']?>:</strong> <span id="showAcceptedBy">Otomatik atanacak</span> |
            <strong><?=$T['accept_date']?>:</strong> <span id="showAcceptDate"><?=date('Y-m-d')?></span> |
            <strong><?=$T['sample_code']?>:</strong> <span id="showSampleCode">Otomatik oluşturulacak</span>
          </div>
        </form>
        <div id="modalAlert" class="alert" style="display:none;"></div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-default" data-dismiss="modal"><?=$T['cancel']?></button>
        <button class="btn btn-success" id="saveAccept"><i class="fa fa-save"></i> <?=$T['save']?></button>
      </div>
    </div>
  </div>
</div>

<script src="bower_components/jquery/dist/jquery.min.js"></script>
<script src="bower_components/bootstrap/dist/js/bootstrap.min.js"></script>
<script>
$(function(){
    // Alt tipi filtrele
    function filterSubtypes(){
        var selType = $('#f_type').val();
        $('#f_subtype option').each(function(){
            var t = $(this).data('type');
            $(this).toggle(!t || t == selType);
        });
    }
    $('#f_type').on('change', filterSubtypes);
    
    // Kabul Et butonu
    $('.accept-btn').on('click', function(){
        var row = $(this).closest('tr').data('row');
        $('#f_item_id').val(row.item_id);
        $('#f_action').val('accept');
        $('#modalTitle').text('<?=$T['accept_btn']?>');
        $('#hiddenFields').show(); // Ek Bilgileri göster
        
        // sample_items'tan gelen verileri doldur
        $('#f_reason').val(row.item_reason || 'OZEL_ISTEK');
        $('#f_type').val(row.analysis_type_id || 0);
        filterSubtypes();
        $('#f_subtype').val(0);
        $('#f_lab').val(row.analysis_lab_id || 0);
        $('#f_details').val('');
        
        // Ek bilgileri doldur (sample_items'tan)
        $('#f_pack').val(row.packaging_type || '(KAYIT)');
        $('#f_prod_date').val(row.production_date || '');
        $('#f_exp_date').val(row.expiry_date || '');
        $('#f_lot').val(row.lot_no || '');
        $('#f_degree').val('');
        $('#f_amount').val('');
        
        $('#showSampleCode').text('NK-<?=date("Ymd")?>-' + String(row.item_id).padStart(4,'0'));
        $('#showAcceptedBy').text('Otomatik atanacak');
        $('#showAcceptDate').text('<?=date("Y-m-d")?>');
        
        $('#modalAlert').hide();
        $('#acceptModal').modal('show');
    });
    
    // Güncelle butonu
    $('.update-btn').on('click', function(){
        var row = $(this).closest('tr').data('row');
        $('#f_item_id').val(row.item_id);
        $('#f_action').val('update');
        $('#modalTitle').text('<?=$T['update_btn']?>');
        $('#hiddenFields').show();
        
        $('#f_reason').val(row.reason || row.item_reason || 'OZEL_ISTEK');
        
        var types = [];
        try { types = JSON.parse(row.types_json || '[]'); } catch(e){}
        $('#f_type').val(types[0] || row.analysis_type_id || 0);
        filterSubtypes();
        
        var subs = [];
        try { subs = JSON.parse(row.subtypes_json || '[]'); } catch(e){}
        $('#f_subtype').val(subs[0] || 0);
        
        var labs = [];
        try { labs = JSON.parse(row.labs_json || '[]'); } catch(e){}
        $('#f_lab').val(labs[0] || row.analysis_lab_id || 0);
        
        $('#f_details').val(row.details || '');
        $('#f_pack').val(row.packaging_type || '(KAYIT)');
        $('#f_prod_date').val(row.production_date || '');
        $('#f_exp_date').val(row.expiry_date || '');
        $('#f_lot').val(row.lot_no || '');
        $('#f_degree').val(row.degree_c || '');
        $('#f_amount').val(row.amount_gr || '');
        
        $('#showSampleCode').text(row.sample_code || '-');
        $('#showAcceptDate').text(row.accepted_at || '<?=date("Y-m-d")?>');
        $('#showAcceptedBy').text(row.accepted_by_name || 'Otomatik');
        
        $('#modalAlert').hide();
        $('#acceptModal').modal('show');
    });
    
    // Kaydet
    $('#saveAccept').on('click', function(){
        var data = $('#acceptForm').serialize();
        data += '&ajax=1';
        
        $.post('sample-accept.php?lang=<?=$lang?>&db=<?=$selectedDb?>', data, function(res){
            if(res && res.success){
                location.reload();
            } else {
                $('#modalAlert').removeClass('alert-success').addClass('alert-danger').text(res.message || 'Hata').show();
            }
        }, 'json').fail(function(xhr){
            $('#modalAlert').removeClass('alert-success').addClass('alert-danger').text(xhr.responseText || 'Hata').show();
        });
    });
    
    // Sil
    $('.delete-btn').on('click', function(){
        if(!confirm('<?=addslashes($T['confirm_delete'])?>')) return;
        var itemId = $(this).data('item');
        
        $.post('sample-accept.php?lang=<?=$lang?>&db=<?=$selectedDb?>', 
            {ajax:1, action:'delete', item_id:itemId},
            function(res){
                if(res && res.success){ location.reload(); }
                else { alert(res.message || 'Hata'); }
            }, 'json'
        ).fail(function(xhr){ alert(xhr.responseText || 'Hata'); });
    });
});
</script>
</body>
</html>