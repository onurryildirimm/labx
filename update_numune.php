<?php
// AJAX ile gönderilen numune formunu güncelleyen dosya
require_once "db.php";

// Oturum kontrolü
session_start();
if (!isset($_SESSION["user_id"])) {
    echo json_encode(["success" => false, "message" => "Oturum sonlanmış. Lütfen tekrar giriş yapınız."]);
    exit;
}

// POST verileri kontrol edilir
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // ID kontrolü
        if (empty($_POST['id'])) {
            echo json_encode(["success" => false, "message" => "Geçersiz numune ID'si."]);
            exit;
        }
        
        $id = intval($_POST['id']);
        
        // Gerekli alanların varlığını kontrol et
        $requiredFields = ['facility_id', 'numune_turu', 'numune-tr', 'numune-en', 'numune-mk', 
                          'yer-tr', 'yer-en', 'yer-mk', 'alim_tarihi', 'alan_kisi', 'numune_kodu'];
        
        foreach ($requiredFields as $field) {
            if (empty($_POST[$field])) {
                echo json_encode(["success" => false, "message" => "Lütfen tüm zorunlu alanları doldurunuz."]);
                exit;
            }
        }
        
        // Veri temizliği
        $facility_id = intval($_POST['facility_id']);
        $numune_turu = htmlspecialchars(trim($_POST['numune_turu']));
        $numune_tr = htmlspecialchars(trim($_POST['numune-tr']));
        $numune_en = htmlspecialchars(trim($_POST['numune-en']));
        $numune_mk = htmlspecialchars(trim($_POST['numune-mk']));
        $yer_tr = htmlspecialchars(trim($_POST['yer-tr']));
        $yer_en = htmlspecialchars(trim($_POST['yer-en']));
        $yer_mk = htmlspecialchars(trim($_POST['yer-mk']));
        $alim_tarihi = $_POST['alim_tarihi'];
        $alan_kisi = htmlspecialchars(trim($_POST['alan_kisi']));
        $numune_kodu = htmlspecialchars(trim($_POST['numune_kodu']));
        $report_path = !empty($_POST['report_path']) ? htmlspecialchars(trim($_POST['report_path'])) : '';
        
        // Veritabanında güncelle
        $stmt = $pdo->prepare("
            UPDATE numune_raporlari SET
                facility_id = :facility_id, 
                numune_turu = :numune_turu, 
                numune_kodu = :numune_kodu,
                `numune-tr` = :numune_tr, 
                `numune-en` = :numune_en, 
                `numune-mk` = :numune_mk,
                `yer-tr` = :yer_tr, 
                `yer-en` = :yer_en, 
                `yer-mk` = :yer_mk,
                alim_tarihi = :alim_tarihi, 
                alan_kisi = :alan_kisi,
                report_path = :report_path
            WHERE id = :id
        ");
        
        $stmt->execute([
            ':id' => $id,
            ':facility_id' => $facility_id,
            ':numune_turu' => $numune_turu,
            ':numune_kodu' => $numune_kodu,
            ':numune_tr' => $numune_tr,
            ':numune_en' => $numune_en,
            ':numune_mk' => $numune_mk,
            ':yer_tr' => $yer_tr,
            ':yer_en' => $yer_en,
            ':yer_mk' => $yer_mk,
            ':alim_tarihi' => $alim_tarihi,
            ':alan_kisi' => $alan_kisi,
            ':report_path' => $report_path
        ]);
        
        // Başarılı sonuç döndür
        echo json_encode([
            "success" => true, 
            "message" => "Numune başarıyla güncellendi."
        ]);
        
    } catch (PDOException $e) {
        // Hata oluşursa
        echo json_encode([
            "success" => false, 
            "message" => "Veritabanı hatası: " . $e->getMessage()
        ]);
    }
} else {
    // POST metodu değilse
    echo json_encode([
        "success" => false, 
        "message" => "Geçersiz istek yöntemi."
    ]);
}
?>