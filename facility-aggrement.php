<?php
require_once "db.php";
session_start();

/* ==== Dil / DB ==== */
$lang = $_GET['lang'] ?? 'tr';
$selectedDb = $_GET['db'] ?? '2025';
$languages = ['tr'=>'Türkçe','en'=>'English','mk'=>'Македонски'];

/* ==== Ortak metinler ==== */
require_once "language_analiz.php"; // $texts
$current_texts = $texts[$lang] ?? $texts['tr'];

/* ==== Sayfaya özgü i18n ==== */
$I = [
 'tr'=>[
   'title'=>'Tesis Anlaşma Tanımlama',
   'banner'=>'TESİS ANLAŞMA TANIMLAMA',
   'info'=>'Burada tesis için anlaşma tanımlaması yapılır. Tip/alt türe göre veya seçilen parametrelere göre fiyatlandırabilirsiniz. Parametreler virgülle yazdırılır.',
   'facility'=>'Firma Adı',
   'reason'=>'Analiz Nedeni',
   'reason_opts'=>['Yasal Analiz','Özel İstek'],
   'type'=>'Analiz Türü',
   'subtype'=>'Alt Analiz Türü',
   'params'=>'Analiz Parametreleri',
   'start'=>'Anlaşma Başlama Tarihi',
   'end'=>'Anlaşma Bitiş Tarihi',
   'price'=>'Fiyat',
   'vat'=>'KDV (%)',
   'add'=>'Kaydet',
   'search'=>'Ara',
   'list'=>'Mevcut Anlaşmalar',
   'actions'=>'Güncelle/Sil',
   'edit'=>'Düzenle',
   'delete'=>'Sil',
   'cancel'=>'İptal',
   'params_hint'=>'(Seçimler alt türe göre otomatik süzülür)',
   'success_add'=>'Anlaşma eklendi.',
   'success_upd'=>'Anlaşma güncellendi.',
   'success_del'=>'Anlaşma silindi.',
   'confirm_del'=>'Bu anlaşmayı silmek istiyor musunuz?',
   'err_required'=>'Lütfen firma ve en azından bir bilgi (tip/alt tip veya parametre) girin.'
 ],
 'en'=>[
   'title'=>'Facility Agreement',
   'banner'=>'FACILITY AGREEMENT',
   'info'=>'Define agreements for a facility. You may price by type/subtype or by selected parameters. Parameters are printed comma-separated.',
   'facility'=>'Facility',
   'reason'=>'Reason',
   'reason_opts'=>['Legal Analysis','Special Request'],
   'type'=>'Analysis Type',
   'subtype'=>'Sub Type',
   'params'=>'Parameters',
   'start'=>'Start Date',
   'end'=>'End Date',
   'price'=>'Price',
   'vat'=>'VAT (%)',
   'add'=>'Save',
   'search'=>'Search',
   'list'=>'Existing Agreements',
   'actions'=>'Actions',
   'edit'=>'Edit',
   'delete'=>'Delete',
   'cancel'=>'Cancel',
   'params_hint'=>'(Filtered by selected subtype)',
   'success_add'=>'Agreement added.',
   'success_upd'=>'Agreement updated.',
   'success_del'=>'Agreement deleted.',
   'confirm_del'=>'Delete this agreement?',
   'err_required'=>'Please choose a facility and provide at least type/subtype or parameters.'
 ],
 'mk'=>[
   'title'=>'Договор за објект',
   'banner'=>'ДОГОВОР ЗА ОБЈЕКТ',
   'info'=>'Дефинирајте договор за објект. Цени по тип/подтип или според избрани параметри.',
   'facility'=>'Фирма',
   'reason'=>'Причина',
   'reason_opts'=>['Законска анализа','Посебно барање'],
   'type'=>'Тип анализа',
   'subtype'=>'Подтип',
   'params'=>'Параметри',
   'start'=>'Почетен датум',
   'end'=>'Краен датум',
   'price'=>'Цена',
   'vat'=>'ДДВ (%)',
   'add'=>'Зачувај',
   'search'=>'Барај',
   'list'=>'Постоечки договори',
   'actions'=>'Дејства',
   'edit'=>'Уреди',
   'delete'=>'Избриши',
   'cancel'=>'Откажи',
   'params_hint'=>'(Филтрирано по подтип)',
   'success_add'=>'Договорот е додаден.',
   'success_upd'=>'Договорот е ажуриран.',
   'success_del'=>'Договорот е избришан.',
   'confirm_del'=>'Да се избрише договорот?',
   'err_required'=>'Изберете фирма и внесете барем тип/подтип или параметри.'
 ]
][$lang];

