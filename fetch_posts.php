<?php
header('Content-Type: text/html; charset=utf-8');

// Připojení 
require_once 'db_connect.php';

try {
    // Získat číslo stránky z parametru
    $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
    
    // Uložení uzivatele do tabulky
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $visited_at = date('Y-m-d H:i:s');
    
    $insert_sql = "INSERT INTO page_visits (ip_address, user_agent, visited_at) VALUES (?, ?, ?)";
    $stmt_visit = $conn->prepare($insert_sql);
    $stmt_visit->bind_param("sss", $ip_address, $user_agent, $visited_at);
    $stmt_visit->execute();
    $stmt_visit->close();

    // Parametry stránkování
    $posts_per_page = 6;
    $offset = ($page - 1) * $posts_per_page;

    // Získání celkového čtu članků
    $count_sql = "SELECT COUNT(*) as total FROM blog_posts WHERE is_published = 1";
    $count_result = $conn->query($count_sql);
    $total_posts = $count_result->fetch_assoc()['total'];

    // Načtení publikovaných
    $sql = "SELECT id, nazev, obsah, datum_vytvoreni, cesta_k_obrazku 
            FROM blog_posts 
            WHERE is_published = 1 
            ORDER BY datum_vytvoreni DESC
            LIMIT $offset, $posts_per_page";
            //limit pro počet zobrazení

    $result = $conn->query($sql);
    $output = '';

    if ($result->num_rows > 0) {
        // Příspěvky na první stránce
        if ($page == 1) {
            $output .= '<div class="posts-grid">';
        }
        
        while ($row = $result->fetch_assoc()) {
            $datum = date('d.m.Y', strtotime($row['datum_vytvoreni']));
            
            $output .= '<article class="post">';
            $output .= '<div class="post-content-wrapper">';
            
            // Načtení obrázku
            if (!empty($row['cesta_k_obrazku'])) {
                $output .= '<div class="post-image">';
                $output .= '<img src="' . htmlspecialchars($row['cesta_k_obrazku']) . '" 
                           alt="' . htmlspecialchars($row['nazev']) . '" loading="lazy">';
                $output .= '</div>';
            }
            
            $output .= '<div class="post-details">';
            $output .= '<h2 class="post-title">' . htmlspecialchars($row['nazev']) . '</h2>';
            $output .= '<div class="post-meta">Publikováno: ' . $datum . '</div>';
            
            $excerpt = mb_substr(strip_tags($row['obsah']), 0, 100) . '...';
            $output .= '<div class="post-excerpt">' . $excerpt . '</div>';
            $output .= '<div class="read-more"><a href="#" data-id="' . $row['id'] . '">Číst více</a></div>';
            $output .= '</div>';
            $output .= '</div>';
            $output .= '</article>';
        }
        
        // Uzavření kontejneru pouze na první stránce
        if ($page == 1) {
            $output .= '</div>';
        }

        // více příspěvků
        $has_more_posts = ($offset + $result->num_rows) < $total_posts;
        
        // Nové tlačítko pro další stránky
        if ($page == 1 && $has_more_posts) {
            $output .= '<div class="load-more-container">';
            $output .= '<button id="load-more-btn" class="load-more-btn" data-next-page="2">Načíst další příspěvky</button>';
            $output .= '</div>';
        }
        
        // stránkování
        if ($page > 1) {
            $output .= '<div class="load-more-data" style="display:none;" ';
            $output .= 'data-has-more="' . ($has_more_posts ? 'true' : 'false') . '" ';
            $output .= 'data-next-page="' . ($page + 1) . '" ';
            $output .= 'data-current-page="' . $page . '">';
            $output .= '</div>';
        }
    } else {
        if ($page == 1) {
            $output = '<p>Žádné příspěvky k zobrazení.</p>';
        } else {
            $output = '<div class="load-more-data" style="display:none;" data-has-more="false"></div>';
        }
    }
    
    echo $output;

} catch (Exception $e) {
    echo '<p>Chyba při načítání příspěvků.</p>';
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>