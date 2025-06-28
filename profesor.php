<?php
include 'verificar_rol.php';
verificarRol('profesor');
include 'conexion.php';

$profesor_id = $_SESSION['id'];

// Obtener concurso
$concurso = $conexion->query("SELECT * FROM concurso WHERE id = 1")->fetch_assoc();
$estado_abierto = $concurso['estado'] === 'abierto';
$obra_obligatoria_valor = isset($concurso['obra_obligatoria']) ? htmlspecialchars($concurso['obra_obligatoria']) : '';

$mensaje = '';
// OMITIMOS la lógica de registro para centrarnos en el comportamiento visual

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $estado_abierto) {
    $nombre = trim($_POST['nombre']);
    $obra_libre = trim($_POST['obra_libre']);
    $obra_obligatoria = trim($_POST['obra_obligatoria']);


    $alumnos = [];
    foreach ($_POST as $key => $value) {
        if (preg_match('/^nombre(\d+)$/', $key, $matches)) {
            $i = $matches[1];
            $nombre_alumno = trim($_POST["nombre$i"]);
            $edad = (int) $_POST["edad$i"];
            $dni = trim($_POST["dni$i"]);
            $instrumento = trim($_POST["instrumento$i"]);

            if (!preg_match('/^\d{8}$/', $dni)) {
                $mensaje = "El DNI $dni no es válido.";
                break;
            }

            $alumnos[] = compact('nombre_alumno', 'edad', 'dni', 'instrumento');
        }
    }

    if (!$mensaje && count($alumnos) < 4) {
        $mensaje = "Debe registrar al menos 4 integrantes.";
    }

    if (!$mensaje) {
        // Verifica si algún alumno ya está registrado
        foreach ($alumnos as $a) {
            $dni_escaped = $conexion->real_escape_string($a['dni']);
            $res = $conexion->query("SELECT id FROM alumnos WHERE dni = '$dni_escaped'");
            if ($res->num_rows > 0) {
                $mensaje = "El alumno con DNI {$a['dni']} ya está en otro cuarteto.";
                break;
            }
        }
    }
    if (!$mensaje) {
        $instrumentos_validos = ['Violín', 'Viola', 'Violonchelo', 'Flauta', 'Arpa', 'Piano'];
        $conteo = [
            'violin' => 0,
            'viola' => 0,
            'chelo' => 0,
            'otros' => 0
        ];

        foreach ($alumnos as $a) {
            $inst = strtolower($a['instrumento']);

            if (!in_array($a['instrumento'], $instrumentos_validos)) {
                $mensaje = "Instrumento inválido: {$a['instrumento']}";
                break;
            }

            if ($inst === 'violín')
                $conteo['violin']++;
            elseif ($inst === 'viola')
                $conteo['viola']++;
            elseif ($inst === 'violonchelo')
                $conteo['chelo']++;
            elseif (in_array($inst, ['flauta', 'arpa', 'piano']))
                $conteo['otros']++;
        }

        // Validación de formación
        $total_violines = $conteo['violin'] + $conteo['otros'];
        if ($conteo['viola'] !== 1 || $conteo['chelo'] !== 1 || $total_violines !== 2) {
            $mensaje = "La formación debe tener 2 violines (o reemplazos), 1 viola y 1 chelo.";
        }
    }
    // Verificar si ya existe un cuarteto con ese nombre
    $nombre_escapado = $conexion->real_escape_string($nombre);
    $existe_nombre = $conexion->query("SELECT id FROM cuartetos WHERE nombre = '$nombre_escapado'")->num_rows > 0;

    if ($existe_nombre) {
        $mensaje = "Ya existe un cuarteto con ese nombre. Por favor elige otro.";
    }


    if (!$mensaje) {
        $stmt = $conexion->prepare("INSERT INTO cuartetos (nombre, obra_obligatoria, obra_libre, profesor_id, estado) VALUES (?, ?, ?, ?, 'en_concurso')");
        $stmt->bind_param("sssi", $nombre, $obra_obligatoria, $obra_libre, $profesor_id);
        if ($stmt->execute()) {
            $cuarteto_id = $stmt->insert_id;

            $stmt_alumno = $conexion->prepare("INSERT INTO alumnos (nombre, edad, dni, instrumento, cuarteto_id) VALUES (?, ?, ?, ?, ?)");
            foreach ($alumnos as $a) {
                $stmt_alumno->bind_param("sissi", $a['nombre_alumno'], $a['edad'], $a['dni'], $a['instrumento'], $cuarteto_id);
                $stmt_alumno->execute();
            }

            // Redirigir tras éxito para evitar reenvío del formulario
            header("Location: profesor.php?exito=1");
            exit;
        } else {
            $mensaje = "Error al registrar cuarteto: " . $stmt->error;
        }
        if (isset($_GET['exito'])) {
            $mensaje = 'Cuarteto registrado correctamente.';
        }

    }
}


