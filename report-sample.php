<?php
require_once "db.php";
$id = (int)($_GET['id'] ?? 0);
$lang = $_GET['lang'] ?? 'tr';
$s = $pdo->prepare("SELECT * FROM samples WHERE id=:id"); $s->execute([':id'=>$id]); $sample=$s->fetch(PDO::FETCH_ASSOC);
$r = $pdo->prepare("SELECT ap.name_tr,ap.name_en,ap.name_mk,sr.* FROM sample_results sr JOIN analysis_parameters ap ON ap.id=sr.parameter_id WHERE sr.sample_id=:id");
$r->execute([':id'=>$id]); $rows=$r->fetchAll(PDO::FETCH_ASSOC);
function nm($x,$l){return $l==='en'?$x['name_en']:($l==='mk'?$x['name_mk']:$x['name_tr']);}
header('Content-Type: text/html; charset=utf-8');
?><!doctype html><html><head><meta charset="utf-8"><title>Report</title>
<style>body{font-family:Arial,Helvetica,sans-serif} table{border-collapse:collapse;width:100%}td,th{border:1px solid #ccc;padding:6px}</style>
</head><body>
<h2>Numune Raporu</h2>
<p><b>Kod:</b> <?=htmlspecialchars($sample['sample_code'])?> |
<b>Başlama:</b> <?=htmlspecialchars($sample['start_date'])?> |
<b>Bitiş:</b> <?=htmlspecialchars($sample['end_date'])?></p>
<p><b>Değerlendirme:</b> <?=htmlspecialchars($sample['evaluation'])?></p>
<table><thead><tr><th>Parametre</th><th>Raporlama Limiti</th><th>Sonuç</th><th>Birim</th><th>Sınır Değer</th></tr></thead><tbody>
<?php foreach($rows as $row): ?>
<tr><td><?=htmlspecialchars(nm($row,$lang))?></td><td><?=htmlspecialchars($row['reporting_limit'])?></td><td><?=htmlspecialchars($row['result_value'])?></td><td><?=htmlspecialchars($row['unit'])?></td><td><?=htmlspecialchars($row['limit_value'])?></td></tr>
<?php endforeach; ?>
</tbody></table>
</body></html>
