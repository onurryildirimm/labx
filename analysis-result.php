<?php
require_once "db.php";
if (session_status() === PHP_SESSION_NONE) { session_start(); }

/* ==== Dil & menüler ==== */
$lang = $_GET['lang'] ?? 'tr';
$selectedDb = $_GET['db'] ?? '2025';
$languages = ['tr'=>'Türkçe','en'=>'English','mk'=>'Македонски'];
require_once "language_analiz.php";
$current_texts = $texts[$lang] ?? $texts['tr'];

/* ==== i18n ==== */
$I = [
 'tr'=>['title'=>'Analiz Sonuç','banner'=>'ANALİZ SONUÇ','sample_code'=>'Numune Kodu','atype'=>'Analiz Türü','asubtype'=>'Analiz Alt Türü','detail'=>'Analiz Detayı','start'=>'Analiz Başlama Tarihi','end'=>'Analiz Bitiş Tarihi','evaluation'=>'Analiz Değerlendirme','update_finalize'=>'Güncelle/Sonuçlandır ve Raporla','search'=>'Ara','param'=>'Analiz Parametre','rl'=>'Raporlama Limiti','result'=>'Sonuç','unit'=>'Birim','limit'=>'Sınır Değer','save'=>'Kaydet','finalize_report'=>'Sonuçlandır & Raporla','not_finalized_note'=>'Raporla seçilmeyen satır kırmızı görünür.','success_save'=>'Kaydedildi.','success_fin'=>'Sonuçlandırıldı.','confirm_fin'=>'Bu numuneyi sonuçlandırıp rapora geçmek istiyor musunuz?','report'=>'Rapor'],
 'en'=>['title'=>'Analysis Result','banner'=>'ANALYSIS RESULT','sample_code'=>'Sample Code','atype'=>'Analysis Type','asubtype'=>'Sub Type','detail'=>'Analysis Detail','start'=>'Start Date','end'=>'End Date','evaluation'=>'Evaluation','update_finalize'=>'Update / Finalize & Report','search'=>'Search','param'=>'Parameter','rl'=>'Reporting Limit','result'=>'Result','unit'=>'Unit','limit'=>'Limit Value','save'=>'Save','finalize_report'=>'Finalize & Report','not_finalized_note'=>'Rows not finalized appear in red.','success_save'=>'Saved.','success_fin'=>'Finalized.','confirm_fin'=>'Finalize this sample and proceed to report?','report'=>'Report'],
 'mk'=>['title'=>'Резултат на анализа','banner'=>'РЕЗУЛТАТ НА АНАЛИЗА','sample_code'=>'Код на примерок','atype'=>'Тип анализа','asubtype'=>'Подтип','detail'=>'Детал на анализа','start'=>'Почетен датум','end'=>'Краен датум','evaluation'=>'Евалуација','update_finalize'=>'Ажурирај / Финализирај и извештај','search'=>'Барај','param'=>'Параметар','rl'=>'Лимит за известување','result'=>'Резултат','unit'=>'Единица','limit'=>'Гранична вредност','save'=>'Зачувај','finalize_report'=>'Финализирај и извештај','not_finalized_note'=>'Нефинализираните редови се црвени.','success_save'=>'Зачувано.','success_fin'=>'Финализирано.','confirm_fin'=>'Да се финализира и да се премине на извештај?','report'=>'Извештај'],
][$lang];

