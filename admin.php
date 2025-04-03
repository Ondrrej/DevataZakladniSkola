<?php 
session_start(); 
header('Content-Type: text/html; charset=utf-8');

// kontrola přihlášení 
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

// generování CSRF tokenu
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// Připojení k databázi
require_once 'db_connect.php';

// aktivní sekce
$section = isset($_GET['section']) ? $_GET['section'] : 'posts';

// proměné pro sekce
$articlesResult = null;
$stats = [];
$galleryResult = null;

// zpracování podle sekce
if ($section === 'posts') {
    $sqlPosts = "SELECT id, nazev, datum_vytvoreni, is_published FROM blog_posts ORDER BY datum_vytvoreni DESC";
    $articlesResult = $conn->query($sqlPosts);
} elseif ($section === 'stats') {
    $sqlAdresses = "SELECT 
    (SELECT COUNT(DISTINCT ip_address) FROM page_visits WHERE visited_at >= CURDATE()) AS unique_ips_today,
    (SELECT COUNT(DISTINCT ip_address) FROM page_visits WHERE YEARWEEK(visited_at, 1) = YEARWEEK(CURDATE(), 1)) AS unique_ips_week,
    (SELECT COUNT(DISTINCT ip_address) FROM page_visits WHERE YEAR(visited_at) = YEAR(CURDATE()) AND MONTH(visited_at) = MONTH(CURDATE())) AS unique_ips_month";
    $result = $conn->query($sqlAdresses);
    $stats = $result->fetch_assoc();
    
    //fotogalerie
} elseif ($section === 'fotogalerie') {
    $sqlGallery = "SELECT id, nazev, popis, cesta_k_obrazku FROM fotogalerie ORDER BY id DESC";
    $galleryResult = $conn->query($sqlGallery);
}
?>

<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <title>Administrace</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: #FFFFFF;
        }

        h1 {
            text-align: center;
            color: #2D3436;
        }

        .logout-wrapper {
            text-align: center;
            margin: 10px 0 20px;
        }

        .logout-button {
            padding: 10px 15px;
            background-color: #d12e2e;
            color: #fff;
            text-decoration: none;
            font-size: 14px;
            border-radius: 4px;
        }

        .logout-button:hover {
            background-color: #af1e1e;
        }

        .nav-bar {
            text-align: center;
            margin-bottom: 20px;
        }

        .nav-bar a {
            display: inline-block;
            margin: 0 10px;
            padding: 10px 15px;
            background-color: #3498db;
            color: #fff;
            text-decoration: none;
            border-radius: 4px;
        }

        .nav-bar a:hover {
            background-color: #2980b9;
        }

        .content-container {
            max-width: 900px;
            margin: 0 auto;
        }

        .article-list,
        .stats-list,
        .gallery-list {
            margin-top: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th,
        td {
            border: 1px solid #ccc;
            padding: 10px;
            text-align: left;
            vertical-align: top;
        }

        th {
            background-color: #F0F0F0;
        }

        a.button {
            display: inline-block;
            margin-top: 20px;
            padding: 12px 20px;
            background-color: #388E3C;
            color: #fff;
            text-decoration: none;
            font-size: 16px;
            border-radius: 4px;
        }

        a.button:hover {
            background-color: #2E7D32;
        }

        .actions a {
            margin-right: 10px;
        }

        .actions a.delete {
            color: #E53935;
        }
    </style>
</head>
<body>
    <h1>Administrace</h1>

    <div class="logout-wrapper">
        <a href="logout.php" class="logout-button">Odhlásit se</a>
    </div>

    <div class="nav-bar">
        <a href="admin.php?section=posts">Články</a>
        <a href="admin.php?section=stats">Statistiky návštěvnosti</a>
        <a href="admin.php?section=fotogalerie">Fotogalerie</a>
    </div>

    <div class="content-container">
        <?php if ($section === 'posts'): ?>
            <!-- články -->
            <div class="article-list">
                <a href="create_post.php" class="button">Vytvořit nový článek</a>

                <table>
                    <thead>
                        <tr>
                            <th>Název</th>
                            <th>Datum vytvoření</th>
                            <th>Publikováno</th>
                            <th>Akce</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (isset($articlesResult) && $articlesResult && $articlesResult->num_rows > 0): ?>
                            <?php while($row = $articlesResult->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['nazev']); ?></td>
                                    <td><?php echo htmlspecialchars($row['datum_vytvoreni']); ?></td>
                                    <td><?php echo $row['is_published'] ? 'Ano' : 'Ne'; ?></td>
                                    <td class="actions">
                                        <a href="edit_post.php?id=<?php echo $row['id']; ?>">Upravit</a>
                                        <a href="delete_post.php?id=<?php echo $row['id']; ?>&token=<?php echo urlencode($csrf_token); ?>" class="delete" onclick="return confirm('Opravdu chcete smazat tento článek?');">Smazat</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4">Žádné články nebyly nalezeny.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        <?php elseif ($section === 'stats'): ?>
            <!-- statistiky -->
            <div class="stats-list">
                <h2>Statistiky návštěvnosti</h2>
                <p><strong>Unikátní IP adresy dnes:</strong> <?php echo (int)$stats['unique_ips_today']; ?></p>
                <p><strong>Unikátní IP adresy tento týden:</strong> <?php echo (int)$stats['unique_ips_week']; ?></p>
                <p><strong>Unikátní IP adresy tento měsíc:</strong> <?php echo (int)$stats['unique_ips_month']; ?></p>
            </div>

        <?php elseif ($section === 'fotogalerie'): ?>
            <!-- fotogalerie -->
            <div class="gallery-list">
                <a href="upload_gallery.php" class="button">Nahrát nový obrázek</a>

                <table>
                    <thead>
                        <tr>
                            <th>Název</th>
                            <th>Popis</th>
                            <th>Náhled</th>
                            <th>Akce</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (isset($galleryResult) && $galleryResult && $galleryResult->num_rows > 0): ?>
                            <?php while($row = $galleryResult->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['nazev']); ?></td>
                                    <td><?php echo htmlspecialchars($row['popis']); ?></td>
                                    <td>
                                        <?php if ($row['cesta_k_obrazku']): ?>
                                            <img src="<?php echo htmlspecialchars($row['cesta_k_obrazku']); ?>" alt="Obrázek" style="max-width: 200px; height: auto;">
                                        <?php else: ?>
                                            Žádný obrázek.
                                        <?php endif; ?>
                                    </td>
                                    <td class="actions">
                                        <a href="delete_image.php?id=<?php echo $row['id']; ?>&token=<?php echo urlencode($csrf_token); ?>" class="delete" onclick="return confirm('Opravdu chcete smazat tento obrázek?');">Smazat</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4">Žádné obrázky ve fotogalerii nebyly nalezeny.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>