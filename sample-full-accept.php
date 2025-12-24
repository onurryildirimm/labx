<?php
require_once "db.php";
if (session_status() === PHP_SESSION_NONE) { session_start(); }

/* ==== Ortak parametreler ==== */
$lang = $_GET['lang'] ?? 'tr';
$selectedDb = $_GET['db'] ?? '2025';
$languages = ['tr' => 'Türkçe', 'en' => 'English', 'mk' => 'Македонски'];

/* ==== Sidebar/menü metinleri ==== */
require_once "language_analiz.php"; // $texts
$current_texts = $texts[$lang] ?? $texts['tr'];

/* ==== Sayfa i18n ==== */
$page_i18n = [
  'tr' => [
    'page_title'   => 'Numune Kabul',
    'top_note'     => 'Yeni tarihliden eskiye doğru sıralanır. Analiz kabul yapılmayan satır kırmızı olarak işaretlenir',
    'section_title'=> 'Ön Kabulü Yapılmış Numuneler',
    'search'       => 'Ara',
    'name'         => 'Numune Adı',
    'facility'     => 'Numune Alınan Firma Adı',
    'producer'     => 'Üretici Adı',
    'address'      => 'Numune Alınan Adres',
    'reason'       => 'Analiz Nedeni',
    'reason_opt'   => ['OZEL_ISTEK'=>'Özel İstek','YASAL_ANALIZ'=>'Yasal Analiz'],
    'atype'        => 'Analiz Türü',
    'asubtype'     => 'Analiz Alt Türü',
    'lab'          => 'Analiz Laboratuvarı',
    'details'      => 'Analiz Detayı',
    'accept_date'  => 'Analiz Kabul Tarihi',
    'accepted_by'  => 'Analizi Kabul Eden',
    'actions'      => 'Analiz Kabul/Güncelle/Sil',
    'accept_btn'   => 'Kabul Et / Güncelle',
    'delete_btn'   => 'Sil',
    'save'         => 'Kaydet',
    'cancel'       => 'İptal',
    'success_save' => 'Analiz kabul kaydedildi.',
    'success_delete'=> 'Analiz kabul kaydı silindi.',
    'confirm_delete'=> 'Bu kalemin analiz kabul kaydı silinsin mi?',
    'no_data'      => 'Kayıt bulunamadı.',
    'item_no'      => 'No',
    'taker'        => 'Numuneyi Alan Kişi',
    'sample_date'  => 'Numune Alım Tarihi',
  ],
  'en' => [
    'page_title'   => 'Sample Full Acceptance',
    'top_note'     => 'Sorted newest to oldest. Rows without full acceptance are highlighted in red.',
    'section_title'=> 'Pre-Accepted Samples',
    'search'       => 'Search',
    'name'         => 'Sample Name',
    'facility'     => 'Facility',
    'producer'     => 'Producer',
    'address'      => 'Address',
    'reason'       => 'Reason',
    'reason_opt'   => ['OZEL_ISTEK'=>'Special Request','YASAL_ANALIZ'=>'Legal'],
    'atype'        => 'Analysis Type',
    'asubtype'     => 'Analysis Subtype',
    'lab'          => 'Laboratory',
    'details'      => 'Details',
    'accept_date'  => 'Acceptance Date',
    'accepted_by'  => 'Accepted By',
    'actions'      => 'Accept / Update / Delete',
    'accept_btn'   => 'Accept / Update',
    'delete_btn'   => 'Delete',
    'save'         => 'Save',
    'cancel'       => 'Cancel',
    'success_save' => 'Saved.',
    'success_delete'=> 'Deleted.',
    'confirm_delete'=> 'Delete acceptance for this item?',
    'no_data'      => 'No records.',
    'item_no'      => 'No',
    'taker'        => 'Taken By',
    'sample_date'  => 'Sample Date',
  ],
  'mk' => [
    'page_title'   => 'Прием на примерок (анализа)',
    'top_note'     => 'Подредено од ново кон старо. Редовите без прифаќање се означени со црвено.',
    'section_title'=> 'Примероци со претприем',
    'search'       => 'Барај',
    'name'         => 'Име на примерок',
    'facility'     => 'Објект',
    'producer'     => 'Производител',
    'address'      => 'Адреса',
    'reason'       => 'Причина',
    'reason_opt'   => ['OZEL_ISTEK'=>'Посебно барање','YASAL_ANALIZ'=>'Законска'],
    'atype'        => 'Тип на анализа',
    'asubtype'     => 'Подтип на анализа',
    'lab'          => 'Лабораторија',
    'details'      => 'Детали',
    'accept_date'  => 'Датум на прифаќање',
    'accepted_by'  => 'Прифатил',
    'actions'      => 'Прифати / Ажурирај / Избриши',
    'accept_btn'   => 'Прифати / Ажурирај',
    'delete_btn'   => 'Избриши',
    'save'         => 'Зачувај',
    'cancel'       => 'Откажи',
    'success_save' => 'Зачувано.',
    'success_delete'=> 'Избришано.',
    'confirm_delete'=> 'Да се избрише прифаќањето?',
    'no_data'      => 'Нема записи.',
    'item_no'      => 'Бр',
    'taker'        => 'Лице што зема',
    'sample_date'  => 'Датум на земање',
  ],
];
$T = $page_i18n[$lang] ?? $page_i18n['tr'];

