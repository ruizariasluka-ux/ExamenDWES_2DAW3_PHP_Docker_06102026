<?php
// Conectamos con la base de datos CAE.
$conexion = new mysqli('localhost', 'root', '', 'CAE');

if ($conexion->connect_error) {
    die('No se pudo conectar con la base de datos.');
}

$conexion->set_charset('utf8mb4');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = trim($_POST['nombre']);
    $apellidos = trim($_POST['apellidos']);
    $dni = strtoupper(trim($_POST['dni']));
    $fecha = $_POST['f_nac'];
    $telefono = trim($_POST['tlf']);
    $email = trim($_POST['email']);
    $profesion = $_POST['profesion'];
    $jornada = $_POST['jornadaParcial'];
    $idiomas = '';

    if (isset($_POST['idiomas'])) {
        $idiomas = implode(', ', $_POST['idiomas']);
    }

    if ($nombre == '' || $apellidos == '' || $dni == '' || $fecha == '' || $telefono == '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die('Rellena todos los campos e introduce un email válido.');
    }

    if ($profesion != 'soldadura' && $profesion != 'informatica' && $profesion != 'socio') {
        die('Elige una profesión válida.');
    }

    if ($jornada != '0' && $jornada != '1') {
        die('Elige una jornada válida.');
    }

    $consulta = $conexion->prepare('INSERT INTO SOLICITUD (nombre, apellidos, dni, f_nac, tlf, email, profesion, jornadaParcial, idiomas) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $consulta->bind_param('sssssssis', $nombre, $apellidos, $dni, $fecha, $telefono, $email, $profesion, $jornada, $idiomas);

    if (!$consulta->execute()) {
        die('No se pudo guardar. Puede que el DNI ya exista.');
    }

    header('Location: tabla.php?tipo=' . $profesion . '&guardada=1');
    exit;
}

// Elegimos el nombre que se verá en la página.
$tipo = $_GET['tipo'];

if ($tipo == 'soldadura') {
    $nombreProfesion = 'Soldadura';
} elseif ($tipo == 'informatica') {
    $nombreProfesion = 'Informática';
} elseif ($tipo == 'socio') {
    $nombreProfesion = 'Asistencia Sociosanitaria';
} else {
    die('Elige una profesión desde la página principal.');
}

$consulta = $conexion->prepare('SELECT * FROM SOLICITUD WHERE profesion = ? ORDER BY apellidos');
$consulta->bind_param('s', $tipo);
$consulta->execute();
$solicitudes = $consulta->get_result();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Solicitudes de <?php echo htmlspecialchars($nombreProfesion); ?></title>
    <link rel="stylesheet" href="../estilos/estilos.css">
</head>
<body>
    <h1>Centro de Ayuda al Empleo</h1>
    <h2>Solicitudes de <?php echo htmlspecialchars($nombreProfesion); ?></h2>

    <?php if (isset($_GET['guardada'])) { ?>
        <p>Solicitud guardada correctamente.</p>
    <?php } ?>

    <?php if ($solicitudes->num_rows == 0) { ?>
        <p>No hay solicitudes para esta profesión.</p>
    <?php } else { ?>
        <table>
            <tr>
                <th>Nombre</th>
                <th>Apellidos</th>
                <th>DNI</th>
                <th>Fecha de nacimiento</th>
                <th>Teléfono</th>
                <th>Email</th>
                <th>Profesión</th>
                <th>Jornada</th>
                <th>Idiomas</th>
            </tr>
            <?php while ($fila = $solicitudes->fetch_assoc()) { ?>
                <tr>
                    <td><?php echo htmlspecialchars($fila['nombre']); ?></td>
                    <td><?php echo htmlspecialchars($fila['apellidos']); ?></td>
                    <td><?php echo htmlspecialchars($fila['dni']); ?></td>
                    <td><?php echo htmlspecialchars($fila['f_nac']); ?></td>
                    <td><?php echo htmlspecialchars($fila['tlf']); ?></td>
                    <td><?php echo htmlspecialchars($fila['email']); ?></td>
                    <td><?php echo htmlspecialchars($nombreProfesion); ?></td>
                    <td><?php echo $fila['jornadaParcial'] ? 'Parcial' : 'Completa'; ?></td>
                    <td><?php echo htmlspecialchars($fila['idiomas'] == '' ? 'Ninguno' : $fila['idiomas']); ?></td>
                </tr>
            <?php } ?>
        </table>
    <?php } ?>

    <p><a href="../html/index.html">Volver al formulario</a></p>
</body>
</html>
<?php $conexion->close(); ?>
