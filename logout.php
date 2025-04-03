<?php
session_start();

// Vymazani session
$_SESSION = [];

// znicenci sessio
session_destroy();

// Přesměrovani na login
header("Location: login.php");
exit;
?>