/* ==== Yardımcılar ==== */
function nm($r,$lang){ return $lang==='en'?$r['name_en'] : ($lang==='mk'?$r['name_mk'] : $r['name_tr']); }

/** labs tablosunu otomatik bul (labs | laboratories) */
function fetchLabs(PDO $pdo): array {
  try {
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    $tbl = in_array('labs', $tables, true) ? 'labs'
         : (in_array('laboratories', $tables, true) ? 'laboratories' : null);
    if (!$tbl) return [];
    return $pdo->query("SELECT id,name FROM `$tbl` ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
  } catch(Exception $e) { return []; }
}

/** facility adını getirmek için tablo & kolon tespiti (facilities|companies|firma|...) */
function detectFacilityLookup(PDO $pdo): ?array {
  try {
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    $cands = ['facilities','facility','companies','company','firma','firms'];
    foreach ($cands as $t) {
      if (in_array($t, $tables, true)) {
        $cols = $pdo->query("SHOW COLUMNS FROM `$t`")->fetchAll(PDO::FETCH_COLUMN);
        foreach (['name','company_name','title','firma_adi'] as $nameCol) {
          if (in_array($nameCol, $cols, true)) return ['table'=>$t, 'col'=>$nameCol];
        }
        return ['table'=>$t, 'col'=>null];
      }
    }
  } catch(Exception $e) { /* ignore */ }
  return null;
}

/* ==== Sözlükler ==== */
$types    = $pdo->query("SELECT id,name_tr,name_en,name_mk FROM analysis_types ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
$subtypes = $pdo->query("SELECT id,type_id,name_tr,name_en,name_mk FROM analysis_subtypes ORDER BY type_id,id")->fetchAll(PDO::FETCH_ASSOC);
$labs     = fetchLabs($pdo);

/* ==== AJAX: full-accept save/delete ==== */
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['ajax'])) {
  header('Content-Type: application/json; charset=utf-8');
  $action = $_POST['action'] ?? '';

  try {
    if ($action === 'save') {
      $sid        = (int)($_POST['sample_item_id'] ?? 0);
      $reason     = $_POST['reason'] ?? null;
      $typesArr   = $_POST['types']    ?? [];
      $subsArr    = $_POST['subtypes'] ?? [];
      $labsArr    = $_POST['labs']     ?? [];
      $details    = trim($_POST['details'] ?? '');
      $acceptedAt = $_POST['accepted_at'] ?? date('Y-m-d H:i:s');
      $acceptedBy = ($_POST['accepted_by'] ?? '') === '' ? null : (int)$_POST['accepted_by'];

      if (!$sid) throw new Exception('Geçersiz kayıt');

      $acceptedAt = date('Y-m-d H:i:s', strtotime($acceptedAt ?: 'now'));

      // 1) Full-accept bilgilerini kaydet
      $ins = $pdo->prepare("
        INSERT INTO sample_full_accepts
          (sample_item_id, reason, types_json, subtypes_json, labs_json, details, accepted_at, accepted_by)
        VALUES
          (:sid, :reason, :types, :subs, :labs, :details, :ad, :ab)
        ON DUPLICATE KEY UPDATE
          reason=VALUES(reason),
          types_json=VALUES(types_json),
          subtypes_json=VALUES(subtypes_json),
          labs_json=VALUES(labs_json),
          details=VALUES(details),
          accepted_at=VALUES(accepted_at),
          accepted_by=VALUES(accepted_by)
      ");
      $ins->execute([
        ':sid'=>$sid,
        ':reason'=>$reason,
        ':types'=>json_encode(array_map('intval',(array)$typesArr), JSON_UNESCAPED_UNICODE),
        ':subs'=>json_encode(array_map('intval',(array)$subsArr),  JSON_UNESCAPED_UNICODE),
        ':labs'=>json_encode(array_map('intval',(array)$labsArr),  JSON_UNESCAPED_UNICODE),
        ':details'=>$details,
        ':ad'=>$acceptedAt,
        ':ab'=>$acceptedBy ?: ($_SESSION['user_id'] ?? null),
      ]);

      // 2) analysis-result listesinde görünsün diye samples.type_id / subtype_id'yi doldur
      //    (ilk seçilen değerler yazılır)
      $firstType = count($typesArr) ? (int)$typesArr[0] : null;
      $firstSub  = count($subsArr)  ? (int)$subsArr[0]  : null;

      if ($firstType || $firstSub) {
        // sample_items -> samples.id
        $q = $pdo->prepare("SELECT sample_id FROM sample_items WHERE id=:sid");
        $q->execute([':sid'=>$sid]);
        if ($row = $q->fetch(PDO::FETCH_ASSOC)) {
          $sampleId = (int)$row['sample_id'];
          $setParts = []; $bind = [':id'=>$sampleId];
          if ($firstType) { $setParts[] = "type_id = :t";    $bind[':t']  = $firstType; }
          if ($firstSub)  { $setParts[] = "subtype_id = :st";$bind[':st'] = $firstSub; }
          if ($setParts) {
            $pdo->prepare("UPDATE samples SET ".implode(', ',$setParts)." WHERE id=:id")->execute($bind);
          }
        }
      }

      echo json_encode(['success'=>true,'message'=>$T['success_save']]); exit;
    }

    if ($action === 'delete') {
      $sid = (int)($_POST['sample_item_id'] ?? 0);
      $pdo->prepare("DELETE FROM sample_full_accepts WHERE sample_item_id=:sid")->execute([':sid'=>$sid]);
      echo json_encode(['success'=>true,'message'=>$T['success_delete']]); exit;
    }

    echo json_encode(['success'=>false,'message'=>'Unknown action']); exit;

  } catch(Exception $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]); exit;
  }
}

