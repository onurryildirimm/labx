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

/* ==== Bu sayfaya özgü çeviriler ==== */
$page_i18n = [
  'tr' => [
    'page_title' => 'Analiz Türü Tanımlama',
    'banner'     => 'ANALİZ TÜRÜ TANIMLAMA',
    'section'    => 'LABORATUVAR TANIMLA',
    'add_block'  => 'ANALİZ LABORATUVARI EKLE',
    'lab_label'  => 'Analiz Laboratuvarı / Analysis Laboratory',
    'placeholder'=> 'Mikrobiyoloji Laboratuvarı',
    'add_btn'    => 'Ekle',
    'list_title' => 'Tanımlı Laboratuvarlar',
    'id'         => 'No',
    'name'       => 'Laboratuvar Adı',
    'actions'    => 'İşlemler',
    'edit'       => 'Düzenle',
    'delete'     => 'Sil',
    'save'       => 'Kaydet',
    'cancel'     => 'İptal',
    'confirm_delete' => 'Bu laboratuvar silinsin mi?',
    'success_add'=> 'Laboratuvar eklendi.',
    'success_upd'=> 'Laboratuvar güncellendi.',
    'success_del'=> 'Laboratuvar silindi.',
    'err_required'=> 'Lütfen laboratuvar adını girin.'
  ],
  'en' => [
    'page_title' => 'Define Analysis Types',
    'banner'     => 'DEFINE ANALYSIS TYPES',
    'section'    => 'DEFINE LABORATORY',
    'add_block'  => 'ADD ANALYSIS LABORATORY',
    'lab_label'  => 'Analysis Laboratory',
    'placeholder'=> 'Microbiology Laboratory',
    'add_btn'    => 'Add',
    'list_title' => 'Defined Laboratories',
    'id'         => 'No',
    'name'       => 'Laboratory Name',
    'actions'    => 'Actions',
    'edit'       => 'Edit',
    'delete'     => 'Delete',
    'save'       => 'Save',
    'cancel'     => 'Cancel',
    'confirm_delete' => 'Delete this laboratory?',
    'success_add'=> 'Laboratory added.',
    'success_upd'=> 'Laboratory updated.',
    'success_del'=> 'Laboratory deleted.',
    'err_required'=> 'Please enter a laboratory name.'
  ],
  'mk' => [
    'page_title' => 'Дефинирање на типови на анализа',
    'banner'     => 'ДЕФИНИРАЊЕ НА ТИП НА АНАЛИЗА',
    'section'    => 'ДЕФИНИРАЈ ЛАБОРАТОРИЈА',
    'add_block'  => 'ДОДАДИ ЛАБОРАТОРИЈА ЗА АНАЛИЗА',
    'lab_label'  => 'Лабораторија за анализа',
    'placeholder'=> 'Микробиолошка лабораторија',
    'add_btn'    => 'Додади',
    'list_title' => 'Дефинирани лаборатории',
    'id'         => 'Бр',
    'name'       => 'Име на лабораторија',
    'actions'    => 'Дејства',
    'edit'       => 'Уреди',
    'delete'     => 'Избриши',
    'save'       => 'Зачувај',
    'cancel'     => 'Откажи',
    'confirm_delete' => 'Да се избрише лабораторијата?',
    'success_add'=> 'Лабораторијата е додадена.',
    'success_upd'=> 'Лабораторијата е ажурирана.',
    'success_del'=> 'Лабораторијата е избришана.',
    'err_required'=> 'Внесете име на лабораторија.'
  ],
];
$T = $page_i18n[$lang] ?? $page_i18n['tr'];

/* ==== AJAX işlemleri (ekle/düzenle/sil) ==== */
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['ajax'])) {
  header('Content-Type: application/json; charset=utf-8');
  $action = $_POST['action'] ?? '';

  try {
    if ($action==='add') {
      $name = trim($_POST['name'] ?? '');
      if ($name==='') throw new Exception($T['err_required']);
      $ins = $pdo->prepare("INSERT INTO laboratories (name) VALUES (:n)");
      $ins->execute([':n'=>$name]);
      echo json_encode(['success'=>true,'message'=>$T['success_add']]); exit;
    }

    if ($action==='update') {
      $id   = (int)($_POST['id'] ?? 0);
      $name = trim($_POST['name'] ?? '');
      if (!$id || $name==='') throw new Exception($T['err_required']);
      $upd = $pdo->prepare("UPDATE laboratories SET name=:n WHERE id=:id");
      $upd->execute([':n'=>$name, ':id'=>$id]);
      echo json_encode(['success'=>true,'message'=>$T['success_upd']]); exit;
    }

    if ($action==='delete') {
      $id = (int)($_POST['id'] ?? 0);
      if (!$id) throw new Exception('Invalid id');
      $del = $pdo->prepare("DELETE FROM laboratories WHERE id=:id");
      $del->execute([':id'=>$id]);
      echo json_encode(['success'=>true,'message'=>$T['success_del']]); exit;
    }

    echo json_encode(['success'=>false,'message'=>'Unknown action']); exit;
  } catch (Exception $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]); exit;
  }
}

