<?php
require_once "db.php";

try {
    // Örnek tesis oluştur
    $pdo->exec("INSERT INTO facilities (name) VALUES ('Örnek Tesis')");
    $facilityId = $pdo->lastInsertId();

    // Admin kullanıcı ekle
    $username = 'admin';
    $password = password_hash('123456', PASSWORD_BCRYPT); // Şifre: 123456
    $stmt = $pdo->prepare("INSERT INTO users (username, password, facility_id) VALUES (:username, :password, :facility_id)");
    $stmt->execute([
        "username" => $username,
        "password" => $password,
        "facility_id" => $facilityId
    ]);

    echo "Admin kullanıcı başarıyla oluşturuldu! Kullanıcı adı: admin | Şifre: 123456";
} catch (Exception $e) {
    echo "Hata: " . $e->getMessage();
}