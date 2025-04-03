<?php
header('Content-Type: text/html; charset=utf-8');

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "blogposts";

try {
    $conn = new mysqli($servername, $username, $password, $dbname);
    $conn->set_charset("utf8mb4");
    
    if ($conn->connect_error) {
        throw new Exception("Nepodařilo se připojit k databázi.");
    }
} catch (Exception $e) {
    die('<p>Nastala chyba při připojení k databázi.</p>');
}
?>