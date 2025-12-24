<?php
require_once "db.php";
session_start();

/* === Dil / DB seçimleri === */
$lang = $_GET['lang'] ?? 'tr';
$selectedDb = $_GET['db'] ?? '2025';
$languages = ['tr' => 'Türkçe', 'en' => 'English', 'mk' => 'Македонски'];

/* === Ortak metinler === */
require_once "language_analiz.php"; // $texts
$current_texts = $texts[$lang] ?? $texts['tr'];

/* === Sayfaya özel i18n === */
$page_i18n = [
  'tr' => [
    'page_title' => 'Firma Kayıt',
    'banner'     => 'FİRMA KAYIT',
    'search'     => 'Arama',
    'add_new'    => 'Yeni Firma Ekle',
    'current'    => 'Mevcut Firmalar',
    'no'         => 'No',
    'name'       => 'Firma Adı',
    'inv_addr'   => 'Firma Fatura Adresi',
    'tax_no'     => 'Firma Vergi Numarası',
    'c_name'     => 'İrtibat Kişi İsim',
    'c_role'     => 'İrtibat Kişi Görev',
    'c_phone'    => 'İrtibat Kişi Telefon',
    'c_email'    => 'İrtibat Kişi E-Posta',
    'lang'       => 'Kullanım Dili',
    'actions'    => 'Güncelleme / Silme',
    'save'       => 'Kaydet',
    'edit'       => 'Düzenle',
    'delete'     => 'Sil',
    'cancel'     => 'İptal',
    'success_add'=> 'Firma eklendi.',
    'success_upd'=> 'Firma güncellendi.',
    'success_del'=> 'Firma silindi.',
    'confirm_del'=> 'Bu firmayı silmek istiyor musunuz?',
    'err_required'=> 'Lütfen en az Firma Adı alanını doldurun.'
  ],
  'en' => [
    'page_title' => 'Facility Register',
    'banner'     => 'FACILITY REGISTER',
    'search'     => 'Search',
    'add_new'    => 'Add New Facility',
    'current'    => 'Existing Facilities',
    'no'         => 'No',
    'name'       => 'Facility Name',
    'inv_addr'   => 'Invoice Address',
    'tax_no'     => 'Tax Number',
    'c_name'     => 'Contact Name',
    'c_role'     => 'Contact Role',
    'c_phone'    => 'Contact Phone',
    'c_email'    => 'Contact E-mail',
    'lang'       => 'Interface Language',
    'actions'    => 'Edit / Delete',
    'save'       => 'Save',
    'edit'       => 'Edit',
    'delete'     => 'Delete',
    'cancel'     => 'Cancel',
    'success_add'=> 'Facility added.',
    'success_upd'=> 'Facility updated.',
    'success_del'=> 'Facility deleted.',
    'confirm_del'=> 'Delete this facility?',
    'err_required'=> 'Please fill at least Facility Name.'
  ],
  'mk' => [
    'page_title' => 'Регистар на фирми',
    'banner'     => 'РЕГИСТАР НА ФИРМИ',
    'search'     => 'Барај',
    'add_new'    => 'Додади нова фирма',
    'current'    => 'Постоечки фирми',
    'no'         => 'Бр',
    'name'       => 'Име на фирма',
    'inv_addr'   => 'Адреса за фактура',
    'tax_no'     => 'Даночен број',
    'c_name'     => 'Контакт име',
    'c_role'     => 'Контакт функција',
    'c_phone'    => 'Контакт телефон',
    'c_email'    => 'Контакт е-пошта',
    'lang'       => 'Јазик',
    'actions'    => 'Уреди / Избриши',
    'save'       => 'Зачувај',
    'edit'       => 'Уреди',
    'delete'     => 'Избриши',
    'cancel'     => 'Откажи',
    'success_add'=> 'Фирмата е додадена.',
    'success_upd'=> 'Фирмата е ажурирана.',
    'success_del'=> 'Фирмата е избришана.',
    'confirm_del'=> 'Да се избрише фирмата?',
    'err_required'=> 'Внесете најмалку име на фирма.'
  ],
];
$T = $page_i18n[$lang] ?? $page_i18n['tr'];

