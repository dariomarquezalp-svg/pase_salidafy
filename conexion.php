<?php
try {
    $pdo = new PDO("mysql:host=localhost;dbname=control_salidas;charset=utf8mb4", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión a control_salidas: " . $e->getMessage());
}
?>