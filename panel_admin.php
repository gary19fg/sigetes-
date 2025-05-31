<?php
session_start();
if (!isset($_SESSION['id']) || $_SESSION['tipo_usuario'] != 'administrativo') {
    header("Location: login.php");
    exit();
}
include 'conexion.php';

// Obtener todas las materias
$sql_materias = "SELECT reticula, nombre FROM materia ORDER BY nombre";
$result_materias = $conn->query($sql_materias);
$materias = [];
while ($row = $result_materias->fetch_assoc()) {
    $materias[$row['reticula']] = $row['nombre'];
}

// Manejo de filtros
$busqueda = isset($_GET['busqueda']) ? $_GET['busqueda'] : '';
$filtro_estado = isset($_GET['filtro_estado']) ? $_GET['filtro_estado'] : '';
$filtro_semestre = isset($_GET['filtro_semestre']) ? intval($_GET['filtro_semestre']) : 0;

// Obtener todos los alumnos (con filtros)
$sql_alumnos = "SELECT id_alumno, CONCAT(nombre, ' ', apellido_paterno, ' ', apellido_materno) AS nombre_completo, semestre_actual FROM alumno WHERE 1=1";

if (!empty($busqueda)) {
    $sql_alumnos .= " AND (nombre LIKE '%" . $conn->real_escape_string($busqueda) . "%' 
                      OR apellido_paterno LIKE '%" . $conn->real_escape_string($busqueda) . "%'
                      OR apellido_materno LIKE '%" . $conn->real_escape_string($busqueda) . "%')";
}
if ($filtro_semestre > 0) {
    $sql_alumnos .= " AND semestre_actual = " . $filtro_semestre;
}

$result_alumnos = $conn->query($sql_alumnos);
$alumnos = [];
while ($row = $result_alumnos->fetch_assoc()) {
    $alumnos[$row['id_alumno']] = ['nombre' => $row['nombre_completo'], 'semestre' => $row['semestre_actual']];
}

// Filtrar alumnos por estado si se aplica
if (!empty($filtro_estado)) {
    $alumnos_filtrados = [];
    foreach ($alumnos as $id => $data) {
        // Verificar si tiene alguna materia con ese estado
        $sql_estados = "SELECT * FROM estado_materia WHERE id_alumno = '$id' AND estado = '$filtro_estado'";
        $result_estados = $conn->query($sql_estados);
        if ($result_estados->num_rows > 0) {
            $alumnos_filtrados[$id] = $data;
        }
    }
    $alumnos = !empty($alumnos_filtrados) ? $alumnos_filtrados : [];
}

