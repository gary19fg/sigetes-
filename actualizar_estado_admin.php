<?php
session_start();
if (!isset($_SESSION['id']) || $_SESSION['tipo_usuario'] != 'administrativo') {
    header("Location: login.php");
    exit();
}

include 'conexion.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['id_alumno'], $_POST['reticula'], $_POST['nuevo_estado'])) {
    $id_alumno = $_POST['id_alumno'];
    $reticula = $_POST['reticula'];
    $nuevo_estado = $_POST['nuevo_estado'];

    $stmt = $conn->prepare("UPDATE estado_materia SET estado = ? WHERE id_alumno = ? AND reticula = ?");
    $stmt->bind_param("sss", $nuevo_estado, $id_alumno, $reticula);

    if ($stmt->execute()) {
        echo "Estado actualizado correctamente.";
    } else {
        echo "Error al actualizar el estado.";
    }

    $stmt->close();
    $conn->close();

    header("Refresh:2; url=panel_admin.php");
}
?>