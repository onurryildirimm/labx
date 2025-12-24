<?php
// user-delete.php
require_once "db.php";
session_start();

// Check if user is logged in and has admin rights
if (!isset($_SESSION["user_id"])) {
    echo json_encode(['success' => false, 'message' => 'Oturum açılmamış']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['user_id'])) {
    $user_id = $_POST['user_id'];
    
    try {
        // Start transaction
        $pdo->beginTransaction();
        
        // First delete from user_facilities table (foreign key constraint)
        $stmt = $pdo->prepare("DELETE FROM user_facilities WHERE user_id = :user_id");
        $stmt->execute(["user_id" => $user_id]);
        
        // Then delete the user
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id");
        $stmt->execute(["id" => $user_id]);
        
        // Commit transaction
        $pdo->commit();
        
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        // Roll back transaction on error
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek']);
}
?>