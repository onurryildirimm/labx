<?php
require_once "db.php";
session_start();

/* ==== Ortak parametreler ==== */
$lang = $_GET['lang'] ?? 'tr';
$selectedDb = $_GET['db'] ?? '2025';
$languages = ['tr' => 'Türkçe', 'en' => 'English', 'mk' => 'Македонски'];

/* ==== Menü/Sidebar metinleri ==== */
require_once "language_analiz.php";
$current_texts = $texts[$lang] ?? $texts['tr'];

/* ==== Sayfaya özgü i18n ==== */
$page_i18n = [
  'tr' => [
    'page_title'   => 'Analiz Türü Tanımlama',
    'banner'       => 'ANALİZ TÜRÜ TANIMLAMA',
    'info'         => 'Mevcut analiz türleri bu sayfada görüntülenecek. Ana sayfa analizler alt bölümleri buna göre şekillenecek. Tesis anlaşmalı olduğu analiz türlerini görecek.',
    'add_block'    => 'ANALİZ TÜRÜ EKLE',
    'list_title'   => 'MEVCUT ANALİZ TÜRÜ TANIMLARI',
    'type_col'     => 'ANALİZ TÜRÜ / ANALYSIS TYPE',
    'lab_col'      => 'ANALİZ LABORATUVARI',
    'actions'      => 'Güncelle/Sil',
    'name_tr'      => 'Türkçe Ad',
    'name_en'      => 'English Name',
    'name_mk'      => 'Македонски Назив',
    'lab_select'   => 'Analiz Laboratuvarı Seç',
    'placeholder_tr'=> 'Gıda Mikrobiyolojik',
    'placeholder_en'=> 'Food Microbiological',
    'placeholder_mk'=> 'Микробиологија на храна',
    'add'          => 'Ekle',
    'edit'         => 'Düzenle',
    'delete'       => 'Sil',
    'save'         => 'Kaydet',
    'cancel'       => 'İptal',
    'confirm_delete'=> 'Bu analiz türü silinsin mi?',
    'success_add'  => 'Analiz türü eklendi.',
    'success_upd'  => 'Analiz türü güncellendi.',
    'success_del'  => 'Analiz türü silindi.',
    'err_required' => 'Lütfen tüm dil alanlarını doldurun.',
    'select'       => 'Seçiniz',
    'no_lab'       => 'Laboratuvar seçilmedi'
  ],
  'en' => [
    'page_title'   => 'Define Analysis Types',
    'banner'       => 'DEFINE ANALYSIS TYPES',
    'info'         => 'Defined types will be listed here. App menus will follow these. Facilities will see only contracted types.',
    'add_block'    => 'ADD ANALYSIS TYPE',
    'list_title'   => 'EXISTING ANALYSIS TYPES',
    'type_col'     => 'ANALYSIS TYPE',
    'lab_col'      => 'LABORATORY',
    'actions'      => 'Edit/Delete',
    'name_tr'      => 'Turkish Name',
    'name_en'      => 'English Name',
    'name_mk'      => 'Macedonian Name',
    'lab_select'   => 'Select Laboratory',
    'placeholder_tr'=> 'Food Microbiological (TR)',
    'placeholder_en'=> 'Food Microbiological',
    'placeholder_mk'=> 'Микробиологија на храна',
    'add'          => 'Add',
    'edit'         => 'Edit',
    'delete'       => 'Delete',
    'save'         => 'Save',
    'cancel'       => 'Cancel',
    'confirm_delete'=> 'Delete this analysis type?',
    'success_add'  => 'Type added.',
    'success_upd'  => 'Type updated.',
    'success_del'  => 'Type deleted.',
    'err_required' => 'Please fill all language fields.',
    'select'       => 'Select',
    'no_lab'       => 'No laboratory selected'
  ],
  'mk' => [
    'page_title'   => 'Дефинирање типови на анализа',
    'banner'       => 'ДЕФИНИРАЊЕ ТИП НА АНАЛИЗА',
    'info'         => 'Дефинираните типови се прикажуваат овде. Менијата ќе се усогласат со нив. Објектите ќе ги гледаат само договорените типови.',
    'add_block'    => 'ДОДАДИ ТИП НА АНАЛИЗА',
    'list_title'   => 'ПОСТОЕЧКИ ТИПОВИ НА АНАЛИЗА',
    'type_col'     => 'ТИП НА АНАЛИЗА',
    'lab_col'      => 'ЛАБОРАТОРИЈА',
    'actions'      => 'Уреди/Избриши',
    'name_tr'      => 'Име (турски)',
    'name_en'      => 'Име (англиски)',
    'name_mk'      => 'Име (македонски)',
    'lab_select'   => 'Избери лабораторија',
    'placeholder_tr'=> 'Микробиологија на храна (TR)',
    'placeholder_en'=> 'Food Microbiological',
    'placeholder_mk'=> 'Микробиологија на храна',
    'add'          => 'Додади',
    'edit'         => 'Уреди',
    'delete'       => 'Избриши',
    'save'         => 'Зачувај',
    'cancel'       => 'Откажи',
    'confirm_delete'=> 'Да се избрише овој тип?',
    'success_add'  => 'Типот е додаден.',
    'success_upd'  => 'Типот е ажуриран.',
    'success_del'  => 'Типот е избришан.',
    'err_required' => 'Пополнете ги сите полиња.',
    'select'       => 'Изберете',
    'no_lab'       => 'Не е избрана лабораторија'
  ],
];
$T = $page_i18n[$lang] ?? $page_i18n['tr'];

