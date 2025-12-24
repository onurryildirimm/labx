<?php
require_once "db.php";
session_start();

/* ==== Ortak parametreler ==== */
$lang = $_GET['lang'] ?? 'tr';
$selectedDb = $_GET['db'] ?? '2025';
$languages = ['tr' => 'Türkçe', 'en' => 'English', 'mk' => 'Македонски'];

/* ==== Menü/Sidebar metinleri ==== */
require_once "language_analiz.php"; // $texts
$current_texts = $texts[$lang] ?? $texts['tr'];

/* ==== Sayfaya özgü i18n ==== */
$page_i18n = [
  'tr' => [
    'page_title'   => 'Alt Analiz Türü Tanımlama',
    'banner'       => 'ALT ANALİZ TÜRÜ TANIMLAMA',
    'info'         => 'Mevcut analiz türleri bu sayfada görüntülenecek. Ana sayfa analizler alt bölümleri buna göre şekillenecek. Tesis anlaşmalı olduğu analiz türlerini görecek.',
    'select_type'  => 'Analiz Türü Seç',
    'define_sub'   => 'Alt Analiz Türü Tanımla',
    'select_lab'   => 'Analiz Laboratuvarı Seç',
    'save'         => 'Kaydet',
    'search'       => 'Ara',
    'list_title'   => 'Mevcut Alt Analiz Türü Tanımları',
    'type_col'     => 'Analiz Türü / Analysis Type',
    'sub_col'      => 'Alt Analiz Türü',
    'lab_col'      => 'Analiz Laboratuvarı / Analysis Laboratory',
    'actions'      => 'Güncelle/Sil',
    'name_tr'      => 'Türkçe Ad',
    'name_en'      => 'English Name',
    'name_mk'      => 'Македонски Назив',
    'placeholder_tr'=> 'Örn: E. coli',
    'placeholder_en'=> 'e.g., E. coli',
    'placeholder_mk'=> 'пр. E. coli',
    'edit'         => 'Düzenle',
    'delete'       => 'Sil',
    'cancel'       => 'İptal',
    'save_btn'     => 'Kaydet',
    'confirm_delete'=> 'Bu alt analiz türü silinsin mi?',
    'success_add'  => 'Alt analiz türü eklendi.',
    'success_upd'  => 'Alt analiz türü güncellendi.',
    'success_del'  => 'Alt analiz türü silindi.',
    'err_required' => 'Lütfen analiz türünü seçin ve tüm dil alanlarını doldurun.'
  ],
  'en' => [
    'page_title'   => 'Define Sub Analysis Types',
    'banner'       => 'DEFINE SUB ANALYSIS TYPES',
    'info'         => 'Existing analysis types are listed here. App sections will follow these settings.',
    'select_type'  => 'Select Analysis Type',
    'define_sub'   => 'Define Sub Analysis Type',
    'select_lab'   => 'Select Analysis Laboratory',
    'save'         => 'Save',
    'search'       => 'Search',
    'list_title'   => 'Existing Sub Analysis Definitions',
    'type_col'     => 'Analysis Type',
    'sub_col'      => 'Sub Analysis Type',
    'lab_col'      => 'Analysis Laboratory',
    'actions'      => 'Edit/Delete',
    'name_tr'      => 'Turkish Name',
    'name_en'      => 'English Name',
    'name_mk'      => 'Macedonian Name',
    'placeholder_tr'=> 'e.g., E. coli (TR)',
    'placeholder_en'=> 'e.g., E. coli',
    'placeholder_mk'=> 'пр. E. coli',
    'edit'         => 'Edit',
    'delete'       => 'Delete',
    'cancel'       => 'Cancel',
    'save_btn'     => 'Save',
    'confirm_delete'=> 'Delete this sub analysis type?',
    'success_add'  => 'Sub type added.',
    'success_upd'  => 'Sub type updated.',
    'success_del'  => 'Sub type deleted.',
    'err_required' => 'Please select analysis type and fill all language fields.'
  ],
  'mk' => [
    'page_title'   => 'Дефинирање подтип на анализа',
    'banner'       => 'ДЕФИНИРАЊЕ НА ПОДТИП НА АНАЛИЗА',
    'info'         => 'Постоечките типови се прикажуваат овде. Секциите ќе се усогласат со нив.',
    'select_type'  => 'Избери тип на анализа',
    'define_sub'   => 'Дефинирај подтип',
    'select_lab'   => 'Избери лабораторија',
    'save'         => 'Зачувај',
    'search'       => 'Барај',
    'list_title'   => 'Постоечки подтипови на анализа',
    'type_col'     => 'Тип на анализа',
    'sub_col'      => 'Подтип',
    'lab_col'      => 'Лабораторија',
    'actions'      => 'Уреди/Избриши',
    'name_tr'      => 'Име (турски)',
    'name_en'      => 'Име (англиски)',
    'name_mk'      => 'Име (македонски)',
    'placeholder_tr'=> 'пр. E. coli (TR)',
    'placeholder_en'=> 'e.g., E. coli',
    'placeholder_mk'=> 'пр. E. coli',
    'edit'         => 'Уреди',
    'delete'       => 'Избриши',
    'cancel'       => 'Откажи',
    'save_btn'     => 'Зачувај',
    'confirm_delete'=> 'Да се избрише подтипот?',
    'success_add'  => 'Подтипот е додаден.',
    'success_upd'  => 'Подтипот е ажуриран.',
    'success_del'  => 'Подтипот е избришан.',
    'err_required' => 'Изберете тип и пополнете ги сите полиња.'
  ],
];
$T = $page_i18n[$lang] ?? $page_i18n['tr'];