/* === AJAX işlemleri === */
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['ajax'])) {
  header('Content-Type: application/json; charset=utf-8');
  $action = $_POST['action'] ?? '';
  try {
    if ($action==='add') {
      $name = trim($_POST['name'] ?? '');
      if ($name==='') throw new Exception($T['err_required']);
      $stmt = $pdo->prepare("INSERT INTO facilities
        (name, invoice_address, tax_no, contact_name, contact_role, contact_phone, contact_email, lang)
        VALUES (:n,:ia,:tx,:cn,:cr,:cp,:ce,:lg)");
      $stmt->execute([
        ':n'=>$name,
        ':ia'=>trim($_POST['invoice_address'] ?? ''),
        ':tx'=>trim($_POST['tax_no'] ?? ''),
        ':cn'=>trim($_POST['contact_name'] ?? ''),
        ':cr'=>trim($_POST['contact_role'] ?? ''),
        ':cp'=>trim($_POST['contact_phone'] ?? ''),
        ':ce'=>trim($_POST['contact_email'] ?? ''),
        ':lg'=>in_array($_POST['lang'] ?? 'tr', ['tr','en','mk']) ? $_POST['lang'] : 'tr'
      ]);
      echo json_encode(['success'=>true,'message'=>$T['success_add']]); exit;
    }

    if ($action==='update') {
      $id   = (int)($_POST['id'] ?? 0);
      $name = trim($_POST['name'] ?? '');
      if (!$id || $name==='') throw new Exception($T['err_required']);
      $stmt = $pdo->prepare("UPDATE facilities SET
        name=:n, invoice_address=:ia, tax_no=:tx, contact_name=:cn, contact_role=:cr,
        contact_phone=:cp, contact_email=:ce, lang=:lg
        WHERE id=:id");
      $stmt->execute([
        ':n'=>$name,
        ':ia'=>trim($_POST['invoice_address'] ?? ''),
        ':tx'=>trim($_POST['tax_no'] ?? ''),
        ':cn'=>trim($_POST['contact_name'] ?? ''),
        ':cr'=>trim($_POST['contact_role'] ?? ''),
        ':cp'=>trim($_POST['contact_phone'] ?? ''),
        ':ce'=>trim($_POST['contact_email'] ?? ''),
        ':lg'=>in_array($_POST['lang'] ?? 'tr', ['tr','en','mk']) ? $_POST['lang'] : 'tr',
        ':id'=>$id
      ]);
      echo json_encode(['success'=>true,'message'=>$T['success_upd']]); exit;
    }

    if ($action==='delete') {
      $id = (int)($_POST['id'] ?? 0);
      $pdo->prepare("DELETE FROM facilities WHERE id=:id")->execute([':id'=>$id]);
      echo json_encode(['success'=>true,'message'=>$T['success_del']]); exit;
    }

    echo json_encode(['success'=>false,'message'=>'Unknown action']); exit;
  } catch (Exception $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]); exit;
  }
}

/* === Arama & Liste === */
$q = trim($_GET['q'] ?? '');
$params=[]; $where=[];
$sql = "SELECT id,name,invoice_address,tax_no,contact_name,contact_role,contact_phone,contact_email,lang,created_at
        FROM facilities";
