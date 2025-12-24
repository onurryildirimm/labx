<?php
require_once "db.php";
if (session_status() === PHP_SESSION_NONE) { session_start(); }

/* ==== Ortak parametreler ==== */
$lang       = $_GET['lang'] ?? 'tr';
$selectedDb = $_GET['db']   ?? '2025';
$languages  = ['tr' => 'Türkçe', 'en' => 'English', 'mk' => 'Македонски'];

/* ==== Genel metinler ==== */
require_once "language_analiz.php";
$current_texts = $texts[$lang] ?? $texts['tr'];

/* ==== Bu sayfaya özgü metinler ==== */
$I = [
  'tr' => [
    'page_title' => 'Numune Kayıt',
    'banner'     => 'NUMUNE KAYIT',

    'date'       => 'Tarih',
    'firm_pick'  => 'Numune Alınan Firma Seç',
    'select'     => 'Seçiniz',
    'info_date'  => 'Günün tarihi otomatik gelir.',

    'taker'      => 'Numuneyi Alan Kişi',
    'taker_note' => 'Laboratuvar görev tanımından seçilir. Zorunlu',
    'address_top'=> 'Numune Alınan Adres',

    'add_row'    => 'Numune Satırı Ekle',
    'save'       => 'Kaydet / Gönder',
    'success'    => 'Kayıt başarıyla kaydedildi.',

    'err_firm'   => "Lütfen 'Numune Alınan Firma'yı seçin.",
    'err_rows'   => "En az bir satırda 'Numune Adı' dolu olmalıdır.",
    'err_date'   => "Tarih formatı geçersiz.",
    'err_taker'  => "Lütfen 'Numuneyi Alan Kişi'yi seçin.",
    'err_addr'   => "Lütfen 'Numune Alınan Adres'i yazın.",

    // tablo başlıkları
    'no'         => 'No',
    'take_date'  => 'Numune Alım Tarihi',
    'taken_firm' => 'Alınan Firma',
    'plant_prod' => 'Alınan Tesis / Üretici Adı',
    'address'    => 'Alınan Adres',
    'sname'      => 'Numune Adı',
    'place'      => 'Alınan Yer',
    'pack'       => 'Ambalaj Türü',
    'production_date' => 'Üretim Tarihi',
    'expiry_date'=> 'Son Kullanma Tarihi',
    'lot_no'     => 'Parti/Lot No',
    'note'       => 'Açıklama',
    'reason'     => 'Analiz Nedeni',
    'atype'      => 'Analiz Türü',
    'deg'        => 'Numune Derecesi °C',
    'amt'        => 'Numune Miktarı gr',
    'acc_date'   => 'Numune Kabul Tarihi',
    'acc_by'     => 'Numuneyi Kabul Eden',
    'actions'    => 'Güncelle / Sil',
    'upd'        => 'GÜNCELLE',
    'accept'     => 'KABUL ET',
    'del'        => 'SİL',
    
    'delivered_by' => 'Numune Teslim Eden',
    'delivered_ph' => 'Teslim eden kişi adı',
    
    'registered_samples' => 'KAYIT EDİLMİŞ NUMUNELER',
    'registered_today'   => 'Bugün kayıt edilen numuneler',
    'sample_code'        => 'Numune Kodu',
    'update_btn'         => 'Güncelle',

    'addr_ph'    => 'Adres (zorunlu değil)',
    'prod_ph'    => 'Üretici / Tesis adı',
    'sname_ph'   => 'Numune adı',
    'place_ph'   => 'Örn: Depo / Sevkiyat / Üretim',
    'note_ph'    => 'Kısa açıklama',
  ],
  'en' => [
    'page_title' => 'Sample Registration',
    'banner'     => 'SAMPLE REGISTRATION',
    'date'       => 'Date',
    'firm_pick'  => 'Select Facility',
    'select'     => 'Select',
    'info_date'  => 'Prefilled with today.',
    'taker'      => 'Taken By',
    'taker_note' => 'Select from lab staff. Required',
    'address_top'=> 'Sample Address',
    'add_row'    => 'Add Sample Row',
    'save'       => 'Save / Send',
    'success'    => 'Saved successfully.',
    'err_firm'   => "Please select a facility.",
    'err_rows'   => "At least one row must include a Sample Name.",
    'err_date'   => "Invalid date.",
    'err_taker'  => "Please select who took the sample.",
    'err_addr'   => "Please enter the sample address.",

    'no'         => 'No',
    'take_date'  => 'Sample Date',
    'taken_firm' => 'Facility',
    'plant_prod' => 'Plant / Producer',
    'address'    => 'Address',
    'sname'      => 'Sample Name',
    'place'      => 'Location',
    'pack'       => 'Package Type',
    'production_date' => 'Production Date',
    'expiry_date'=> 'Expiry Date',
    'lot_no'     => 'Lot No',
    'note'       => 'Note',
    'reason'     => 'Reason',
    'atype'      => 'Analysis Type',
    'deg'        => 'Temp °C',
    'amt'        => 'Amount (g)',
    'acc_date'   => 'Acceptance Date',
    'acc_by'     => 'Accepted By',
    'actions'    => 'Update / Delete',
    'upd'        => 'UPDATE',
    'accept'     => 'ACCEPT',
    'del'        => 'DELETE',
    
    'delivered_by' => 'Delivered By',
    'delivered_ph' => 'Name of person who delivered',
    
    'registered_samples' => 'REGISTERED SAMPLES',
    'registered_today'   => 'Samples registered today',
    'sample_code'        => 'Sample Code',
    'update_btn'         => 'Update',

    'addr_ph'    => 'Address (optional)',
    'prod_ph'    => 'Producer / Plant',
    'sname_ph'   => 'Sample name',
    'place_ph'   => 'e.g., Warehouse / Shipping / Line',
    'note_ph'    => 'Short note',
  ],
  'mk' => [
    'page_title' => 'Регистрација на примерок',
    'banner'     => 'РЕГИСТРАЦИЈА НА ПРИМЕРОК',
    'date'       => 'Датум',
    'firm_pick'  => 'Изберете објект',
    'select'     => 'Изберете',
    'info_date'  => 'Денешен датум.',
    'taker'      => 'Лице што зема примерок',
    'taker_note' => 'Одберете од лабораторискиот персонал. Задолжително',
    'address_top'=> 'Адреса на земање',
    'add_row'    => 'Додади ред',
    'save'       => 'Зачувај',
    'success'    => 'Успешно зачувано.',
    'err_firm'   => "Изберете објект.",
    'err_rows'   => "Барем еден ред мора да има име на примерок.",
    'err_date'   => "Невалиден датум.",
    'err_taker'  => "Изберете лице што зема примерок.",
    'err_addr'   => "Внесете адреса на земање.",

    'no'         => 'Бр',
    'take_date'  => 'Датум на земање',
    'taken_firm' => 'Објект',
    'plant_prod' => 'Погон / Производител',
    'address'    => 'Адреса',
    'sname'      => 'Име на примерок',
    'place'      => 'Локација',
    'pack'       => 'Амбалажа',
    'production_date' => 'Датум на производство',
    'expiry_date'=> 'Рок на траење',
    'lot_no'     => 'Лот бр.',
    'note'       => 'Белешка',
    'reason'     => 'Причина',
    'atype'      => 'Тип анализа',
    'deg'        => 'Темп °C',
    'amt'        => 'Количина (g)',
    'acc_date'   => 'Датум на прием',
    'acc_by'     => 'Прифатил',
    'actions'    => 'Прифати / Ажурирај / Избриши',
    'upd'        => 'АЖУРИРАЈ',
    'accept'     => 'ПРИФАТИ',
    'del'        => 'ИЗБРИШИ',
    
    'delivered_by' => 'Достави',
    'delivered_ph' => 'Име на лице што достави',
    
    'registered_samples' => 'РЕГИСТРИРАНИ ПРИМЕРОЦИ',
    'registered_today'   => 'Примероци регистрирани денес',
    'sample_code'        => 'Код на примерок',
    'update_btn'         => 'Ажурирај',

    'addr_ph'    => 'Адреса (незад.)',
    'prod_ph'    => 'Производител / Погон',
    'sname_ph'   => 'Име на примерок',
    'place_ph'   => 'Пр. магацин / испорака / линија',
    'note_ph'    => 'Кратка белешка',
  ],
];
$T = $I[$lang];

