<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['id']) || $_SESSION['tipo_usuario'] != 'administrativo') {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

include 'conexion.php';

// Obtener datos JSON
$input = json_decode(file_get_contents("php://input"), true);

if (!isset($input['estados']) || !is_array($input['estados'])) {
    echo json_encode(['success' => false, 'error' => 'Datos inválidos']);
    exit;
}

$estados = $input['estados'];
$error = false;

foreach ($estados as $id_alumno => $materias) {
    foreach ($materias as $reticula => $estado) {
        $id_alumno = $conn->real_escape_string($id_alumno);
        $reticula = $conn->real_escape_string($reticula);
        $estado = $conn->real_escape_string($estado);

        // Revisar si ya existe
        $check = $conn->query("SELECT * FROM estado_materia WHERE id_alumno='$id_alumno' AND reticula='$reticula'");
        if ($check->num_rows > 0) {
            // Actualizar
            $update = $conn->query("UPDATE estado_materia SET estado='$estado' WHERE id_alumno='$id_alumno' AND reticula='$reticula'");
            if (!$update) $error = true;
        } else {
            // Insertar
            $insert = $conn->query("INSERT INTO estado_materia (id_alumno, reticula, estado) VALUES ('$id_alumno', '$reticula', '$estado')");
            if (!$insert) $error = true;
        }
    }
}

if ($error) {
    echo json_encode(['success' => false, 'error' => 'Error en la base de datos']);
} else {
    echo json_encode(['success' => true]);
}
?>
