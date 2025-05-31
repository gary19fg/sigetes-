<?php
session_start();
include 'conexion.php';

// Verificar sesión
if (!isset($_SESSION['correo']) || $_SESSION['tipo_usuario'] != 'administrativo') {
    header("Location: login.php");
    exit();
}

$id_alumno = $_GET['id'];

// Obtener información del alumno
$stmt = $conn->prepare("
    SELECT id_alumno, CONCAT(nombre, ' ', apellido_paterno, ' ', apellido_materno) AS nombre_completo 
    FROM alumno 
    WHERE id_alumno = ?
");
$stmt->bind_param("s", $id_alumno);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die("Alumno no encontrado.");
}

$alumno = $result->fetch_assoc();

// Obtener todas las materias
$sql_materias = "SELECT reticula, nombre FROM materia ORDER BY nombre";
$result_materias = $conn->query($sql_materias);
$materias = [];
while ($row = $result_materias->fetch_assoc()) {
    $materias[$row['reticula']] = $row['nombre'];
}

// Obtener estados de materias para este alumno
$stmt = $conn->prepare("SELECT reticula, estado FROM estado_materia WHERE id_alumno = ?");
$stmt->bind_param("s", $id_alumno);
$stmt->execute();
$result_estados = $stmt->get_result();

$estados = [];
while ($row = $result_estados->fetch_assoc()) {
    $estados[$row['reticula']] = $row['estado'];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <title>Detalle del Alumno - SIGE</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f0f8f5;
            margin: 0;
            padding: 0;
        }
        header {
            background-color: #2f4f2f;
            color: white;
            padding: 20px;
            text-align: center;
        }
        nav {
            background-color: #4CAF50;
            padding: 10px;
            text-align: center;
        }
        nav a, nav button {
            color: white;
            margin: 0 15px;
            text-decoration: none;
            font-weight: bold;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 16px;
        }
        .container {
            max-width: 1200px;
            margin: auto;
            padding: 20px;
        }
        h2 {
            color: #2f4f2f;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background-color: #fff;
            box-shadow: 0 6px 12px rgba(0,0,0,0.1);
            margin-top: 20px;
        }
        th, td {
            padding: 12px;
            border: 1px solid #ddd;
            text-align: center;
        }
        th {
            background-color: #4CAF50;
            color: white;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .estado-aprobado {
            color: green;
            font-weight: bold;
        }
        .estado-noaprobado {
            color: red;
            font-weight: bold;
        }
        .estado-en-curso {
            color: orange;
            font-weight: bold;
        }
        .filtro-container {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
        }
        select#filtroEstado {
            padding: 8px;
            font-size: 14px;
            border-radius: 4px;
        }
        .btn-volver {
            display: inline-block;
            background-color: #2f4f2f;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 20px;
        }
        .btn-volver:hover {
            background-color: #3e8e41;
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

<nav>
    <button type="button" onclick="window.history.back()">Volver</button>
    <a href="logout.php">Cerrar Sesión</a>
</nav>

<div class="container">

    <h2>Detalle del Alumno: <?= htmlspecialchars($alumno['nombre_completo']) ?></h2>

    <!-- Filtro por estado -->
    <div class="filtro-container">
        <label for="filtroEstado"><strong>Filtrar por estado:</strong></label>
        <select id="filtroEstado">
            <option value="">Mostrar todos</option>
            <option value="1">Aprobadas</option>
            <option value="0">No Aprobadas</option>
            <option value="R">Reprobadas</option>
            <option value="RR">Retraso</option>
            <option value="OR">En Curso</option>
            <option value="sin_registro">Sin Registro</option>
        </select>
    </div>

    <!-- Tabla de materias -->
    <table id="tablaMaterias">
        <thead>
            <tr>
                <th>Materia</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($materias as $reticula => $nombre): 
                $estado = $estados[$reticula] ?? '';
                $clase_estado = match($estado) {
                    '1' => 'estado-aprobado',
                    '0', 'R', 'RR' => 'estado-noaprobado',
                    'OR' => 'estado-en-curso',
                    default => '',
                };
                $texto_estado = match($estado) {
                    '1' => 'Aprobada',
                    '0' => 'No Aprobada',
                    'R' => 'Reprobada',
                    'RR' => 'Retraso',
                    'OR' => 'En Curso',
                    default => 'Sin registro',
                };
            ?>
                <tr class="fila-materia" data-estado="<?= $estado ?: 'sin_registro' ?>">
                    <td><?= htmlspecialchars($nombre) ?></td>
                    <td class="<?= $clase_estado ?>">
                        <?= $texto_estado ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <br>
    <a href="panel_admin.php" class="btn-volver">Regresar al Panel</a>

</div>

<script>
    document.getElementById('filtroEstado').addEventListener('change', function () {
        const filtro = this.value;

        // Obtener todas las filas de la tabla
        const filas = document.querySelectorAll("#tablaMaterias tbody tr");

        filas.forEach(fila => {
            const estadoFila = fila.getAttribute("data-estado");

            if (filtro === "" || estadoFila === filtro) {
                fila.style.display = "";
            } else {
                fila.style.display = "none";
            }
        });
    });
</script>

</body>
</html>