<?php
try {
    $pdo = new PDO(
        "mysql:host=localhost;dbname=plant_sampling_system",
        "root",
        ""
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>
