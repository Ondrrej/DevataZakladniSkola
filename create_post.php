<?php
session_start();
header('Content-Type: text/html; charset=utf-8');

// Kontrola přihlášení
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

// Generování CSRF tokenu
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// načtení API TinyMCE
require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <title>Vytvořit nový článek</title>
    <!-- TinyMCE editor s API klíčem -->
    <script src="https://cdn.tiny.cloud/1/<?php echo TINYMCE_API_KEY; ?>/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
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
            max-width: 800px;
            margin: 0 auto;
        }
        label {
            display: block;
            margin-top: 20px;
            font-weight: bold;
            color: #2D3436;
        }
        input[type="text"], input[type="file"] {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            box-sizing: border-box;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        input[type="checkbox"] {
            margin-top: 5px;
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
        .form-actions {
            display: flex;
            align-items: center;
            margin-top: 20px;
        }
        .publish-container {
            display: flex;
            align-items: center;
            margin-right: 30px;
        }
        .publish-container label {
            display: inline;
            margin-top: 0;
            margin-right: 10px;
        }
        .publish-container input[type="checkbox"] {
            margin-top: 0;
        }
        .form-actions button {
            margin-top: 0;
        }
    </style>
    <script>
        // Inicializace TinyMCE
        tinymce.init({
            selector: '#obsah',
            language: 'cs',
            height: 500,
            plugins: 'print preview importcss searchreplace autolink autosave save directionality code visualblocks visualchars fullscreen image link media template codesample table charmap hr pagebreak nonbreaking anchor insertdatetime advlist lists wordcount charmap quickbars emoticons',
            toolbar: 'undo redo | bold italic underline strikethrough | fontselect fontsizeselect formatselect | alignleft aligncenter alignright alignjustify | outdent indent |  numlist bullist checklist | forecolor backcolor removeformat | pagebreak | charmap emoticons | fullscreen  preview save print | insertfile image media link anchor codesample',
            content_style: 'body { font-family:Arial,sans-serif; font-size:14px }',
            // Ochrana proti XSS
            valid_elements: 'p,strong,em,h1,h2,h3,h4,h5,h6,ul,ol,li,a[href|target],img[src|alt],br,div,span[style]',
            extended_valid_elements: 'iframe[src|width|height|frameborder]',
            invalid_elements: 'script,object,embed,form',
            cleanup: true,
            verify_html: true
        });
    </script>
</head>
<body>
    <h1>Vytvořit nový článek</h1>
    <form action="save_post.php" method="post" enctype="multipart/form-data">
        <!-- CSRF token -->
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
        
        <label for="nazev">Název článku:</label>
        <input type="text" name="nazev" id="nazev" required>

        <label for="obsah">Obsah článku:</label>
        <textarea name="obsah" id="obsah"></textarea>

        <label for="obrazek">Vyberte obrázek k nahrání:</label>
        <input type="file" name="obrazek" id="obrazek" accept="image/*">

        <div class="form-actions">
            <div class="publish-container">
                <label for="is_published">Publikovat:</label>
                <input type="checkbox" name="is_published" id="is_published" value="1">
            </div>
            <button type="submit">Uložit</button>
        </div>
    </form>
</body>
</html>