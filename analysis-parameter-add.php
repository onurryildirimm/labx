<?php
require_once "db.php";
session_start();

/* === Ortak parametreler === */
$lang = $_GET['lang'] ?? 'tr';
$selectedDb = $_GET['db'] ?? '2025';
$languages = ['tr'=>'Türkçe','en'=>'English','mk'=>'Македонски'];

/* === Menü/Sidebar metinleri === */
require_once "language_analiz.php";
$current_texts = $texts[$lang] ?? $texts['tr'];

/* === Sayfa i18n === */
$page_i18n = [
 'tr'=>[
  'page_title'=>'Analiz Parametre Tanımlama',
  'banner'=>'ANALİZ PARAMETRE TANIMLAMA',
  'select_type'=>'Analiz Türü Seç',
  'select_subtype'=>'Alt Analiz Türü Seç',
  'add_param'=>'PARAMETRE EKLE',
  'list_title'=>'Mevcut Tanımlı Parametreler',
  'type_col'=>'Analiz Türü',
  'subtype_col'=>'Analiz Alt Türü',
  'name'=>'Analiz Adı / Analysis Name',
  'unit'=>'Birim / Unit',
  'product_group'=>'Ürün Grubu',
  'method'=>'Analiz Metodu / Analysis Method',
  'uncertainty'=>'Ölçüm Belirsizliği / Measurement Uncertainty',
  'period'=>'Analiz Süresi / Analysis Period',
  'report_limit'=>'Raporlama Limiti',
  'limit_value'=>'Sınır Değer',
  'default_result'=>'Standart Sonuç',
  'default_result_ph'=>'Örn: Tespit Edilmedi',
  'price'=>'Fiyat',
  'actions'=>'Güncelle/Sil',
  'save'=>'Kaydet',
  'edit'=>'Düzenle',
  'delete'=>'Sil',
  'cancel'=>'İptal',
  'search'=>'Ara',
  'success_add'=>'Parametre eklendi.',
  'success_upd'=>'Parametre güncellendi.',
  'success_del'=>'Parametre silindi.',
  'confirm_delete'=>'Bu parametre silinsin mi?',
  'err_required'=>'Lütfen analiz türü, alt tür ve isim alanlarını doldurun.',
  'name_tr'=>'Ad (TR)',
  'name_en'=>'Ad (EN)',
  'name_mk'=>'Ad (MK)',
  'select'=>'Seçiniz'
 ],
 'en'=>[
  'page_title'=>'Define Analysis Parameters',
  'banner'=>'DEFINE ANALYSIS PARAMETERS',
  'select_type'=>'Select Analysis Type',
  'select_subtype'=>'Select Sub Analysis Type',
  'add_param'=>'ADD PARAMETER',
  'list_title'=>'Defined Parameters',
  'type_col'=>'Analysis Type',
  'subtype_col'=>'Sub Analysis Type',
  'name'=>'Analysis Name',
  'unit'=>'Unit',
  'product_group'=>'Product Group',
  'method'=>'Analysis Method',
  'uncertainty'=>'Measurement Uncertainty',
  'period'=>'Analysis Period',
  'report_limit'=>'Reporting Limit',
  'limit_value'=>'Limit Value',
  'default_result'=>'Standard Result',
  'default_result_ph'=>'e.g., Not Detected',
  'price'=>'Price',
  'actions'=>'Actions',
  'save'=>'Save',
  'edit'=>'Edit',
  'delete'=>'Delete',
  'cancel'=>'Cancel',
  'search'=>'Search',
  'success_add'=>'Parameter added.',
  'success_upd'=>'Parameter updated.',
  'success_del'=>'Parameter deleted.',
  'confirm_delete'=>'Delete this parameter?',
  'err_required'=>'Please select type/subtype and fill the names.',
  'name_tr'=>'Name (TR)',
  'name_en'=>'Name (EN)',
  'name_mk'=>'Name (MK)',
  'select'=>'Select'
 ],
 'mk'=>[
  'page_title'=>'Дефинирање параметри на анализа',
  'banner'=>'ДЕФИНИРАЊЕ ПАРАМЕТРИ НА АНАЛИЗА',
  'select_type'=>'Избери тип на анализа',
  'select_subtype'=>'Избери подтип',
  'add_param'=>'ДОДАДИ ПАРАМЕТАР',
  'list_title'=>'Постоечки параметри',
  'type_col'=>'Тип на анализа',
  'subtype_col'=>'Подтип',
  'name'=>'Име на анализа',
  'unit'=>'Единица',
  'product_group'=>'Производна група',
  'method'=>'Метод на анализа',
  'uncertainty'=>'Мерна неизвесност',
  'period'=>'Период на анализа',
  'report_limit'=>'Ограничување за известување',
  'limit_value'=>'Гранична вредност',
  'default_result'=>'Стандарден резултат',
  'default_result_ph'=>'Пр: Не е откриено',
  'price'=>'Цена',
  'actions'=>'Дејства',
  'save'=>'Зачувај',
  'edit'=>'Уреди',
  'delete'=>'Избриши',
  'cancel'=>'Откажи',
  'search'=>'Барај',
  'success_add'=>'Параметарот е додаден.',
  'success_upd'=>'Параметарот е ажуриран.',
  'success_del'=>'Параметарот е избришан.',
  'confirm_delete'=>'Да се избрише параметарот?',
  'err_required'=>'Изберете тип/подтип и пополнете ги имињата.',
  'name_tr'=>'Име (TR)',
  'name_en'=>'Име (EN)',
  'name_mk'=>'Име (MK)',
  'select'=>'Изберете'
 ]
];
$T = $page_i18n[$lang] ?? $page_i18n['tr'];