if ($q!=='') {
  $where[] = "(name LIKE :q OR contact_name LIKE :q OR tax_no LIKE :q OR contact_email LIKE :q)";
  $params[':q'] = "%{$q}%";
}
if ($where) $sql .= " WHERE ".implode(" AND ",$where);
$sql .= " ORDER BY id DESC";
$st = $pdo->prepare($sql); $st->execute($params);
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
    .table td,.table th{vertical-align:middle;}
    .mt8{margin-top:8px;}
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
          <header class="blk-title"><?=$T['banner']?></header>
          <div class="panel-body">

            <!-- Arama + Ekle -->
            <div class="row">
              <div class="col-sm-6">
                <form method="get" action="facility-add.php" class="mt8">
                  <input type="hidden" name="lang" value="<?=htmlspecialchars($lang)?>">
                  <input type="hidden" name="db" value="<?=htmlspecialchars($selectedDb)?>">
                  <div class="input-group">
                    <input type="text" name="q" class="form-control" value="<?=htmlspecialchars($q)?>" placeholder="<?=$T['search'].'...'?>">
                    <span class="input-group-btn">
                      <button class="btn btn-default"><i class="fa fa-search"></i> <?=$T['search']?></button>
                    </span>
                  </div>
                </form>
              </div>
              <div class="col-sm-6 text-right mt8">
                <button class="btn btn-primary" data-toggle="collapse" data-target="#addBox"><i class="fa fa-plus"></i> <?=$T['add_new']?></button>
              </div>
            </div>

            <!-- Yeni Firma Ekle -->
            <div id="addBox" class="collapse in" style="margin-top:12px;">
              <div class="sub-title"><?=$T['add_new']?></div>
              <form id="addForm">
                <div class="row">
                  <div class="col-sm-4"><label><?=$T['name']?></label><input name="name" class="form-control" required></div>
                  <div class="col-sm-4"><label><?=$T['inv_addr']?></label><input name="invoice_address" class="form-control"></div>
                  <div class="col-sm-4"><label><?=$T['tax_no']?></label><input name="tax_no" class="form-control"></div>
                </div>
                <div class="row mt8">
                  <div class="col-sm-3"><label><?=$T['c_name']?></label><input name="contact_name" class="form-control"></div>
                  <div class="col-sm-3"><label><?=$T['c_role']?></label><input name="contact_role" class="form-control"></div>
                  <div class="col-sm-3"><label><?=$T['c_phone']?></label><input name="contact_phone" class="form-control"></div>
                  <div class="col-sm-2"><label><?=$T['c_email']?></label><input name="contact_email" type="email" class="form-control"></div>
                  <div class="col-sm-1"><label><?=$T['lang']?></label>
                    <select name="lang" class="form-control">
                      <option value="tr">TR</option><option value="en">EN</option><option value="mk">MK</option>
                    </select>
                  </div>
                </div>
                <div class="row mt8">
                  <div class="col-sm-12 text-right">
                    <button type="submit" class="btn btn-success"><i class="fa fa-save"></i> <?=$T['save']?></button>
                  </div>
                </div>
              </form>
              <div id="addAlert" class="alert" style="display:none;margin-top:8px;"></div>
            </div>

            <!-- Mevcut Firmalar -->
            <div class="sub-title" style="margin-top:18px;"><?=$T['current']?></div>
            <div class="table-responsive">
              <table class="table table-bordered table-striped">
                <thead>
                  <tr>
                    <th style="width:70px;"><?=$T['no']?></th>
                    <th><?=$T['name']?></th>
                    <th><?=$T['inv_addr']?></th>
                    <th><?=$T['tax_no']?></th>
                    <th><?=$T['c_name']?></th>
                    <th><?=$T['c_role']?></th>
                    <th><?=$T['c_phone']?></th>
                    <th><?=$T['c_email']?></th>
                    <th><?=$T['lang']?></th>
                    <th style="width:170px;"><?=$T['actions']?></th>
                  </tr>
                </thead>
                <tbody>
                <?php if(!$rows): ?>
                  <tr><td colspan="10" class="text-center text-muted">-</td></tr>
                <?php endif; ?>
                <?php $i=1; foreach($rows as $r): ?>
                  <tr>
                    <td><?=$i++?></td>
                    <td><?=htmlspecialchars($r['name'])?></td>
                    <td><?=htmlspecialchars($r['invoice_address'])?></td>
                    <td><?=htmlspecialchars($r['tax_no'])?></td>
                    <td><?=htmlspecialchars($r['contact_name'])?></td>
                    <td><?=htmlspecialchars($r['contact_role'])?></td>
                    <td><?=htmlspecialchars($r['contact_phone'])?></td>
                    <td><?=htmlspecialchars($r['contact_email'])?></td>
                    <td style="text-transform:uppercase;"><?=htmlspecialchars($r['lang'])?></td>
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
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4 class="modal-title"><?=$T['edit']?></h4>
      </div>
      <div class="modal-body">
        <form id="editForm">
          <input type="hidden" name="id" id="e_id">
          <div class="row">
            <div class="col-sm-4"><label><?=$T['name']?></label><input name="name" id="e_name" class="form-control" required></div>
            <div class="col-sm-4"><label><?=$T['inv_addr']?></label><input name="invoice_address" id="e_inv" class="form-control"></div>
            <div class="col-sm-4"><label><?=$T['tax_no']?></label><input name="tax_no" id="e_tax" class="form-control"></div>
          </div>
          <div class="row mt8">
            <div class="col-sm-3"><label><?=$T['c_name']?></label><input name="contact_name" id="e_cname" class="form-control"></div>
            <div class="col-sm-3"><label><?=$T['c_role']?></label><input name="contact_role" id="e_crole" class="form-control"></div>
            <div class="col-sm-3"><label><?=$T['c_phone']?></label><input name="contact_phone" id="e_cphone" class="form-control"></div>
            <div class="col-sm-2"><label><?=$T['c_email']?></label><input type="email" name="contact_email" id="e_cmail" class="form-control"></div>
            <div class="col-sm-1"><label><?=$T['lang']?></label>
              <select name="lang" id="e_lang" class="form-control">
                <option value="tr">TR</option><option value="en">EN</option><option value="mk">MK</option>
              </select>
            </div>
          </div>
        </form>
        <div id="editAlert" class="alert" style="display:none;margin-top:8px;"></div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-default" data-dismiss="modal"><?=$T['cancel']?></button>
        <button class="btn btn-primary" id="saveEdit"><?=$T['save']?></button>
      </div>
    </div>
  </div>