/* ==== Liste ==== */
$q = trim($_GET['q'] ?? '');
$params = [];

// Facility join/alanı opsiyonel
$fac = detectFacilityLookup($pdo);
$facJoin  = '';
$facField = 'NULL AS facility_name';
$facLike  = '';
if ($fac) {
  $facJoin  = " LEFT JOIN `{$fac['table']}` f ON f.id = s.facility_id ";
  $facField = $fac['col'] ? "f.`{$fac['col']}` AS facility_name" : "NULL AS facility_name";
  $facLike  = $fac['col'] ? " OR f.`{$fac['col']}` LIKE :q " : "";
}

$sql = "
  SELECT
    si.id AS item_id, si.item_no, si.sample_name, si.producer_name,
    s.address, s.sample_date, s.taker_id,
    lu_taker.full_name AS taker_name,
    sfa.id AS full_id, sfa.reason, sfa.types_json, sfa.subtypes_json, sfa.labs_json,
    sfa.details, sfa.accepted_at, sfa.accepted_by,
    lu_acc.full_name AS accepted_by_name,
    $facField
  FROM sample_items si
  JOIN samples s ON s.id = si.sample_id
  LEFT JOIN lab_users lu_taker ON lu_taker.id = s.taker_id
  LEFT JOIN sample_full_accepts sfa ON sfa.sample_item_id = si.id
  LEFT JOIN lab_users lu_acc ON lu_acc.id = sfa.accepted_by
  $facJoin
";

