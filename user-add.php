<?php
/* =========================================
   1. AYARLAR VE GÜVENLİK
   ========================================= */
require_once "header.php";
require_once "sidebar.php";

// Yetki Kontrolü
if (!isset($_SESSION['user_id'])) { exit("Erişim reddedildi."); }

/* =========================================
   2. DİL VE ÇEVİRİLER
   ========================================= */
$all_page_texts = [
    'tr' => [
        'title' => 'Kullanıcı Yönetimi', 'add_new' => 'Yeni Kullanıcı Ekle',
        'list' => 'Mevcut Kullanıcılar', 'username' => 'Kullanıcı Adı',
        'password' => 'Şifre', 'facility' => 'Tesis',
        'ui_lang' => 'Arayüz Dili', 'save' => 'Kaydet',
        'edit' => 'Düzenle', 'delete' => 'Sil', 'cancel' => 'İptal',
        'ph_pwd' => 'Boş bırakılırsa değişmez', 'select_fac' => 'Tesis Seçin'
    ],
    'en' => [
        'title' => 'User Management', 'add_new' => 'Add New User',
        'list' => 'Existing Users', 'username' => 'Username',
        'password' => 'Password', 'facility' => 'Facility',
        'ui_lang' => 'Interface Language', 'save' => 'Save',
        'edit' => 'Edit', 'delete' => 'Delete', 'cancel' => 'Cancel',
        'ph_pwd' => 'Leave empty to keep', 'select_fac' => 'Select Facility'
    ],
    'mk' => [
        'title' => 'Управување со корисници', 'add_new' => 'Додај нов корисник',
        'list' => 'Постоечки корисници', 'username' => 'Корисничко име',
        'password' => 'Лозинка', 'facility' => 'Објект',
        'ui_lang' => 'Јазик на интерфејс', 'save' => 'Зачувај',
        'edit' => 'Уреди', 'delete' => 'Избриши', 'cancel' => 'Откажи',
        'ph_pwd' => 'Оставете празно за исто', 'select_fac' => 'Изберете објект'
    ]
];
$pt = $all_page_texts[$lang] ?? $all_page_texts['tr'];

/* =========================================
   3. VERİTABANI İŞLEMLERİ
   ========================================= */
