<?php 
session_start();
header('Content-Type: text/html; charset=utf-8');

// Připojení
require_once 'db_connect.php';

$error = "";

// Rate limiting
$max_attempts = 5;             // maximální počet pokusů
$lockout_time = 15 * 60;       // doba uzamčení v sekundach
$ip_address = $_SERVER['REMOTE_ADDR'];

try {
    // Kontrola jestli je IP adresa uzamčena
    $stmt = $conn->prepare("SELECT attempts, last_attempt FROM login_attempts WHERE ip_address = ?");
    $stmt->bind_param("s", $ip_address);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        $stmt->bind_result($attempts, $last_attempt);
        $stmt->fetch();
        
        // Kontrola jestlli vypršel čas uzamčení
        if ($attempts >= $max_attempts && (time() - $last_attempt) < $lockout_time) {
            $remaining = $lockout_time - (time() - $last_attempt);
            $error = "Příliš mnoho pokusů o přihlášení. Zkuste to znovu za " . ceil($remaining / 60) . " minut.";
            // Vypíše chybovou hlášku a ukončí přihlášení
        } elseif ($attempts >= $max_attempts) {
            // Reset počtu pokusů 
            $stmt = $conn->prepare("UPDATE login_attempts SET attempts = 0, last_attempt = ? WHERE ip_address = ?");
            $time = time();
            $stmt->bind_param("is", $time, $ip_address);
            $stmt->execute();
        }
    }
    
    // zpracování loginu a hesla
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($error)) {
        $formUsername = $_POST['username'] ?? '';
        $formPassword = $_POST['password'] ?? '';

        // najít uživatele podle username
        $stmt = $conn->prepare("SELECT id, username, password_hash FROM users WHERE username = ?");
        $stmt->bind_param("s", $formUsername);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            // když ho najdu
            $stmt->bind_result($userId, $dbUsername, $dbPasswordHash);
            $stmt->fetch();

            // ověření hesla
            if (password_verify($formPassword, $dbPasswordHash)) {
                // přihlášení úspěšné - resetovat počet pokusů
                $stmt = $conn->prepare("DELETE FROM login_attempts WHERE ip_address = ?");
                $stmt->bind_param("s", $ip_address);
                $stmt->execute();
                
                $_SESSION['logged_in'] = true;
                $_SESSION['username'] = $dbUsername;
                
                // přesměrování do administrace
                header("Location: admin.php");
                exit;
            } else {
                // špatné heslo
                incrementLoginAttempts($conn, $ip_address);
                $error = "Neplatné přihlašovací údaje.";
            }
        } else {
            // uživatel neexistuje 
            incrementLoginAttempts($conn, $ip_address);
            $error = "Uživatelské jméno nebylo nalezeno.";
        }
    }
} catch (Exception $e) {
    $error = "Nastala chyba: " . $e->getMessage();
}

// Funkce pro zvýšení počtu neúspěšných pokusů
function incrementLoginAttempts($conn, $ip_address) {
    $stmt = $conn->prepare("SELECT attempts FROM login_attempts WHERE ip_address = ?");
    $stmt->bind_param("s", $ip_address);
    $stmt->execute();
    $stmt->store_result();
    
    $time = time();
    
    if ($stmt->num_rows > 0) {
        $stmt = $conn->prepare("UPDATE login_attempts SET attempts = attempts + 1, last_attempt = ? WHERE ip_address = ?");
        $stmt->bind_param("is", $time, $ip_address);
    } else {
        $stmt = $conn->prepare("INSERT INTO login_attempts (ip_address, attempts, last_attempt) VALUES (?, 1, ?)");
        $stmt->bind_param("si", $ip_address, $time);
    }
    $stmt->execute();
}

// uzavření připojení
if (isset($conn)) {
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <title>Přihlášení do administrace</title>
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
            max-width: 400px;
            margin: 0 auto;
        }

        label {
            display: block;
            margin-top: 20px;
            font-weight: bold;
            color: #2D3436;
        }

        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            box-sizing: border-box;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        .error {
            color: red;
            font-weight: bold;
            margin-top: 15px;
            text-align: center;
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

        .center {
            text-align: center;
        }
    </style>
</head>
<body>
    <h1>Přihlášení do administrace</h1>

    <?php if (!empty($error)): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="post" action="">
        <label for="username">Uživatelské jméno:</label>
        <input type="text" name="username" id="username" required>

        <label for="password">Heslo:</label>
        <input type="password" name="password" id="password" required>

        <div class="center">
            <button type="submit">Přihlásit se</button>
        </div>
    </form>
</body>
</html>