/* ==== Sözlükler ==== */
$facilities = $pdo->query("SELECT id,name FROM facilities ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$types = $pdo->query("SELECT id,name_tr,name_en,name_mk FROM analysis_types ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
$subtypes = $pdo->query("SELECT id,type_id,name_tr,name_en,name_mk FROM analysis_subtypes ORDER BY type_id,id")->fetchAll(PDO::FETCH_ASSOC);

/* Parametreler (fiyatı da çekeceğiz) */
$params = $pdo->query("
  SELECT p.id, p.type_id, p.subtype_id, p.name_tr, p.name_en, p.name_mk, p.price
  FROM analysis_parameters p
  ORDER BY p.type_id, p.subtype_id, p.id
")->fetchAll(PDO::FETCH_ASSOC);

function nm($row,$lang){ return $lang==='en'?$row['name_en']:($lang==='mk'?$row['name_mk']:$row['name_tr']); }

/* ==== AJAX ==== */
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['ajax'])) {
  header('Content-Type: application/json; charset=utf-8');
  $action = $_POST['action'] ?? '';
  try {
    if ($action==='add') {
      $facility_id = (int)($_POST['facility_id'] ?? 0);
      $reason      = trim($_POST['reason'] ?? '');
      $type_id     = $_POST['type_id'] === '' ? null : (int)$_POST['type_id'];
      $subtype_id  = $_POST['subtype_id'] === '' ? null : (int)$_POST['subtype_id'];
      $start_date  = $_POST['start_date'] ?: null;
      $end_date    = $_POST['end_date'] ?: null;
      $price       = (float)($_POST['price'] ?? 0);
      $vat_rate    = (float)($_POST['vat_rate'] ?? 18);
      $param_ids   = isset($_POST['param_ids']) && is_array($_POST['param_ids']) ? array_map('intval', $_POST['param_ids']) : [];

      if (!$facility_id || (empty($type_id) && empty($subtype_id) && empty($param_ids))) {
        throw new Exception($I['err_required']);
      }

      $ins = $pdo->prepare("INSERT INTO facility_agreements
        (facility_id, reason, type_id, subtype_id, start_date, end_date, price, vat_rate)
        VALUES (:f,:r,:t,:st,:sd,:ed,:pr,:vat)");
      $ins->execute([
        ':f'=>$facility_id, ':r'=>$reason, ':t'=>$type_id, ':st'=>$subtype_id,
        ':sd'=>$start_date, ':ed'=>$end_date, ':pr'=>$price, ':vat'=>$vat_rate
      ]);
      $agr_id = $pdo->lastInsertId();

      if ($param_ids) {
        $ins2 = $pdo->prepare("INSERT INTO facility_agreement_params (agreement_id, parameter_id, unit_price) VALUES (:a,:p,NULL)");
        foreach($param_ids as $pid){ $ins2->execute([':a'=>$agr_id, ':p'=>$pid]); }
      }

      echo json_encode(['success'=>true,'message'=>$I['success_add']]); exit;
    }

    if ($action==='update') {
      $id          = (int)($_POST['id'] ?? 0);
      $facility_id = (int)($_POST['facility_id'] ?? 0);
      $reason      = trim($_POST['reason'] ?? '');
      $type_id     = $_POST['type_id'] === '' ? null : (int)$_POST['type_id'];
      $subtype_id  = $_POST['subtype_id'] === '' ? null : (int)$_POST['subtype_id'];
      $start_date  = $_POST['start_date'] ?: null;
      $end_date    = $_POST['end_date'] ?: null;
      $price       = (float)($_POST['price'] ?? 0);
      $vat_rate    = (float)($_POST['vat_rate'] ?? 18);
      $param_ids   = isset($_POST['param_ids']) && is_array($_POST['param_ids']) ? array_map('intval', $_POST['param_ids']) : [];

      if (!$id || !$facility_id || (empty($type_id) && empty($subtype_id) && empty($param_ids))) {
        throw new Exception($I['err_required']);
      }

      $upd = $pdo->prepare("UPDATE facility_agreements SET
        facility_id=:f, reason=:r, type_id=:t, subtype_id=:st, start_date=:sd, end_date=:ed, price=:pr, vat_rate=:vat
        WHERE id=:id");
      $upd->execute([
        ':f'=>$facility_id, ':r'=>$reason, ':t'=>$type_id, ':st'=>$subtype_id,
        ':sd'=>$start_date, ':ed'=>$end_date, ':pr'=>$price, ':vat'=>$vat_rate, ':id'=>$id
      ]);

      // Parametreleri yenile
      $pdo->prepare("DELETE FROM facility_agreement_params WHERE agreement_id=:a")->execute([':a'=>$id]);
      if ($param_ids) {
        $ins2 = $pdo->prepare("INSERT INTO facility_agreement_params (agreement_id, parameter_id, unit_price) VALUES (:a,:p,NULL)");
        foreach($param_ids as $pid){ $ins2->execute([':a'=>$id, ':p'=>$pid]); }
      }

      echo json_encode(['success'=>true,'message'=>$I['success_upd']]); exit;
    }

    if ($action==='delete') {
      $id = (int)($_POST['id'] ?? 0);
      $pdo->prepare("DELETE FROM facility_agreement_params WHERE agreement_id=:a")->execute([':a'=>$id]);
      $pdo->prepare("DELETE FROM facility_agreements WHERE id=:id")->execute([':id'=>$id]);
      echo json_encode(['success'=>true,'message'=>$I['success_del']]); exit;
    }

    echo json_encode(['success'=>false,'message'=>'Unknown action']); exit;
  } catch(Exception $e){
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]); exit;
  }
}

