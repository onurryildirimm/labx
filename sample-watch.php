<?php
require_once "db.php";
if (session_status() === PHP_SESSION_NONE) { session_start(); }

/* ==== Dil & menüler ==== */
$lang = $_GET['lang'] ?? 'tr';
$selectedDb = $_GET['db'] ?? '2025';
$languages = ['tr'=>'Türkçe','en'=>'English','mk'=>'Македонски'];
require_once "language_analiz.php";
$current_texts = $texts[$lang] ?? $texts['tr'];
$Lx = $texts[$lang] ?? $texts['tr'];

/* ==== Sayfa i18n (kısa) ==== */
$I = [
  'tr'=>[
    'title'=>'Numune İzlenebilirlik',
    'banner'=>'NUMUNE İZLENEBİLİRLİK',
    'search'=>'Ara',
    'code'=>'Numune Kodu',
    'sname'=>'Numune Adı',
    'facility'=>'Numune Alınan Firma Adı',
    'producer'=>'Üretici Adı',
    'address'=>'Numune Alınan Adres',
    'reason'=>'Analiz Nedeni',
    'rdate'=>'Rapor Tarihi',
    'rno'=>'Rapor No',
    'status'=>'DURUM',
    'view'=>'RAPOR GÖRÜNTÜLE',
    'admin'=>'SADECE ADMIN',
    'update_delete'=>'GÜNCELLEME/SİL',
    'accept_stage'=>'KABUL AŞAMASINDA',
    'analysis_stage'=>'ANALİZ AŞAMASINDA',
    'report_stage'=>'RAPOR AŞAMASINDA',
    'approved'=>'RAPOR ONAYLANDI',
    'update'=>'Güncelle',
    'edit_accept'=>'Kabul',
    'edit_analysis'=>'Analiz',
    'edit_report'=>'Rapor',
    'pdf'=>'PDF',
    'none'=>'-'
  ],
  'en'=>[
    'title'=>'Sample Traceability',
    'banner'=>'SAMPLE TRACEABILITY',
    'search'=>'Search',
    'code'=>'Sample Code',
    'sname'=>'Sample Name',
    'facility'=>'Facility',
    'producer'=>'Producer',
    'address'=>'Address',
    'reason'=>'Reason',
    'rdate'=>'Report Date',
    'rno'=>'Report No',
    'status'=>'STATUS',
    'view'=>'VIEW REPORT',
    'admin'=>'ADMIN ONLY',
    'update_delete'=>'UPDATE/DELETE',
    'accept_stage'=>'IN ACCEPTANCE',
    'analysis_stage'=>'IN ANALYSIS',
    'report_stage'=>'IN REPORT',
    'approved'=>'REPORT APPROVED',
    'update'=>'Update',
    'edit_accept'=>'Acceptance',
    'edit_analysis'=>'Analysis',
    'edit_report'=>'Report',
    'pdf'=>'PDF',
    'none'=>'-'
  ],
  'mk'=>[
    'title'=>'Следливост на примерок',
    'banner'=>'СЛЕДЛИВОСТ НА ПРИМЕРОК',
    'search'=>'Барај',
    'code'=>'Код',
    'sname'=>'Име на примерок',
    'facility'=>'Објект',
    'producer'=>'Производител',
    'address'=>'Адреса',
    'reason'=>'Причина',
    'rdate'=>'Датум на извештај',
    'rno'=>'Број на извештај',
    'status'=>'СТАТУС',
    'view'=>'ПРИКАЖИ ИЗВЕШТАЈ',
    'admin'=>'САМО АДМИН',
    'update_delete'=>'АЖУРИРАЈ/ИЗБРИШИ',
    'accept_stage'=>'ВО ПРИЕМАЊЕ',
    'analysis_stage'=>'ВО АНАЛИЗА',
    'report_stage'=>'ВО ИЗВЕШТАЈ',
    'approved'=>'ИЗВЕШТАЈ ОДОБРЕН',
    'update'=>'Ажурирај',
    'edit_accept'=>'Прием',
    'edit_analysis'=>'Анализа',
    'edit_report'=>'Извештај',
    'pdf'=>'PDF',
    'none'=>'-'
  ]
][$lang];

/* ==== Yardımcılar ==== */
function nm($r,$lang){ return $lang==='en'?$r['name_en'] : ($lang==='mk'?$r['name_mk'] : $r['name_tr']); }

/* samples tablosu kolon çözümü (dinamik) */
function smp_cols(PDO $pdo): array {
  $cols = $pdo->query("SHOW COLUMNS FROM samples")->fetchAll(PDO::FETCH_COLUMN);
  $pick = function($arr) use($cols){ foreach($arr as $c){ if(in_array($c,$cols,true)) return $c; } return null; };
  return [
    'id'=>'id',
    'code'=>$pick(['sample_code','code']),
    'final'=>$pick(['is_finalized','finalized']),
    'type'=>$pick(['type_id','analysis_type_id']),
    'subtype'=>$pick(['subtype_id','analysis_subtype_id']),
    'rdate'=>$pick(['report_date','rapor_tarihi']),
    'rno'=>$pick(['report_no','rapor_no']),
    'address'=>$pick(['address','sample_address']),
  ];
}
$S = smp_cols($pdo);