/* ==== Liste ==== */
$labs = $pdo->query("SELECT id, name FROM laboratories ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
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
    .thin{padding:6px 8px;}
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
        <a href="#" class="dropdown-toggle" data-toggle="dropdown">
          <i class="fa fa-globe"></i> <?=$languages[$lang]?>
        </a>
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

      <!-- User -->
      <li class="dropdown dropdown-usermenu">
        <a href="#" class="dropdown-toggle" data-toggle="dropdown">
          <div class="user-avatar"><img src="imgs/a0.jpg" alt=""></div>
        </a>
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

            <div class="sub-title"><?=htmlspecialchars($T['section'])?></div>

            <!-- Ekleme Bloğu -->
            <div class="panel" style="border:1px solid #eee;">
              <header class="panel-heading"><?=htmlspecialchars($T['add_block'])?></header>
              <div class="panel-body">
                <form id="addForm" class="form-inline">
                  <div class="form-group" style="min-width:320px;">
                    <label class="sr-only"><?=$T['lab_label']?></label>
                    <input type="text" name="name" class="form-control" style="width:100%;"
                           placeholder="<?=htmlspecialchars($T['placeholder'])?>">
                  </div>
                  <button type="submit" class="btn btn-primary"><i class="fa fa-plus"></i> <?=$T['add_btn']?></button>
                </form>
                <div id="addAlert" class="alert" style="display:none;margin-top:10px;"></div>
              </div>
            </div>

            <!-- Liste -->
            <div class="table-responsive">
              <table class="table table-bordered table-striped">
                <thead>
                  <tr>
                    <th style="width:70px;"><?=$T['id']?></th>
                    <th><?=$T['name']?></th>
                    <th style="width:180px;"><?=$T['actions']?></th>
                  </tr>
                </thead>
                <tbody>
                  <?php if(!$labs): ?>
                    <tr><td colspan="3" class="text-center text-muted">-</td></tr>
                  <?php endif; ?>
                  <?php foreach($labs as $l): ?>
                  <tr>
                    <td class="text-center"><?=$l['id']?></td>
                    <td><?=htmlspecialchars($l['name'])?></td>
                    <td class="text-center">
                      <button class="btn btn-xs btn-primary edit-btn"
                              data-id="<?=$l['id']?>"
                              data-name="<?=htmlspecialchars($l['name'], ENT_QUOTES)?>">
                        <i class="fa fa-edit"></i> <?=$T['edit']?>
                      </button>
                      <button class="btn btn-xs btn-danger del-btn" data-id="<?=$l['id']?>">
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
          <label><?=$T['name']?></label>
          <input type="text" name="name" id="e_name" class="form-control">
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
    var name = $.trim($(this).find('[name="name"]').val());
    if(!name){ $('#addAlert').removeClass('alert-success').addClass('alert-danger').text('<?=addslashes($T['err_required'])?>').show(); return; }
    $.post('add-analysis-lab.php?lang=<?=$lang?>&db=<?=$selectedDb?>',
      {ajax:1, action:'add', name:name},
      function(res){ if(res.success){ location.reload(); } else { $('#addAlert').removeClass('alert-success').addClass('alert-danger').text(res.message||'Hata').show(); } },
      'json'
    ).fail(function(xhr){ $('#addAlert').removeClass('alert-success').addClass('alert-danger').text(xhr.responseText||'Hata').show(); });
  });

  // Düzenle modal aç
  $('.edit-btn').on('click', function(){
    $('#e_id').val($(this).data('id'));
    $('#e_name').val($(this).data('name'));
    $('#editAlert').hide();
    $('#editModal').modal('show');
  });

  // Düzenlemeyi kaydet
  $('#saveEdit').on('click', function(){
    var id = $('#e_id').val(), name = $.trim($('#e_name').val());
    if(!name){ $('#editAlert').removeClass('alert-success').addClass('alert-danger').text('<?=addslashes($T['err_required'])?>').show(); return; }
    $.post('add-analysis-lab.php?lang=<?=$lang?>&db=<?=$selectedDb?>',
      {ajax:1, action:'update', id:id, name:name},
      function(res){ if(res.success){ location.reload(); } else { $('#editAlert').removeClass('alert-success').addClass('alert-danger').text(res.message||'Hata').show(); } },
      'json'
    ).fail(function(xhr){ $('#editAlert').removeClass('alert-success').addClass('alert-danger').text(xhr.responseText||'Hata').show(); });
  });

  // Sil
  $('.del-btn').on('click', function(){
    if(!confirm('<?=addslashes($T['confirm_delete'])?>')) return;
    var id = $(this).data('id');
    $.post('add-analysis-lab.php?lang=<?=$lang?>&db=<?=$selectedDb?>',
      {ajax:1, action:'delete', id:id},
      function(res){ if(res.success){ location.reload(); } else { alert(res.message||'Hata'); } },
      'json'
    ).fail(function(xhr){ alert(xhr.responseText||'Hata'); });
  });
});
</script>
</body>
</html>