/* ==== Seçim listeleri ==== */
$facilities = $pdo->query("SELECT id, name FROM facilities ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$labUsers   = $pdo->query("SELECT id, full_name FROM lab_users ORDER BY full_name")->fetchAll(PDO::FETCH_ASSOC);
$types      = $pdo->query("SELECT id, name_tr, name_en, name_mk FROM analysis_types ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
$labName    = fn($r) => ($lang==='en' ? $r['name_en'] : ($lang==='mk' ? $r['name_mk'] : $r['name_tr']));

/* ==== sample_items için esnek kolonlar ==== */
$siCols = $pdo->query("SHOW COLUMNS FROM sample_items")->fetchAll(PDO::FETCH_COLUMN);
$hasCol = function($c) use ($siCols) { return in_array($c, $siCols, true); };

/* ==== samples tablosunda delivered_by var mı kontrol ==== */
$sCols = $pdo->query("SHOW COLUMNS FROM samples")->fetchAll(PDO::FETCH_COLUMN);
$hasDeliveredBy = in_array('delivered_by', $sCols, true);

/* ==== Ambalaj ENUM değerleri (DB ile birebir) ==== */
$packagingOptions = [
    'ORJINAL AMBALAJ',
    'STERIL KAP',
    'STERIL SWAB',
    'STERIL PETRI',
    'STERIL ŞİŞE',
    'POŞET',
    '(KAYIT)'
];

/* ==== Seçili firma (GET) ==== */
$selectedFacilityId = isset($_GET['facility_id']) ? (int)$_GET['facility_id'] : 0;

/* ==== Kayıt işlemi ==== */
$errors  = [];
$success = isset($_GET['ok']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $facility_id = (int)($_POST['facility_id'] ?? 0);
    $selectedFacilityId = $facility_id;
    $form_date   = trim($_POST['form_date'] ?? date('Y-m-d'));
    $taker_id    = (int)($_POST['taker_id'] ?? 0);
    $top_address = trim($_POST['top_address'] ?? '');
    $delivered_by = trim($_POST['delivered_by'] ?? '');

    if (!$facility_id) $errors[] = $T['err_firm'];
    if (!$taker_id)    $errors[] = $T['err_taker'];
    if ($top_address === '') $errors[] = $T['err_addr'];
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $form_date)) $errors[] = $T['err_date'];

    // Satırları topla
    $rows = [];
    $cnt  = isset($_POST['sname']) ? count($_POST['sname']) : 0;

    for ($i = 0; $i < $cnt; $i++) {
        $sname = trim($_POST['sname'][$i] ?? '');
        if ($sname === '') continue;

        $rows[] = [
            'no'        => (int)($_POST['no'][$i] ?? ($i + 1)),
            'take_date' => $_POST['take_date'][$i] ?? $form_date,
            'producer'  => trim($_POST['producer'][$i] ?? ''),
            'addr'      => trim($_POST['addr'][$i] ?? ''),
            'sname'     => $sname,
            'place'     => trim($_POST['place'][$i] ?? ''),
            'pack'      => trim($_POST['pack'][$i] ?? ''),
            'production_date' => trim($_POST['production_date'][$i] ?? ''),
            'expiry_date' => trim($_POST['expiry_date'][$i] ?? ''),
            'lot_no'    => trim($_POST['lot_no'][$i] ?? ''),
            'reason'    => $_POST['reason'][$i] ?? null,
            'atype'     => (int)($_POST['atype'][$i] ?? 0),
        ];
    }

    if (!$rows) $errors[] = $T['err_rows'];

    if (!$errors) {
        try {
            $pdo->beginTransaction();

            // samples kaydı
            if ($hasDeliveredBy) {
                $stSample = $pdo->prepare("
                    INSERT INTO samples (sample_code, facility_id, sample_date, taker_id, delivered_by, address, is_finalized, created_at)
                    VALUES ('', :f, :d, :t, :db, :a, 0, NOW())
                ");
                $stSample->execute([
                    ':f' => $facility_id,
                    ':d' => $form_date,
                    ':t' => $taker_id,
                    ':db' => $delivered_by,
                    ':a' => $top_address
                ]);
            } else {
                $stSample = $pdo->prepare("
                    INSERT INTO samples (sample_code, facility_id, sample_date, taker_id, address, is_finalized, created_at)
                    VALUES ('', :f, :d, :t, :a, 0, NOW())
                ");
                $stSample->execute([
                    ':f' => $facility_id,
                    ':d' => $form_date,
                    ':t' => $taker_id,
                    ':a' => $top_address
                ]);
            }

            $sample_id = (int)$pdo->lastInsertId();
            $code = date('Ymd') . '-' . str_pad((string)$sample_id, 4, '0', STR_PAD_LEFT);
            $pdo->prepare("UPDATE samples SET sample_code = :c WHERE id = :id")
                ->execute([':c' => $code, ':id' => $sample_id]);

            // sample_items insert
            foreach ($rows as $r) {
                $rowAddr = $r['addr'] !== '' ? $r['addr'] : $top_address;

                $cols = ['sample_id', 'item_no', 'sample_name'];
                $vals = [':sid', ':no', ':sn'];
                $bind = [
                    ':sid' => $sample_id,
                    ':no'  => $r['no'],
                    ':sn'  => $r['sname'],
                ];

                if ($hasCol('producer_name')) {
                    $cols[] = 'producer_name'; $vals[] = ':pn';
                    $bind[':pn'] = $r['producer'] ?: null;
                }
                if ($hasCol('address')) {
                    $cols[] = 'address'; $vals[] = ':ad';
                    $bind[':ad'] = $rowAddr ?: null;
                }
                if ($hasCol('taken_place')) {
                    $cols[] = 'taken_place'; $vals[] = ':pl';
                    $bind[':pl'] = $r['place'] ?: null;
                }
                if ($hasCol('reason')) {
                    $cols[] = 'reason'; $vals[] = ':rs';
                    $bind[':rs'] = $r['reason'] ?: null;
                }
                if ($hasCol('analysis_type_id')) {
                    $cols[] = 'analysis_type_id'; $vals[] = ':at';
                    $bind[':at'] = $r['atype'] ?: null;
                }
                if ($hasCol('take_date')) {
                    $cols[] = 'take_date'; $vals[] = ':td';
                    $bind[':td'] = $r['take_date'] ?: null;
                }
                if ($hasCol('production_date')) {
                    $cols[] = 'production_date'; $vals[] = ':prd';
                    $bind[':prd'] = $r['production_date'] ?: null;
                }
                if ($hasCol('expiry_date')) {
                    $cols[] = 'expiry_date'; $vals[] = ':exp';
                    $bind[':exp'] = $r['expiry_date'] ?: null;
                }
                if ($hasCol('lot_no')) {
                    $cols[] = 'lot_no'; $vals[] = ':lot';
                    $bind[':lot'] = $r['lot_no'] ?: null;
                }

                $sqlItems = "INSERT INTO sample_items (" . implode(',', $cols) . ") VALUES (" . implode(',', $vals) . ")";
                $pdo->prepare($sqlItems)->execute($bind);
            }

            $pdo->commit();
            header("Location: lab-sample-add.php?lang={$lang}&db={$selectedDb}&facility_id={$facility_id}&ok=1");
            exit;

        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $errors[] = "DB Error: " . $e->getMessage();
        }
    }
}

/* ==== BUGÜN KAYIT EDİLMİŞ NUMUNELER ==== */
$todaySamples = [];
$today = date('Y-m-d');

// Dinamik sütun kontrolü
$extraCols = "";
if ($hasCol('reason')) $extraCols .= ", si.reason";
if ($hasCol('analysis_type_id')) $extraCols .= ", si.analysis_type_id, at.name_tr AS analysis_type_tr, at.name_en AS analysis_type_en, at.name_mk AS analysis_type_mk";

$sqlToday = "
    SELECT 
        s.id AS sample_id,
        s.sample_code,
        s.sample_date,
        s.address,
        f.name AS facility_name,
        lu.full_name AS taker_name,
        si.id AS item_id,
        si.item_no,
        si.sample_name,
        si.producer_name
        {$extraCols}
    FROM samples s
    INNER JOIN facilities f ON f.id = s.facility_id
    INNER JOIN lab_users lu ON lu.id = s.taker_id
    INNER JOIN sample_items si ON si.sample_id = s.id
    " . ($hasCol('analysis_type_id') ? "LEFT JOIN analysis_types at ON at.id = si.analysis_type_id" : "") . "
    WHERE DATE(s.sample_date) = :today
    ORDER BY s.id DESC, si.item_no ASC
";
$stToday = $pdo->prepare($sqlToday);
$stToday->execute([':today' => $today]);
$todaySamples = $stToday->fetchAll(PDO::FETCH_ASSOC);
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
    .sub-title{background:#f0ad4e;color:#fff;padding:8px 12px;font-weight:600;text-align:center;margin-top:20px;}
    .table thead th{vertical-align:middle;text-align:left;}
    .table tbody td{vertical-align:middle;}
    .thin{padding:6px 8px;height:32px;}
    .btn-mini{padding:3px 6px;font-size:12px;line-height:1.2}
    .btn-yellow{background:#ffd800;border-color:#e7c400;color:#333}
    .btn-green{background:#3fbf62;border-color:#33a852;color:#fff}
    .delivered-box{background:#fffde7;border:1px solid #ffc107;padding:15px;border-radius:5px;margin:15px 0;}
  </style>
</head>
<body>
<div id="ui" class="ui">

  <!-- Header -->
  <header id="header" class="ui-header">
    <div class="navbar-header">
      <a href="index.php" class="navbar-brand">
        <span class="logo"><img src="imgs/labx.png" width="100"></span>
      </a>
    </div>
    <ul class="nav navbar-nav navbar-right">
      <li class="dropdown">
        <a href="#" class="dropdown-toggle" data-toggle="dropdown">
          <i class="fa fa-globe"></i> <?=$languages[$lang]?>
        </a>
        <ul class="dropdown-menu">
          <?php foreach ($languages as $k=>$v): ?>
            <li><a href="?lang=<?=$k?>&db=<?=htmlspecialchars($selectedDb)?>"><?=$v?></a></li>
          <?php endforeach; ?>
        </ul>
      </li>
      <li class="dropdown">
        <a href="#" class="dropdown-toggle" data-toggle="dropdown">
          <i class="fa fa-database"></i> <?=$current_texts['select_db'] ?? 'Veritabanı Seç'?>
        </a>
        <ul class="dropdown-menu">
          <?php foreach (['2023','2024','2025'] as $db): ?>
            <li><a href="?lang=<?=$lang?>&db=<?=$db?>"><?=$db?></a></li>
          <?php endforeach; ?>
        </ul>
      </li>
    </ul>
  </header>

  <!-- Sidebar -->
  <?php include "sidebar.php"; ?>

  <!-- Content -->
  <div id="content" class="ui-content">
    <div class="ui-content-body">
      <div class="ui-container">

        <div class="panel">
          <header class="blk-title"><?=htmlspecialchars($T['banner'])?></header>
          <div class="panel-body">

            <?php if($errors): ?>
              <div class="alert alert-danger">
                <?php foreach($errors as $e) echo "<div>".htmlspecialchars($e)."</div>"; ?>
              </div>
            <?php elseif($success): ?>
              <div class="alert alert-success"><?=$T['success']?></div>
            <?php endif; ?>

            <form method="post" id="frm"
                  action="lab-sample-add.php?lang=<?=$lang?>&db=<?=$selectedDb?><?php if($selectedFacilityId>0) echo '&facility_id='.$selectedFacilityId; ?>">

              <!-- ÜST BLOK -->
              <div class="row">
                <div class="col-sm-3 form-group">
                  <label><?=$T['date']?></label>
                  <input type="date" class="form-control" name="form_date" value="<?=date('Y-m-d')?>">
                  <small class="text-muted"><?=$T['info_date']?></small>
                </div>

                <div class="col-sm-3 form-group">
                  <label><?=$T['firm_pick']?></label>
                  <select name="facility_id" class="form-control" id="facilityMain">
                    <option value=""><?=$T['select']?></option>
                    <?php foreach($facilities as $f): ?>
                      <option value="<?=$f['id']?>"
                        <?= ($selectedFacilityId === (int)$f['id']) ? 'selected' : '' ?>>
                        <?=htmlspecialchars($f['name'])?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>

                <div class="col-sm-3 form-group">
                  <label><?=$T['taker']?></label>
                  <select name="taker_id" class="form-control">
                    <option value=""><?=$T['select']?></option>
                    <?php foreach($labUsers as $u): ?>
                      <option value="<?=$u['id']?>"><?=htmlspecialchars($u['full_name'])?></option>
                    <?php endforeach; ?>
                  </select>
                  <small class="text-muted"><?=$T['taker_note']?></small>
                </div>

                <div class="col-sm-3 form-group">
                  <label><?=$T['address_top']?></label>
                  <input type="text" name="top_address" class="form-control" placeholder="<?=$T['addr_ph']?>">
                </div>
              </div>

              <!-- NUMUNE KAYIT TABLOSU -->
              <div class="table-responsive">
                <table class="table table-bordered" id="tbl">
                  <thead>
                    <tr>
                      <th><?=$T['no']?></th>
                      <th><?=$T['sname']?></th>
                      <th><?=$T['place']?></th>
                      <th><?=$T['pack']?></th>
                      <th><?=$T['production_date']?></th>
                      <th><?=$T['expiry_date']?></th>
                      <th><?=$T['lot_no']?></th>
                      <th><?=$T['reason']?></th>
                      <th><?=$T['atype']?></th>
                      <th><?=$T['actions']?></th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr>
                      <td style="width:60px">
                        <input name="no[]" class="form-control thin text-center" value="1" readonly>
                      </td>
                      <td>
                        <input name="sname[]" class="form-control thin" placeholder="<?=$T['sname_ph']?>">
                        <input type="hidden" name="take_date[]" value="<?=date('Y-m-d')?>">
                        <input type="hidden" name="producer[]" value="">
                        <input type="hidden" name="addr[]" value="">
                      </td>
                      <td>
                        <input name="place[]" class="form-control thin" placeholder="<?=$T['place_ph']?>">
                      </td>
                      <td style="width:140px">
                        <select name="pack[]" class="form-control thin">
                          <?php foreach($packagingOptions as $p): ?>
                            <option value="<?=htmlspecialchars($p)?>"><?=htmlspecialchars($p)?></option>
                          <?php endforeach; ?>
                        </select>
                      </td>
                      <td style="width:130px">
                        <input type="date" name="production_date[]" class="form-control thin">
                      </td>
                      <td style="width:130px">
                        <input type="date" name="expiry_date[]" class="form-control thin">
                      </td>
                      <td style="width:100px">
                        <input name="lot_no[]" class="form-control thin" placeholder="Lot No">
                      </td>
                      <td style="width:130px">
                        <select name="reason[]" class="form-control thin">
                          <option value="OZEL_ISTEK">Özel İstek</option>
                          <option value="YASAL_ANALIZ">Yasal Analiz</option>
                        </select>
                      </td>
                      <td style="width:160px">
                        <select name="atype[]" class="form-control thin">
                          <option value="0"><?=$T['select']?></option>
                          <?php foreach($types as $t): ?>
                            <option value="<?=$t['id']?>"><?=htmlspecialchars($labName($t))?></option>
                          <?php endforeach; ?>
                        </select>
                      </td>
                      <td style="width:80px" class="text-center">
                        <button type="button" class="btn btn-mini btn-danger row-del">
                          <i class="fa fa-trash"></i>
                        </button>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <!-- NUMUNE SATIRI EKLE - TESLİM EDEN - KAYDET BUTONU -->
              <div class="row">
                <div class="col-sm-3">
                  <button type="button" id="rowAdd" class="btn btn-default">
                    <i class="fa fa-plus"></i> <?=$T['add_row']?>
                  </button>
                </div>
                <div class="col-sm-6">
                  <div class="delivered-box">
                    <label><i class="fa fa-user"></i> <?=$T['delivered_by']?></label>
                    <input type="text" name="delivered_by" class="form-control" placeholder="<?=$T['delivered_ph']?>">
                  </div>
                </div>
                <div class="col-sm-3 text-right" style="padding-top:25px;">
                  <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fa fa-save"></i> <strong><?=$T['save']?></strong>
                  </button>
                </div>
              </div>

            </form>

            <!-- KAYIT EDİLMİŞ NUMUNELER BÖLÜMÜ -->
            <div class="sub-title"><?=$T['registered_samples']?></div>
            <p class="text-muted" style="margin-top:10px;"><i class="fa fa-info-circle"></i> <?=$T['registered_today']?></p>
            
            <?php if (!empty($todaySamples)): ?>
              <div class="table-responsive">
                <table class="table table-bordered table-striped">
                  <thead>
                    <tr style="background:#fff3cd;">
                      <th><?=$T['no']?></th>
                      <th><?=$T['take_date']?></th>
                      <th><?=$T['taken_firm']?></th>
                      <th><?=$T['plant_prod']?></th>
                      <th><?=$T['address']?></th>
                      <th><?=$T['sname']?></th>
                      <th><?=$T['atype']?></th>
                      <th><?=$T['reason']?></th>
                      <th><?=$T['actions']?></th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php 
                    $rowNum = 1;
                    foreach ($todaySamples as $ts): 
                      // Analiz Türü adını al
                      $atypeName = '-';
                      if (isset($ts['analysis_type_id']) && $ts['analysis_type_id']) {
                        if ($lang === 'en' && isset($ts['analysis_type_en'])) {
                          $atypeName = $ts['analysis_type_en'];
                        } elseif ($lang === 'mk' && isset($ts['analysis_type_mk'])) {
                          $atypeName = $ts['analysis_type_mk'];
                        } elseif (isset($ts['analysis_type_tr'])) {
                          $atypeName = $ts['analysis_type_tr'];
                        }
                      }
                      
                      // Analiz Nedeni
                      $reasonDisplay = '-';
                      if (isset($ts['reason']) && $ts['reason']) {
                        $reasonLabels = [
                          'tr' => ['OZEL_ISTEK' => 'Özel İstek', 'YASAL_ANALIZ' => 'Yasal Analiz'],
                          'en' => ['OZEL_ISTEK' => 'Special Request', 'YASAL_ANALIZ' => 'Legal Analysis'],
                          'mk' => ['OZEL_ISTEK' => 'Посебно барање', 'YASAL_ANALIZ' => 'Правна анализа']
                        ];
                        $reasonDisplay = $reasonLabels[$lang][$ts['reason']] ?? $ts['reason'];
                      }
                    ?>
                      <tr>
                        <td><?=$rowNum++?></td>
                        <td><?=htmlspecialchars($ts['sample_date'])?></td>
                        <td><?=htmlspecialchars($ts['facility_name'])?></td>
                        <td><?=htmlspecialchars($ts['producer_name'] ?? '')?></td>
                        <td><?=htmlspecialchars($ts['address'] ?? '')?></td>
                        <td><?=htmlspecialchars($ts['sample_name'])?></td>
                        <td><?=htmlspecialchars($atypeName)?></td>
                        <td><?=htmlspecialchars($reasonDisplay)?></td>
                        <td class="text-center">
                          <a href="sample-update.php?lang=<?=$lang?>&db=<?=$selectedDb?>&id=<?=$ts['item_id']?>" 
                             class="btn btn-xs btn-yellow">
                            <i class="fa fa-edit"></i> <?=$T['update_btn']?>
                          </a>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            <?php else: ?>
              <p class="text-muted text-center" style="padding:20px;">
                <i class="fa fa-inbox"></i> <?=($lang==='tr' ? 'Bugün kayıt edilmiş numune bulunmuyor.' : ($lang==='en' ? 'No samples registered today.' : 'Нема регистрирани примероци денес.'))?> 
              </p>
            <?php endif; ?>

          </div>
        </div>

      </div>
    </div>
  </div>

  <footer id="footer" class="ui-footer">
    <?=$current_texts['footer'] ?? '2025 &copy; Labx by Vektraweb.'?>
  </footer>
</div>

<script src="bower_components/jquery/dist/jquery.min.js"></script>
<script src="bower_components/bootstrap/dist/js/bootstrap.min.js"></script>
<script>
(function(){
  function renumber(){
    $('#tbl tbody tr').each(function(i){
      $(this).find('input[name="no[]"]').val(i+1);
    });
  }

  $('#rowAdd').on('click', function(){
    var $last = $('#tbl tbody tr:last');
    var $tr   = $last.clone();

    $tr.find('input,select').each(function(){
      var name = this.name;
      if (name === 'no[]') return;

      if (this.type === 'date') {
        this.value = '';
      } else if (this.tagName === 'SELECT') {
        this.selectedIndex = 0;
      } else {
        this.value = '';
      }
    });

    $('#tbl tbody').append($tr);
    renumber();
  });

  $('#tbl').on('click', '.row-del', function(){
    if ($('#tbl tbody tr').length > 1) {
      $(this).closest('tr').remove();
      renumber();
    }
  });

  // Firma değişince sayfayı yeniden yükle
  $('select[name="facility_id"]').on('change', function(){
    var lang = "<?= addslashes($lang) ?>";
    var db   = "<?= addslashes($selectedDb) ?>";
    var fid  = $(this).val() || '';

    var url = "lab-sample-add.php?lang=" + encodeURIComponent(lang) +
              "&db=" + encodeURIComponent(db);
    if (fid !== '') {
      url += "&facility_id=" + encodeURIComponent(fid);
    }
    window.location.href = url;
  });
  
  // Sayfa yüklendiğinde ve form submit öncesi producer alanını doldur
  function updateProducerFields() {
    var facilityName = $('select[name="facility_id"] option:selected').text().trim();
    if (facilityName && facilityName !== '<?=$T['select']?>') {
      $('input[name="producer[]"]').val(facilityName);
    }
  }
  
  // Sayfa yüklendiğinde
  updateProducerFields();
  
  // Satır eklendiğinde de doldur
  var origRowAdd = $('#rowAdd').off('click').on('click', function(){
    var $last = $('#tbl tbody tr:last');
    var $tr   = $last.clone();

    $tr.find('input,select').each(function(){
      var name = this.name;
      if (name === 'no[]' || name === 'producer[]') return;

      if (this.type === 'date') {
        this.value = '';
      } else if (this.tagName === 'SELECT') {
        this.selectedIndex = 0;
      } else {
        this.value = '';
      }
    });

    $('#tbl tbody').append($tr);
    renumber();
    updateProducerFields();
  });
})();
</script>
</body>
</html>