/* labs/companies benzeri isimleri çözmek için */
function detectFacilityLookup(PDO $pdo): ?array {
  try{
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    foreach(['facilities','facility','companies','company','firma','firms'] as $t){
      if(in_array($t,$tables,true)){
        $cols = $pdo->query("SHOW COLUMNS FROM `$t`")->fetchAll(PDO::FETCH_COLUMN);
        foreach(['name','company_name','title','firma_adi'] as $c){
          if(in_array($c,$cols,true)) return ['table'=>$t,'col'=>$c];
        }
        return ['table'=>$t,'col'=>null];
      }
    }
  }catch(Exception $e){}
  return null;
}
$fac = detectFacilityLookup($pdo);

/* tür/alt tür adları (opsiyonel görüntü için hazır) */
$types = $pdo->query("SELECT id,name_tr,name_en,name_mk FROM analysis_types ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
$subs  = $pdo->query("SELECT id,type_id,name_tr,name_en,name_mk FROM analysis_subtypes ORDER BY type_id,id")->fetchAll(PDO::FETCH_ASSOC);
$mapTypes = []; foreach($types as $t){ $mapTypes[$t['id']] = nm($t,$lang); }
$mapSubs  = []; foreach($subs  as $s){ $mapSubs [$s['id']] = nm($s,$lang); }

/* rapor yedek tablosu var mı? */
$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
$hasSR  = in_array('sample_reports',$tables,true);

/* ==== Liste ==== */
$q = trim($_GET['q'] ?? '');
$pr = [];
$codeCol = $S['code'] ?: 'sample_code';
$finalCol= $S['final']?: 'is_finalized';
$addrCol = $S['address'] ?: 'address';

$sql = "SELECT
          s.id sid,
          s.`$codeCol` scode,
          s.`$finalCol` sfin,
          s.`$addrCol` saddr,
          (SELECT si.sample_name   FROM sample_items si WHERE si.sample_id=s.id ORDER BY si.item_no ASC LIMIT 1) as sname,
          (SELECT si.producer_name FROM sample_items si WHERE si.sample_id=s.id ORDER BY si.item_no ASC LIMIT 1) as prod,
          ";

/* rapor tarih/no */
if ($S['rdate']) $sql .= " s.`{$S['rdate']}` rdate, "; else $sql .= " (SELECT report_date FROM sample_reports sr WHERE sr.sample_id=s.id LIMIT 1) rdate, ";
if ($S['rno'])   $sql .= " s.`{$S['rno']}`   rno,   "; else $sql .= " (SELECT report_no   FROM sample_reports sr WHERE sr.sample_id=s.id LIMIT 1) rno,   ";

/* kabul nedeni: herhangi bir item kabul kaydı varsa onu al */
$sql .= " (SELECT sfa.reason FROM sample_full_accepts sfa
           JOIN sample_items si2 ON si2.id = sfa.sample_item_id
           WHERE si2.sample_id = s.id ORDER BY sfa.accepted_at DESC LIMIT 1) as reason ";

if ($fac){
  $sql .= ", ".($fac['col'] ? "f.`{$fac['col']}` AS facility_name" : "NULL AS facility_name");
}

$sql .= " FROM samples s ";
if ($fac){ $sql .= " LEFT JOIN `{$fac['table']}` f ON f.id = s.facility_id "; }

if ($q!==''){
  $sql .= " WHERE ( s.`$codeCol` LIKE :q
            OR s.`$addrCol` LIKE :q
            OR EXISTS(SELECT 1 FROM sample_items x WHERE x.sample_id=s.id AND (x.sample_name LIKE :q OR x.producer_name LIKE :q))
            ".($fac && $fac['col'] ? " OR f.`{$fac['col']}` LIKE :q " : "")."
          )";
  $pr[':q'] = "%{$q}%";
}

$sql .= " ORDER BY s.id DESC";
$st = $pdo->prepare($sql);
$st->execute($pr);
$rows = $st->fetchAll(PDO::FETCH_ASSOC);

/* durum belirleyici */
function status_of(array $r, PDO $pdo): string {
  // kabul var mı?
  $hasAccept = false;
  $q = $pdo->prepare("SELECT COUNT(*) FROM sample_full_accepts sfa JOIN sample_items si ON si.id=sfa.sample_item_id WHERE si.sample_id=:sid");
  $q->execute([':sid'=>$r['sid']]);
  $hasAccept = ((int)$q->fetchColumn()) > 0;

  if (!$hasAccept) return 'accept';
  if (!$r['sfin']) return 'analysis';
  if (empty($r['rno']) || empty($r['rdate'])) return 'report';
  return 'approved';
}