$wheres = [];
if ($q !== '') {
  $wheres[] = "(si.sample_name LIKE :q OR si.producer_name LIKE :q OR s.address LIKE :q $facLike)";
  $params[':q'] = "%{$q}%";
}
if ($wheres) $sql .= " WHERE ".implode(' AND ', $wheres);
$sql .= " ORDER BY COALESCE(sfa.accepted_at, s.sample_date) DESC, si.item_no ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* Maplar (liste için isim çözme) */
$mapTypes = []; foreach($types as $t){ $mapTypes[$t['id']] = nm($t,$lang); }
$mapSubs  = []; foreach($subtypes as $s){ $mapSubs[$s['id']] = nm($s,$lang); }
$mapLabs  = []; foreach($labs as $l){ $mapLabs[$l['id']] = $l['name']; }
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
    .blk-title{background:#000;color:#fff;padding:8px 12px;font-weight:600;letter-spacing:.5px;text-align:center;}
    .sub-title{background:#f7f7f7;color:#333;padding:8px 12px;border:1px solid #ddd;text-align:center;font-weight:600;}
    .table thead th{vertical-align:middle;text-align:left;}
    .table tbody td{vertical-align:middle;}
    .row-missing{background:#f9d6d5 !important;}
    .search-box{margin:10px 0 15px;}
    .multisel{min-height:120px;}
  </style>
</head>
<body>
<div id="ui" class="ui">

  <!-- HEADER -->
  <header id="header" class="ui-header">
    <div class="navbar-header">
      <a href="index.php" class="navbar-brand"><span class="logo"><img src="imgs/labx.png" width="100"></span></a>
    </div>

    <ul class="nav navbar-nav navbar-right">
      <li class="dropdown">
        <a href="#" class="dropdown-toggle" data-toggle="dropdown"><i class="fa fa-globe"></i> <?=$languages[$lang]?></a>
        <ul class="dropdown-menu">
          <?php foreach ($languages as $key => $language): ?>
            <li><a href="?lang=<?=$key?>&db=<?=htmlspecialchars($selectedDb)?>"><?=$language?></a></li>
          <?php endforeach; ?>
        </ul>
      </li>
      <li class="dropdown">
        <a href="#" class="dropdown-toggle" data-toggle="dropdown"><i class="fa fa-database"></i> <?=$current_texts['select_db'] ?? 'Veritabanı Seç'?></a>
        <ul class="dropdown-menu">
          <?php foreach (['2023','2024','2025'] as $db): ?>
            <li><a href="?lang=<?=$lang?>&db=<?=$db?>"><?=$db?></a></li>
          <?php endforeach; ?>
        </ul>
      </li>
      <li class="dropdown dropdown-usermenu">
        <a href="#" class="dropdown-toggle" data-toggle="dropdown"><div class="user-avatar"><img src="imgs/a0.jpg" alt=""></div></a>
        <ul class="dropdown-menu dropdown-menu-usermenu pull-right">
          <li><a href="settings.php"><i class="fa fa-cogs"></i> <?=$current_texts['settings']?></a></li>
          <li><a href="logout.php"><i class="fa fa-sign-out"></i> <?=$current_texts['logout']?></a></li>
        </ul>
      </li>
    </ul>
  </header>

  <!-- SIDEBAR -->
  <?php include "sidebar.php"; ?>

  <!-- CONTENT -->
  <div id="content" class="ui-content">
    <div class="ui-content-body">
      <div class="ui-container">

        <div class="panel">
          <header class="blk-title"><?=htmlspecialchars($T['page_title'])?></header>
          <div class="panel-body">
            <div class="text-center text-muted" style="margin-bottom:10px;"><?=$T['top_note']?></div>

            <!-- Arama -->
            <form class="search-box" method="get" action="sample-full-accept.php">
              <input type="hidden" name="lang" value="<?=htmlspecialchars($lang)?>">
              <input type="hidden" name="db" value="<?=htmlspecialchars($selectedDb)?>">
              <div class="input-group">
                <input type="text" class="form-control" name="q" value="<?=htmlspecialchars($q)?>" placeholder="<?=$T['search'].'...'?>">
                <span class="input-group-btn">
                  <button class="btn btn-default"><i class="fa fa-search"></i> <?=$T['search']?></button>
                </span>
              </div>
            </form>

            <div class="sub-title"><?=htmlspecialchars($T['section_title'])?></div>

            <div class="table-responsive">
              <table class="table table-bordered table-striped">
                <thead>
                  <tr>
                    <th style="width:60px;"><?=$T['item_no']?></th>
                    <th><?=$T['name']?></th>
                    <th><?=$T['facility']?></th>
                    <th><?=$T['producer']?></th>
                    <th><?=$T['address']?></th>
                    <th><?=$T['sample_date']?></th>
                    <th><?=$T['taker']?></th>
                    <th><?=$T['reason']?></th>
                    <th><?=$T['atype']?></th>
                    <th><?=$T['asubtype']?></th>
                    <th><?=$T['lab']?></th>
                    <th><?=$T['accept_date']?></th>
                    <th><?=$T['accepted_by']?></th>
                    <th style="width:180px;"><?=$T['actions']?></th>
                  </tr>
                </thead>
                <tbody>
                <?php if(!$rows): ?>
                  <tr><td colspan="14" class="text-center text-muted"><?=$T['no_data']?></td></tr>
                <?php endif; ?>
                <?php foreach($rows as $r):
                  $missing = empty($r['full_id']);

                  $typesTxt = $subsTxt = $labsTxt = '';
                  if(!$missing){
                    $tids = json_decode($r['types_json']     ?: '[]', true) ?: [];
                    $sids = json_decode($r['subtypes_json'] ?: '[]', true) ?: [];
                    $lids = json_decode($r['labs_json']     ?: '[]', true) ?: [];

                    $typesTxt = implode(', ', array_values(array_intersect_key($mapTypes, array_flip($tids))));
                    $subsTxt  = implode(', ', array_values(array_intersect_key($mapSubs,  array_flip($sids))));
                    $labsTxt  = implode(', ', array_values(array_intersect_key($mapLabs,  array_flip($lids))));
                  }
                ?>
                  <tr class="<?= $missing ? 'row-missing' : '' ?>">
                    <td class="text-center"><?=htmlspecialchars($r['item_no'])?></td>
                    <td><?=htmlspecialchars($r['sample_name'])?></td>
                    <td><?=htmlspecialchars($r['facility_name'] ?? '')?></td>
                    <td><?=htmlspecialchars($r['producer_name'])?></td>
                    <td><?=htmlspecialchars($r['address'])?></td>
                    <td><?=htmlspecialchars($r['sample_date'])?></td>
                    <td><?=htmlspecialchars($r['taker_name'])?></td>
                    <td><?= $r['reason'] ? ($T['reason_opt'][$r['reason']] ?? $r['reason']) : '' ?></td>
                    <td><?=htmlspecialchars($typesTxt)?></td>
                    <td><?=htmlspecialchars($subsTxt)?></td>
                    <td><?=htmlspecialchars($labsTxt)?></td>
                    <td><?=htmlspecialchars($r['accepted_at'] ?? '')?></td>
                    <td><?=htmlspecialchars($r['accepted_by_name'] ?? '')?></td>
                    <td class="text-center">
                      <button class="btn btn-xs btn-primary full-accept-btn"
                        data-row='<?=json_encode($r, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP)?>'>
                        <i class="fa fa-check"></i> <?=$T['accept_btn']?>
                      </button>
                      <?php if(!$missing): ?>
                      <button class="btn btn-xs btn-danger delete-full" data-id="<?=$r['item_id']?>">
                        <i class="fa fa-trash"></i> <?=$T['delete_btn']?>
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

<!-- Modal: Analiz Kabul -->
<div class="modal fade" id="fullAcceptModal" tabindex="-1">
  <div class="modal-dialog modal-lg"><div class="modal-content">
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal">&times;</button>
      <h4 class="modal-title"><?=$T['accept_btn']?></h4>
    </div>
    <div class="modal-body">
      <form id="fullAcceptForm">
        <input type="hidden" name="sample_item_id" id="fa_item_id">

        <div class="row">
          <div class="col-sm-3 form-group">
            <label><?=$T['reason']?></label>
            <select class="form-control" name="reason" id="fa_reason">
              <?php foreach($T['reason_opt'] as $k=>$v): ?>
                <option value="<?=$k?>"><?=$v?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-sm-5 form-group">
            <label><?=$T['atype']?></label>
            <select multiple class="form-control multisel" name="types[]" id="fa_types">
              <?php foreach($types as $t): ?>
                <option value="<?=$t['id']?>"><?=htmlspecialchars(nm($t,$lang))?></option>
              <?php endforeach; ?>
            </select>
            <small class="text-muted">Ctrl/Shift ile çoklu seçim</small>
          </div>
          <div class="col-sm-4 form-group">
            <label><?=$T['lab']?></label>
            <select multiple class="form-control multisel" name="labs[]" id="fa_labs">
              <?php foreach($labs as $l): ?>
                <option value="<?=$l['id']?>"><?=htmlspecialchars($l['name'])?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="row">
          <div class="col-sm-8 form-group">
            <label><?=$T['asubtype']?></label>
            <select multiple class="form-control multisel" name="subtypes[]" id="fa_subtypes">
              <?php foreach($subtypes as $s): ?>
                <option value="<?=$s['id']?>" data-type="<?=$s['type_id']?>"><?=htmlspecialchars(nm($s,$lang))?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-sm-4 form-group">
            <label><?=$T['accept_date']?></label>
            <input type="datetime-local" class="form-control" name="accepted_at" id="fa_date" value="<?=date('Y-m-d\TH:i')?>">
            <label style="margin-top:10px;"><?=$T['accepted_by']?></label>
            <select class="form-control" name="accepted_by" id="fa_accby">
              <option value=""><?=( $current_texts['select'] ?? 'Seçiniz' )?></option>
              <?php
                $labUsers = $pdo->query("SELECT id, full_name FROM lab_users ORDER BY full_name")->fetchAll(PDO::FETCH_ASSOC);
                foreach($labUsers as $u): ?>
                <option value="<?=$u['id']?>"><?=htmlspecialchars($u['full_name'])?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="form-group">
          <label><?=$T['details']?></label>
          <textarea class="form-control" name="details" id="fa_details" rows="3"></textarea>
        </div>
      </form>
      <div id="fullAcceptAlert" class="alert" style="display:none;"></div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-default" data-dismiss="modal"><?=$T['cancel']?></button>
      <button class="btn btn-primary" id="saveFullAccept"><?=$T['save']?></button>
    </div>
  </div></div>
</div>

<script src="bower_components/jquery/dist/jquery.min.js"></script>
<script src="bower_components/bootstrap/dist/js/bootstrap.min.js"></script>
<script>
$(function(){
  function filterSubtypes(){
    var selTypes = $('#fa_types').val() || [];
    $('#fa_subtypes option').each(function(){
      var t = String($(this).data('type'));
      $(this).toggle(selTypes.indexOf(t) >= 0);
    });
  }
  $('#fa_types').on('change', filterSubtypes);

  $('.full-accept-btn').on('click', function(){
    var r = $(this).data('row');
    $('#fa_item_id').val(r.item_id);

    $('#fa_reason').val(r.reason || 'OZEL_ISTEK');

    var types = [], subs=[], labs=[];
    try { types = JSON.parse(r.types_json || '[]'); } catch(e){ types=[]; }
    try { subs  = JSON.parse(r.subtypes_json || '[]'); } catch(e){ subs=[]; }
    try { labs  = JSON.parse(r.labs_json || '[]'); } catch(e){ labs=[]; }

    $('#fa_types option, #fa_subtypes option, #fa_labs option').prop('selected', false);
    types.forEach(function(id){ $('#fa_types option[value="'+id+'"]').prop('selected', true); });
    filterSubtypes();
    subs.forEach(function(id){ $('#fa_subtypes option[value="'+id+'"]').prop('selected', true); });
    labs.forEach(function(id){ $('#fa_labs option[value="'+id+'"]').prop('selected', true); });

    $('#fa_date').val(r.accepted_at ? r.accepted_at.replace(' ','T').slice(0,16) : new Date().toISOString().slice(0,16));
    $('#fa_accby').val(r.accepted_by || '');
    $('#fa_details').val(r.details || '');

    $('#fullAcceptAlert').hide();
    $('#fullAcceptModal').modal('show');
  });

  $('#saveFullAccept').on('click', function(){
    var data = $('#fullAcceptForm').serializeArray();
    data.push({name:'ajax', value:1}, {name:'action', value:'save'});
    $.post('sample-full-accept.php?lang=<?= $lang ?>&db=<?= $selectedDb ?>', data, function(res){
      if(res && res.success){ location.reload(); }
      else {
        $('#fullAcceptAlert').removeClass('alert-success').addClass('alert-danger').text(res.message || 'Hata').show();
      }
    }, 'json').fail(function(xhr){
      $('#fullAcceptAlert').removeClass('alert-success').addClass('alert-danger').text(xhr.responseText || 'Hata').show();
    });
  });

  $('.delete-full').on('click', function(){
    if(!confirm('<?= addslashes($T['confirm_delete']) ?>')) return;
    var itemId = $(this).data('id');
    $.post('sample-full-accept.php?lang=<?= $lang ?>&db=<?= $selectedDb ?>',
      {ajax:1, action:'delete', sample_item_id:itemId},
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
