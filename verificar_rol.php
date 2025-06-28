<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function verificarRol($rolRequerido) {
    if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== $rolRequerido) {
        header('Location: index.php');
        exit;
    }
}
?>
