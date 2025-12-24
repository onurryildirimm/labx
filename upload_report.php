<?php
require_once "db.php";

// Dil ve Veritabanı Seçimi
$lang = $_GET['lang'] ?? 'tr';
$selectedDb = $_GET['db'] ?? '2025';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $facilityId = $_POST['facility_id'];
    $reportType = $_POST['report_type'];
    $code = $_POST['code'];
    $numunetr = $_POST['numune-tr'];
    $numuneen = $_POST['numune-en'];
    $numunemk = $_POST['numune-mk'];
    $yertr = $_POST['yer-tr'];
    $yeren = $_POST['yer-en'];
    $yermk = $_POST['yer-mk'];

    // Dosya yükleme işlemi
    if (isset($_FILES['report_file']) && $_FILES['report_file']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/reports/';
        $fileName = uniqid() . '_' . basename($_FILES['report_file']['name']);
        $uploadPath = $uploadDir . $fileName;

        // Klasör kontrolü ve oluşturma
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        if (move_uploaded_file($_FILES['report_file']['tmp_name'], $uploadPath)) {
            // Veritabanına kaydetme
            $stmt = $pdo->prepare("
                INSERT INTO lab_results (facility_id, test_name, code, `numune-tr`, `numune-en`, `numune-mk`, `yer-tr`, `yer-en`, `yer-mk`, report_path) 
                VALUES (:facility_id, :test_name, :code, :numunetr, :numuneen, :numunemk, :yertr, :yeren, :yermk, :report_path)
            ");
            $stmt->execute([
                'facility_id' => $facilityId,
                'test_name' => $reportType,
                'code' => $code,
                'numunetr' => $numunetr,
                'numuneen' => $numuneen,
                'numunemk' => $numunemk,
                'yertr' => $yertr,
                'yeren' => $yeren,
                'yermk' => $yermk,
                'report_path' => $uploadPath
            ]);

            // Başarı mesajı
            header("Location: upload-report.php?lang=$lang&db=$selectedDb&success=1");
        } else {
            header("Location: upload-report.php?lang=$lang&db=$selectedDb&error=upload");
        }
    } else {
        header("Location: upload-report.php?lang=$lang&db=$selectedDb&error=file");
    }
}
?>