</div>

<script src="bower_components/jquery/dist/jquery.min.js"></script>
<script src="bower_components/bootstrap/dist/js/bootstrap.min.js"></script>
<script>
$(function(){
  // Ekle
  $('#addForm').on('submit', function(e){
    e.preventDefault();
    var data = $(this).serializeArray();
    data.push({name:'ajax', value:1}, {name:'action', value:'add'});
    $.post('facility-add.php?lang=<?= $lang ?>&db=<?= $selectedDb ?>', data, function(res){
      if(res && res.success){ location.reload(); }
      else { $('#addAlert').removeClass('alert-success').addClass('alert-danger').text(res.message||'Hata').show(); }
    }, 'json').fail(function(xhr){
      $('#addAlert').removeClass('alert-success').addClass('alert-danger').text(xhr.responseText||'Hata').show();
    });
  });

  // Düzenle modalını aç
  $('.edit-btn').on('click', function(){
    var r = $(this).data('row');
    $('#e_id').val(r.id);
    $('#e_name').val(r.name);
    $('#e_inv').val(r.invoice_address);
    $('#e_tax').val(r.tax_no);
    $('#e_cname').val(r.contact_name);
    $('#e_crole').val(r.contact_role);
    $('#e_cphone').val(r.contact_phone);
    $('#e_cmail').val(r.contact_email);
    $('#e_lang').val(r.lang || 'tr');
    $('#editAlert').hide();
    $('#editModal').modal('show');
  });

  // Düzenlemeyi kaydet
  $('#saveEdit').on('click', function(){
    var data = $('#editForm').serializeArray();
    data.push({name:'ajax', value:1}, {name:'action', value:'update'});
    $.post('facility-add.php?lang=<?= $lang ?>&db=<?= $selectedDb ?>', data, function(res){
      if(res && res.success){ location.reload(); }
      else { $('#editAlert').removeClass('alert-success').addClass('alert-danger').text(res.message||'Hata').show(); }
    }, 'json').fail(function(xhr){
      $('#editAlert').removeClass('alert-success').addClass('alert-danger').text(xhr.responseText||'Hata').show();
    });
  });

  // Sil
  $('.del-btn').on('click', function(){
    if(!confirm('<?= addslashes($T['confirm_del']) ?>')) return;
    $.post('facility-add.php?lang=<?= $lang ?>&db=<?= $selectedDb ?>',
      {ajax:1, action:'delete', id:$(this).data('id')},
      function(res){ if(res && res.success){ location.reload(); } else { alert(res.message||'Hata'); } },
      'json'
    ).fail(function(xhr){ alert(xhr.responseText||'Hata'); });
  });
});
</script>
</body>
</html>
