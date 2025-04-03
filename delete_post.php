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

        // smazání článku
        $stmt = $conn->prepare("DELETE FROM blog_posts WHERE id = ?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            // přesměrování zpátk\y na admin
            header('Location: admin.php');
            exit;
        } else {
            throw new Exception("Chyba při mazání článku.");
        }

        $stmt->close();
        $conn->close();
    } else {
        throw new Exception("Chyba při mazání.");
    }
} catch (Exception $e) {
    echo '<p>Chyba vymazání příspěvku.</p>';
}
?>