/* ==== Sözlükler ==== */
$types = $pdo->query("SELECT id, name_tr, name_en, name_mk FROM analysis_types ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
$labs  = $pdo->query("SELECT id, name FROM laboratories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

/* ==== Yardımcı: sözlük adı ==== */
function dictName($row,$lang){
  return $lang==='en' ? $row['name_en'] : ($lang==='mk' ? $row['name_mk'] : $row['name_tr']);
}

/* ==== AJAX (add/update/delete) ==== */
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['ajax'])) {
  header('Content-Type: application/json; charset=utf-8');
  $action = $_POST['action'] ?? '';
  try {
    if ($action==='add') {
      $type_id = (int)($_POST['type_id'] ?? 0);
      $lab_id  = ($_POST['lab_id'] ?? '')==='' ? null : (int)$_POST['lab_id'];
      $tr = trim($_POST['name_tr'] ?? '');
      $en = trim($_POST['name_en'] ?? '');
      $mk = trim($_POST['name_mk'] ?? '');
      if (!$type_id || $tr==='' || $en==='' || $mk==='') throw new Exception($T['err_required']);
      $ins = $pdo->prepare("INSERT INTO analysis_subtypes (type_id, lab_id, name_tr, name_en, name_mk)
                            VALUES (:t,:l,:tr,:en,:mk)");
      $ins->execute([':t'=>$type_id, ':l'=>$lab_id, ':tr'=>$tr, ':en'=>$en, ':mk'=>$mk]);
      echo json_encode(['success'=>true,'message'=>$T['success_add']]); exit;
    }
    if ($action==='update') {
      $id = (int)($_POST['id'] ?? 0);
      $type_id = (int)($_POST['type_id'] ?? 0);
      $lab_id  = ($_POST['lab_id'] ?? '')==='' ? null : (int)$_POST['lab_id'];
      $tr = trim($_POST['name_tr'] ?? '');
      $en = trim($_POST['name_en'] ?? '');
      $mk = trim($_POST['name_mk'] ?? '');
      if (!$id || !$type_id || $tr==='' || $en==='' || $mk==='') throw new Exception($T['err_required']);
      $upd = $pdo->prepare("UPDATE analysis_subtypes
                            SET type_id=:t, lab_id=:l, name_tr=:tr, name_en=:en, name_mk=:mk
                            WHERE id=:id");
      $upd->execute([':t'=>$type_id, ':l'=>$lab_id, ':tr'=>$tr, ':en'=>$en, ':mk'=>$mk, ':id'=>$id]);
      echo json_encode(['success'=>true,'message'=>$T['success_upd']]); exit;
    }
    if ($action==='delete') {
      $id = (int)($_POST['id'] ?? 0);
      $del = $pdo->prepare("DELETE FROM analysis_subtypes WHERE id=:id");
      $del->execute([':id'=>$id]);
      echo json_encode(['success'=>true,'message'=>$T['success_del']]); exit;
    }
    echo json_encode(['success'=>false,'message'=>'Unknown action']); exit;
  } catch (Exception $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]); exit;
  }
}

