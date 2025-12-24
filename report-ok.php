<?php
require_once "db.php";
if (session_status() === PHP_SESSION_NONE) { session_start(); }

/* ==== Dil & üst menü ==== */
$lang = $_GET['lang'] ?? 'tr';
$selectedDb = $_GET['db'] ?? '2025';
$languages = ['tr'=>'Türkçe','en'=>'English','mk'=>'Македонски'];
require_once "language_analiz.php";
$current_texts = $texts[$lang] ?? $texts['tr'];
$Lx = $texts[$lang] ?? $texts['tr'];

/* ==== Bu sayfaya özel metinler ==== */
$I = [
 'tr'=>[
   'title'=>'Rapor Sonuç Onay',
   'banner'=>'RAPOR SONUÇ ONAY',
   'top_note'=>'Rapor onayla ve yayınla seçilmeyen satırlar kırmızı görüntülenir.',
   'search'=>'Ara',
   'pick_code'=>'Numune Kodu Seç',
   'sample_code'=>'Numune Kodu',
   'sample_name'=>'Numune Adı',
   'atype'=>'Analiz Türü',
   'asubtype'=>'Analiz Alt Türü',
   'rdate'=>'Rapor Tarihi',
   'rno'=>'Rapor No',
   'actions'=>'Güncelle/Rapor Onayla ve Yayınla',
   'save'=>'Kaydet',
   'approve_publish'=>'Raporu Onayla & Yayınla',
   'pdf'=>'PDF',
   'saved'=>'Kaydedildi.',
   'approved'=>'Rapor onaylandı & yayınlandı.',
   'confirm_pub'=>'Bu raporu onaylayıp yayınlamak istiyor musunuz?',
   'none'=>'-'
 ],
 'en'=>[
   'title'=>'Report Approval',
   'banner'=>'REPORT APPROVAL',
   'top_note'=>'Rows not approved/published are highlighted in red.',
   'search'=>'Search',
   'pick_code'=>'Pick Sample Code',
   'sample_code'=>'Sample Code',
   'sample_name'=>'Sample Name',
   'atype'=>'Analysis Type',
   'asubtype'=>'Subtype',
   'rdate'=>'Report Date',
   'rno'=>'Report No',
   'actions'=>'Update / Approve & Publish',
   'save'=>'Save',
   'approve_publish'=>'Approve & Publish',
   'pdf'=>'PDF',
   'saved'=>'Saved.',
   'approved'=>'Approved & published.',
   'confirm_pub'=>'Approve & publish this report?',
   'none'=>'-'
 ],
 'mk'=>[
   'title'=>'Одобрување на извештај',
   'banner'=>'ОДОБРУВАЊЕ НА ИЗВЕШТАЈ',
   'top_note'=>'Неодобрени редови се означени со црвено.',
   'search'=>'Барај',
   'pick_code'=>'Изберете код',
   'sample_code'=>'Код',
   'sample_name'=>'Име на примерок',
   'atype'=>'Тип анализа',
   'asubtype'=>'Подтип',
   'rdate'=>'Датум на извештај',
   'rno'=>'Број на извештај',
   'actions'=>'Ажурирај / Одобри и објави',
   'save'=>'Зачувај',
   'approve_publish'=>'Одобри и објави',
   'pdf'=>'PDF',
   'saved'=>'Зачувано.',
   'approved'=>'Одобрено и објавено.',
   'confirm_pub'=>'Да се одобри и објави извештајот?',
   'none'=>'-'
 ]
][$lang];

/* ==== Yardımcılar ==== */
function smp_cols(PDO $pdo): array {
  $cols = $pdo->query("SHOW COLUMNS FROM samples")->fetchAll(PDO::FETCH_COLUMN);
  $pick = function($arr) use($cols){ foreach($arr as $c){ if(in_array($c,$cols,true)) return $c; } return null; };
  return [
    'id'      => 'id',
    'code'    => $pick(['sample_code','code','numune_kodu']),
    'type'    => $pick(['type_id','analysis_type_id','analiz_turu_id']),
    'subtype' => $pick(['subtype_id','analysis_subtype_id','analiz_alt_turu_id']),
    'final'   => $pick(['is_finalized','finalized']),
    'rdate'   => $pick(['report_date','rapor_tarihi']),
    'rno'     => $pick(['report_no','rapor_no']),
  ];
}
$S = smp_cols($pdo);