/* === Sözlükler === */
$types = $pdo->query("SELECT id, name_tr, name_en, name_mk FROM analysis_types ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
$subtypes = $pdo->query("SELECT id, type_id, name_tr, name_en, name_mk FROM analysis_subtypes ORDER BY type_id,id")->fetchAll(PDO::FETCH_ASSOC);

function dictName($row,$lang){ return $lang==='en' ? $row['name_en'] : ($lang==='mk' ? $row['name_mk'] : $row['name_tr']); }

/* === default_result sütununun tipini kontrol et === */
$colInfo = $pdo->query("SHOW COLUMNS FROM analysis_parameters LIKE 'default_result'")->fetch(PDO::FETCH_ASSOC);
$isDefaultResultText = $colInfo && (stripos($colInfo['Type'], 'varchar') !== false || stripos($colInfo['Type'], 'text') !== false);

/* === AJAX add/update/delete === */
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['ajax'])) {
  header('Content-Type: application/json; charset=utf-8');
  $action = $_POST['action'] ?? '';
  try {
    if ($action==='add') {
      $type_id    = (int)($_POST['type_id'] ?? 0);
      $subtype_id = (int)($_POST['subtype_id'] ?? 0);
      $name_tr = trim($_POST['name_tr'] ?? '');
      $name_en = trim($_POST['name_en'] ?? '');
      $name_mk = trim($_POST['name_mk'] ?? '');
      if (!$type_id || !$subtype_id || $name_tr==='' || $name_en==='' || $name_mk==='') throw new Exception($T['err_required']);
      
      $unit = trim($_POST['unit'] ?? '');
      $product_group = trim($_POST['product_group'] ?? '');
      $method = trim($_POST['method'] ?? '');
      $uncertainty = trim($_POST['uncertainty'] ?? '');
      $period = trim($_POST['period'] ?? '');
      $reporting_limit = trim($_POST['reporting_limit'] ?? '');
      $limit_value = trim($_POST['limit_value'] ?? '');
      $default_result = trim($_POST['default_result'] ?? '');
      $price = (float)($_POST['price'] ?? 0);
      
      $ins=$pdo->prepare("INSERT INTO analysis_parameters
        (type_id,subtype_id,name_tr,name_en,name_mk,unit,product_group,method,uncertainty,period,reporting_limit,limit_value,default_result,price)
        VALUES (:t,:st,:tr,:en,:mk,:u,:pg,:m,:uc,:p,:rl,:lv,:dr,:pr)");
      $ins->execute([
        ':t'=>$type_id, ':st'=>$subtype_id, ':tr'=>$name_tr, ':en'=>$name_en, ':mk'=>$name_mk,
        ':u'=>$unit, ':pg'=>$product_group, ':m'=>$method, ':uc'=>$uncertainty, ':p'=>$period,
        ':rl'=>$reporting_limit, ':lv'=>$limit_value, ':dr'=>$default_result, ':pr'=>$price
      ]);
      echo json_encode(['success'=>true,'message'=>$T['success_add']]); exit;
    }
    
    if ($action==='update') {
      $id = (int)($_POST['id'] ?? 0);
      $type_id    = (int)($_POST['type_id'] ?? 0);
      $subtype_id = (int)($_POST['subtype_id'] ?? 0);
      $name_tr = trim($_POST['name_tr'] ?? '');
      $name_en = trim($_POST['name_en'] ?? '');
      $name_mk = trim($_POST['name_mk'] ?? '');
      if (!$id || !$type_id || !$subtype_id || $name_tr==='' || $name_en==='' || $name_mk==='') throw new Exception($T['err_required']);
      
      $unit = trim($_POST['unit'] ?? '');
      $product_group = trim($_POST['product_group'] ?? '');
      $method = trim($_POST['method'] ?? '');
      $uncertainty = trim($_POST['uncertainty'] ?? '');
      $period = trim($_POST['period'] ?? '');
      $reporting_limit = trim($_POST['reporting_limit'] ?? '');
      $limit_value = trim($_POST['limit_value'] ?? '');
      $default_result = trim($_POST['default_result'] ?? '');
      $price = (float)($_POST['price'] ?? 0);
      
      $upd=$pdo->prepare("UPDATE analysis_parameters SET
        type_id=:t,subtype_id=:st,name_tr=:tr,name_en=:en,name_mk=:mk,unit=:u,product_group=:pg,method=:m,uncertainty=:uc,
        period=:p,reporting_limit=:rl,limit_value=:lv,default_result=:dr,price=:pr WHERE id=:id");
      $upd->execute([
        ':t'=>$type_id, ':st'=>$subtype_id, ':tr'=>$name_tr, ':en'=>$name_en, ':mk'=>$name_mk,
        ':u'=>$unit, ':pg'=>$product_group, ':m'=>$method, ':uc'=>$uncertainty, ':p'=>$period,
        ':rl'=>$reporting_limit, ':lv'=>$limit_value, ':dr'=>$default_result, ':pr'=>$price, ':id'=>$id
      ]);
      echo json_encode(['success'=>true,'message'=>$T['success_upd']]); exit;
    }
    
    if ($action==='delete') {
      $id = (int)($_POST['id'] ?? 0);
      $pdo->prepare("DELETE FROM analysis_parameters WHERE id=:id")->execute([':id'=>$id]);
      echo json_encode(['success'=>true,'message'=>$T['success_del']]); exit;
    }
    echo json_encode(['success'=>false,'message'=>'Unknown action']); exit;
  } catch(Exception $e){
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]); exit;
  }
}

