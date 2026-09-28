<?php
require 'conexion.php';

$resultado = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $matricula = trim($_POST['matricula'] ?? '');
    $curp      = strtoupper(trim($_POST['curp'] ?? ''));

    $patron_curp = "/^[A-Z]{4}[0-9]{6}[HM][A-Z]{2}[B-DF-HJ-NP-TV-Z]{3}[0-9A-Z][0-9]$/";

    if (!preg_match($patron_curp, $curp)) {
        $resultado = [
            'status'  => 'error',
            'mensaje' => 'El formato del CURP ingresado no es válido.'
        ];
    } else {
        $sql = "SELECT a.nombre AS alumno_nombre, a.grupo, a.estado_salida, t.nombre AS tutor_nombre
                FROM tutor_alumno ta
                INNER JOIN tutores t ON ta.tutor_id = t.id
                INNER JOIN alumnos a ON ta.alumno_id = a.matricula
                WHERE a.matricula = :matricula AND t.curp = :curp";

        $stmt = $pdo->prepare($sql);
        $stmt->execute(['matricula' => $matricula, 'curp' => $curp]);
        $registro = $stmt->fetch();

        if ($registro) {
            if ($registro['estado_salida'] === 'Permitido') {
                $resultado = [
                    'status'  => 'exito',
                    'mensaje' => 'SALIDA PERMITIDA: El tutor ' . htmlspecialchars($registro['tutor_nombre']) . 
                                 ' está autorizado para retirar al alumno ' . htmlspecialchars($registro['alumno_nombre']) . 
                                 ' (' . htmlspecialchars($registro['grupo']) . ').'
                ];
            } else {
                $resultado = [
                    'status'  => 'denegado',
                    'mensaje' => 'SALIDA DENEGADA: El pase de salida del alumno está marcado como inactivo o restringido.'
                ];
            }
        } else {
            $resultado = [
                'status'  => 'denegado',
                'mensaje' => 'SALIDA DENEGADA: El CURP ingresado no corresponde a ningún tutor registrado para esta matrícula.'
            ];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Verificación de Pase de Salida</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .box { padding: 20px; border-radius: 6px; margin-top: 20px; color: white; font-weight: bold; }
        .exito { background-color: #28a745; }
        .denegado { background-color: #dc3545; }
        .error { background-color: #ffc107; color: #333; }
        form { max-width: 400px; }
        input { width: 100%; padding: 8px; margin: 8px 0; box-sizing: border-box; }
        button { width: 100%; padding: 10px; background: #007bff; color: white; border: none; cursor: pointer; }
    </style>
</head>
<body>

    <h2>Control de Acceso: Pase de Salida</h2>

    <form method="POST" action="">
        <label>Matrícula del Alumno:</label>
        <input type="text" name="matricula" placeholder="Ej. 24319052580071" required>

        <label>CURP del Padre / Tutor:</label>
        <input type="text" name="curp" maxlength="18" placeholder="18 caracteres" style="text-transform:uppercase;" required>

        <button type="submit">Validar Pase</button>
    </form>

    <?php if ($resultado): ?>
        <div class="box <?= $resultado['status'] ?>">
            <?= $resultado['mensaje'] ?>
        </div>
    <?php endif; ?>

</body>
</html>