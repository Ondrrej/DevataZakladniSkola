<?php
session_start();
header('Content-Type: text/html; charset=utf-8');

// kontrola přihlášení
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

// Kontrola CSRF tokenu
if (!isset($_SESSION['csrf_token']) || !isset($_GET['token']) || $_SESSION['csrf_token'] !== $_GET['token']) {
    http_response_code(403);
    die('Chyba ověření bezpečnostního tokenu.');
}

// připojení
require_once 'db_connect.php';

try {
    if (isset($_GET['id'])) {
        $id = intval($_GET['id']);

        // zjištění, jestli záznam existuje, a zjištění cesty k obrázku
        $stmtSelect = $conn->prepare("SELECT cesta_k_obrazku FROM fotogalerie WHERE id = ?");
        $stmtSelect->bind_param("i", $id);
        $stmtSelect->execute();
        $result = $stmtSelect->get_result();
        $row = $result->fetch_assoc();
        $stmtSelect->close();

        if (!$row) {
            throw new Exception("Obrázek nebyl nalezen.");
        }

        // smazání souboru ze serveru
        $filePath = __DIR__ . "/" . $row['cesta_k_obrazku'];
        if (!empty($row['cesta_k_obrazku']) && file_exists($filePath)) {
            unlink($filePath);
        }

        // Smazání záznamu z databáze
        $stmtDelete = $conn->prepare("DELETE FROM fotogalerie WHERE id = ?");
        $stmtDelete->bind_param("i", $id);
        
        if ($stmtDelete->execute()) {
            $stmtDelete->close();
            $conn->close();
            header('Location: admin.php?section=fotogalerie');
            exit;
        } else {
            throw new Exception("Chyba při mazání obrázku.");
        }
    } else {
        throw new Exception("Chybějící parametr id.");
    }
} catch (Exception $e) {
    echo '<p>Chyba vymázání obrázku</p>';
}
?>