/* ==== Sözlükler ==== */
$types    = $pdo->query("SELECT id,name_tr,name_en,name_mk FROM analysis_types ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
$subtypes = $pdo->query("SELECT id,type_id,name_tr,name_en,name_mk FROM analysis_subtypes ORDER BY type_id,id")->fetchAll(PDO::FETCH_ASSOC);
function nm($r,$lang){ return $lang==='en'?$r['name_en'] : ($lang==='mk'?$r['name_mk'] : $r['name_tr']); }

/* ---- samples tablo sütunlarını tespit ---- */
function resolveSampleCols(PDO $pdo): array {
  $cols = $pdo->query("SHOW COLUMNS FROM samples")->fetchAll(PDO::FETCH_COLUMN);
  $pick = function(array $cands) use ($cols) { foreach ($cands as $c) if (in_array($c, $cols, true)) return $c; return null; };
  return [
    'code'    => $pick(['sample_code','code','numune_kodu']) ?: 'sample_code',
    'type'    => $pick(['type_id','analysis_type_id','analiz_turu_id']) ?: 'type_id',
    'subtype' => $pick(['subtype_id','analysis_subtype_id','analiz_alt_turu_id']) ?: 'subtype_id',
    'detail'  => $pick(['detail','analysis_detail']) ?: 'detail',
    'start'   => $pick(['start_date','analysis_start_date']) ?: 'start_date',
    'end'     => $pick(['end_date','analysis_end_date']) ?: 'end_date',
    'eval'    => $pick(['evaluation','analysis_evaluation']) ?: 'evaluation',
    'final'   => $pick(['is_finalized','finalized']) ?: 'is_finalized',
  ];
}
$S = resolveSampleCols($pdo);
$codeCol   = $S['code'];   $typeCol   = $S['type'];   $subtypeCol= $S['subtype'];
$detailCol = $S['detail']; $startCol  = $S['start'];  $endCol    = $S['end'];
$evalCol   = $S['eval'];   $finalCol  = $S['final'];

/* ==== AJAX ==== */
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['ajax'])) {
  header('Content-Type: application/json; charset=utf-8');
  $action = $_POST['action'] ?? '';
  try {
    if ($action==='load_sample') {
      $sid = (int)($_POST['sample_id'] ?? 0);
      $s = $pdo->prepare("SELECT * FROM samples WHERE id=:id");
      $s->execute([':id'=>$sid]);
      $sample = $s->fetch(PDO::FETCH_ASSOC);
      if (!$sample) throw new Exception('Sample not found');

      $stId = (int)($sample[$subtypeCol] ?? 0);
      $params = [];
      if ($stId > 0) {
        $p = $pdo->prepare("
          SELECT ap.id pid, ap.name_tr, ap.name_en, ap.name_mk, ap.unit, ap.reporting_limit, ap.limit_value,
                 sr.result_value
          FROM analysis_parameters ap
          LEFT JOIN sample_results sr
                 ON sr.parameter_id = ap.id AND sr.sample_id = :sid
          WHERE ap.subtype_id = :st
          ORDER BY ap.id
        ");
        $p->execute([':sid'=>$sid, ':st'=>$stId]);
        $params = $p->fetchAll(PDO::FETCH_ASSOC);
      }
      echo json_encode(['success'=>true,'sample'=>$sample,'params'=>$params]); exit;
    }

    if ($action==='save_results') {
      $sid = (int)($_POST['sample_id'] ?? 0);
      $detail = trim($_POST['detail'] ?? '');
      $start  = $_POST['start_date'] ?: null;
      $end    = $_POST['end_date'] ?: null;
      $eval   = trim($_POST['evaluation'] ?? '');

      $pdo->prepare("UPDATE samples SET `$detailCol`=:d, `$startCol`=:s, `$endCol`=:e, `$evalCol`=:ev WHERE id=:id")
          ->execute([':d'=>$detail, ':s'=>$start, ':e'=>$end, ':ev'=>$eval, ':id'=>$sid]);

      $rows = $_POST['rows'] ?? [];
      if (is_array($rows) && $rows) {
        $meta = $pdo->prepare("SELECT id, unit, reporting_limit, limit_value FROM analysis_parameters WHERE id=:id");
        $ins  = $pdo->prepare("
          INSERT INTO sample_results (sample_id, parameter_id, result_value, unit, reporting_limit, limit_value)
          VALUES (:sid,:pid,:val,:u,:rl,:lv)
          ON DUPLICATE KEY UPDATE
            result_value = VALUES(result_value),
            unit = VALUES(unit),
            reporting_limit = VALUES(reporting_limit),
            limit_value = VALUES(limit_value)
        ");
        foreach($rows as $pid=>$val){
          $meta->execute([':id'=>(int)$pid]);
          if ($m=$meta->fetch(PDO::FETCH_ASSOC)){
            $ins->execute([
              ':sid'=>$sid, ':pid'=>$m['id'], ':val'=>trim((string)$val),
              ':u'=>$m['unit'], ':rl'=>$m['reporting_limit'], ':lv'=>$m['limit_value']
            ]);
          }
        }
      }
      echo json_encode(['success'=>true,'message'=>$I['success_save']]); exit;
    }

    if ($action==='finalize') {
      $sid = (int)($_POST['sample_id'] ?? 0);
      $pdo->prepare("UPDATE samples SET `$finalCol`=1, `$endCol`=IFNULL(`$endCol`,CURDATE()) WHERE id=:id")
          ->execute([':id'=>$sid]);
      echo json_encode(['success'=>true,'message'=>$I['success_fin']]); exit;
    }

    echo json_encode(['success'=>false,'message'=>'Unknown action']); exit;
  } catch(Exception $e){
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]); exit;
  }
}

/* ==== Liste ==== */
$q = trim($_GET['q'] ?? '');
$where=[]; $pr=[];
$sql = "SELECT
          s.id,
          s.`$codeCol`    AS s_code,
          s.`$typeCol`    AS s_type,
          s.`$subtypeCol` AS s_subtype,
          s.`$detailCol`  AS s_detail,
          s.`$startCol`   AS s_start,
          s.`$endCol`     AS s_end,
          s.`$evalCol`    AS s_eval,
          s.`$finalCol`   AS s_final,
          t.name_tr t_tr,  t.name_en t_en,  t.name_mk t_mk,
          st.name_tr st_tr, st.name_en st_en, st.name_mk st_mk
        FROM samples s
        LEFT JOIN analysis_types t     ON t.id  = s.`$typeCol`
        LEFT JOIN analysis_subtypes st ON st.id = s.`$subtypeCol`";
