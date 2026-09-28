<?php
require 'conexion.php';

session_start();

$mensaje_ine_error = '';
$registro_exitoso = false;
$alumno_registrado_nombre = '';

if (isset($_SESSION['registro_exitoso_nombre'])) {
    $registro_exitoso = true;
    $alumno_registrado_nombre = $_SESSION['registro_exitoso_nombre'];
    unset($_SESSION['registro_exitoso_nombre']);
}

$val_matricula        = $_POST['matricula'] ?? '';
$val_nombre           = $_POST['nombre'] ?? '';
$val_primer_apellido   = $_POST['primer_apellido'] ?? '';
$val_segundo_apellido  = $_POST['segundo_apellido'] ?? '';
$val_grupo             = $_POST['grupo'] ?? '';
$val_turno             = $_POST['turno'] ?? '';
$val_carrera           = $_POST['carrera'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $matricula        = strval(trim($val_matricula));
    $nombre           = trim($val_nombre);
    $primer_apellido   = trim($val_primer_apellido);
    $segundo_apellido  = trim($val_segundo_apellido);
    $grupo            = trim($val_grupo);
    $turno            = trim($val_turno);
    $carrera          = trim($val_carrera);

    $tutores_data     = $_POST['tutores'] ?? [];

    if (!preg_match("/^[0-9]{14}$/", $matricula)) {
        $mensaje_ine_error = "Error: La matrícula debe contener exactamente 14 números.";
    } elseif (!preg_match("/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/u", $nombre) || 
              !preg_match("/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/u", $primer_apellido) ||
              (!empty($segundo_apellido) && !preg_match("/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/u", $segundo_apellido))) {
        $mensaje_ine_error = "Error: El nombre y apellidos del alumno solo deben contener letras.";
    } else {

        foreach ($tutores_data as $idx => $tutor) {
            $t_nombre = trim($tutor['nombre'] ?? '');
            $t_tel = trim($tutor['telefono'] ?? '');

            if (!empty($t_nombre) && !preg_match("/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/u", $t_nombre)) {
                $mensaje_ine_error = "Error: El nombre de la persona autorizada #" . ($idx + 1) . " solo debe contener letras.";
                break;
            }
            if (!empty($t_tel) && !preg_match("/^[0-9]{10}$/", $t_tel)) {
                $mensaje_ine_error = "Error: El teléfono de la persona autorizada #" . ($idx + 1) . " debe contener exactamente 10 dígitos.";
                break;
            }
        }

        if (empty($mensaje_ine_error)) {
            $directorio_destino = "uploads/ines/";
            if (!file_exists($directorio_destino)) {
                mkdir($directorio_destino, 0777, true);
            }

            try {
                $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM alumnos WHERE matricula = ?");
                $stmtCheck->execute([$matricula]);

                if ($stmtCheck->fetchColumn() > 0) {
                    $mensaje_ine_error = "⚠️ Ya existe un alumno registrado con la matrícula '$matricula'.";
                } else {
                    // Insertar Alumno en control_salidas
                    $stmt = $pdo->prepare("INSERT INTO alumnos (matricula, nombre, primer_apellido, segundo_apellido, grupo, turno, carrera, estatus, estado_salida) VALUES (?, ?, ?, ?, ?, ?, ?, 'Activo', 'Permitido')");
                    $stmt->execute([$matricula, $nombre, $primer_apellido, $segundo_apellido, $grupo, $turno, $carrera]);

                    // Insertar Tutores y ligar mediante matricula
                    foreach ($tutores_data as $idx => $t) {
                        $t_nombre     = trim($t['nombre'] ?? '');
                        $t_parentesco = trim($t['parentesco'] ?? '');
                        $t_telefono   = trim($t['telefono'] ?? '');

                        if (empty($t_nombre)) continue;

                        $ruta_ine_guardada = null;

                        if (isset($_FILES['tutores_ine']['name'][$idx]) && $_FILES['tutores_ine']['error'][$idx] === UPLOAD_ERR_OK) {
                            $tmp_name = $_FILES['tutores_ine']['tmp_name'][$idx];
                            $file_name = $_FILES['tutores_ine']['name'][$idx];
                            $extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                            $extensiones_permitidas = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

                            if (in_array($extension, $extensiones_permitidas)) {
                                $nuevo_nombre = "ine_" . time() . "_" . uniqid() . "_tutor{$idx}." . $extension;
                                $ruta_final = $directorio_destino . $nuevo_nombre;
                                if (move_uploaded_file($tmp_name, $ruta_final)) {
                                    $ruta_ine_guardada = $ruta_final;
                                }
                            }
                        }

                        $stmtTutor = $pdo->prepare("INSERT INTO tutores (nombre, telefono, ine_foto) VALUES (?, ?, ?)");
                        $stmtTutor->execute([$t_nombre, $t_telefono, $ruta_ine_guardada]);

                        $tutor_id = $pdo->lastInsertId();

                        $stmtRel = $pdo->prepare("INSERT INTO tutor_alumno (alumno_id, tutor_id, parentesco) VALUES (?, ?, ?)");
                        $stmtRel->execute([$matricula, $tutor_id, $t_parentesco]);
                    }

                    $_SESSION['registro_exitoso_nombre'] = trim("$nombre $primer_apellido $segundo_apellido");
                    header("Location: " . $_SERVER['PHP_SELF']);
                    exit;
                }

            } catch (PDOException $e) {
                $mensaje_ine_error = "Error al guardar en la BD: " . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CBTis 258 - Registro Completo</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="altas.css">
</head>
<body>

<div class="container">
    <div class="nav-bar">
        <div class="nav-brand">
            <div class="cobra-avatar">
                <img src="mascota_cobra.png" alt="CBTis">
            </div>
            <div class="nav-title">
                <h3>Módulo de Registro</h3>
                <p>CBTis No. 258 "Mariano Escobedo"</p>
            </div>
        </div>
        <a href="menu_principal.php" class="btn-home">⬅️ Volver al Menú</a>
    </div>

    <?php if (!empty($mensaje_ine_error)): ?>
        <div class="alert-ine-error">
            <?= htmlspecialchars($mensaje_ine_error) ?>
        </div>
    <?php endif; ?>

    <form id="formRegistro" action="" method="POST" enctype="multipart/form-data">

        <div class="card">
            <div class="card-section-title">
                <div><span class="title-number">1</span> Datos Personales del Alumno</div>
            </div>
            
            <div class="form-group personal-data-group">
                <label>Matrícula (14 dígitos):</label>
                <input type="text" name="matricula" value="<?= htmlspecialchars($val_matricula) ?>" maxlength="14" placeholder="Ej. 24319052580071" oninput="validarSoloNumeros14(this)" required>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label>Nombre(s):</label>
                    <input type="text" name="nombre" value="<?= htmlspecialchars($val_nombre) ?>" oninput="validarSoloLetras(this)" required>
                </div>
                <div class="form-group">
                    <label>Primer Apellido:</label>
                    <input type="text" name="primer_apellido" value="<?= htmlspecialchars($val_primer_apellido) ?>" oninput="validarSoloLetras(this)" required>
                </div>
                <div class="form-group">
                    <label>Segundo Apellido:</label>
                    <input type="text" name="segundo_apellido" value="<?= htmlspecialchars($val_segundo_apellido) ?>" oninput="validarSoloLetras(this)">
                </div>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label>Grupo:</label>
                    <select name="grupo" required>
                        <option value="" disabled <?= empty($val_grupo) ? 'selected' : '' ?>>Seleccionar...</option>
                        <?php 
                        $grupos = ["1°A","1°B","1°C","1°D","1°E","1°F","1°G","1°H","1°I","1°J","3°A","3°B","5°A","5°B"];
                        foreach ($grupos as $g) {
                            $sel = ($val_grupo === $g) ? 'selected' : '';
                            echo "<option value='$g' $sel>$g</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Turno:</label>
                    <select name="turno" required>
                        <option value="" disabled <?= empty($val_turno) ? 'selected' : '' ?>>Seleccionar...</option>
                        <option value="Matutino" <?= ($val_turno === 'Matutino') ? 'selected' : '' ?>>Matutino</option>
                        <option value="Vespertino" <?= ($val_turno === 'Vespertino') ? 'selected' : '' ?>>Vespertino</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Especialidad / Carrera:</label>
                    <select name="carrera" required>
                        <option value="" disabled <?= empty($val_carrera) ? 'selected' : '' ?>>Seleccionar...</option>
                        <?php 
                        $carreras = ["PROGRAMACIÓN", "MECÁNICA INDUSTRIAL", "SERVICIOS DE HOSPEDAJE", "ALIMENTOS Y BEBIDAS", "CONTABILIDAD", "LOGÍSTICA", "INTELIGENCIA ARTIFICIAL", "CIBERSEGURIDAD"];
                        foreach ($carreras as $c) {
                            $sel = ($val_carrera === $c) ? 'selected' : '';
                            echo "<option value='$c' $sel>$c</option>";
                        }
                        ?>
                    </select>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-section-title">
                <div><span class="title-number">2</span> Personas Autorizadas / Tutores e Identificaciones (Máx. 4)</div>
            </div>

            <div id="tutores-container"></div>

            <button type="button" class="btn-add-tutor" id="btn-add-tutor" onclick="agregarTutor()">
                ➕ Agregar otra persona autorizada / tutor
            </button>
        </div>

        <button type="submit" class="btn-submit">💾 Guardar Registro Completo</button>
    </form>
</div>

<?php if ($registro_exitoso): ?>
<div class="modal-overlay" id="modalExito">
    <div class="modal-card">
        <div class="modal-icon-wrapper">✓</div>
        <h2 class="modal-title">¡Registro Exitoso!</h2>
        <p class="modal-text">
            El alumno <strong><?= htmlspecialchars($alumno_registrado_nombre) ?></strong> y las personas autorizadas han sido guardadas correctamente.
        </p>
        <div class="modal-actions">
            <button type="button" class="btn-modal-primary" onclick="cerrarModal()">➕ Registrar otro alumno</button>
            <a href="menu_principal.php" class="btn-modal-secondary">🏠 Volver al Menú Principal</a>
        </div>
    </div>
</div>
<?php endif; ?>

<script src="altas.js"></script>
</body>
</html>