/* ==== Laboratuvarlar ==== */
$labs = $pdo->query("SELECT id, name FROM laboratories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

/* ==== lab_id sütunu var mı kontrol ==== */
$typeCols = $pdo->query("SHOW COLUMNS FROM analysis_types")->fetchAll(PDO::FETCH_COLUMN);
$hasLabId = in_array('lab_id', $typeCols, true);

/* ==== AJAX (add/update/delete) ==== */
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['ajax'])) {
  header('Content-Type: application/json; charset=utf-8');
  $action = $_POST['action'] ?? '';

  try {
    if ($action==='add') {
      $tr = trim($_POST['name_tr'] ?? '');
      $en = trim($_POST['name_en'] ?? '');
      $mk = trim($_POST['name_mk'] ?? '');
      $lab_id = (int)($_POST['lab_id'] ?? 0);
      
      if ($tr==='' || $en==='' || $mk==='') throw new Exception($T['err_required']);
      
      if ($hasLabId) {
        $ins = $pdo->prepare("INSERT INTO analysis_types (name_tr, name_en, name_mk, lab_id) VALUES (:tr,:en,:mk,:lab)");
        $ins->execute([':tr'=>$tr, ':en'=>$en, ':mk'=>$mk, ':lab'=>$lab_id ?: null]);
      } else {
        $ins = $pdo->prepare("INSERT INTO analysis_types (name_tr, name_en, name_mk) VALUES (:tr,:en,:mk)");
        $ins->execute([':tr'=>$tr, ':en'=>$en, ':mk'=>$mk]);
      }
      echo json_encode(['success'=>true,'message'=>$T['success_add']]); exit;
    }

    if ($action==='update') {
      $id = (int)($_POST['id'] ?? 0);
      $tr = trim($_POST['name_tr'] ?? '');
      $en = trim($_POST['name_en'] ?? '');
      $mk = trim($_POST['name_mk'] ?? '');
      $lab_id = (int)($_POST['lab_id'] ?? 0);
      
      if (!$id || $tr==='' || $en==='' || $mk==='') throw new Exception($T['err_required']);
      
      if ($hasLabId) {
        $upd = $pdo->prepare("UPDATE analysis_types SET name_tr=:tr, name_en=:en, name_mk=:mk, lab_id=:lab WHERE id=:id");
        $upd->execute([':tr'=>$tr, ':en'=>$en, ':mk'=>$mk, ':lab'=>$lab_id ?: null, ':id'=>$id]);
      } else {
        $upd = $pdo->prepare("UPDATE analysis_types SET name_tr=:tr, name_en=:en, name_mk=:mk WHERE id=:id");
        $upd->execute([':tr'=>$tr, ':en'=>$en, ':mk'=>$mk, ':id'=>$id]);
      }
      echo json_encode(['success'=>true,'message'=>$T['success_upd']]); exit;
    }

    if ($action==='delete') {
      $id = (int)($_POST['id'] ?? 0);
      if (!$id) throw new Exception('Invalid id');
      $del = $pdo->prepare("DELETE FROM analysis_types WHERE id=:id");
      $del->execute([':id'=>$id]);
      echo json_encode(['success'=>true,'message'=>$T['success_del']]); exit;
    }

    echo json_encode(['success'=>false,'message'=>'Unknown action']); exit;
  } catch (Exception $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]); exit;
  }
}

