<?php
session_start();
header('Content-Type: text/html; charset=utf-8');

// zabezpečení 
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

// Generování CSRF tokenu
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// připojení 
require_once 'db_connect.php';

$error = "";
$success = "";

// zpracování formuláře
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Kontrola CSRF tokenu
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Chyba ověření bezpečnostního tokenu. Zkuste akci opakovat.";
    } else {
        $nazev = $_POST['nazev'] ?? '';
        $popis = $_POST['popis'] ?? '';

        $nazev = trim($nazev);
        $popis = trim($popis);

        $uploadedFilePath = null;
        if (isset($_FILES['obrazek']) && $_FILES['obrazek']['error'] === UPLOAD_ERR_OK) {
            // nastavení cílové složky
            $targetDirectory = __DIR__ . '/fotogalerie/';
            // vytvoří složku, pokud neexistuje
            if (!is_dir($targetDirectory)) {
                mkdir($targetDirectory, 0755, true); // Bezpečnější oprávnění než 0777
            }

            // generování unikátního názvu souboru pro bezpečnost
            $uniqueName = uniqid('foto_', true) . "_" . basename($_FILES['obrazek']['name']);
            $targetFile = $targetDirectory . $uniqueName;

            // relativní cesta do databáze
            $uploadedFilePath = 'fotogalerie/' . $uniqueName;

            // ověření typu souboru
            $fileType = mime_content_type($_FILES['obrazek']['tmp_name']);
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            if (!in_array($fileType, $allowedTypes)) {
                $error = "Nepodporovaný formát souboru. Povolené formáty: JPEG, PNG, GIF.";
            } else {
                // Přesun souboru složky TU chyba
                if (!move_uploaded_file($_FILES['obrazek']['tmp_name'], $targetFile)) {
                    $error = "Nahrání souboru se nezdařilo.";
                }
            }
        } else {
            $error = "Musíte vybrat obrázek k nahrání.";
        }

        // vložení záznamu do tabulky
        if (empty($error) && $uploadedFilePath) {
            $stmt = $conn->prepare("INSERT INTO fotogalerie (nazev, popis, cesta_k_obrazku) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $nazev, $popis, $uploadedFilePath);

            if ($stmt->execute()) {
                $success = "Obrázek byl úspěšně nahrán.";
            } else {
                $error = "Chyba při ukládání do databáze: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <title>Nahrát obrázek do fotogalerie</title>
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
        form {
            max-width: 600px;
            margin: 0 auto;
        }
        label {
            display: block;
            margin-top: 20px;
            font-weight: bold;
            color: #2D3436;
        }
        input[type="text"], textarea, input[type="file"] {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            box-sizing: border-box;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        textarea {
            resize: vertical;
            min-height: 80px;
        }
        button {
            margin-top: 20px;
            padding: 12px 20px;
            background-color: #388E3C;
            color: #fff;
            border: none;
            cursor: pointer;
            font-size: 16px;
            border-radius: 4px;
        }
        button:hover {
            background-color: #2E7D32;
        }
        .message {
            margin-top: 20px;
            font-weight: bold;
            text-align: center;
        }
        .error {
            color: red;
        }
        .success {
            color: green;
        }
        .back-link {
            display: block;
            text-align: center;
            margin-top: 30px;
        }
        .back-link a {
            text-decoration: none;
            color: #3498db;
        }
        .back-link a:hover {
            color: #2980b9;
        }
    </style>
</head>
<body>

<h1>Nahrát obrázek do fotogalerie</h1>

<form action="" method="post" enctype="multipart/form-data">
    <!-- CSRF token -->
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
    
    <label for="nazev">Název obrázku:</label>
    <input type="text" name="nazev" id="nazev" required>

    <label for="popis">Popis:</label>
    <textarea name="popis" id="popis"></textarea>

    <label for="obrazek">Vyberte obrázek k nahrání:</label>
    <input type="file" name="obrazek" id="obrazek" accept="image/*" required>

    <button type="submit">Nahrát</button>

    <?php if(!empty($error)): ?>
        <div class="message error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if(!empty($success)): ?>
        <div class="message success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
</form>

<div class="back-link">
    <a href="admin.php?section=fotogalerie">Zpět do fotogalerie</a>
</div>

</body>
</html>