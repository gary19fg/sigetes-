<?php
session_start();
include 'conexion.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $correo = $_POST['correo'];
    $contrasena = $_POST['contrasena'];

    // Consulta segura con prepared statements
    $stmt = $conn->prepare("SELECT id, correo, contrasena, tipo_usuario FROM usuarios WHERE correo = ?");
    $stmt->bind_param("s", $correo);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();

        // Comparar contraseñas directamente
        if ($contrasena === $user['contrasena']) {
            // Guardar sesión
            $_SESSION['id'] = $user['id'];
            $_SESSION['correo'] = $user['correo'];
            $_SESSION['tipo_usuario'] = $user['tipo_usuario'];

            // Redirigir según el tipo de usuario
            if ($user['tipo_usuario'] === 'administrativo') {
                header("Location: panel_admin.php");
                exit();
            } else if ($user['tipo_usuario'] === 'alumno') {
                header("Location: panel_alumno.php");
                exit();
            }
        } else {
            echo "Contraseña incorrecta.";
        }
    } else {
        echo "Usuario no encontrado.";
    }

    $stmt->close();
    $conn->close();
}
?>