/* ==== Liste ==== */
if ($hasLabId) {
  $types = $pdo->query("
    SELECT t.id, t.name_tr, t.name_en, t.name_mk, t.lab_id, l.name AS lab_name
    FROM analysis_types t
    LEFT JOIN laboratories l ON l.id = t.lab_id
    ORDER BY t.id
  ")->fetchAll(PDO::FETCH_ASSOC);
} else {
  $types = $pdo->query("SELECT id, name_tr, name_en, name_mk FROM analysis_types ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
}

/* ==== Yardımcı: geçerli dil adını getir ==== */
function typeNameByLang($row, $lang){
  return $lang==='en' ? $row['name_en'] : ($lang==='mk' ? $row['name_mk'] : $row['name_tr']);
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
    .blk-title{background:#000;color:#fff;padding:8px 12px;font-weight:600;letter-spacing:.5px;text-align:center;}
    .sub-title{background:#f7f7f7;color:#333;padding:8px 12px;border:1px solid #ddd;text-align:center;font-weight:600;}
    .info{background:#fafafa;border:1px dashed #ddd;padding:10px;margin:10px 0;color:#666;}
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
        <a href="#" class="dropdown-toggle" data-toggle="dropdown">
          <i class="fa fa-database"></i> <?=$current_texts['select_db'] ?? 'Veritabanı Seç'?>
        </a>
        <ul class="dropdown-menu">
          <?php foreach (['2023','2024','2025'] as $db): ?>
            <li><a href="?lang=<?=$lang?>&db=<?=$db?>"><?=$db?></a></li>
          <?php endforeach; ?>
        </ul>
      </li>

      <!-- Kullanıcı -->
      <li class="dropdown">
        <a href="#" class="dropdown-toggle" data-toggle="dropdown">
          <i class="fa fa-user"></i> <?=$_SESSION['lab_user_name'] ?? 'Admin'?>
        </a>
        <ul class="dropdown-menu">
          <li><a href="profile.php"><i class="fa fa-cog"></i> <?=$current_texts['profile'] ?? 'Profil'?></a></li>
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

            <!-- Ekle Bloğu -->
            <div class="sub-title"><?=htmlspecialchars($T['add_block'])?></div>
            <form id="addForm" class="row" style="margin:10px 0;">
              <div class="col-sm-3 form-group">
                <label><?=$T['name_tr']?></label>
                <input type="text" name="name_tr" class="form-control" placeholder="<?=htmlspecialchars($T['placeholder_tr'])?>">
              </div>
              <div class="col-sm-3 form-group">
                <label><?=$T['name_en']?></label>
                <input type="text" name="name_en" class="form-control" placeholder="<?=htmlspecialchars($T['placeholder_en'])?>">
              </div>
              <div class="col-sm-3 form-group">
                <label><?=$T['name_mk']?></label>
                <input type="text" name="name_mk" class="form-control" placeholder="<?=htmlspecialchars($T['placeholder_mk'])?>">
              </div>
              <div class="col-sm-3 form-group">
                <label><?=$T['lab_select']?></label>
                <select name="lab_id" class="form-control">
                  <option value="0"><?=$T['select']?></option>
                  <?php foreach($labs as $l): ?>
                    <option value="<?=$l['id']?>"><?=htmlspecialchars($l['name'])?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-sm-12">
                <button type="submit" class="btn btn-primary"><i class="fa fa-plus"></i> <?=$T['add']?></button>
              </div>
            </form>
            <div id="addAlert" class="alert" style="display:none;"></div>

            <!-- Liste -->
            <div class="sub-title" style="margin-top:15px;"><?=htmlspecialchars($T['list_title'])?></div>
            <div class="table-responsive">
              <table class="table table-bordered table-striped">
                <thead>
                  <tr>
                    <th><?=$T['type_col']?></th>
                    <th><?=$T['lab_col']?></th>
                    <th style="width:160px;"><?=$T['actions']?></th>
                  </tr>
                </thead>
                <tbody>
                  <?php if(!$types): ?>
                    <tr><td colspan="3" class="text-center text-muted">-</td></tr>
                  <?php endif; ?>
                  <?php foreach($types as $t): ?>
                    <tr>
                      <td><?=htmlspecialchars(typeNameByLang($t, $lang))?></td>
                      <td><?=htmlspecialchars($t['lab_name'] ?? $T['no_lab'])?></td>
                      <td class="text-center">
                        <button class="btn btn-xs btn-primary edit-btn"
                          data-id="<?=$t['id']?>"
                          data-tr="<?=htmlspecialchars($t['name_tr'], ENT_QUOTES)?>"
                          data-en="<?=htmlspecialchars($t['name_en'], ENT_QUOTES)?>"
                          data-mk="<?=htmlspecialchars($t['name_mk'], ENT_QUOTES)?>"
                          data-lab="<?=$t['lab_id'] ?? 0?>">
                          <i class="fa fa-edit"></i> <?=$T['edit']?>
                        </button>
                        <button class="btn btn-xs btn-danger del-btn" data-id="<?=$t['id']?>">
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

<!-- Düzenleme Modalı -->
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
          <label><?=$T['name_tr']?></label>
          <input type="text" name="name_tr" id="e_tr" class="form-control">
        </div>
        <div class="form-group">
          <label><?=$T['name_en']?></label>
          <input type="text" name="name_en" id="e_en" class="form-control">
        </div>
        <div class="form-group">
          <label><?=$T['name_mk']?></label>
          <input type="text" name="name_mk" id="e_mk" class="form-control">
        </div>
        <div class="form-group">
          <label><?=$T['lab_select']?></label>
          <select name="lab_id" id="e_lab" class="form-control">
            <option value="0"><?=$T['select']?></option>
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
      <button class="btn btn-primary" id="saveEdit"><?=$T['save']?></button>
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
    var tr = $.trim($('[name="name_tr"]').val()),
        en = $.trim($('[name="name_en"]').val()),
        mk = $.trim($('[name="name_mk"]').val()),
        lab = $('[name="lab_id"]').val();
    if(!tr || !en || !mk){
      $('#addAlert').removeClass('alert-success').addClass('alert-danger').text('<?=addslashes($T['err_required'])?>').show();
      return;
    }
    $.post('add-analysis-type.php?lang=<?=$lang?>&db=<?=$selectedDb?>',
      {ajax:1, action:'add', name_tr:tr, name_en:en, name_mk:mk, lab_id:lab},
      function(res){ if(res.success){ location.reload(); } else { $('#addAlert').removeClass('alert-success').addClass('alert-danger').text(res.message||'Hata').show(); } },
      'json'
    ).fail(function(xhr){ $('#addAlert').removeClass('alert-success').addClass('alert-danger').text(xhr.responseText||'Hata').show(); });
  });

  // Düzenle modalını aç
  $('.edit-btn').on('click', function(){
    $('#e_id').val($(this).data('id'));
    $('#e_tr').val($(this).data('tr'));
    $('#e_en').val($(this).data('en'));
    $('#e_mk').val($(this).data('mk'));
    $('#e_lab').val($(this).data('lab') || 0);
    $('#editAlert').hide();
    $('#editModal').modal('show');
  });

  // Düzenlemeyi kaydet
  $('#saveEdit').on('click', function(){
    var id = $('#e_id').val(),
        tr = $.trim($('#e_tr').val()),
        en = $.trim($('#e_en').val()),
        mk = $.trim($('#e_mk').val()),
        lab = $('#e_lab').val();
    if(!tr || !en || !mk){
      $('#editAlert').removeClass('alert-success').addClass('alert-danger').text('<?=addslashes($T['err_required'])?>').show();
      return;
    }
    $.post('add-analysis-type.php?lang=<?=$lang?>&db=<?=$selectedDb?>',
      {ajax:1, action:'update', id:id, name_tr:tr, name_en:en, name_mk:mk, lab_id:lab},
      function(res){ if(res.success){ location.reload(); } else { $('#editAlert').removeClass('alert-success').addClass('alert-danger').text(res.message||'Hata').show(); } },
      'json'
    ).fail(function(xhr){ $('#editAlert').removeClass('alert-success').addClass('alert-danger').text(xhr.responseText||'Hata').show(); });
  });

  // Sil
  $('.del-btn').on('click', function(){
    if(!confirm('<?=addslashes($T['confirm_delete'])?>')) return;
    var id = $(this).data('id');
    $.post('add-analysis-type.php?lang=<?=$lang?>&db=<?=$selectedDb?>',
      {ajax:1, action:'delete', id:id},
      function(res){ if(res.success){ location.reload(); } else { alert(res.message||'Hata'); } },
      'json'
    ).fail(function(xhr){ alert(xhr.responseText||'Hata'); });
  });
});
</script>
</body>
</html>