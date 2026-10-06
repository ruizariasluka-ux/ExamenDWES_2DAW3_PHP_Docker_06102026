<?php
// Conexión con MySQL. Cambia estos datos si tu usuario tiene contraseña.
$conexion = new mysqli('localhost', 'root', '', 'CAE');
if ($conexion->connect_error) {
    die('Error de conexión: ' . htmlspecialchars($conexion->connect_error));
}
$conexion->set_charset('utf8mb4');

// Textos que se mostrarán para cada valor guardado en profesion.
$profesiones = [
    'soldadura' => 'Soldadura',
    'informatica' => 'Informática',
    'socio' => 'Asistencia Sociosanitaria'
];

// El formulario envía los datos con POST.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $apellidos = trim($_POST['apellidos'] ?? '');
    $dni = strtoupper(trim($_POST['dni'] ?? ''));
    $f_nac = $_POST['f_nac'] ?? '';
    $tlf = trim($_POST['tlf'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $profesion = $_POST['profesion'] ?? '';
    $jornadaParcial = $_POST['jornadaParcial'] ?? '';
    $idiomas = $_POST['idiomas'] ?? [];

    // Comprobaciones básicas de los datos obligatorios.
    if ($nombre === '' || $apellidos === '' || $dni === '' || $f_nac === '' || $tlf === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die('Completa todos los campos e introduce un email válido.');
    }
    if (!isset($profesiones[$profesion]) || !in_array($jornadaParcial, ['0', '1'], true)) {
        die('Selecciona una profesión y una jornada válidas.');
    }
    if (!is_array($idiomas)) {
        $idiomas = [];
    }
    $idiomas = array_intersect($idiomas, ['Euskera', 'Inglés']);
    $idiomasTexto = implode(', ', $idiomas);

    // La consulta preparada evita insertar los datos directamente en el SQL.
    $consulta = $conexion->prepare(
        'INSERT INTO SOLICITUD (nombre, apellidos, dni, f_nac, tlf, email, profesion, jornadaParcial, idiomas)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $consulta->bind_param('sssssssis', $nombre, $apellidos, $dni, $f_nac, $tlf, $email, $profesion, $jornadaParcial, $idiomasTexto);
    if (!$consulta->execute()) {
        die('No se pudo guardar la solicitud. Comprueba que el DNI no esté repetido.');
    }

    // Después de guardar, mostramos la tabla de la profesión elegida.
    header('Location: tabla.php?tipo=' . urlencode($profesion) . '&guardada=1');
    exit;
}

// Los botones de la página principal envían el tipo por GET.
$tipo = $_GET['tipo'] ?? '';
if (!isset($profesiones[$tipo])) {
    die('Selecciona una profesión desde la página principal.');
}

$consulta = $conexion->prepare(
    'SELECT nombre, apellidos, dni, f_nac, tlf, email, profesion, jornadaParcial, idiomas
     FROM SOLICITUD WHERE profesion = ? ORDER BY apellidos, nombre'
);
$consulta->bind_param('s', $tipo);
$consulta->execute();
$solicitudes = $consulta->get_result();
?>
<!doctype html>
<html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Solicitudes de <?= htmlspecialchars($profesiones[$tipo]) ?></title>
        <link rel="icon" type="image/png" sizes="32x32" href="../imagenes/favicon.jpeg">
        <link rel="stylesheet" type="text/css" href="../estilos/estilos.css">
    </head>
    <body>
        <h1>Centro de Ayuda al Empleo</h1>
        <h2>Solicitudes de <?= htmlspecialchars($profesiones[$tipo]) ?></h2>

        <?php if (isset($_GET['guardada'])): ?>
            <p>Solicitud guardada correctamente.</p>
        <?php endif; ?>

        <?php if ($solicitudes->num_rows === 0): ?>
            <p>No hay solicitudes para esta profesión.</p>
        <?php else: ?>
            <table>
                <tr>
                    <th>Nombre</th><th>Apellidos</th><th>DNI</th><th>Fecha de nacimiento</th>
                    <th>Teléfono</th><th>Email</th><th>Profesión</th><th>Jornada</th><th>Idiomas</th>
                </tr>
                <?php while ($fila = $solicitudes->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($fila['nombre']) ?></td>
                        <td><?= htmlspecialchars($fila['apellidos']) ?></td>
                        <td><?= htmlspecialchars($fila['dni']) ?></td>
                        <td><?= htmlspecialchars($fila['f_nac']) ?></td>
                        <td><?= htmlspecialchars($fila['tlf']) ?></td>
                        <td><?= htmlspecialchars($fila['email']) ?></td>
                        <td><?= htmlspecialchars($profesiones[$fila['profesion']]) ?></td>
                        <td><?= $fila['jornadaParcial'] ? 'Parcial' : 'Completa' ?></td>
                        <td><?= htmlspecialchars($fila['idiomas'] ?: 'Ninguno') ?></td>
                    </tr>
                <?php endwhile; ?>
            </table>
        <?php endif; ?>

        <button type="button" onclick="location.href='../html/index.html'">Volver al formulario</button>
    </body>
</html>
<?php $conexion->close(); ?>