/* admin mi? */
$isAdmin = !empty($_SESSION['is_admin']) || (($_SESSION['role'] ?? '')==='admin');

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
  .stage-accept   {background:#fff7e6;}  /* sarımsı */
  .stage-analysis {background:#e6f7ff;}  /* mavi */
  .stage-report   {background:#f9f1ff;}  /* morumsu */
  .stage-approved {background:#effaf1;}  /* yeşilimsi */
  .sticky-top{position:sticky;top:0;background:#fff;z-index:2}
  .thin{padding:4px 6px;height:28px;}
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

<?php include "sidebar.php"; ?>

<div id="content" class="ui-content">
  <div class="ui-content-body">
    <div class="ui-container">

      <div class="panel">
        <header class="blk-title"><?=$I['banner']?></header>
        <div class="panel-body">

          <!-- Arama -->
          <form class="row" method="get" action="sample-watch.php">
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
                  <th><?=$I['code']?></th>
                  <th><?=$I['sname']?></th>
                  <th><?=$I['facility']?></th>
                  <th><?=$I['producer']?></th>
                  <th><?=$I['address']?></th>
                  <th><?=$I['reason']?></th>
                  <th><?=$I['rdate']?></th>
                  <th><?=$I['rno']?></th>
                  <th><?=$I['status']?></th>
                  <th><?=$I['view']?></th>
                  <?php if($isAdmin): ?><th><?=$I['update_delete']?></th><?php endif; ?>
                </tr>
              </thead>
              <tbody>
              <?php if(!$rows): ?>
                <tr><td colspan="<?= $isAdmin?11:10 ?>" class="text-center text-muted"><?=$I['none']?></td></tr>
              <?php endif; ?>

              <?php foreach($rows as $r):
                $st = status_of($r, $pdo);
                $cls = $st==='accept'?'stage-accept' : ($st==='analysis'?'stage-analysis' : ($st==='report'?'stage-report' : 'stage-approved'));
                $statusLabel = $st==='accept' ? $I['accept_stage'] : ($st==='analysis' ? $I['analysis_stage'] : ($st==='report' ? $I['report_stage'] : $I['approved']));
              ?>
                <tr class="<?=$cls?>">
                  <td><strong><?=htmlspecialchars($r['scode'])?></strong></td>
                  <td><?=htmlspecialchars($r['sname'] ?? '')?></td>
                  <td><?=htmlspecialchars($r['facility_name'] ?? '')?></td>
                  <td><?=htmlspecialchars($r['prod'] ?? '')?></td>
                  <td><?=htmlspecialchars($r['saddr'] ?? '')?></td>
                  <td><?php
                    $opt = [
                      'OZEL_ISTEK'  => ($lang==='en'?'Special Request':($lang==='mk'?'Посебно барање':'Özel İstek')),
                      'YASAL_ANALIZ'=> ($lang==='en'?'Legal':($lang==='mk'?'Законска':'Yasal Analiz')),
                    ];
                    echo htmlspecialchars($opt[$r['reason']] ?? ($r['reason'] ?: ''));
                  ?></td>
                  <td><?=htmlspecialchars($r['rdate'] ?? '')?></td>
                  <td><?=htmlspecialchars($r['rno'] ?? '')?></td>
                  <td><span class="label label-default" style="display:inline-block;min-width:150px;"><?=$statusLabel?></span></td>
                  <td class="text-center">
                    <a class="btn btn-xs btn-default" target="_blank" href="report-sample.php?id=<?=$r['sid']?>&lang=<?=$lang?>">
                      <i class="fa fa-file-pdf-o"></i> <?=$I['pdf']?>
                    </a>
                  </td>
                  <?php if($isAdmin): ?>
                  <td class="text-center">
                    <?php if($st==='accept'): ?>
                      <a class="btn btn-xs btn-primary" href="sample-full-accept.php?lang=<?=$lang?>&db=<?=$selectedDb?>&q=<?=urlencode($r['sname']??$r['scode'])?>">
                        <i class="fa fa-edit"></i> <?=$I['edit_accept']?>
                      </a>
                    <?php elseif($st==='analysis'): ?>
                      <a class="btn btn-xs btn-info" href="analysis-result.php?lang=<?=$lang?>&db=<?=$selectedDb?>&q=<?=urlencode($r['scode'])?>">
                        <i class="fa fa-flask"></i> <?=$I['edit_analysis']?>
                      </a>
                    <?php elseif($st==='report' || $st==='approved'): ?>
                      <a class="btn btn-xs btn-success" href="report-ok.php?lang=<?=$lang?>&db=<?=$selectedDb?>&q=<?=urlencode($r['scode'])?>">
                        <i class="fa fa-file-text-o"></i> <?=$I['edit_report']?>
                      </a>
                    <?php endif; ?>
                  </td>
                  <?php endif; ?>
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
</body>
</html>
