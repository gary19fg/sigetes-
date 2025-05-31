<?php
session_start();
if (!isset($_SESSION['id']) || $_SESSION['tipo_usuario'] != 'administrativo') {
    echo json_encode(['success' => false, 'error' => 'Acceso denegado']);
    exit();
}

include 'conexion.php';
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['alumnos']) || !is_array($data['alumnos']) || !isset($data['semestre'])) {
    echo json_encode(['success' => false, 'error' => 'Datos inválidos']);
    exit();
}

$alumnos = $data['alumnos'];
$semestre = intval($data['semestre']);

$stmt = $conn->prepare("UPDATE alumno SET semestre_actual = ? WHERE id_alumno = ?");
$stmt->bind_param("ii", $semestre, $id_alumno);

foreach ($alumnos as $id_alumno) {
    $stmt->bind_param("ii", $semestre, $id_alumno);
    $stmt->execute();
}

$stmt->close();
$conn->close();

echo json_encode(['success' => true]);