<?php
require_once "db.php";
header('Content-Type: application/json; charset=utf-8');
$id = (int)($_GET['id'] ?? 0);
$st = $pdo->prepare("SELECT parameter_id FROM facility_agreement_params WHERE agreement_id=:a");
$st->execute([':a'=>$id]);
$ids = array_map('intval', array_column($st->fetchAll(PDO::FETCH_ASSOC),'parameter_id'));
echo json_encode(['ids'=>$ids]);
