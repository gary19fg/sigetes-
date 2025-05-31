<?php
session_start();
if (!isset($_SESSION['id']) || $_SESSION['tipo_usuario'] != 'administrativo') {
    header("Location: login.php");
    exit();
}

include 'conexion.php';

// Obtener filtros
$filtro_estado = isset($_GET['estado']) ? $_GET['estado'] : '';
$busqueda = isset($_GET['busqueda']) ? $conn->real_escape_string($_GET['busqueda']) : '';

// Obtener todas las materias
$sql_materias = "SELECT nombre FROM materia ORDER BY nombre";
$result_materias = $conn->query($sql_materias);
$materias = [];
while ($row = $result_materias->fetch_assoc()) {
    $materias[] = $row['nombre'];
}

// Obtener alumnos (filtrados por nombre)
$sql_alumnos = "SELECT id_alumno, CONCAT(nombre, ' ', apellido_paterno, ' ', apellido_materno) AS nombre_completo FROM alumno";
if (!empty($busqueda)) {
    $sql_alumnos .= " WHERE nombre LIKE '%$busqueda%' 
                      OR apellido_paterno LIKE '%$busqueda%'
                      OR apellido_materno LIKE '%$busqueda%'";
}
$result_alumnos = $conn->query($sql_alumnos);
$alumnos = [];
while ($row = $result_alumnos->fetch_assoc()) {
    $alumnos[$row['id_alumno']] = $row['nombre_completo'];
}

// Filtrar por estado si aplica
if (!empty($filtro_estado)) {
    $alumnos_filtrados = [];

    foreach ($alumnos as $id => $nombre) {
        foreach ($materias as $nombre_materia) {
            $stmt = $conn->prepare("SELECT em.estado FROM estado_materia em JOIN materia m ON em.reticula = m.reticula WHERE em.id_alumno = ? AND em.estado = ?");
            $stmt->bind_param("ss", $id, $filtro_estado);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res->num_rows > 0) {
                $alumnos_filtrados[$id] = $nombre;
                break;
            }
        }
    }

    $alumnos = !empty($alumnos_filtrados) ? $alumnos_filtrados : $alumnos;
}

// Obtener relación reticula -> materia
$sql_reticula_nombre = "SELECT reticula, nombre FROM materia";
$result_reticula_nombre = $conn->query($sql_reticula_nombre);
$reticula_nombre = [];
while ($row = $result_reticula_nombre->fetch_assoc()) {
    $reticula_nombre[$row['reticula']] = $row['nombre'];
}

// Obtener estados
$sql_estados = "SELECT id_alumno, reticula, estado FROM estado_materia";
$result_estados = $conn->query($sql_estados);
$estados = [];

while ($row = $result_estados->fetch_assoc()) {
    $estados[$row['id_alumno']][$row['reticula']] = $row['estado'];
}
?>

<?php
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="reporte_estado_materias.csv"');

$output = fopen('php://output', 'w');

// Encabezado: Alumno + Materias
fputcsv($output, array_merge(['Alumno'], array_values($reticula_nombre)));

// Datos por alumno
foreach ($alumnos as $id_alumno => $nombre_alumno) {
    $rowData = [$nombre_alumno];
    foreach ($reticula_nombre as $reticula => $nombre_materia) {
        $rowData[] = $estados[$id_alumno][$reticula] ?? '-';
    }
    fputcsv($output, $rowData);
}

fclose($output);
$conn->close();
exit;
?>