<?php
header('Content-Type: text/html; charset=utf-8');
require_once 'db_connect.php';

try {
    // Obrázky z tabulky galerii sestupde
    $sql = "SELECT id, nazev, popis, cesta_k_obrazku FROM fotogalerie ORDER BY id DESC";
    $result = $conn->query($sql);

    $output = '';
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            // Obrázek obalen do divu 
            $output .= '<div class="gallery-item" data-title="' . htmlspecialchars($row['nazev']) . '" data-description="Popis obrázku: ' . htmlspecialchars($row['popis']) . '">';
            $output .= '<img src="' . htmlspecialchars($row['cesta_k_obrazku']) . '" alt="' . htmlspecialchars($row['nazev']) . '">';
            $output .= '</div>';
        }
    } else {
        $output = '<div class="gallery-item"><p>Žádné obrázky k zobrazení.</p></div>';
    }
    echo $output;
} catch (Exception $e) {
    echo '<div class="gallery-item"><p>Chyba při načítání galerie.</p></div>';
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>