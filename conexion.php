<?php
$host = "localhost";
$usuario = "root";
$contrasena = ""; // Solo déjalo vacío si usas XAMPP sin contraseña
$base_datos = "concurso_web";

$conexion = new mysqli($host, $usuario, $contrasena, $base_datos);

if ($conexion->connect_error) {
    die("Conexión fallida: " . $conexion->connect_error);
}
?>