/* ==== Liste ==== */
$q = trim($_GET['q'] ?? '');
$paramsSQL = [];
$where = [];
$sql = "
  SELECT a.*, f.name facility_name,
         t.name_tr t_tr, t.name_en t_en, t.name_mk t_mk,
         st.name_tr st_tr, st.name_en st_en, st.name_mk st_mk,
         (SELECT GROUP_CONCAT(CASE '$lang'
                   WHEN 'en' THEN ap.name_en
                   WHEN 'mk' THEN ap.name_mk
                   ELSE ap.name_tr END SEPARATOR ', ')
          FROM facility_agreement_params fp
          JOIN analysis_parameters ap ON ap.id = fp.parameter_id
          WHERE fp.agreement_id = a.id) AS param_names
  FROM facility_agreements a
  JOIN facilities f ON f.id = a.facility_id
  LEFT JOIN analysis_types t ON t.id = a.type_id
  LEFT JOIN analysis_subtypes st ON st.id = a.subtype_id
";
if ($q!==''){
  $where[] = "(f.name LIKE :q OR a.reason LIKE :q)";
  $paramsSQL[':q'] = "%{$q}%";
}
if ($where) $sql .= " WHERE ".implode(" AND ",$where);
$sql .= " ORDER BY a.id DESC";

