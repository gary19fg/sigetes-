<?php
session_start();
include 'conexion.php';

// Validar datos
$nombre = trim($_POST['nombre']);
$apellido_paterno = trim($_POST['apellido_paterno']);
$apellido_materno = trim($_POST['apellido_materno']);
$correo = trim($_POST['correo']);
$contrasena = trim($_POST['contrasena']);
$tipo_usuario = $_POST['tipo_usuario'] ?? '';

$errores = [];

// Validaciones
if (strlen($nombre) < 2 || strlen($nombre) > 30)
    $errores[] = "Nombre inválido.";
if (strlen($apellido_paterno) < 2 || strlen($apellido_paterno) > 30)
    $errores[] = "Apellido paterno inválido.";
if (strlen($apellido_materno) < 2 || strlen($apellido_materno) > 30)
    $errores[] = "Apellido materno inválido.";
if (!filter_var($correo, FILTER_VALIDATE_EMAIL))
    $errores[] = "Correo inválido.";
if (strlen($contrasena) < 6 || strlen($contrasena) > 255)
    $errores[] = "Contraseña debe tener entre 6 y 255 caracteres.";
if ($tipo_usuario !== 'alumno' && $tipo_usuario !== 'administrativo')
    $errores[] = "Tipo de usuario inválido.";

if (!empty($errores)) {
    echo "<p><strong>Errores encontrados:</strong></p>";
    echo "<ul>";
    foreach ($errores as $error) {
        echo "<li>$error</li>";
    }
    echo "</ul>";
    echo "<button onclick='window.history.back()'>Volver</button>";
    exit;
}

// Insertar en tabla usuarios
$stmt = $conn->prepare("INSERT INTO usuarios (nombre, correo, contrasena, tipo_usuario) VALUES (?, ?, ?, ?)");
$stmt->bind_param("ssss", $nombre_completo, $correo, $contrasena, $tipo_usuario);

$nombre_completo = "$nombre $apellido_paterno $apellido_materno";

if (!$stmt->execute()) {
    die("Error al registrar usuario: " . $conn->error);
}

// Si es alumno, registrar en tabla alumno
if ($tipo_usuario === 'alumno') {
    $id_alumno = trim($_POST['id_alumno']);
    $semestre_actual = intval($_POST['semestre_actual']);
    $estatus = trim($_POST['estatus']);

    if (empty($id_alumno) || strlen($id_alumno) > 20)
        die("ID de alumno inválido.");

    if ($semestre_actual < 1 || $semestre_actual > 12)
        die("Semestre inválido.");

    if (empty($estatus) || strlen($estatus) > 20)
        die("Estatus inválido.");

    $stmt_alumno = $conn->prepare("INSERT INTO alumno (id_alumno, nombre, apellido_paterno, apellido_materno, correo, semestre_actual, estatus) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt_alumno->bind_param("sssssis", $id_alumno, $nombre, $apellido_paterno, $apellido_materno, $correo, $semestre_actual, $estatus);

    if (!$stmt_alumno->execute())
        die("Error al registrar alumno: " . $conn->error);

    $stmt_alumno->close();
}

// Si es administrativo, registrar en tabla administrativos
if ($tipo_usuario === 'administrativo') {
    $id_administrativo = trim($_POST['id_administrativo']);
    $cargo = trim($_POST['cargo']);
    $carrera = trim($_POST['carrera']);

    if (empty($id_administrativo) || strlen($id_administrativo) > 20)
        die("ID de administrativo inválido.");

    if (empty($cargo) || strlen($cargo) > 50)
        die("Cargo inválido.");

    if (empty($carrera) || strlen($carrera) > 100)
        die("Carrera inválida.");

    $stmt_admin = $conn->prepare("INSERT INTO administrativos (id_administrativos, nombre, apellido_paterno, apellido_materno, cargo, carrera, correo) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt_admin->bind_param("sssssss", $id_administrativo, $nombre, $apellido_paterno, $apellido_materno, $cargo, $carrera, $correo);

    if (!$stmt_admin->execute())
        die("Error al registrar administrativo: " . $conn->error);

    $stmt_admin->close();
}

$stmt->close();
$conn->close();

echo "<p>Usuario registrado correctamente.</p>";
header("Refresh:2; url=login.html");
exit();
?>