if ($q!==''){ $where[]="(s.`$codeCol` LIKE :q)"; $pr[':q']="%{$q}%"; }
if ($where){ $sql .= " WHERE ".implode(' AND ',$where); }
$sql .= " ORDER BY s.id DESC";
$st = $pdo->prepare($sql);
$st->execute($pr);
$samples = $st->fetchAll(PDO::FETCH_ASSOC);

$mapTypes = array_column($types, null, 'id');
$mapSubs  = array_column($subtypes, null, 'id');
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
 .param-table thead th{background:#f7f7f7;}
 .sticky-top{position:sticky;top:0;background:#fff;z-index:2}
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
      <a href="#" class="dropdown-toggle" data-toggle="dropdown"><i class="fa fa-database"></i> <?=$current_texts['select_db'] ?? 'Veritabanı Seç'?></a>
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

          <p class="muted"><?=$I['not_finalized_note']?></p>

          <form class="row" method="get" action="analysis-result.php">
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
                  <th><?=$I['atype']?></th>
                  <th><?=$I['asubtype']?></th>
                  <th><?=$I['detail']?></th>
                  <th><?=$I['start']?></th>
                  <th><?=$I['end']?></th>
                  <th><?=$I['evaluation']?></th>
                  <th style="width:220px;"><?=$I['update_finalize']?></th>
                </tr>
              </thead>
              <tbody>
              <?php if(!$samples): ?>
                <tr><td colspan="8" class="text-center text-muted">-</td></tr>
              <?php endif; ?>
              <?php foreach($samples as $s):
                $tname  = isset($mapTypes[$s['s_type'] ?? null]) ? nm($mapTypes[$s['s_type']], $lang)   : '';
                $stname = isset($mapSubs[$s['s_subtype'] ?? null])? nm($mapSubs[$s['s_subtype']], $lang): '';
                $code   = trim((string)($s['s_code'] ?? ''));
                if ($code==='') { $code = '#'.$s['id']; } // kod yoksa en azından ID göster
              ?>
                <tr class="<?= !empty($s['s_final']) ? '' : 'danger-row' ?>">
                  <td><strong><?=htmlspecialchars($code)?></strong></td>
                  <td><?=htmlspecialchars($tname)?></td>
                  <td><?=htmlspecialchars($stname)?></td>
                  <td><?=htmlspecialchars($s['s_detail'] ?? '')?></td>
                  <td><?=htmlspecialchars($s['s_start']  ?? '')?></td>
                  <td><?=htmlspecialchars($s['s_end']    ?? '')?></td>
                  <td><?=htmlspecialchars($s['s_eval']   ?? '')?></td>
                  <td class="text-center">
                    <button class="btn btn-xs btn-primary open-result"
                      data-id="<?=$s['id']?>"><i class="fa fa-edit"></i> <?=$I['save']?></button>
                    <button class="btn btn-xs btn-success finalize-btn" data-id="<?=$s['id']?>"><i class="fa fa-check"></i> <?=$I['finalize_report']?></button>
                    <a class="btn btn-xs btn-default" href="report-sample.php?id=<?=$s['id']?>&lang=<?=$lang?>"><i class="fa fa-file-pdf-o"></i> <?=$I['report']?></a>
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

<!-- Modal -->
<div class="modal fade" id="resultModal" tabindex="-1">
  <div class="modal-dialog modal-lg"><div class="modal-content">
    <div class="modal-header">
      <button class="close" data-dismiss="modal">&times;</button>
      <h4 class="modal-title"><?=$I['update_finalize']?></h4>
    </div>
    <div class="modal-body">
      <form id="resultForm">
        <input type="hidden" name="sample_id" id="s_id">
        <div class="row">
          <div class="col-sm-3"><label><?=$I['sample_code']?></label><input class="form-control" id="s_code" readonly></div>
          <div class="col-sm-3"><label><?=$I['atype']?></label><input class="form-control" id="s_type" readonly></div>
          <div class="col-sm-3"><label><?=$I['asubtype']?></label><input class="form-control" id="s_subtype" readonly></div>
          <div class="col-sm-3"><label><?=$I['detail']?></label><input name="detail" id="s_detail" class="form-control"></div>
        </div>
        <div class="row" style="margin-top:8px;">
          <div class="col-sm-3"><label><?=$I['start']?></label><input type="date" name="start_date" id="s_start" class="form-control"></div>
          <div class="col-sm-3"><label><?=$I['end']?></label><input type="date" name="end_date" id="s_end" class="form-control"></div>
          <div class="col-sm-6"><label><?=$I['evaluation']?></label><input name="evaluation" id="s_eval" class="form-control"></div>
        </div>
        <div class="table-responsive" style="margin-top:12px;">
          <table class="table table-bordered param-table">
            <thead>
              <tr>
                <th style="width:38%;"><?=$I['param']?></th>
                <th style="width:14%;"><?=$I['rl']?></th>
                <th style="width:20%;"><?=$I['result']?></th>
                <th style="width:12%;"><?=$I['unit']?></th>
                <th style="width:16%;"><?=$I['limit']?></th>
              </tr>
            </thead>
            <tbody id="paramRows"></tbody>
          </table>
        </div>
      </form>
      <div id="resAlert" class="alert" style="display:none;"></div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-default" data-dismiss="modal"><?=$I['search']?></button>
      <button class="btn btn-primary" id="btnSave"><?=$I['save']?></button>
    </div>
  </div></div>
</div>

<script src="bower_components/jquery/dist/jquery.min.js"></script>
<script src="bower_components/bootstrap/dist/js/bootstrap.min.js"></script>
<script>
function tName(row){ return <?= json_encode($lang) ?>==='en' ? row.name_en : (<?= json_encode($lang) ?>==='mk' ? row.name_mk : row.name_tr); }

function loadSample(sid){
  $.post('analysis-result.php?lang=<?= $lang ?>&db=<?= $selectedDb ?>',
    {ajax:1, action:'load_sample', sample_id:sid},
    function(res){
      if(!res || !res.success){ alert(res.message||'Hata'); return; }
      var s = res.sample;
      $('#s_id').val(s.id);
      $('#s_code').val(s.<?= $codeCol ?> || ('#'+s.id));
      $('#s_type').val(<?= json_encode(array_column($types, null, 'id')) ?>[s.<?= $typeCol ?>] ? tName(<?= json_encode(array_column($types, null, 'id')) ?>[s.<?= $typeCol ?>]) : (s.<?= $typeCol ?>||''));
      $('#s_subtype').val(<?= json_encode(array_column($subtypes, null, 'id')) ?>[s.<?= $subtypeCol ?>] ? tName(<?= json_encode(array_column($subtypes, null, 'id')) ?>[s.<?= $subtypeCol ?>]) : (s.<?= $subtypeCol ?>||''));
      $('#s_detail').val(s.<?= $detailCol ?>||'');
      $('#s_start').val(s.<?= $startCol ?>||'');
      $('#s_end').val(s.<?= $endCol ?>||'');
      $('#s_eval').val(s.<?= $evalCol ?>||'');

      var html='';
      (res.params||[]).forEach(function(p){
        html += '<tr>'+
          '<td>'+ $('<div>').text(tName(p)).html() +'</td>'+
          '<td>'+ (p.reporting_limit||'') +'</td>'+
          '<td><input class="form-control" name="rows['+p.pid+']" value="'+ (p.result_value||'') +'"></td>'+
          '<td>'+ (p.unit||'') +'</td>'+
          '<td>'+ (p.limit_value||'') +'</td>'+
        '</tr>';
      });
      $('#paramRows').html(html);
      $('#resAlert').hide();
      $('#resultModal').modal('show');
    }, 'json');
}

$(function(){
  $('.open-result').on('click', function(){ loadSample($(this).data('id')); });

  $('#btnSave').on('click', function(){
    var data = $('#resultForm').serializeArray();
    data.push({name:'ajax', value:1}, {name:'action', value:'save_results'});
    $.post('analysis-result.php?lang=<?= $lang ?>&db=<?= $selectedDb ?>', data, function(res){
      if(res && res.success){ $('#resAlert').removeClass('alert-danger').addClass('alert-success').text(res.message).show(); setTimeout(()=>location.reload(), 700); }
      else { $('#resAlert').removeClass('alert-success').addClass('alert-danger').text(res.message||'Hata').show(); }
    }, 'json').fail(function(xhr){
      $('#resAlert').removeClass('alert-success').addClass('alert-danger').text(xhr.responseText||'Hata').show();
    });
  });

  $('.finalize-btn').on('click', function(){
    var sid = $(this).data('id');
    if(!confirm('<?= addslashes($I['confirm_fin']) ?>')) return;
    $.post('analysis-result.php?lang=<?= $lang ?>&db=<?= $selectedDb ?>',
      {ajax:1, action:'finalize', sample_id:sid},
      function(res){ if(res && res.success){ location.reload(); } else { alert(res.message||'Hata'); } },
      'json'
    ).fail(function(xhr){ alert(xhr.responseText||'Hata'); });
  });
});
</script>
</body>
</html>