// Obtener cuartetos
$cuartetos = $conexion->query("SELECT * FROM cuartetos WHERE profesor_id = $profesor_id");
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Mis Cuartetos</title>
    <link rel="stylesheet" href="style_prof.css">
</head>

<body>
    <div class="logout-container">
        <a href="logout.php" class="boton-rojo">Cerrar sesión</a>
    </div>

    <header>
        <h1>Bienvenido, <?= htmlspecialchars($_SESSION['nombre']) ?></h1>

        <p><strong>Estado del concurso:</strong> <?= strtoupper($concurso['estado']) ?> | <strong>Fase:</strong>
            <?= ucfirst($concurso['fase']) ?></p>
        
    </header>

    <?php if ($mensaje): ?>
        <div class="mensaje" id="mensaje"><?= htmlspecialchars($mensaje) ?></div>
    <?php endif; ?>

    <?php if ($estado_abierto): ?>
        <div class="container">
            <div class="formulario">
                <h2>Registrar Nuevo Cuarteto</h2>
                <form method="post" id="form-cuarteto">
                    <div class="campo">
                        <label>Nombre del cuarteto:</label>
                        <input type="text" name="nombre" id="nombre" required>
                        <span id="nombre-error" class="mensaje-error"></span>

                    </div>
                    <div class="campo">
                        <label>Obra obligatoria:</label>
                        <input type="text" name="obra_obligatoria" id="obra_obligatoria" required>
                        
                    </div>
                    <div class="campo">
                        <label>Obra libre:</label>
                        <input type="text" name="obra_libre" id="obra_libre" required>
                    </div>

                    <button type="button" id="btn-agregar-integrante" class="boton" disabled>Añadir integrante</button>

                    <div id="integrantes-container"></div>
                    <div id="error-formacion" class="mensaje-error"></div>


                    <div id="botones-finales" style="display:none;">
                        <button type="button" id="btn-agregar-suplente" class="boton">Añadir suplente</button>
                        <button type="submit" class="boton">Registrar Cuarteto</button>
                    </div>
                </form>
            </div>

            <div class="panel-tabla">
                <h2>Mis Cuartetos</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Obra Obligatoria</th>
                            <th>Obra Libre</th>
                            <th>Estado</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($c = $cuartetos->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($c['nombre']) ?></td>
                                <td><?= htmlspecialchars($c['obra_obligatoria']) ?></td>
                                <td><?= htmlspecialchars($c['obra_libre']) ?></td>
                                <<td>
                                    <?php
                                    switch ($c['estado']) {
                                        case 'en_concurso':
                                            echo 'En Concurso';
                                            break;
                                        case 'eliminado':
                                            echo 'Eliminado';
                                            break;
                                        case 'pendiente':
                                            echo 'Pendiente';
                                            break;
                                        default:
                                            echo ucfirst($c['estado']);
                                    }
                                    ?>
                                    </td>

                                    <td>
                                        <?php if ($estado_abierto): ?>
                                            <a href="editar.php?id=<?= $c['id'] ?>" class="boton">Editar</a>
                                        <?php else: ?>
                                            <em>No editable</em>
                                        <?php endif; ?>
                                    </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <template id="integrante-template">
        <fieldset class="integrante-card">
            <legend>Integrante <span class="num"></span></legend>
            <input type="text" name="nombre__INDEX__" placeholder="Nombre completo" required>
            <input type="number" name="edad__INDEX__" placeholder="Edad" min="1" required>
            <input type="text" name="dni__INDEX__" class="dni-input" placeholder="DNI" pattern="\d{8}" required>
            <span class="dni-error" style="color: red;"></span>

            <select name="instrumento__INDEX__" required>
                <option value="">Seleccione instrumento</option>
                <option value="Violín">Violín</option>
                <option value="Viola">Viola</option>
                <option value="Violonchelo">Violonchelo</option>
                <option value="Flauta">Flauta</option>
                <option value="Arpa">Arpa</option>
                <option value="Piano">Piano</option>
            </select>
        </fieldset>
    </template>

    <script src="mesaje.js"></script>

</body>

</html>