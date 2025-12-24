<?php
// user-update.php
require_once "db.php";
session_start();

// Check if user is logged in
if (!isset($_SESSION["user_id"])) {
    echo json_encode(['success' => false, 'message' => 'Oturum açılmamış']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_POST['user_id'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    $facility_id = $_POST['facility_id'];
    
    // YENİ: Arayüz dilini al, gelmezse varsayılan 'tr' yap
    $ui_lang = $_POST['ui_lang'] ?? 'tr';
    
    try {
        // Start transaction
        $pdo->beginTransaction();
        
        // Update user details
        if (!empty($password)) {
            // Şifre varsa: Şifre + ui_lang güncelle
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET username = :username, password = :password, facility_id = :facility_id, ui_lang = :ui_lang WHERE id = :id");
            $stmt->execute([
                "username" => $username,
                "password" => $hashed_password,
                "facility_id" => $facility_id,
                "ui_lang" => $ui_lang, // Yeni eklenen
                "id" => $user_id
            ]);
        } else {
            // Şifre yoksa: Sadece diğer bilgiler + ui_lang güncelle
            $stmt = $pdo->prepare("UPDATE users SET username = :username, facility_id = :facility_id, ui_lang = :ui_lang WHERE id = :id");
            $stmt->execute([
                "username" => $username,
                "facility_id" => $facility_id,
                "ui_lang" => $ui_lang, // Yeni eklenen
                "id" => $user_id
            ]);
        }
        
        // Update user-facility relation (first delete existing, then add new)
        $stmt = $pdo->prepare("DELETE FROM user_facilities WHERE user_id = :user_id");
        $stmt->execute(["user_id" => $user_id]);
        
        if (!empty($facility_id)) {
            $stmt = $pdo->prepare("INSERT INTO user_facilities (user_id, facility_id) VALUES (:user_id, :facility_id)");
            $stmt->execute([
                "user_id" => $user_id,
                "facility_id" => $facility_id
            ]);
        }
        
        // Commit transaction
        $pdo->commit();
        
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        // Roll back transaction on error
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek metodu']);
}
?>