$facilities = $pdo->query("SELECT id, name FROM facilities ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

$users = $pdo->query("
    SELECT u.id, u.username, u.created_at, u.ui_lang, u.facility_id, f.name AS facility_name 
    FROM users u 
    LEFT JOIN facilities f ON u.facility_id = f.id 
    ORDER BY u.id DESC
")->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'create') {
    $u = trim($_POST['username']);
    $p = $_POST['password'];
    $f = !empty($_POST['facility_id']) ? $_POST['facility_id'] : null;
    $l = $_POST['ui_lang'] ?? 'tr';

    if ($u && $p) {
        $hash = password_hash($p, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, password, facility_id, ui_lang, created_at) VALUES (?, ?, ?, ?, NOW())");
        if ($stmt->execute([$u, $hash, $f, $l])) {
             echo "<script>window.location.href = 'user-add.php?lang=".$lang."&db=".$selectedDb."';</script>";
             exit;
        }
    }
}
?>

<div id="content" class="ui-content">
    <div class="ui-content-body">
        <div class="ui-container">
            
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title"><?= $pt['add_new'] ?></h3>
                </div>
                <div class="panel-body">
                    <form method="post">
                        <input type="hidden" name="action" value="create">
                        <div class="row">
                            <div class="col-md-3 form-group">
                                <label><?= $pt['username'] ?></label>
                                <input type="text" name="username" class="form-control" required>
                            </div>
                            <div class="col-md-3 form-group">
                                <label><?= $pt['password'] ?></label>
                                <input type="password" name="password" class="form-control" required>
                            </div>
                            <div class="col-md-3 form-group">
                                <label><?= $pt['facility'] ?></label>
                                <select name="facility_id" class="form-control">
                                    <option value=""><?= $pt['select_fac'] ?></option>
                                    <?php foreach ($facilities as $fac): ?>
                                        <option value="<?= $fac['id'] ?>"><?= htmlspecialchars($fac['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3 form-group">
                                <label class="text-primary"><?= $pt['ui_lang'] ?></label>
                                <select name="ui_lang" class="form-control">
                                    <option value="tr">Türkçe</option>
                                    <option value="en">English</option>
                                    <option value="mk">Македонски</option>
                                </select>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-success"><i class="fa fa-plus"></i> <?= $pt['save'] ?></button>
                    </form>
                </div>
            </div>

            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title"><?= $pt['list'] ?></h3>
                </div>
                <div class="panel-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th width="50">ID</th>
                                    <th><?= $pt['username'] ?></th>
                                    <th><?= $pt['facility'] ?></th>
                                    <th>Dil</th>
                                    <th width="150" class="text-right"><?= $pt['edit'] ?> / <?= $pt['delete'] ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $row): ?>
                                <tr>
                                    <td><?= $row['id'] ?></td>
                                    <td><?= htmlspecialchars($row['username']) ?></td>
                                    <td><?= htmlspecialchars($row['facility_name'] ?? '-') ?></td>
                                    <td><span class="label label-info"><?= strtoupper($row['ui_lang'] ?? 'TR') ?></span></td>
                                    <td class="text-right">
                                        <button class="btn btn-xs btn-primary btn-edit" type="button"
                                                data-id="<?= $row['id'] ?>"
                                                data-user="<?= htmlspecialchars($row['username']) ?>"
                                                data-fac="<?= $row['facility_id'] ?>"
                                                data-lang="<?= $row['ui_lang'] ?>">
                                            <i class="fa fa-edit"></i>
                                        </button>
                                        <button class="btn btn-xs btn-danger btn-delete" type="button" data-id="<?= $row['id'] ?>">
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
        </div>
    </div>
</div>

<div class="modal fade" id="modalEdit" tabindex="-1" role="dialog" style="z-index: 99999;">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
        <h4 class="modal-title"><?= $pt['edit'] ?></h4>
      </div>
      <div class="modal-body">
         <form id="formEdit">
             <input type="hidden" id="e_id">
             <div class="form-group">
                 <label><?= $pt['username'] ?></label>
                 <input type="text" id="e_user" class="form-control" required>
             </div>
             <div class="form-group">
                 <label><?= $pt['password'] ?> <small class="text-muted">(<?= $pt['ph_pwd'] ?>)</small></label>
                 <input type="password" id="e_pass" class="form-control">
             </div>
             <div class="form-group">
                 <label><?= $pt['facility'] ?></label>
                 <select id="e_fac" class="form-control">
                     <option value=""><?= $pt['select_fac'] ?></option>
                     <?php foreach ($facilities as $fac): ?>
                        <option value="<?= $fac['id'] ?>"><?= htmlspecialchars($fac['name']) ?></option>
                     <?php endforeach; ?>
                 </select>
             </div>
             <div class="form-group">
                 <label><?= $pt['ui_lang'] ?></label>
                 <select id="e_lang" class="form-control">
                    <option value="tr">Türkçe</option>
                    <option value="en">English</option>
                    <option value="mk">Македонски</option>
                 </select>
             </div>
         </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal"><?= $pt['cancel'] ?></button>
        <button type="button" class="btn btn-primary" id="btnSaveEdit"><?= $pt['save'] ?></button>
      </div>
    </div>
  </div>
</div>

<?php 
// 1. ADIMDA OLUŞTURDUĞUMUZ FOOTER'I ÇAĞIRIYORUZ
require_once "footer.php"; 
?>

<script>
$(document).ready(function() {
    
    // Düzenle Butonu
    $(document).on('click', '.btn-edit', function() {
        console.log("Düzenle tıklandı!"); 
        var btn = $(this);
        $('#e_id').val(btn.data('id'));
        $('#e_user').val(btn.data('user'));
        $('#e_pass').val('');
        $('#e_fac').val(btn.data('fac'));
        $('#e_lang').val(btn.data('lang'));
        $('#modalEdit').modal('show');
    });

    // Kaydet (AJAX)
    $('#btnSaveEdit').click(function() {
        var data = {
            user_id: $('#e_id').val(),
            username: $('#e_user').val(),
            password: $('#e_pass').val(),
            facility_id: $('#e_fac').val(),
            ui_lang: $('#e_lang').val()
        };
        $.post('user-update.php', data, function(res) {
            location.reload(); 
        }, 'json').fail(function(xhr) {
            console.log("Hata: " + xhr.responseText);
            alert("İşlem hatası! Veritabanı veya yol sorunu.");
        });
    });

    // Silme
    $(document).on('click', '.btn-delete', function() {
        if(confirm('Emin misiniz?')) {
            var id = $(this).data('id');
            $.post('user-delete.php', {user_id: id}, function(res) {
                location.reload();
            }, 'json').fail(function() {
                alert("Silme hatası!");
            });
        }
    });

});
</script>