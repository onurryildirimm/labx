<?php
// AJAX ile ID'si gönderilen numuneyi silen dosya
require_once "db.php";

// Oturum kontrolü
session_start();
if (!isset($_SESSION["user_id"])) {
    echo json_encode(["success" => false, "message" => "Oturum sonlanmış. Lütfen tekrar giriş yapınız."]);
    exit;
}

// POST verisi kontrol edilir
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['id']) && is_numeric($_POST['id'])) {
    try {
        $id = intval($_POST['id']);
        
        // Numuneyi veritabanından sil
        $stmt = $pdo->prepare("
            DELETE FROM numune_raporlari WHERE id = :id
        ");
        
        $stmt->execute([':id' => $id]);
        
        if ($stmt->rowCount() > 0) {
            // Başarılı sonuç döndür
            echo json_encode([
                "success" => true, 
                "message" => "Numune başarıyla silindi."
            ]);
        } else {
            // Silinecek numune bulunamadıysa
            echo json_encode([
                "success" => false, 
                "message" => "Numune bulunamadı veya silinirken bir hata oluştu."
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
    // Geçerli bir ID yoksa veya POST metodu değilse
    echo json_encode([
        "success" => false, 
        "message" => "Geçersiz istek veya numune ID'si."
    ]);
}
?>