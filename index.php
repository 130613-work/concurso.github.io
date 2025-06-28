<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Login Concurso</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <h1>CONCURSO DE CUARTETOS</h1>
    <h2>Iniciar Sesión</h2>

    <form id="loginForm" method="POST" action="procesar_login.php" autocomplete="off">
        <label for="usuario">Usuario</label><br>
        <input type="text" id="usuario" name="usuario" placeholder="Ingresa tu usuario" required><br><br>

        <label for="clave">Contraseña</label><br>
        <div class="campo-password">
            <input type="password" id="clave" name="clave" placeholder="Ingresa tu contraseña" required>
            <span class="toggle-password" id="togglePassword">&#128065;</span>
        </div>
        <span id="errorClave" class="error"></span><br><br>

        <!-- Mensaje de error general antes del botón -->
        <span id="errorGeneral" class="error arriba-boton"></span>

        <button type="submit">Entrar</button>
    </form>

    <script src="login.js"></script>
</body>
</html>