/* ==== Filtreler (üst seçim ve arama) ==== */
$typeFilter = isset($_GET['type_id']) ? (int)$_GET['type_id'] : 0;
$q = trim($_GET['q'] ?? '');
$params = [];
$sql = "
  SELECT s.id, s.type_id, s.lab_id, s.name_tr, s.name_en, s.name_mk,
         t.name_tr t_tr, t.name_en t_en, t.name_mk t_mk,
         l.name lab_name
  FROM analysis_subtypes s
  JOIN analysis_types t ON t.id = s.type_id
  LEFT JOIN laboratories l ON l.id = s.lab_id
";
$w = [];
if ($typeFilter) { $w[] = "s.type_id = :tid"; $params[':tid'] = $typeFilter; }
if ($q !== '') {
  $w[] = "(s.name_tr LIKE :q OR s.name_en LIKE :q OR s.name_mk LIKE :q OR l.name LIKE :q)";
  $params[':q'] = "%{$q}%";
}
if ($w) $sql .= " WHERE ".implode(" AND ", $w);
$sql .= " ORDER BY s.type_id, s.id";
$st = $pdo->prepare($sql);
$st->execute($params);
$list = $st->fetchAll(PDO::FETCH_ASSOC);
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
    .info{background:#fafafa;border:1px dashed #ddd;padding:10px;margin:10px 0;color:#666;}
    .search-box{margin:10px 0;}
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
      <!-- Dil -->
      <li class="dropdown">
        <a href="#" class="dropdown-toggle" data-toggle="dropdown"><i class="fa fa-globe"></i> <?=$languages[$lang]?></a>
        <ul class="dropdown-menu">
          <?php foreach ($languages as $key => $language): ?>
            <li><a href="?lang=<?=$key?>&db=<?=htmlspecialchars($selectedDb)?>"><?=$language?></a></li>
          <?php endforeach; ?>
        </ul>
      </li>
      <!-- DB -->
      <li class="dropdown">
        <a href="#" class="dropdown-toggle" data-toggle="dropdown"><i class="fa fa-database"></i> <?=$current_texts['select_db'] ?? 'Veritabanı Seç'?></a>
        <ul class="dropdown-menu">
          <?php foreach (['2023','2024','2025'] as $db): ?>
            <li><a href="?lang=<?=$lang?>&db=<?=$db?>"><?=$db?></a></li>
          <?php endforeach; ?>
        </ul>
      </li>
      <!-- User -->
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
          <header class="blk-title"><?=htmlspecialchars($T['banner'])?></header>
          <div class="panel-body">
            <div class="info"><?=$T['info']?></div>

            <!-- Üst form -->
            <div class="row" style="margin-bottom:10px;">
              <div class="col-sm-4">
                <label><?=$T['select_type']?></label>
                <form method="get" action="sub-analysis.php" id="typeFilterForm">
                  <input type="hidden" name="lang" value="<?=htmlspecialchars($lang)?>">
                  <input type="hidden" name="db" value="<?=htmlspecialchars($selectedDb)?>">
                  <select class="form-control" name="type_id" onchange="document.getElementById('typeFilterForm').submit();">
                    <option value="0">--</option>
                    <?php foreach ($types as $t): ?>
                      <option value="<?=$t['id']?>" <?= $typeFilter==$t['id']?'selected':'' ?>>
                        <?=htmlspecialchars(dictName($t,$lang))?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </form>
              </div>

              <div class="col-sm-5">
                <label><?=$T['define_sub']?></label>
                <form id="addForm">
                  <div class="row">
                    <div class="col-xs-12 col-sm-4" style="margin-bottom:6px;">
                      <input type="text" name="name_tr" class="form-control" placeholder="<?=htmlspecialchars($T['placeholder_tr'])?>">
                    </div>
                    <div class="col-xs-12 col-sm-4" style="margin-bottom:6px;">
                      <input type="text" name="name_en" class="form-control" placeholder="<?=htmlspecialchars($T['placeholder_en'])?>">
                    </div>
                    <div class="col-xs-12 col-sm-4" style="margin-bottom:6px;">
                      <input type="text" name="name_mk" class="form-control" placeholder="<?=htmlspecialchars($T['placeholder_mk'])?>">
                    </div>
                  </div>
              </div>

              <div class="col-sm-3">
                <label><?=$T['select_lab']?></label>
                <select class="form-control" name="lab_id">
                  <option value=""><?=( $current_texts['select'] ?? 'Seçiniz')?></option>
                  <?php foreach ($labs as $l): ?>
                    <option value="<?=$l['id']?>"><?=htmlspecialchars($l['name'])?></option>
                  <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-primary" style="margin-top:8px;"><i class="fa fa-save"></i> <?=$T['save']?></button>
                </form>
                <div id="addAlert" class="alert" style="display:none;margin-top:8px;"></div>
              </div>
            </div>

            <!-- Arama -->
            <form class="search-box" method="get" action="sub-analysis.php">
              <input type="hidden" name="lang" value="<?=htmlspecialchars($lang)?>">
              <input type="hidden" name="db" value="<?=htmlspecialchars($selectedDb)?>">
              <?php if ($typeFilter): ?><input type="hidden" name="type_id" value="<?=$typeFilter?>"><?php endif; ?>
              <div class="input-group">
                <input type="text" class="form-control" name="q" value="<?=htmlspecialchars($q)?>" placeholder="<?=$T['search'].'...'?>">
                <span class="input-group-btn">
                  <button class="btn btn-default"><i class="fa fa-search"></i> <?=$T['search']?></button>
                </span>
              </div>
            </form>

            <div class="sub-title"><?=htmlspecialchars($T['list_title'])?></div>

            <!-- Liste -->
            <div class="table-responsive">
              <table class="table table-bordered table-striped">
                <thead>
                  <tr>
                    <th><?=$T['type_col']?></th>
                    <th><?=$T['sub_col']?></th>
                    <th><?=$T['lab_col']?></th>
                    <th style="width:180px;"><?=$T['actions']?></th>
                  </tr>
                </thead>
                <tbody>
                <?php if (!$list): ?>
                  <tr><td colspan="4" class="text-center text-muted">-</td></tr>
                <?php endif; ?>
                <?php foreach ($list as $row): ?>
                  <tr>
                    <td><?=htmlspecialchars( $lang==='en' ? $row['t_en'] : ($lang==='mk' ? $row['t_mk'] : $row['t_tr']) )?></td>
                    <td><?=htmlspecialchars( $lang==='en' ? $row['name_en'] : ($lang==='mk' ? $row['name_mk'] : $row['name_tr']) )?></td>
                    <td><?=htmlspecialchars($row['lab_name'] ?? '')?></td>
                    <td class="text-center">
                      <button class="btn btn-xs btn-primary edit-btn"
                        data-id="<?=$row['id']?>"
                        data-type="<?=$row['type_id']?>"
                        data-lab="<?=$row['lab_id']?>"
                        data-tr="<?=htmlspecialchars($row['name_tr'], ENT_QUOTES)?>"
                        data-en="<?=htmlspecialchars($row['name_en'], ENT_QUOTES)?>"
                        data-mk="<?=htmlspecialchars($row['name_mk'], ENT_QUOTES)?>">
                        <i class="fa fa-edit"></i> <?=$T['edit']?>
                      </button>
                      <button class="btn btn-xs btn-danger del-btn" data-id="<?=$row['id']?>">
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
  <div class="modal-dialog"><div class="modal-content">
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal">&times;</button>
      <h4 class="modal-title"><?=$T['edit']?></h4>
    </div>
    <div class="modal-body">
      <form id="editForm">
        <input type="hidden" name="id" id="e_id">
        <div class="form-group">
          <label><?=$T['select_type']?></label>
          <select class="form-control" name="type_id" id="e_type">
            <?php foreach($types as $t): ?>
              <option value="<?=$t['id']?>"><?=htmlspecialchars(dictName($t,$lang))?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="row">
          <div class="col-sm-4 form-group">
            <label><?=$T['name_tr']?></label>
            <input type="text" class="form-control" name="name_tr" id="e_tr">
          </div>
          <div class="col-sm-4 form-group">
            <label><?=$T['name_en']?></label>
            <input type="text" class="form-control" name="name_en" id="e_en">
          </div>
          <div class="col-sm-4 form-group">
            <label><?=$T['name_mk']?></label>
            <input type="text" class="form-control" name="name_mk" id="e_mk">
          </div>
        </div>
        <div class="form-group">
          <label><?=$T['select_lab']?></label>
          <select class="form-control" name="lab_id" id="e_lab">
            <option value=""><?=( $current_texts['select'] ?? 'Seçiniz')?></option>
            <?php foreach($labs as $l): ?>
              <option value="<?=$l['id']?>"><?=htmlspecialchars($l['name'])?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </form>
      <div id="editAlert" class="alert" style="display:none;"></div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-default" data-dismiss="modal"><?=$T['cancel']?></button>
      <button class="btn btn-primary" id="saveEdit"><?=$T['save_btn']?></button>
    </div>
  </div></div>
</div>

<script src="bower_components/jquery/dist/jquery.min.js"></script>
<script src="bower_components/bootstrap/dist/js/bootstrap.min.js"></script>
<script>
$(function(){
  // Ekle
  $('#addForm').on('submit', function(e){
    e.preventDefault();
    var typeId = $('select[name="type_id"]', '#typeFilterForm').val();
    var tr = $.trim($('[name="name_tr"]', this).val());
    var en = $.trim($('[name="name_en"]', this).val());
    var mk = $.trim($('[name="name_mk"]', this).val());
    var lab = $('[name="lab_id"]', this).val();

    if(!typeId || !tr || !en || !mk){
      $('#addAlert').removeClass('alert-success').addClass('alert-danger').text('<?=addslashes($T['err_required'])?>').show(); return;
    }
    $.post('sub-analysis.php?lang=<?=$lang?>&db=<?=$selectedDb?>',
      {ajax:1, action:'add', type_id:typeId, lab_id:lab, name_tr:tr, name_en:en, name_mk:mk},
      function(res){ if(res.success){ location.reload(); } else { $('#addAlert').removeClass('alert-success').addClass('alert-danger').text(res.message||'Hata').show(); } },
      'json'
    ).fail(function(xhr){ $('#addAlert').removeClass('alert-success').addClass('alert-danger').text(xhr.responseText||'Hata').show(); });
  });

  // Düzenle modalı aç
  $('.edit-btn').on('click', function(){
    $('#e_id').val($(this).data('id'));
    $('#e_type').val($(this).data('type'));
    $('#e_lab').val($(this).data('lab') || '');
    $('#e_tr').val($(this).data('tr'));
    $('#e_en').val($(this).data('en'));
    $('#e_mk').val($(this).data('mk'));
    $('#editAlert').hide();
    $('#editModal').modal('show');
  });

  // Düzenlemeyi kaydet
  $('#saveEdit').on('click', function(){
    var id = $('#e_id').val(),
        type = $('#e_type').val(),
        lab  = $('#e_lab').val(),
        tr = $.trim($('#e_tr').val()),
        en = $.trim($('#e_en').val()),
        mk = $.trim($('#e_mk').val());
    if(!id || !type || !tr || !en || !mk){
      $('#editAlert').removeClass('alert-success').addClass('alert-danger').text('<?=addslashes($T['err_required'])?>').show(); return;
    }
    $.post('sub-analysis.php?lang=<?=$lang?>&db=<?=$selectedDb?>',
      {ajax:1, action:'update', id:id, type_id:type, lab_id:lab, name_tr:tr, name_en:en, name_mk:mk},
      function(res){ if(res.success){ location.reload(); } else { $('#editAlert').removeClass('alert-success').addClass('alert-danger').text(res.message||'Hata').show(); } },
      'json'
    ).fail(function(xhr){ $('#editAlert').removeClass('alert-success').addClass('alert-danger').text(xhr.responseText||'Hata').show(); });
  });

  // Sil
  $('.del-btn').on('click', function(){
    if(!confirm('<?=addslashes($T['confirm_delete'])?>')) return;
    var id = $(this).data('id');
    $.post('sub-analysis.php?lang=<?=$lang?>&db=<?=$selectedDb?>',
      {ajax:1, action:'delete', id:id},
      function(res){ if(res.success){ location.reload(); } else { alert(res.message||'Hata'); } },
      'json'
    ).fail(function(xhr){ alert(xhr.responseText||'Hata'); });
  });
});
</script>
</body>
</html>