function nm($r,$lang){ return $lang==='en'?$r['name_en'] : ($lang==='mk'?$r['name_mk'] : $r['name_tr']); }

/** sample_reports tablosu yoksa oluştur */
function ensure_sample_reports(PDO $pdo){
  static $done = false; if ($done) return;
  $pdo->exec("
    CREATE TABLE IF NOT EXISTS sample_reports (
      id INT AUTO_INCREMENT PRIMARY KEY,
      sample_id INT NOT NULL UNIQUE,
      report_date DATE DEFAULT NULL,
      report_no VARCHAR(64) DEFAULT NULL,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      KEY idx_report_date (report_date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
  ");
  $done = true;
}

/* ==== Sözlükler ==== */
$types = $pdo->query("SELECT id,name_tr,name_en,name_mk FROM analysis_types ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
$subs  = $pdo->query("SELECT id,type_id,name_tr,name_en,name_mk FROM analysis_subtypes ORDER BY type_id,id")->fetchAll(PDO::FETCH_ASSOC);
$mapTypes=[]; foreach($types as $t){ $mapTypes[$t['id']] = nm($t,$lang); }
$mapSubs =[]; foreach($subs  as $s){ $mapSubs[$s['id']] = nm($s,$lang); }

/* ==== AJAX ==== */
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['ajax'])) {
  // JSON dışı çıktıları temizle
  while (ob_get_level()) { ob_end_clean(); }
  header('Content-Type: application/json; charset=utf-8');

  $action = $_POST['action'] ?? '';
  try {
    if ($action==='save') {
      $sid   = (int)($_POST['sample_id'] ?? 0);
      $rdate = trim($_POST['report_date'] ?? '');
      $rno   = trim($_POST['report_no'] ?? '');

      if (!$sid) throw new Exception('Geçersiz kayıt');

      $rdateSql = $rdate ? date('Y-m-d', strtotime($rdate)) : null;

      if ($S['rdate'] || $S['rno']) {
        $sets=[]; $bind=[':id'=>$sid];
        if ($S['rdate']) { $sets[]="`{$S['rdate']}`=:d"; $bind[':d']=$rdateSql; }
        if ($S['rno'])   { $sets[]="`{$S['rno']}`=:n"; $bind[':n']=($rno!==''?$rno:null); }
        if ($sets){
          $sql = "UPDATE samples SET ".implode(',',$sets)." WHERE id=:id";
          $pdo->prepare($sql)->execute($bind);
        }
      } else {
        ensure_sample_reports($pdo);
        $pdo->prepare("
          INSERT INTO sample_reports (sample_id, report_date, report_no)
          VALUES (:id,:d,:n)
          ON DUPLICATE KEY UPDATE report_date=VALUES(report_date), report_no=VALUES(report_no)
        ")->execute([':id'=>$sid, ':d'=>$rdateSql, ':n'=>($rno!==''?$rno:null)]);
      }

      echo json_encode(['success'=>true,'message'=>$I['saved']]); exit;
    }

    if ($action==='publish') {
      $sid = (int)($_POST['sample_id'] ?? 0);
      if (!$sid) throw new Exception('Geçersiz kayıt');

      $today = date('Y-m-d');

      // Aynı güne ait sıra numarası bul
      if ($S['rdate']) {
        $st=$pdo->prepare("SELECT COUNT(*) FROM samples WHERE `{$S['rdate']}`=:d");
        $st->execute([':d'=>$today]);
        $cnt=(int)$st->fetchColumn();
      } else {
        ensure_sample_reports($pdo);
        $st=$pdo->prepare("SELECT COUNT(*) FROM sample_reports WHERE report_date=:d");
        $st->execute([':d'=>$today]);
        $cnt=(int)$st->fetchColumn();
      }
      $seq = $cnt + 1;
      $rno = date('Y/m/d').'-'.str_pad((string)$seq,4,'0',STR_PAD_LEFT);

      if ($S['rdate'] || $S['rno']) {
        $sets=[]; $bind=[':id'=>$sid];
        if ($S['rdate']) { $sets[]="`{$S['rdate']}`=:d"; $bind[':d']=$today; }
        if ($S['rno'])   { $sets[]="`{$S['rno']}`=:n"; $bind[':n']=$rno; }
        if ($sets){
          $sql = "UPDATE samples SET ".implode(',',$sets)." WHERE id=:id";
          $pdo->prepare($sql)->execute($bind);
        }
      } else {
        ensure_sample_reports($pdo);
        $pdo->prepare("
          INSERT INTO sample_reports (sample_id, report_date, report_no)
          VALUES (:id,:d,:n)
          ON DUPLICATE KEY UPDATE report_date=VALUES(report_date), report_no=VALUES(report_no)
        ")->execute([':id'=>$sid, ':d'=>$today, ':n'=>$rno]);
      }

      echo json_encode(['success'=>true,'message'=>$I['approved'],'report_no'=>$rno,'report_date'=>$today]); exit;
    }

    echo json_encode(['success'=>false,'message'=>'Unknown action']); exit;

  } catch(Exception $e){
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]); exit;
  }
}

/* ==== Liste ==== */
$q = trim($_GET['q'] ?? '');
$where=[]; $pr=[];

$codeCol = $S['code'] ?: 'sample_code';
$finalCol= $S['final']?: 'is_finalized';
$rdateCol= $S['rdate']; // olabilir null
$rnoCol  = $S['rno'];   // olabilir null

// sample_reports alt sorguda kullanılacaksa tabloyu hazır et
if (!$rdateCol || !$rnoCol) {
  ensure_sample_reports($pdo);
}

$sql = "SELECT s.id sid, s.`$codeCol` scode, s.`$finalCol` sfin,
               s.`".($S['type']??'type_id')."` stype,
               s.`".($S['subtype']??'subtype_id')."` ssub";

if ($rdateCol) $sql.=", s.`$rdateCol` rdate";
else $sql.=", (SELECT sr.report_date FROM sample_reports sr WHERE sr.sample_id=s.id LIMIT 1) rdate";

if ($rnoCol) $sql.=", s.`$rnoCol` rno";
else $sql.=", (SELECT sr.report_no FROM sample_reports sr WHERE sr.sample_id=s.id LIMIT 1) rno";

$sql .= ",
        (SELECT si.sample_name FROM sample_items si WHERE si.sample_id=s.id ORDER BY si.item_no ASC LIMIT 1) sname
        FROM samples s
        WHERE s.`$finalCol`=1";

if ($q!==''){
  $sql.=" AND (s.`$codeCol` LIKE :q OR EXISTS(SELECT 1 FROM sample_items si2 WHERE si2.sample_id=s.id AND si2.sample_name LIKE :q))";
  $pr[':q']="%{$q}%";
}

$sql.=" ORDER BY s.id DESC";

$st=$pdo->prepare($sql);
$st->execute($pr);
$rows=$st->fetchAll(PDO::FETCH_ASSOC);

function label($id,$map){ return $id && isset($map[$id]) ? $map[$id] : ''; }
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
 .muted{color:#777}
 .table td,.table th{vertical-align:middle;}
 .danger-row{background:#ffecec;}
 .sticky-top{position:sticky;top:0;background:#fff;z-index:2}
 .thin{padding:4px 6px;height:28px;}
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
        <?php foreach($languages as $k=>$v): ?><li><a href="?lang=<?=$k?>&db=<?=htmlspecialchars($selectedDb)?>"><?=$v?></a></li><?php endforeach; ?>
      </ul>
    </li>
    <li class="dropdown">
      <a href="#" class="dropdown-toggle" data-toggle="dropdown"><i class="fa fa-database"></i> <?=$Lx['select_db'] ?? 'Veritabanı Seç'?></a>
      <ul class="dropdown-menu">
        <?php foreach(['2023','2024','2025'] as $db): ?><li><a href="?lang=<?=$lang?>&db=<?=$db?>"><?=$db?></a></li><?php endforeach; ?>
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

          <p class="muted"><?=$I['top_note']?></p>

          <!-- Arama -->
          <form class="row" method="get" action="report-ok.php">
            <input type="hidden" name="lang" value="<?=htmlspecialchars($lang)?>">
            <input type="hidden" name="db" value="<?=htmlspecialchars($selectedDb)?>">
            <div class="col-sm-6">
              <div class="input-group">
                <input class="form-control" name="q" value="<?=htmlspecialchars($q)?>" placeholder="<?=$I['search'].'...'?>">
                <span class="input-group-btn"><button class="btn btn-default"><i class="fa fa-search"></i> <?=$I['search']?></button></span>
              </div>
            </div>
          </form>

          <div class="table-responsive" style="margin-top:10px;">
            <table class="table table-bordered table-striped">
              <thead>
                <tr class="sticky-top">
                  <th><?=$I['sample_code']?></th>
                  <th><?=$I['sample_name']?></th>
                  <th><?=$I['atype']?></th>
                  <th><?=$I['asubtype']?></th>
                  <th><?=$I['rdate']?></th>
                  <th><?=$I['rno']?></th>
                  <th style="width:260px;"><?=$I['actions']?></th>
                </tr>
              </thead>
              <tbody>
              <?php if(!$rows): ?>
                <tr><td colspan="7" class="text-center text-muted"><?=$I['none']?></td></tr>
              <?php endif; ?>
              <?php foreach($rows as $r):
                $tname = label($r['stype']??null,$mapTypes);
                $sname = label($r['ssub']??null,$mapSubs);
                $missing = empty($r['rno']) || empty($r['rdate']);
              ?>
                <tr class="<?=$missing?'danger-row':''?>">
                  <td><strong><?=htmlspecialchars($r['scode'])?></strong></td>
                  <td><?=htmlspecialchars($r['sname'] ?: '')?></td>
                  <td><?=htmlspecialchars($tname)?></td>
                  <td><?=htmlspecialchars($sname)?></td>
                  <td style="width:150px;">
                    <input type="date" class="form-control thin rdate" value="<?= htmlspecialchars($r['rdate'] ?: '') ?>" data-id="<?=$r['sid']?>">
                  </td>
                  <td style="width:190px;">
                    <input type="text" class="form-control thin rno" value="<?= htmlspecialchars($r['rno'] ?: '') ?>" placeholder="YYYY/MM/DD-0001" data-id="<?=$r['sid']?>">
                  </td>
                  <td class="text-center">
                    <button class="btn btn-xs btn-primary btn-save" data-id="<?=$r['sid']?>"><i class="fa fa-floppy-o"></i> <?=$I['save']?></button>
                    <button class="btn btn-xs btn-success btn-publish" data-id="<?=$r['sid']?>"><i class="fa fa-check"></i> <?=$I['approve_publish']?></button>
                    <a class="btn btn-xs btn-default" target="_blank" href="report-sample.php?id=<?=$r['sid']?>&lang=<?=$lang?>"><i class="fa fa-file-pdf-o"></i> <?=$I['pdf']?></a>
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

<footer id="footer" class="ui-footer"><?=$Lx['footer'] ?? '2025 &copy; Labx by Vektraweb.'?></footer>
</div>

<script src="bower_components/jquery/dist/jquery.min.js"></script>
<script src="bower_components/bootstrap/dist/js/bootstrap.min.js"></script>
<script>
$(function(){
  function rowData(id){
    return {
      report_date: $('input.rdate[data-id="'+id+'"]').val(),
      report_no:   $('input.rno[data-id="'+id+'"]').val()
    };
  }

  $('.btn-save').on('click', function(){
    var id = $(this).data('id');
    var d = rowData(id);
    $.ajax({
      url: 'report-ok.php?lang=<?= $lang ?>&db=<?= $selectedDb ?>',
      method: 'POST',
      dataType: 'json',
      data: {ajax:1, action:'save', sample_id:id, report_date:d.report_date, report_no:d.report_no}
    }).done(function(res){
      if(res && res.success){ location.reload(); }
      else { alert((res && res.message) || 'Hata'); }
    }).fail(function(xhr){
      console.error(xhr.responseText);
      alert('Sunucudan beklenmeyen cevap alındı.');
    });
  });

  $('.btn-publish').on('click', function(){
    if(!confirm('<?= addslashes($I['confirm_pub']) ?>')) return;
    var id = $(this).data('id');
    $.ajax({
      url: 'report-ok.php?lang=<?= $lang ?>&db=<?= $selectedDb ?>',
      method: 'POST',
      dataType: 'json',
      data: {ajax:1, action:'publish', sample_id:id}
    }).done(function(res){
      if(res && res.success){
        if(res.report_date){ $('input.rdate[data-id="'+id+'"]').val(res.report_date); }
        if(res.report_no){   $('input.rno[data-id="'+id+'"]').val(res.report_no); }
        location.reload();
      } else { alert((res && res.message) || 'Hata'); }
    }).fail(function(xhr){
      console.error(xhr.responseText);
      alert('Sunucudan beklenmeyen cevap alındı.');
    });
  });
});
</script>
</body>
</html>
