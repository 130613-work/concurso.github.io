<?php 
include 'verificar_rol.php';
verificarRol('profesor');
include 'conexion.php';

$profesor_id = $_SESSION['id'];
$cuarteto_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Verifica que el cuarteto pertenezca al profesor
$cuarteto = $conexion->query("SELECT * FROM cuartetos WHERE id = $cuarteto_id AND profesor_id = $profesor_id")->fetch_assoc();
if (!$cuarteto) {
    die("No tienes permiso para editar este cuarteto.");
}

// Verificar si el concurso está abierto
$concurso = $conexion->query("SELECT estado FROM concurso LIMIT 1")->fetch_assoc();
if (!$concurso || strtolower($concurso['estado']) !== 'abierto') {
    die("El concurso está cerrado. No se pueden editar los datos del cuarteto.");
}

$mensaje = '';
$alumnos = $conexion->query("SELECT * FROM alumnos WHERE cuarteto_id = $cuarteto_id")->fetch_all(MYSQLI_ASSOC);
$obra_obligatoria = $cuarteto['obra_obligatoria'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre']);
    $obra_libre = trim($_POST['obra_libre']);
    $instrumentos_validos = ['Violín', 'Viola', 'Violonchelo', 'Flauta', 'Arpa', 'Piano'];

    $nuevos_alumnos = [];
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

            if (!in_array($instrumento, $instrumentos_validos)) {
                $mensaje = "Instrumento no válido: $instrumento";
                break;
            }

            $nuevos_alumnos[] = compact('nombre_alumno', 'edad', 'dni', 'instrumento');
        }
    }

    if (!$mensaje && count($nuevos_alumnos) < 4) {
        $mensaje = "Debe haber al menos 4 integrantes.";
    }

    if (!$mensaje) {
        $conteo = ['violin' => 0, 'viola' => 0, 'chelo' => 0, 'otros' => 0];
        foreach ($nuevos_alumnos as $a) {
            $inst = strtolower($a['instrumento']);
            if ($inst === 'violín') $conteo['violin']++;
            elseif ($inst === 'viola') $conteo['viola']++;
            elseif ($inst === 'violonchelo') $conteo['chelo']++;
            elseif (in_array($inst, ['flauta', 'arpa', 'piano'])) $conteo['otros']++;
        }

        $total_violines = $conteo['violin'] + $conteo['otros'];
        if ($conteo['viola'] !== 1 || $conteo['chelo'] !== 1 || $total_violines !== 2) {
            $mensaje = "La formación debe tener 2 violines (o reemplazos), 1 viola y 1 chelo.";
        }
    }

    if (!$mensaje) {
        // Verificar que los nuevos DNIs no estén en otro cuarteto
        foreach ($nuevos_alumnos as $a) {
            $dni = $conexion->real_escape_string($a['dni']);
            $res = $conexion->query("SELECT id FROM alumnos WHERE dni = '$dni' AND cuarteto_id != $cuarteto_id");
            if ($res->num_rows > 0) {
                $mensaje = "El alumno con DNI {$a['dni']} ya pertenece a otro cuarteto.";
                break;
            }
        }
    }

    if (!$mensaje) {
        $stmt = $conexion->prepare("UPDATE cuartetos SET nombre = ?, obra_libre = ? WHERE id = ?");
        $stmt->bind_param("ssi", $nombre, $obra_libre, $cuarteto_id);
        $stmt->execute();

        $conexion->query("DELETE FROM alumnos WHERE cuarteto_id = $cuarteto_id");

        $stmt_alumno = $conexion->prepare("INSERT INTO alumnos (nombre, edad, dni, instrumento, cuarteto_id) VALUES (?, ?, ?, ?, ?)");
        foreach ($nuevos_alumnos as $a) {
            $stmt_alumno->bind_param("sissi", $a['nombre_alumno'], $a['edad'], $a['dni'], $a['instrumento'], $cuarteto_id);
            $stmt_alumno->execute();
        }

        header("Location: profesor.php?editado=1");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Editar Cuarteto</title>
    <link rel="stylesheet" href="style_prof.css">
</head>

<body>
    <div class="logout-container">
        <a href="profesor.php" class="boton">← Volver</a>
    </div>

    <header>
        <h1>Editar Cuarteto</h1>
    </header>

    <?php if ($mensaje): ?>
        <div class="mensaje" id="mensaje"><?= htmlspecialchars($mensaje) ?></div>
    <?php endif; ?>

    <div class="container">
        <form method="post" id="form-cuarteto">
            <div class="campo">
                <label>Nombre del cuarteto:</label>
                <input type="text" name="nombre" id="nombre" value="<?= htmlspecialchars($cuarteto['nombre']) ?>"
                    required>
            </div>
            <div class="campo">
                <label>Obra obligatoria:</label>
                <input type="text" name="obra_obligatoria" value="<?= htmlspecialchars($obra_obligatoria) ?>" readonly>
            </div>
            <div class="campo">
                <label>Obra libre:</label>
                <input type="text" name="obra_libre" id="obra_libre"
                    value="<?= htmlspecialchars($cuarteto['obra_libre']) ?>" required>
            </div>

            <div id="integrantes-container"></div>

            <div id="botones-finales" style="display:block;">
                <button type="button" id="btn-agregar-suplente" class="boton" style="display:none;">Añadir
                    suplente</button>
                <button type="submit" class="boton">Guardar Cambios</button>
            </div>
        </form>
    </div>

    <template id="integrante-template">
        <fieldset class="integrante-card">
            <legend>Integrante <span class="num"></span></legend>
            <input type="text" name="nombre__INDEX__" placeholder="Nombre completo" required>
            <input type="number" name="edad__INDEX__" placeholder="Edad" min="1" required>
            <input type="text" name="dni__INDEX__" placeholder="DNI" pattern="\d{8}" required>
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

    <script>
        const integrantesContainer = document.getElementById('integrantes-container');
        const template = document.getElementById('integrante-template').content;
        const alumnosExistentes = <?= json_encode($alumnos) ?>;
        console.log(alumnosExistentes);

        const btnSuplente = document.getElementById('btn-agregar-suplente');

        alumnosExistentes.forEach((a, i) => {
            const clone = document.importNode(template, true);
            clone.querySelector('legend .num').textContent = i < 4 ? (i + 1) : 'Suplente';

            const inputNombre = clone.querySelector('[name="nombre__INDEX__"]');
            inputNombre.name = "nombre" + i;
            inputNombre.value = a.nombre;

            const inputEdad = clone.querySelector('[name="edad__INDEX__"]');
            inputEdad.name = "edad" + i;
            inputEdad.value = a.edad;

            const inputDNI = clone.querySelector('[name="dni__INDEX__"]');
            inputDNI.name = "dni" + i;
            inputDNI.value = a.dni;

            const selectInstrumento = clone.querySelector('[name="instrumento__INDEX__"]');
            selectInstrumento.name = "instrumento" + i;
            [...selectInstrumento.options].forEach(opt => {
                if (opt.value === a.instrumento) opt.selected = true;
            });

            const fieldset = clone.querySelector('fieldset');
            fieldset.style.opacity = 0;
            integrantesContainer.appendChild(clone);
            setTimeout(() => fieldset.style.opacity = 1, 10);


        });

        // Si hay menos de 5 integrantes, mostrar botón para agregar suplente
        if (alumnosExistentes.length < 5) {
            btnSuplente.style.display = 'inline-block';
            btnSuplente.addEventListener('click', () => {
                const i = integrantesContainer.children.length;
                const clone = document.importNode(template, true);
                clone.querySelector('legend .num').textContent = "Suplente";

                clone.querySelector(`[name="nombre__INDEX__"]`).name = "nombre" + i;
                clone.querySelector(`[name="edad__INDEX__"]`).name = "edad" + i;
                clone.querySelector(`[name="dni__INDEX__"]`).name = "dni" + i;
                clone.querySelector(`[name="instrumento__INDEX__"]`).name = "instrumento" + i;

                const fieldset = clone.querySelector('fieldset');
                fieldset.style.opacity = 0;
                integrantesContainer.appendChild(clone);
                setTimeout(() => fieldset.style.opacity = 1, 10);
                btnSuplente.style.display = 'none';
            });
        }
    </script>
</body>

</html>