$st = $pdo->prepare($sql);
$st->execute($paramsSQL);
$list = $st->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="<?=htmlspecialchars($lang)?>">
<head>
<meta charset="utf-8">
<title>Labx - <?=htmlspecialchars($I['title'])?></title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="bower_components/bootstrap/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="bower_components/font-awesome/css/font-awesome.min.css">
<link rel="stylesheet" href="dist/css/main.css">
<link rel="icon" type="image/png" href="/imgs/favicon.png">
<style>
  .blk-title{background:#000;color:#fff;padding:8px 12px;font-weight:600;text-align:center;}
  .sub-title{background:#f7f7f7;color:#333;padding:8px 12px;border:1px solid #ddd;text-align:center;font-weight:600;}
  .table td,.table th{vertical-align:middle;}
  .muted{color:#888}
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
        <?php foreach($languages as $k=>$v): ?>
          <li><a href="?lang=<?=$k?>&db=<?=htmlspecialchars($selectedDb)?>"><?=$v?></a></li>
        <?php endforeach; ?>
      </ul>
    </li>
    <li class="dropdown">
      <a href="#" class="dropdown-toggle" data-toggle="dropdown"><i class="fa fa-database"></i> <?=$current_texts['select_db'] ?? 'Veritabanı Seç'?></a>
      <ul class="dropdown-menu">
        <?php foreach(['2023','2024','2025'] as $db): ?>
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

<?php include "sidebar.php"; ?>

<div id="content" class="ui-content">
  <div class="ui-content-body">
    <div class="ui-container">

      <div class="panel">
        <header class="blk-title"><?=$I['banner']?></header>
        <div class="panel-body">

          <p class="muted"><?=$I['info']?></p>

          <!-- Form -->
          <form id="addForm">
            <div class="row">
              <div class="col-sm-3">
                <label><?=$I['facility']?></label>
                <select class="form-control" name="facility_id" required>
                  <option value=""><?= $current_texts['select'] ?? 'Seçiniz' ?></option>
                  <?php foreach($facilities as $f): ?>
                    <option value="<?=$f['id']?>"><?=htmlspecialchars($f['name'])?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-sm-2">
                <label><?=$I['reason']?></label>
                <select class="form-control" name="reason">
                  <?php foreach($I['reason_opts'] as $r): ?><option value="<?=$r?>"><?=$r?></option><?php endforeach; ?>
                </select>
              </div>
              <div class="col-sm-2">
                <label><?=$I['type']?></label>
                <select class="form-control" name="type_id" id="selType">
                  <option value=""></option>
                  <?php foreach($types as $t): ?>
                    <option value="<?=$t['id']?>"><?=htmlspecialchars(nm($t,$lang))?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-sm-2">
                <label><?=$I['subtype']?></label>
                <select class="form-control" name="subtype_id" id="selSubtype">
                  <option value=""></option>
                  <?php foreach($subtypes as $s): ?>
                    <option value="<?=$s['id']?>" data-type="<?=$s['type_id']?>"><?=htmlspecialchars(nm($s,$lang))?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-sm-3">
                <label><?=$I['params']?> <small class="muted"><?=$I['params_hint']?></small></label>
                <select multiple class="form-control" size="5" name="param_ids[]" id="selParams">
                  <?php foreach($params as $p): ?>
                    <option value="<?=$p['id']?>" data-type="<?=$p['type_id']?>" data-subtype="<?=$p['subtype_id']?>" data-price="<?=$p['price']?>">
                      <?=htmlspecialchars(nm($p,$lang))?> (<?=$p['price']?>)
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>

            <div class="row" style="margin-top:8px;">
              <div class="col-sm-2"><label><?=$I['start']?></label><input type="date" class="form-control" name="start_date"></div>
              <div class="col-sm-2"><label><?=$I['end']?></label><input type="date" class="form-control" name="end_date"></div>
              <div class="col-sm-2"><label><?=$I['price']?></label><input type="number" step="0.01" class="form-control" name="price" id="price"></div>
              <!-- 18% sabit oranı sunucuya gönderelim (gizli) -->
<input type="hidden" name="vat_rate" value="18">
<div class="col-sm-2">
  <label>KDV Tutarı (18%)</label>
  <input type="number" step="0.01" class="form-control" id="vat_amount" readonly>
</div>

              <div class="col-sm-2"><label>&nbsp;</label><button class="btn btn-primary btn-block" type="submit"><i class="fa fa-save"></i> <?=$I['add']?></button></div>
            </div>
          </form>
          <div id="addAlert" class="alert" style="display:none;margin-top:8px;"></div>

          <!-- Arama -->
          <form class="row" method="get" action="facility-aggrement.php" style="margin-top:16px;">
            <input type="hidden" name="lang" value="<?=htmlspecialchars($lang)?>">
            <input type="hidden" name="db" value="<?=htmlspecialchars($selectedDb)?>">
            <div class="col-sm-6">
              <div class="input-group">
                <input type="text" class="form-control" name="q" value="<?=htmlspecialchars($q)?>" placeholder="<?=$I['search'].'...'?>">
                <span class="input-group-btn"><button class="btn btn-default"><i class="fa fa-search"></i> <?=$I['search']?></button></span>
              </div>
            </div>
          </form>

          <!-- Liste -->
          <div class="sub-title" style="margin-top:12px;"><?=$I['list']?></div>
          <div class="table-responsive">
            <table class="table table-bordered table-striped">
              <thead>
                <tr>
                  <th><?=$I['facility']?></th>
                  <th><?=$I['reason']?></th>
                  <th><?=$I['type']?></th>
                  <th><?=$I['subtype']?></th>
                  <th><?=$I['params']?></th>
                  <th><?=$I['start']?></th>
                  <th><?=$I['end']?></th>
                  <th class="text-right"><?=$I['price']?></th>
                  <th><?=$I['vat']?></th>
                  <th style="width:160px;"><?=$I['actions']?></th>
                </tr>
              </thead>
              <tbody>
                <?php if(!$list): ?>
                  <tr><td colspan="10" class="text-center text-muted">-</td></tr>
                <?php endif; ?>
                <?php foreach($list as $row): ?>
                  <?php
                    $tname = $row['t_tr']; $sname = $row['st_tr'];
                    if ($lang==='en'){ $tname=$row['t_en']; $sname=$row['st_en']; }
                    if ($lang==='mk'){ $tname=$row['t_mk']; $sname=$row['st_mk']; }
                  ?>
                  <tr>
                    <td><?=htmlspecialchars($row['facility_name'])?></td>
                    <td><?=htmlspecialchars($row['reason'])?></td>
                    <td><?=htmlspecialchars($tname ?? '')?></td>
                    <td><?=htmlspecialchars($sname ?? '')?></td>
                    <td style="max-width:420px;"><?=htmlspecialchars($row['param_names'] ?? '')?></td>
                    <td><?=htmlspecialchars($row['start_date'])?></td>
                    <td><?=htmlspecialchars($row['end_date'])?></td>
                    <td class="text-right"><?=number_format((float)$row['price'],2,',','.')?></td>
                    <td><?=htmlspecialchars($row['vat_rate'])?></td>
                    <td class="text-center">
                      <button class="btn btn-xs btn-primary edit-btn"
                        data-id="<?=$row['id']?>"
                        data-facility="<?=$row['facility_id']?>"
                        data-reason="<?=htmlspecialchars($row['reason'],ENT_QUOTES)?>"
                        data-type="<?=$row['type_id']?>"
                        data-subtype="<?=$row['subtype_id']?>"
                        data-start="<?=$row['start_date']?>"
                        data-end="<?=$row['end_date']?>"
                        data-price="<?=$row['price']?>"
                        data-vat="<?=$row['vat_rate']?>"
                      ><i class="fa fa-edit"></i> <?=$I['edit']?></button>
                      <button class="btn btn-xs btn-danger del-btn" data-id="<?=$row['id']?>">
                        <i class="fa fa-trash"></i> <?=$I['delete']?>
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

<footer id="footer" class="ui-footer"><?=$current_texts['footer'] ?? '2025 &copy; Labx by Vektraweb.'?></footer>
</div>

<!-- Düzenleme Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
  <div class="modal-dialog modal-lg"><div class="modal-content">
    <div class="modal-header">
      <button class="close" data-dismiss="modal">&times;</button>
      <h4 class="modal-title"><?=$I['edit']?></h4>
    </div>
    <div class="modal-body">
      <form id="editForm">
        <input type="hidden" name="id" id="e_id">
        <div class="row">
          <div class="col-sm-3">
            <label><?=$I['facility']?></label>
            <select class="form-control" name="facility_id" id="e_fac">
              <?php foreach($facilities as $f): ?>
                <option value="<?=$f['id']?>"><?=htmlspecialchars($f['name'])?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-sm-2">
            <label><?=$I['reason']?></label>
            <select class="form-control" name="reason" id="e_reason">
              <?php foreach($I['reason_opts'] as $r): ?><option value="<?=$r?>"><?=$r?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="col-sm-2">
            <label><?=$I['type']?></label>
            <select class="form-control" name="type_id" id="e_type">
              <option value=""></option>
              <?php foreach($types as $t): ?>
                <option value="<?=$t['id']?>"><?=htmlspecialchars(nm($t,$lang))?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-sm-2">
            <label><?=$I['subtype']?></label>
            <select class="form-control" name="subtype_id" id="e_subtype">
              <option value=""></option>
              <?php foreach($subtypes as $s): ?>
                <option value="<?=$s['id']?>" data-type="<?=$s['type_id']?>"><?=htmlspecialchars(nm($s,$lang))?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-sm-3">
            <label><?=$I['params']?></label>
            <select multiple class="form-control" size="6" name="param_ids[]" id="e_params">
              <?php foreach($params as $p): ?>
                <option value="<?=$p['id']?>" data-type="<?=$p['type_id']?>" data-subtype="<?=$p['subtype_id']?>" data-price="<?=$p['price']?>">
                  <?=htmlspecialchars(nm($p,$lang))?> (<?=$p['price']?>)
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="row" style="margin-top:8px;">
          <div class="col-sm-2"><label><?=$I['start']?></label><input type="date" class="form-control" name="start_date" id="e_start"></div>
          <div class="col-sm-2"><label><?=$I['end']?></label><input type="date" class="form-control" name="end_date" id="e_end"></div>
          <div class="col-sm-2"><label><?=$I['price']?></label><input type="number" step="0.01" class="form-control" name="price" id="e_price"></div>
          <input type="hidden" name="vat_rate" id="e_vat_rate" value="18">
<div class="col-sm-2">
  <label>KDV Tutarı (18%)</label>
  <input type="number" step="0.01" class="form-control" id="e_vat_amount" readonly>
</div>
      </form>
      <div id="editAlert" class="alert" style="display:none;margin-top:8px;"></div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-default" data-dismiss="modal"><?=$I['cancel']?></button>
      <button class="btn btn-primary" id="saveEdit"><?=$I['edit']?></button>
    </div>
  </div></div>
</div>

<script src="bower_components/jquery/dist/jquery.min.js"></script>
<script src="bower_components/bootstrap/dist/js/bootstrap.min.js"></script>
<script>
function filterSubtypes($sel, typeId){
  $sel.find('option').each(function(){
    var ok = !$(this).data('type') || $(this).data('type')==typeId || $(this).val()==='';
    $(this).toggle(ok);
  });
}
function updateVatAmount($priceInput, $vatAmountInput) {
  var price = parseFloat($priceInput.val()) || 0;
  var vat = price * 0.18; // %18
  $vatAmountInput.val(vat.toFixed(2));
}

function filterParams($sel, typeId, subId){
  $sel.find('option').each(function(){
    var t = $(this).data('type'), s = $(this).data('subtype');
    var ok = (!typeId || t==typeId) && (!subId || s==subId);
    $(this).toggle(ok);
  });
}
function calcSelectedPrice($sel, $priceInput){
  var sum = 0;
  $sel.find('option:selected').each(function(){
    var p = parseFloat($(this).data('price')) || 0;
    sum += p;
  });
  if(sum>0 && !$priceInput.val()) $priceInput.val(sum.toFixed(2));
}

$(function(){
  // Formda tip değişince alt tip ve parametreleri filtrele
  $('#selType').on('change', function(){
    var t = $(this).val();
    filterSubtypes($('#selSubtype'), t);
    filterParams($('#selParams'), t, $('#selSubtype').val());
  });
  $('#selSubtype').on('change', function(){
    filterParams($('#selParams'), $('#selType').val(), $(this).val());
  });
  $('#selParams').on('change', function(){ calcSelectedPrice($('#selParams'), $('#price')); });
// fiyat değişince KDV tutarını güncelle
$('#price').on('input', function(){
  updateVatAmount($('#price'), $('#vat_amount'));
});

// parametre seçimi toplam fiyatı dolduruyorsa KDV’yi de güncelle
$('#selParams').on('change', function(){
  calcSelectedPrice($('#selParams'), $('#price'));
  updateVatAmount($('#price'), $('#vat_amount'));
});

// ilk yüklemede de bir kez hesapla (boşsa 0,00 olur)
updateVatAmount($('#price'), $('#vat_amount'));
  // Ekle
  $('#addForm').on('submit', function(e){
    e.preventDefault();
    var data = $(this).serializeArray();
    data.push({name:'ajax', value:1}, {name:'action', value:'add'});
    $.post('facility-aggrement.php?lang=<?= $lang ?>&db=<?= $selectedDb ?>', data, function(res){
      if(res && res.success){ location.reload(); }
      else { $('#addAlert').removeClass('alert-success').addClass('alert-danger').text(res.message||'Hata').show(); }
    }, 'json').fail(function(xhr){
      $('#addAlert').removeClass('alert-success').addClass('alert-danger').text(xhr.responseText||'Hata').show();
    });
  });

  // Düzenle modalını aç
  $('.edit-btn').on('click', function(){
    var btn = $(this);
    $('#e_id').val(btn.data('id'));
    $('#e_fac').val(btn.data('facility'));
    $('#e_reason').val(btn.data('reason'));
    $('#e_type').val(btn.data('type'));
    filterSubtypes($('#e_subtype'), btn.data('type') || '');
    $('#e_subtype').val(btn.data('subtype'));
    filterParams($('#e_params'), $('#e_type').val(), $('#e_subtype').val());
    // seçili parametreleri çekmek için küçük istek:
    $.getJSON('fetch-agreement-params.php', {id: btn.data('id')}, function(resp){
      if(resp && resp.ids){ $('#e_params').val(resp.ids.map(String)); }
    });
    $('#e_start').val(btn.data('start')||'');
    $('#e_end').val(btn.data('end')||'');
    $('#e_price').val(btn.data('price')||'');
    $('#e_vat').val(btn.data('vat')||'18');
    $('#editAlert').hide();
    $('#editModal').modal('show');
  });

  // Düzenlemeyi kaydet
  $('#saveEdit').on('click', function(){
    var data = $('#editForm').serializeArray();
    data.push({name:'ajax', value:1}, {name:'action', value:'update'});
    $.post('facility-aggrement.php?lang=<?= $lang ?>&db=<?= $selectedDb ?>', data, function(res){
      if(res && res.success){ location.reload(); }
      else { $('#editAlert').removeClass('alert-success').addClass('alert-danger').text(res.message||'Hata').show(); }
    }, 'json').fail(function(xhr){
      $('#editAlert').removeClass('alert-success').addClass('alert-danger').text(xhr.responseText||'Hata').show();
    });
  });

  // Sil
  $('.del-btn').on('click', function(){
    if(!confirm('<?= addslashes($I['confirm_del']) ?>')) return;
    $.post('facility-aggrement.php?lang=<?= $lang ?>&db=<?= $selectedDb ?>',
      {ajax:1, action:'delete', id:$(this).data('id')},
      function(res){ if(res && res.success){ location.reload(); } else { alert(res.message||'Hata'); } },
      'json'
    ).fail(function(xhr){ alert(xhr.responseText||'Hata'); });
  });

  // Edit modal içi filtre
  $('#e_type').on('change', function(){
    filterSubtypes($('#e_subtype'), $(this).val());
    filterParams($('#e_params'), $(this).val(), $('#e_subtype').val());
  });
  $('#e_subtype').on('change', function(){
    filterParams($('#e_params'), $('#e_type').val(), $(this).val());
  });
});
</script>
</body>
</html>