/* === Filtreler & Liste === */
$fltType = isset($_GET['type_id']) ? (int)$_GET['type_id'] : 0;
$fltSub  = isset($_GET['subtype_id']) ? (int)$_GET['subtype_id'] : 0;
$q = trim($_GET['q'] ?? '');

$params=[]; $w=[];
$sql = "
  SELECT p.*, 
         t.name_tr t_tr, t.name_en t_en, t.name_mk t_mk,
         st.name_tr st_tr, st.name_en st_en, st.name_mk st_mk
  FROM analysis_parameters p
  JOIN analysis_types t ON t.id = p.type_id
  JOIN analysis_subtypes st ON st.id = p.subtype_id
";
if ($fltType){ $w[]='p.type_id=:t'; $params[':t']=$fltType; }
if ($fltSub) { $w[]='p.subtype_id=:st'; $params[':st']=$fltSub; }
if ($q!==''){ $w[]='(p.name_tr LIKE :q OR p.name_en LIKE :q OR p.name_mk LIKE :q OR p.method LIKE :q)'; $params[':q']="%{$q}%"; }
if ($w) $sql .= " WHERE ".implode(' AND ',$w);
$sql .= " ORDER BY p.id DESC";

$st = $pdo->prepare($sql);
$st->execute($params);
$rows = $st->fetchAll(PDO::FETCH_ASSOC);
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
 .sub-title{background:#f7f7f7;color:#333;padding:8px 12px;border:1px solid #ddd;text-align:center;font-weight:600;}
 .search-box{margin:10px 0;}
 .table td, .table th{vertical-align:middle;}
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
        <?php foreach($languages as $key=>$language): ?>
          <li><a href="?lang=<?=$key?>&db=<?=htmlspecialchars($selectedDb)?>"><?=$language?></a></li>
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
        <header class="blk-title"><?=$T['banner']?></header>
        <div class="panel-body">

          <!-- Üst seçimler -->
          <form class="row" method="get" action="analysis-parameter-add.php" id="fltForm">
            <input type="hidden" name="lang" value="<?=htmlspecialchars($lang)?>">
            <input type="hidden" name="db" value="<?=htmlspecialchars($selectedDb)?>">
            <div class="col-sm-4">
              <label><?=$T['select_type']?></label>
              <select class="form-control" name="type_id" id="fltType">
                <option value="0">--</option>
                <?php foreach($types as $t): ?>
                  <option value="<?=$t['id']?>" <?=$fltType==$t['id']?'selected':''?>><?=htmlspecialchars(dictName($t,$lang))?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-sm-4">
              <label><?=$T['select_subtype']?></label>
              <select class="form-control" name="subtype_id" id="fltSub">
                <option value="0">--</option>
                <?php foreach($subtypes as $s): ?>
                  <option value="<?=$s['id']?>" data-type="<?=$s['type_id']?>" <?=$fltSub==$s['id']?'selected':''?>>
                    <?=htmlspecialchars(dictName($s,$lang))?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-sm-4">
              <label><?=$T['search']?></label>
              <div class="input-group">
                <input class="form-control" name="q" value="<?=htmlspecialchars($q)?>" placeholder="<?=$T['search'].'...'?>">
                <span class="input-group-btn">
                  <button class="btn btn-default"><i class="fa fa-search"></i></button>
                </span>
              </div>
            </div>
          </form>

          <!-- Ekle bloğu -->
          <div class="sub-title"><?=$T['add_param']?></div>
          <form id="addForm">
            <div class="row">
              <div class="col-sm-4">
                <label><?=$T['name_tr']?></label>
                <input type="text" name="name_tr" class="form-control">
              </div>
              <div class="col-sm-4">
                <label><?=$T['name_en']?></label>
                <input type="text" name="name_en" class="form-control">
              </div>
              <div class="col-sm-4">
                <label><?=$T['name_mk']?></label>
                <input type="text" name="name_mk" class="form-control">
              </div>
            </div>

            <div class="row" style="margin-top:8px;">
              <div class="col-sm-2"><label><?=$T['unit']?></label><input class="form-control" name="unit"></div>
              <div class="col-sm-2"><label><?=$T['product_group']?></label><input class="form-control" name="product_group"></div>
              <div class="col-sm-3"><label><?=$T['method']?></label><input class="form-control" name="method"></div>
              <div class="col-sm-2"><label><?=$T['uncertainty']?></label><input class="form-control" name="uncertainty"></div>
              <div class="col-sm-3"><label><?=$T['period']?></label><input class="form-control" name="period"></div>
            </div>

            <div class="row" style="margin-top:8px;">
              <div class="col-sm-2"><label><?=$T['report_limit']?></label><input class="form-control" name="reporting_limit"></div>
              <div class="col-sm-2"><label><?=$T['limit_value']?></label><input class="form-control" name="limit_value"></div>
              <div class="col-sm-2"><label><?=$T['price']?></label><input type="number" step="0.01" class="form-control" name="price" value="0"></div>
              <div class="col-sm-3">
                <label><?=$T['default_result']?></label>
                <input type="text" class="form-control" name="default_result" placeholder="<?=$T['default_result_ph']?>">
              </div>
              <div class="col-sm-3">
                <label>&nbsp;</label>
                <button type="submit" class="btn btn-primary btn-block"><i class="fa fa-save"></i> <?=$T['save']?></button>
              </div>
            </div>
          </form>
          <div id="addAlert" class="alert" style="display:none;margin-top:8px;"></div>

          <!-- Liste -->
          <div class="sub-title" style="margin-top:15px;"><?=$T['list_title']?></div>
          <div class="table-responsive">
            <table class="table table-bordered table-striped">
              <thead>
                <tr>
                  <th><?=$T['type_col']?></th>
                  <th><?=$T['subtype_col']?></th>
                  <th><?=$T['name']?></th>
                  <th><?=$T['unit']?></th>
                  <th><?=$T['product_group']?></th>
                  <th><?=$T['method']?></th>
                  <th><?=$T['uncertainty']?></th>
                  <th><?=$T['period']?></th>
                  <th><?=$T['report_limit']?></th>
                  <th><?=$T['limit_value']?></th>
                  <th class="text-right"><?=$T['price']?></th>
                  <th><?=$T['default_result']?></th>
                  <th style="width:170px;"><?=$T['actions']?></th>
                </tr>
              </thead>
              <tbody>
                <?php if(!$rows): ?>
                  <tr><td colspan="13" class="text-center text-muted">-</td></tr>
                <?php endif; ?>
                <?php foreach($rows as $r): ?>
                  <?php
                    $typeName = $lang==='en' ? $r['t_en'] : ($lang==='mk' ? $r['t_mk'] : $r['t_tr']);
                    $subName  = $lang==='en' ? $r['st_en'] : ($lang==='mk' ? $r['st_mk'] : $r['st_tr']);
                    $paramName= $lang==='en' ? $r['name_en'] : ($lang==='mk' ? $r['name_mk'] : $r['name_tr']);
                  ?>
                  <tr>
                    <td><?=htmlspecialchars($typeName)?></td>
                    <td><?=htmlspecialchars($subName)?></td>
                    <td><?=htmlspecialchars($paramName)?></td>
                    <td><?=htmlspecialchars($r['unit'])?></td>
                    <td><?=htmlspecialchars($r['product_group'])?></td>
                    <td><?=htmlspecialchars($r['method'])?></td>
                    <td><?=htmlspecialchars($r['uncertainty'])?></td>
                    <td><?=htmlspecialchars($r['period'])?></td>
                    <td><?=htmlspecialchars($r['reporting_limit'])?></td>
                    <td><?=htmlspecialchars($r['limit_value'])?></td>
                    <td class="text-right"><?=number_format((float)$r['price'],2,',','.')?></td>
                    <td><strong><?=htmlspecialchars($r['default_result'] ?? '')?></strong></td>
                    <td class="text-center">
                      <button class="btn btn-xs btn-primary edit-btn"
                        data-row='<?=json_encode($r, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP)?>'>
                        <i class="fa fa-edit"></i> <?=$T['edit']?>
                      </button>
                      <button class="btn btn-xs btn-danger del-btn" data-id="<?=$r['id']?>">
                        <i class="fa fa-trash"></i> <?=$T['delete']?>
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

<!-- Düzenle Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
  <div class="modal-dialog modal-lg"><div class="modal-content">
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal">&times;</button>
      <h4 class="modal-title"><?=$T['edit']?></h4>
    </div>
    <div class="modal-body">
      <form id="editForm">
        <input type="hidden" name="id" id="e_id">
        <div class="row">
          <div class="col-sm-4">
            <label><?=$T['select_type']?></label>
            <select class="form-control" name="type_id" id="e_type">
              <?php foreach($types as $t): ?>
                <option value="<?=$t['id']?>"><?=htmlspecialchars(dictName($t,$lang))?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-sm-4">
            <label><?=$T['select_subtype']?></label>
            <select class="form-control" name="subtype_id" id="e_sub">
              <?php foreach($subtypes as $s): ?>
                <option value="<?=$s['id']?>" data-type="<?=$s['type_id']?>"><?=htmlspecialchars(dictName($s,$lang))?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="row" style="margin-top:8px;">
          <div class="col-sm-4"><label><?=$T['name_tr']?></label><input class="form-control" name="name_tr" id="e_tr"></div>
          <div class="col-sm-4"><label><?=$T['name_en']?></label><input class="form-control" name="name_en" id="e_en"></div>
          <div class="col-sm-4"><label><?=$T['name_mk']?></label><input class="form-control" name="name_mk" id="e_mk"></div>
        </div>

        <div class="row" style="margin-top:8px;">
          <div class="col-sm-2"><label><?=$T['unit']?></label><input class="form-control" name="unit" id="e_unit"></div>
          <div class="col-sm-2"><label><?=$T['product_group']?></label><input class="form-control" name="product_group" id="e_pg"></div>
          <div class="col-sm-3"><label><?=$T['method']?></label><input class="form-control" name="method" id="e_method"></div>
          <div class="col-sm-2"><label><?=$T['uncertainty']?></label><input class="form-control" name="uncertainty" id="e_unc"></div>
          <div class="col-sm-3"><label><?=$T['period']?></label><input class="form-control" name="period" id="e_period"></div>
        </div>

        <div class="row" style="margin-top:8px;">
          <div class="col-sm-3"><label><?=$T['report_limit']?></label><input class="form-control" name="reporting_limit" id="e_rl"></div>
          <div class="col-sm-3"><label><?=$T['limit_value']?></label><input class="form-control" name="limit_value" id="e_lv"></div>
          <div class="col-sm-2"><label><?=$T['price']?></label><input type="number" step="0.01" class="form-control" name="price" id="e_price"></div>
          <div class="col-sm-4">
            <label><?=$T['default_result']?></label>
            <input type="text" class="form-control" name="default_result" id="e_dr" placeholder="<?=$T['default_result_ph']?>">
          </div>
        </div>
      </form>
      <div id="editAlert" class="alert" style="display:none;"></div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-default" data-dismiss="modal"><?=$T['cancel']?></button>
      <button class="btn btn-primary" id="saveEdit"><?=$T['save']?></button>
    </div>
  </div></div>
</div>

<script src="bower_components/jquery/dist/jquery.min.js"></script>
<script src="bower_components/bootstrap/dist/js/bootstrap.min.js"></script>
<script>
function filterSubByType($subSelect, typeId){
  $subSelect.find('option').each(function(){
    var ok = !$(this).data('type') || $(this).data('type') == typeId;
    $(this).toggle(ok);
  });
}
$(function(){
  // Filtre formunda alt türü, tipe göre filtrele
  function applyFilterSelects(){
    var tid = $('#fltType').val();
    filterSubByType($('#fltSub'), tid);
  }
  applyFilterSelects();
  $('#fltType').on('change', function(){ applyFilterSelects(); $('#fltForm').submit(); });

  // Ekle
  $('#addForm').on('submit', function(e){
    e.preventDefault();
    var t = $('#fltType').val(), st = $('#fltSub').val();
    var data = $(this).serializeArray();
    data.push({name:'type_id', value:t}, {name:'subtype_id', value:st}, {name:'ajax', value:1}, {name:'action', value:'add'});
    $.post('analysis-parameter-add.php?lang=<?= $lang ?>&db=<?= $selectedDb ?>', data, function(res){
      if(res && res.success){ location.reload(); }
      else { $('#addAlert').removeClass('alert-success').addClass('alert-danger').text(res.message||'Hata').show(); }
    }, 'json').fail(function(xhr){
      $('#addAlert').removeClass('alert-success').addClass('alert-danger').text(xhr.responseText||'Hata').show();
    });
  });

  // Düzenle modalı
  $('.edit-btn').on('click', function(){
    var r = $(this).data('row');
    $('#e_id').val(r.id);
    $('#e_type').val(r.type_id);
    filterSubByType($('#e_sub'), r.type_id);
    $('#e_sub').val(r.subtype_id);
    $('#e_tr').val(r.name_tr);
    $('#e_en').val(r.name_en);
    $('#e_mk').val(r.name_mk);
    $('#e_unit').val(r.unit); $('#e_pg').val(r.product_group);
    $('#e_method').val(r.method); $('#e_unc').val(r.uncertainty);
    $('#e_period').val(r.period); $('#e_rl').val(r.reporting_limit);
    $('#e_lv').val(r.limit_value); $('#e_price').val(r.price);
    $('#e_dr').val(r.default_result || '');
    $('#editAlert').hide();
    $('#editModal').modal('show');
  });

  // Düzenlemeyi kaydet
  $('#saveEdit').on('click', function(){
    var data = $('#editForm').serializeArray();
    data.push({name:'ajax', value:1}, {name:'action', value:'update'});
    $.post('analysis-parameter-add.php?lang=<?= $lang ?>&db=<?= $selectedDb ?>', data, function(res){
      if(res && res.success){ location.reload(); }
      else { $('#editAlert').removeClass('alert-success').addClass('alert-danger').text(res.message||'Hata').show(); }
    }, 'json').fail(function(xhr){
      $('#editAlert').removeClass('alert-success').addClass('alert-danger').text(xhr.responseText||'Hata').show();
    });
  });

  // Sil
  $('.del-btn').on('click', function(){
    if(!confirm('<?= addslashes($T['confirm_delete']) ?>')) return;
    $.post('analysis-parameter-add.php?lang=<?= $lang ?>&db=<?= $selectedDb ?>',
      {ajax:1, action:'delete', id:$(this).data('id')},
      function(res){ if(res && res.success){ location.reload(); } else { alert(res.message||'Hata'); } },
      'json'
    ).fail(function(xhr){ alert(xhr.responseText||'Hata'); });
  });

  // Edit modaldaki tip değişince alt türü filtrele
  $('#e_type').on('change', function(){ filterSubByType($('#e_sub'), $(this).val()); });
});
</script>
</body>
</html>