// Obtener estados de materias por alumno
$sql_estados = "SELECT id_alumno, reticula, estado FROM estado_materia";
$result_estados = $conn->query($sql_estados);
$estados = [];
while ($row = $result_estados->fetch_assoc()) {
    $estados[$row['id_alumno']][$row['reticula']] = $row['estado'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <title>Panel Administrativo - SIGE</title>
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
        nav a {
            color: white;
            margin: 0 15px;
            text-decoration: none;
            font-weight: bold;
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
            padding: 10px;
            border: 1px solid #ddd;
            text-align: center;
        }
        th {
            background-color: #4CAF50;
            color: white;
        }
        .estado-select {
            width: 100%;
            padding: 5px;
            border-radius: 4px;
        }
        .alumno-row td:first-child {
            font-weight: bold;
            text-align: left;
            background-color: #f9f9f9;
        }
        .filters {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
            margin-bottom: 20px;
        }
        .filters input[type="text"],
        .filters select,
        .filters button {
            padding: 8px;
            font-size: 14px;
        }
        .btn {
            background-color: rgb(28, 65, 30);
            color: white;
            border: none;
            padding: 8px 12px;
            cursor: pointer;
            border-radius: 4px;
        }
        .btn:hover {
            background-color: #45a049;
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
    <button type="button" id="saveChanges" class="btn">Guardar Cambios</button>
    <button type="button" id="updateSemesterBtn" class="btn">Actualizar Semestre</button>
    <a href="exportar_excel.php?estado=<?= urlencode($filtro_estado) ?>&busqueda=<?= urlencode($busqueda) ?>" class="btn">Exportar a Excel</a>
    <a href="logout.php" style="color: white; text-decoration: none; font-weight: bold;">Cerrar Sesión</a>
</nav>
<div class="container">
    <h2>Estados de Materias por Alumno</h2>

    <!-- Filtros -->
    <form method="GET" class="filters">
        <select name="filtro_estado">
            <option value="">Filtrar por estado</option>
            <option value="1" <?= $filtro_estado == '1' ? 'selected' : '' ?>>Aprobado</option>
            <option value="0" <?= $filtro_estado == '0' ? 'selected' : '' ?>>No Aprobado</option>
            <option value="R" <?= $filtro_estado == 'R' ? 'selected' : '' ?>>Reprobado</option>
            <option value="RR" <?= $filtro_estado == 'RR' ? 'selected' : '' ?>>Retraso</option>
            <option value="OR" <?= $filtro_estado == 'OR' ? 'selected' : '' ?>>En Curso</option>
        </select>
        <select name="filtro_semestre">
            <option value="">Todos los semestres</option>
            <?php for ($i = 1; $i <= 12; $i++): ?>
                <option value="<?= $i ?>" <?= $filtro_semestre == $i ? 'selected' : '' ?>><?= $i ?>º Semestre</option>
            <?php endfor; ?>
        </select>
        
        <button type="submit" class="btn">Aplicar Filtros</button>
    </form>

    <!-- Campo oculto para actualizar semestre -->
    <div style="margin-bottom: 20px;">
        <label for="nuevo_semestre">Nuevo Semestre:</label>
        <input type="number" id="nuevo_semestre" min="1" max="12" style="width: 60px;" />
    </div>

    <!-- Tabla principal -->
    <table>
        <thead>
        <tr>
            <th><input type="checkbox" id="checkAll"></th>
            <th>Alumno</th>
            <?php foreach ($materias as $reticula => $nombre): ?>
                <th title="<?= htmlspecialchars($nombre) ?>">
                    <?= htmlspecialchars(substr($nombre, 0, 20)) ?>...
                </th>
            <?php endforeach; ?>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($alumnos as $id_alumno => $data): ?>
            <tr class="alumno-row">
                <td><input type="checkbox" class="alumno-check" data-id="<?= $id_alumno ?>"></td>
                <td><a href="detalle_alumno.php?id=<?= urlencode($id_alumno) ?>"><?= htmlspecialchars($data['nombre']) ?></a></td>
                <?php foreach ($materias as $reticula => $nombre_materia): ?>
                    <td>
                        <select class="estado-select" data-alumno="<?= $id_alumno ?>" data-materia="<?= $reticula ?>">
                            <option value="">Seleccionar</option>
                            <option value="0" <?= (isset($estados[$id_alumno][$reticula]) && $estados[$id_alumno][$reticula] == '0') ? 'selected' : '' ?>>No Aprobada</option>
                            <option value="1" <?= (isset($estados[$id_alumno][$reticula]) && $estados[$id_alumno][$reticula] == '1') ? 'selected' : '' ?>>Aprobada</option>
                            <option value="R" <?= (isset($estados[$id_alumno][$reticula]) && $estados[$id_alumno][$reticula] == 'R') ? 'selected' : '' ?>>Reprobada</option>
                            <option value="RR" <?= (isset($estados[$id_alumno][$reticula]) && $estados[$id_alumno][$reticula] == 'RR') ? 'selected' : '' ?>>Retraso</option>
                            <option value="OR" <?= (isset($estados[$id_alumno][$reticula]) && $estados[$id_alumno][$reticula] == 'OR') ? 'selected' : '' ?>>En Curso</option>
                        </select>
                    </td>
                <?php endforeach; ?>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Estadísticas -->
    <h2>Estadísticas de Aprobación</h2>
    <table>
        <thead>
        <tr>
            <th>Materia</th>
            <th>Aprobados</th>
            <th>Total</th>
            <th>% Aprobación</th>
        </tr>
        </thead>
        <tbody>
        <?php
        $stats = [];
        foreach ($materias as $reticula => $nombre) {
            $aprobados = 0;
            $total = 0;
            foreach ($estados as $alumno_id => $mat) {
                if (isset($mat[$reticula])) {
                    $total++;
                    if ($mat[$reticula] === '1') {
                        $aprobados++;
                    }
                }
            }
            if ($total > 0) {
                $porcentaje = ($aprobados / $total) * 100;
                $stats[$reticula] = [
                    'nombre' => $nombre,
                    'aprobados' => $aprobados,
                    'total' => $total,
                    'porcentaje' => round($porcentaje, 2)
                ];
            }
        }
        foreach ($stats as $info): ?>
            <tr>
                <td><?= htmlspecialchars($info['nombre']) ?></td>
                <td><?= $info['aprobados'] ?></td>
                <td><?= $info['total'] ?></td>
                <td><?= $info['porcentaje'] ?>%</td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
    // Seleccionar todos los alumnos al hacer clic en el checkbox de cabecera
    document.getElementById('checkAll').addEventListener('change', function () {
        const isChecked = this.checked;
        document.querySelectorAll('.alumno-check').forEach(cb => cb.checked = isChecked);
    });

    // Actualizar el estado del checkbox de cabecera cuando cambien los alumnos
    document.querySelectorAll('.alumno-check').forEach(cb => {
        cb.addEventListener('change', function () {
            const allChecked = Array.from(document.querySelectorAll('.alumno-check')).every(cb => cb.checked);
            document.getElementById('checkAll').checked = allChecked;
        });
    });

    // Guardar cambios en estados de materias
    document.getElementById('saveChanges').addEventListener('click', function () {
        const selectedStudents = new Set();
        const states = {};
        document.querySelectorAll('.alumno-check:checked').forEach(checkbox => {
            const id = checkbox.getAttribute('data-id');
            selectedStudents.add(id);
            states[id] = {};
            document.querySelectorAll(`select[data-alumno="${id}"]`).forEach(select => {
                const materia = select.getAttribute('data-materia');
                const estado = select.value;
                if (estado !== "") {
                    states[id][materia] = estado;
                }
            });
        });
        if (selectedStudents.size === 0) {
            alert("Por favor selecciona al menos un alumno.");
            return;
        }
        fetch('actualizar_estado_multiple.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ estados: states })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert("Cambios guardados exitosamente.");
                location.reload();
            } else {
                alert("Error al guardar cambios.");
                console.error(data.error);
            }
        })
        .catch(error => {
            alert("Error en la solicitud.");
            console.error(error);
        });
    });

    // Actualizar semestre de alumnos seleccionados
    document.getElementById('updateSemesterBtn').addEventListener('click', function () {
        const selectedStudents = Array.from(document.querySelectorAll('.alumno-check:checked'))
            .map(cb => cb.getAttribute('data-id'));
        const nuevoSemestre = document.getElementById('nuevo_semestre').value;

        if (selectedStudents.length === 0) {
            alert("Por favor selecciona al menos un alumno.");
            return;
        }

        if (!nuevoSemestre || nuevoSemestre < 1 || nuevoSemestre > 12) {
            alert("Ingresa un semestre válido entre 1 y 12.");
            return;
        }

        fetch('actualizar_semestre.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                alumnos: selectedStudents,
                semestre: parseInt(nuevoSemestre)
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert("Semestre actualizado correctamente.");
                location.reload();
            } else {
                alert("Hubo un error al actualizar los semestres.");
            }
        })
        .catch(err => {
            alert("Error en la conexión.");
            console.error(err);
        });
    });
</script>
</body>
</html>