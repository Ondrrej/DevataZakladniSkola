<?php
session_start();
header('Content-Type: text/html; charset=utf-8');

// kontrola přihlášení
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

// Kontrola CSRF tokenu
if (!isset($_SESSION['csrf_token']) || !isset($_POST['csrf_token']) || $_SESSION['csrf_token'] !== $_POST['csrf_token']) {
    http_response_code(403);
    die('Chyba ověření bezpečnostního tokenu. Zkuste akci opakovat.');
}

// připojení
require_once 'db_connect.php';

try {
    // získání dat z formuláře
    $nazev = $_POST['nazev'] ?? '';
    $obsah = $_POST['obsah'] ?? '';
    $is_published = isset($_POST['is_published']) ? 1 : 0;

    // zpracování nahraného souboru
    $uploadedFilePath = null;
    if (isset($_FILES['obrazek']) && $_FILES['obrazek']['error'] === UPLOAD_ERR_OK) {
        // cesta ke složce
        $targetDirectory = __DIR__ . '/obrazky/';
        
        // konečná cesta k souboru
        $targetFile = $targetDirectory . basename($_FILES['obrazek']['name']);
        
        // cesta do databáze
        $uploadedFilePath = 'obrazky/' . basename($_FILES['obrazek']['name']);
        
        // přesun obrázku do složky
        if (!move_uploaded_file($_FILES['obrazek']['tmp_name'], $targetFile)) {
            throw new Exception("Nahrání souboru se nezdařilo.");
        }
    }

    // pokud je v id číslo je to úprava
    if (isset($_POST['id']) && !empty($_POST['id'])) {
        $id = intval($_POST['id']);
        
        // změna cesty k obrázku
        if ($uploadedFilePath !== null) {
            $stmt = $conn->prepare("UPDATE blog_posts SET nazev = ?, obsah = ?, cesta_k_obrazku = ?, is_published = ? WHERE id = ?");
            $stmt->bind_param("sssii", $nazev, $obsah, $uploadedFilePath, $is_published, $id);
        } else {
            // pokud nebyl nahrán nový obrázek cestu neměním
            $stmt = $conn->prepare("UPDATE blog_posts SET nazev = ?, obsah = ?, is_published = ? WHERE id = ?");
            $stmt->bind_param("ssii", $nazev, $obsah, $is_published, $id);
        }

        if ($stmt->execute()) {
            header('Location: admin.php');
            exit;
        } else {
            throw new Exception("Chyba při aktualizaci článku: " . $stmt->error);
        }
        $stmt->close();
    } else {
        // vložení nového článku
        $stmt = $conn->prepare("INSERT INTO blog_posts (nazev, obsah, cesta_k_obrazku, is_published) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sssi", $nazev, $obsah, $uploadedFilePath, $is_published);
        
        if ($stmt->execute()) {
            header('Location: admin.php');
            exit;
        } else {
            throw new Exception("Chyba při ukládání článku: ");
        }
        $stmt->close();
    }

    $conn->close();
} catch (Exception $e) {
    echo '<p>Chyba.</p>';
}
?>