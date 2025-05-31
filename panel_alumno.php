<?php
session_start();
if (!isset($_SESSION['id']) || $_SESSION['tipo_usuario'] != 'alumno') {
    header("Location: login.php");
    exit();
}

include 'conexion.php';

$id_usuario = $_SESSION['id'];

// Obtener correo del usuario
$stmt = $conn->prepare("SELECT correo FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$result_id = $stmt->get_result();
$row_id = $result_id->fetch_assoc();
$correo_usuario = $row_id['correo'];

// Obtener información del alumno
$stmt = $conn->prepare("SELECT id_alumno, nombre, semestre_actual FROM alumno WHERE correo = ?");
$stmt->bind_param("s", $correo_usuario);
$stmt->execute();
$result_alumno = $stmt->get_result();
$alumno = $result_alumno->fetch_assoc();

if (!$alumno) {
    die("Error: No se encontró un alumno asociado.");
}

$id_alumno = $alumno['id_alumno'];
$nombre_completo = $alumno['nombre'];
$semestre_actual = $alumno['semestre_actual'];

$mensaje = "";

// Actualizar estado
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reticula'], $_POST['nuevo_estado'])) {
    $reticula = $_POST['reticula'];
    $nuevo_estado = $_POST['nuevo_estado'];
    $estados_permitidos = ['', '0', 'OR', 'OE', '1', 'R', 'RR'];

    if (!in_array($nuevo_estado, $estados_permitidos)) {
        $mensaje = "Estado no válido.";
    } else {
        $stmt_update = $conn->prepare("UPDATE estado_materia SET estado = ? WHERE id_alumno = ? AND reticula = ?");
        $stmt_update->bind_param("sss", $nuevo_estado, $id_alumno, $reticula);
        $mensaje = $stmt_update->execute() ? "Estado actualizado correctamente." : "Error al actualizar.";
        $stmt_update->close();
    }
}

// Agregar todas las materias al alumno (si no existen aún)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['agregar_materias'])) {
    $stmt_check = $conn->prepare("SELECT COUNT(*) as total FROM estado_materia WHERE id_alumno = ?");
    $stmt_check->bind_param("s", $id_alumno);
    $stmt_check->execute();
    $row_check = $stmt_check->get_result()->fetch_assoc();
    $stmt_check->close();

    if ($row_check['total'] > 0) {
        $mensaje = "Ya tienes materias registradas.";
    } else {
        $materias = $conn->query("SELECT reticula FROM materia");
        $insert_stmt = $conn->prepare("INSERT INTO estado_materia (id_alumno, reticula, estado) VALUES (?, ?, '')");

        while ($materia = $materias->fetch_assoc()) {
            $reticula = $materia['reticula'];
            $insert_stmt->bind_param("ss", $id_alumno, $reticula);
            $insert_stmt->execute();
        }

        $insert_stmt->close();
        $mensaje = "Materias agregadas correctamente.";
    }
}

// Obtener materias del alumno hasta su semestre actual
$stmt_materias = $conn->prepare("
    SELECT m.reticula, m.nombre, m.semestre, e.estado 
    FROM estado_materia e 
    JOIN materia m ON e.reticula = m.reticula 
    WHERE e.id_alumno = ? AND m.semestre <= ?
    ORDER BY m.semestre, m.reticula
");
$stmt_materias->bind_param("ii", $id_alumno, $semestre_actual);
$stmt_materias->execute();
$materias_result = $stmt_materias->get_result();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <title>Panel Alumno - SIGE</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: #f4f6f9;
            margin: 0;
            padding: 20px;
        }
        header {
            background-color: #007bff;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 8px;
        }
        nav {
            text-align: right;
            margin: 10px 0;
        }
        nav a {
            background-color: #dc3545;
            color: white;
            padding: 8px 16px;
            text-decoration: none;
            border-radius: 5px;
        }
        nav a:hover {
            background-color: #c82333;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: white;
            border-radius: 8px;
            overflow: hidden;
        }
        th, td {
            padding: 12px 16px;
            border-bottom: 1px solid #eee;
            text-align: left;
        }
        th {
            background-color: #007bff;
            color: white;
        }
        select, input[type="submit"] {
            padding: 6px 10px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 14px;
        }
        input[type="submit"] {
            background-color: #28a745;
            color: white;
            cursor: pointer;
        }
        .mensaje, .error {
            margin-top: 20px;
            padding: 12px;
            border-radius: 6px;
        }
        .mensaje {
            background-color: #d4edda;
            color: #155724;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
        }
        @media (max-width: 768px) {
    header div {
        flex-direction: column;
        align-items: center;
    }
    header h1 {
        margin: 10px 0;
    }
}
    </style>
</head>
<body>
<header>
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap;">
        <img src="logo1.png" alt="Logo 1" style="height: 60px;">
        <h1 style="flex: 1; text-align: center; color: white;">Panel Administrativo - SIGE</h1>
        <div style="display: flex; gap: 10px;">
            <img src="logo2.png" alt="Logo 2" style="height: 60px;">
            <img src="logo3.png" alt="Logo 3" style="height: 60px;">
        </div>
    </div>
</header>
<nav><a href="logout.php">Cerrar Sesión</a></nav>

<h2>Bienvenido <?= htmlspecialchars($nombre_completo) ?> (ID: <?= htmlspecialchars($id_alumno) ?>)</h2>
<h3>Semestre actual: <?= htmlspecialchars($semestre_actual) ?></h3>

<form method="post">
    <input type="hidden" name="agregar_materias" value="1">
    <input type="submit" value="Agregar Todas las Materias" style="background-color: #17a2b8;">
</form>

<?php if ($mensaje): ?>
    <div class="<?= strpos($mensaje, 'correctamente') !== false ? 'mensaje' : 'error' ?>">
        <?= htmlspecialchars($mensaje) ?>
    </div>
<?php endif; ?>

<table>
    <thead>
        <tr>
            <th>Materia</th>
            <th>Semestre</th>
            <th>Estado</th>
            <th>Actualizar</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($materias_result->num_rows > 0): ?>
            <?php while ($fila = $materias_result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($fila['nombre']) ?></td>
                    <td><?= htmlspecialchars($fila['semestre']) ?></td>
                    <td><?= htmlspecialchars($fila['estado']) ?></td>
                    <td>
                        <form method="post" action="panel_alumno.php">
                            <input type="hidden" name="reticula" value="<?= htmlspecialchars($fila['reticula']) ?>">
                            <select name="nuevo_estado">
                                <option value="">Seleccionar</option>
                                <option value="0" <?= $fila['estado'] == '0' ? 'selected' : '' ?>>No Aprobada</option>
                                <option value="1" <?= $fila['estado'] == '1' ? 'selected' : '' ?>>Aprobada</option>
                                <option value="R" <?= $fila['estado'] == 'R' ? 'selected' : '' ?>>Reprobada</option>
                                <option value="RR" <?= $fila['estado'] == 'RR' ? 'selected' : '' ?>>Reprobada por Retraso</option>
                                <option value="OR" <?= $fila['estado'] == 'OR' ? 'selected' : '' ?>>En Curso</option>
                            </select>
                            <input type="submit" value="Actualizar">
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="4">No tienes materias asignadas.</td></tr>
        <?php endif; ?>
    </tbody>
</table>

<?php
$stmt->close();
$stmt_materias->close();
$conn->close();
?>
</body>
</html>
