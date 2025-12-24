<?php
// AJAX ile ID'si gönderilen numunenin bilgilerini döndüren dosya
require_once "db.php";

// Oturum kontrolü
session_start();
if (!isset($_SESSION["user_id"])) {
    echo json_encode(["success" => false, "message" => "Oturum sonlanmış. Lütfen tekrar giriş yapınız."]);
    exit;
}

// GET verisi kontrol edilir
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    try {
        $id = intval($_GET['id']);
        
        // Numune bilgilerini veritabanından al
        $stmt = $pdo->prepare("
            SELECT * FROM numune_raporlari WHERE id = :id LIMIT 1
        ");
        
        $stmt->execute([':id' => $id]);
        $sample = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($sample) {
            // Başarılı sonuç döndür
            echo json_encode([
                "success" => true, 
                "sample" => $sample
            ]);
        } else {
            // Numune bulunamadıysa
            echo json_encode([
                "success" => false, 
                "message" => "Numune bulunamadı."
            ]);
        }
        
    } catch (PDOException $e) {
        // Hata oluşursa
        echo json_encode([
            "success" => false, 
            "message" => "Veritabanı hatası: " . $e->getMessage()
        ]);
    }
} else {
    // Geçerli bir ID yoksa
    echo json_encode([
        "success" => false, 
        "message" => "Geçersiz numune ID'si."
    ]);
}
?>