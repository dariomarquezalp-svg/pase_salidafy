<?php
require 'conexion.php';

session_start();

$mensaje_ine_error = ''; // Variable exclusiva para errores en la sección INE
$registro_exitoso = false;
$alumno_registrado_nombre = '';

// Verificar si se acaba de registrar a un alumno con éxito
if (isset($_SESSION['registro_exitoso_nombre'])) {$registro_exitoso = true;
    $alumno_registrado_nombre =$_SESSION['registro_exitoso_nombre'];
    unset($_SESSION['registro_exitoso_nombre']);
}

// Variables para conservar los datos ingresados en el formulario en caso de error
$val_matricula        =$_POST['matricula'] ?? '';
$val_nombre           =$_POST['nombre'] ?? '';
$val_primer_apellido   =$_POST['primer_apellido'] ?? '';
$val_segundo_apellido  =$_POST['segundo_apellido'] ?? '';
$val_grupo             =$_POST['grupo'] ?? '';
$val_turno             =$_POST['turno'] ?? '';
$val_carrera           =$_POST['carrera'] ?? '';
$val_tutor_nombre      =$_POST['tutor_nombre'] ?? '';
$val_tutor_parentesco  =$_POST['tutor_parentesco'] ?? '';
$val_tutor_telefono    =$_POST['tutor_telefono'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $matricula        = trim($val_matricula);
    $nombre           = trim($val_nombre);
    $primer_apellido   = trim($val_primer_apellido);
    $segundo_apellido  = trim($val_segundo_apellido);
    $grupo            = trim($val_grupo);
    $turno            = trim($val_turno);
    $carrera          = trim($val_carrera);
    
    $tutor_nombre     = trim($val_tutor_nombre);
    $tutor_parentesco = trim($val_tutor_parentesco);
    $tutor_telefono   = trim($val_tutor_telefono);

    // Validaciones
    if (!preg_match("/^[0-9]{14}$/", $matricula)) {$mensaje_ine_error = "Error: La matrícula debe contener exactamente 14 números.";
    } elseif (!preg_match("/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/u", $nombre) || 
        !preg_match("/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/u", $primer_apellido) ||
        (!empty($segundo_apellido) && !preg_match("/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/u", $segundo_apellido))) {$mensaje_ine_error = "Error: El nombre y apellidos del alumno solo deben contener letras.";
    } elseif (!empty($tutor_nombre) && !preg_match("/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/u", $tutor_nombre)) {$mensaje_ine_error = "Error: El nombre del tutor solo debe contener letras.";
    } elseif (!empty($tutor_telefono) && !preg_match("/^[0-9]{10}$/", $tutor_telefono)) {$mensaje_ine_error = "Error: El teléfono del tutor debe contener exactamente 10 dígitos.";
    } else {

        // Procesamiento de subida de imágenes de INE
        $directorio_destino = "uploads/ines/";
        if (!file_exists($directorio_destino)) {
            mkdir($directorio_destino, 0777, true);
        }

        $rutas_ines = [];
        if (isset($_FILES['ines']) && !empty($_FILES['ines']['name'][0])) {
            $total_archivos = count($_FILES['ines']['name']);
            if ($total_archivos > 4) {$mensaje_ine_error = "⚠️ Únicamente se permite subir un máximo de 4 imágenes de INE.";
            } else {
                for ($i = 0; $i < $total_archivos; $i++) {
                    if ($_FILES['ines']['error'][$i] === UPLOAD_ERR_OK) {$tmp_name = $_FILES['ines']['tmp_name'][$i];
                        $extension = strtolower(pathinfo($_FILES['ines']['name'][$i], PATHINFO_EXTENSION));$extensiones_permitidas = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
                        
                        if (!in_array($extension, $extensiones_permitidas)) {$mensaje_ine_error = "⚠️ Tipo de archivo no permitido. Solo puedes seleccionar archivos de imagen (JPG, PNG, WEBP, GIF).";
                            break;
                        }
                        $nuevo_nombre = "ine_" . time() . "_" . uniqid() . "_$i." . $extension;
                        $ruta_final = $directorio_destino .$nuevo_nombre;
                        if (move_uploaded_file($tmp_name,$ruta_final)) {
                            $rutas_ines[] =$ruta_final;
                        }
                    }
                }
            }
        }

        if (empty($mensaje_ine_error)) {
            try {
                // Verificar si la matrícula ya está registrada
                $stmtCheck =$pdo->prepare("SELECT COUNT(*) FROM alumnos WHERE matricula = ?");
                $stmtCheck->execute([$matricula]);
                if ($stmtCheck->fetchColumn() > 0) {$mensaje_ine_error = "⚠️ Ya existe un alumno registrado con la matrícula '$matricula'.";
                } else {
                    $pdo->beginTransaction();

                    // 1. Insertar Alumno (incluyendo matricula)
                    $stmt =$pdo->prepare("INSERT INTO alumnos (matricula, nombre, primer_apellido, segundo_apellido, grupo, turno, carrera, estatus) VALUES (?, ?, ?, ?, ?, ?, ?, 'Activo')");
                    $stmt->execute([$matricula, $nombre,$primer_apellido, $segundo_apellido,$grupo, $turno,$carrera]);
                    
                    $alumno_id =$pdo->lastInsertId();

                    // 2. Insertar Tutor y Vincular
                    if (!empty($tutor_nombre)) {$string_ines = !empty($rutas_ines) ? implode(',', $rutas_ines) : null;

                        try {
                            $stmtTutor =$pdo->prepare("INSERT INTO tutores (nombre, telefono, ine_foto) VALUES (?, ?, ?)");
                            $stmtTutor->execute([$tutor_nombre, $tutor_telefono,$string_ines]);
                        } catch (PDOException $exT) {
                            $stmtTutor =$pdo->prepare("INSERT INTO tutores (nombre, telefono, foto_ine) VALUES (?, ?, ?)");
                            $stmtTutor->execute([$tutor_nombre, $tutor_telefono,$string_ines]);
                        }
                        
                        $tutor_id =$pdo->lastInsertId();

                        $stmtRel =$pdo->prepare("INSERT INTO tutor_alumno (alumno_id, tutor_id, parentesco) VALUES (?, ?, ?)");
                        $stmtRel->execute([$alumno_id, $tutor_id,$tutor_parentesco]);
                    }

                    $pdo->commit();
                    
                    $_SESSION['registro_exitoso_nombre'] = trim("$nombre $primer_apellido$segundo_apellido");
                    header("Location: admin_registro.php");
                    exit;
                }

            } catch (PDOException $e) {
                if ($pdo->inTransaction()) {$pdo->rollBack();
                }
                $mensaje_ine_error = "Error al guardar en la base de datos: " . $e->getMessage();
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
    <style>
        :root {
            --cbtis-vino: #691C32;
            --cbtis-vino-dark: #3A0B18;
            --cbtis-beige: #C2A269;
            --card-glass: rgba(255, 255, 255, 0.85);
            --text-dark: #0f172a;
            --text-muted: #475569;
            --border-color: rgba(255, 255, 255, 0.6);
        }

        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Montserrat', sans-serif; }

        body {
            min-height: 100vh;
            width: 100%;
            background: linear-gradient(135deg, #cbd5e1 0%, #94a3b8 100%);
            background-attachment: fixed;
            color: var(--text-dark);
            padding: 30px 20px;
            display: flex;
            justify-content: center;
        }

        .container { width: 100%; max-width: 950px; position: relative; z-index: 2; }

        .nav-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            background: var(--card-glass);
            backdrop-filter: blur(14px);
            padding: 14px 24px;
            border-radius: 18px;
            border: 1px solid var(--border-color);
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
        }

        .nav-brand { display: flex; align-items: center; gap: 12px; }

        .cobra-avatar {
            width: 46px; height: 46px;
            border-radius: 50%;
            background: radial-gradient(circle, #7A1E3A 0%, var(--cbtis-vino-dark) 100%);
            padding: 3px;
            border: 2px solid var(--cbtis-beige);
            display: flex; align-items: center; justify-content: center;
        }

        .cobra-avatar img { width: 100%; height: 100%; object-fit: contain; }

        .nav-title h3 { font-size: 1.1rem; font-weight: 800; color: var(--cbtis-vino); }
        .nav-title p { font-size: 0.75rem; color: var(--text-muted); font-weight: 600; }

        .btn-home {
            background: var(--cbtis-vino);
            color: white;
            text-decoration: none;
            padding: 10px 18px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 0.85rem;
            transition: all 0.3s ease;
        }

        .btn-home:hover { background: var(--cbtis-vino-dark); }

        .card {
            background: var(--card-glass);
            backdrop-filter: blur(14px);
            padding: 30px;
            border-radius: 22px;
            border: 1px solid var(--border-color);
            box-shadow: 0 12px 30px rgba(0,0,0,0.06);
            margin-bottom: 25px;
        }

        .card-section-title {
            font-size: 1.15rem; font-weight: 800; color: var(--cbtis-vino);
            margin-bottom: 22px; display: flex; align-items: center; gap: 12px;
            border-bottom: 2px solid rgba(0, 0, 0, 0.05); padding-bottom: 12px;
        }

        .title-number {
            background: var(--cbtis-vino); color: white;
            width: 28px; height: 28px; border-radius: 8px;
            display: inline-flex; align-items: center; justify-content: center;
            font-size: 0.85rem; font-weight: 900;
        }

        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 18px; }
        .form-group { display: flex; flex-direction: column; gap: 6px; margin-bottom: 10px; }
        .form-group label { font-size: 0.8rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase; }

        .form-group input, .form-group select {
            padding: 12px 14px;
            border: 1.5px solid #cbd5e1;
            border-radius: 12px;
            font-size: 0.92rem;
            background: rgba(255, 255, 255, 0.95);
            font-weight: 600;
            outline: none;
        }

        .form-group input:focus, .form-group select:focus {
            border-color: var(--cbtis-vino);
            background: #ffffff;
            box-shadow: 0 0 0 4px rgba(105, 28, 50, 0.12);
        }

        .upload-dropzone {
            border: 2px dashed #cbd5e1;
            background: rgba(255, 255, 255, 0.6);
            border-radius: 16px; padding: 25px; text-align: center; cursor: pointer;
        }

        .btn-upload {
            background-color: var(--cbtis-beige); color: white;
            padding: 12px 24px; border: none; border-radius: 10px;
            font-weight: 700; cursor: pointer; margin-top: 10px;
        }

        .preview-container { display: flex; flex-wrap: wrap; gap: 14px; margin-top: 18px; justify-content: center; }
        .preview-item { position: relative; width: 95px; height: 95px; border-radius: 12px; overflow: hidden; border: 2px solid #cbd5e1; }
        .preview-item img { width: 100%; height: 100%; object-fit: cover; }
        .preview-item .remove-btn { position: absolute; top: 4px; right: 4px; background: rgba(105, 28, 50, 0.95); color: white; border: none; border-radius: 50%; width: 22px; height: 22px; cursor: pointer; }

        .alert-ine-error {
            background-color: #fee2e2;
            color: #991b1b;
            padding: 12px 16px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 0.88rem;
            margin-bottom: 15px;
            border: 1px solid #fca5a5;
            text-align: center;
        }

        .btn-submit {
            background: linear-gradient(135deg, var(--cbtis-vino) 0%, var(--cbtis-vino-dark) 100%);
            color: white; border: none; padding: 16px; font-weight: 800; border-radius: 14px;
            cursor: pointer; width: 100%; font-size: 1.1rem; box-shadow: 0 8px 20px rgba(105, 28, 50, 0.3);
        }

        .modal-overlay {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(15, 23, 42, 0.65);
            backdrop-filter: blur(8px);
            display: flex; align-items: center; justify-content: center;
            z-index: 1000;
            padding: 20px;
        }

        .modal-card {
            background: #ffffff;
            border-radius: 24px;
            padding: 35px 30px;
            max-width: 460px;
            width: 100%;
            text-align: center;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            border: 1px solid rgba(255, 255, 255, 0.8);
            animation: popIn 0.35s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        @keyframes popIn {
            0% { opacity: 0; transform: scale(0.8); }
            100% { opacity: 1; transform: scale(1); }
        }

        .modal-icon-wrapper {
            width: 80px; height: 80px;
            background: #dcfce7;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 20px auto;
            color: #16a34a; font-size: 2.5rem;
        }

        .modal-title { font-size: 1.4rem; font-weight: 800; color: var(--cbtis-vino); margin-bottom: 10px; }
        .modal-text { font-size: 0.95rem; color: var(--text-muted); font-weight: 500; line-height: 1.5; margin-bottom: 25px; }

        .modal-actions { display: flex; flex-direction: column; gap: 12px; }

        .btn-modal-primary {
            background: var(--cbtis-vino);
            color: white; border: none;
            padding: 14px 20px; border-radius: 12px;
            font-weight: 800; font-size: 0.95rem; cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            display: block;
        }

        .btn-modal-primary:hover { background: var(--cbtis-vino-dark); }

        .btn-modal-secondary {
            background: #f1f5f9;
            color: var(--text-dark);
            border: 1px solid #cbd5e1;
            padding: 14px 20px; border-radius: 12px;
            font-weight: 700; font-size: 0.95rem; cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            display: block;
        }

        .btn-modal-secondary:hover { background: #e2e8f0; }
    </style>
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

    <form id="formRegistro" action="" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="scroll_pos" id="scroll_pos" value="0">

        <div class="card">
            <div class="card-section-title"><span class="title-number">1</span> Datos Personales del Alumno</div>
            
            <div class="form-group" style="margin-bottom: 18px;">
                <label>Matrícula (14 dígitos):</label>
                <input type="text" name="matricula" value="<?= htmlspecialchars($val_matricula) ?>" maxlength="14" placeholder="Ej. 21319050001234" oninput="validarSoloNumeros14(this)" required>
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
                        foreach ($grupos as $g) {$sel = ($val_grupo ===$g) ? 'selected' : '';
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
                        foreach ($carreras as $c) {$sel = ($val_carrera ===$c) ? 'selected' : '';
                            echo "<option value='$c' $sel>$c</option>";
                        }
                        ?>
                    </select>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-section-title"><span class="title-number">2</span> Datos del Tutor</div>
            <div class="form-grid">
                <div class="form-group">
                    <label>Nombre del Tutor:</label>
                    <input type="text" name="tutor_nombre" value="<?= htmlspecialchars($val_tutor_nombre) ?>" oninput="validarSoloLetras(this)">
                </div>
                <div class="form-group">
                    <label>Parentesco:</label>
                    <select name="tutor_parentesco">
                        <option value="Padre" <?= ($val_tutor_parentesco === 'Padre') ? 'selected' : '' ?>>Padre</option>
                        <option value="Madre" <?= ($val_tutor_parentesco === 'Madre') ? 'selected' : '' ?>>Madre</option>
                        <option value="Tutor Legal" <?= ($val_tutor_parentesco === 'Tutor Legal') ? 'selected' : '' ?>>Tutor Legal</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Teléfono (10 dígitos):</label>
                    <input type="tel" name="tutor_telefono" value="<?= htmlspecialchars($val_tutor_telefono) ?>" maxlength="10" oninput="validarSoloNumeros10(this)">
                </div>
            </div>
        </div>

        <div class="card" id="seccion_ines">
            <div class="card-section-title"><span class="title-number">3</span> Identificaciones Oficiales (INE)</div>
            
            <div id="js-ine-error" class="alert-ine-error" style="<?= empty($mensaje_ine_error) ? 'display: none;' : '' ?>">
                <?= htmlspecialchars($mensaje_ine_error) ?>
            </div>

            <div class="upload-dropzone" onclick="document.getElementById('ine_files').click();">
                <p>Selecciona hasta 4 imágenes de la identificación oficial del tutor.</p>
                <input type="file" id="ine_files" name="ines[]" accept="image/*" multiple style="display: none;" onchange="manejarArchivos(this.files)">
                <button type="button" class="btn-upload">📁 Seleccionar Imagen(es)</button>
                <div id="ine-preview-list" class="preview-container"></div>
            </div>
        </div>

        <button type="submit" class="btn-submit">💾 Guardar Registro Completo</button>
    </form>
</div>

<?php if ($registro_exitoso): ?>
<div class="modal-overlay" id="modalExito">
    <div class="modal-card">
        <div class="modal-icon-wrapper">
            ✓
        </div>
        <h2 class="modal-title">¡Registro Exitoso!</h2>
        <p class="modal-text">
            El alumno <strong><?= htmlspecialchars($alumno_registrado_nombre) ?></strong> y los datos de su tutor han sido guardados correctamente en el sistema.
        </p>
        <div class="modal-actions">
            <button type="button" class="btn-modal-primary" onclick="cerrarModal()">➕ Registrar otro alumno</button>
            <a href="menu_principal.php" class="btn-modal-secondary">🏠 Volver al Menú Principal</a>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
// MANTENER Y RECUPERAR LA POSICIÓN DEL SCROLL EN PANTALLA
document.getElementById('formRegistro').addEventListener('submit', function() {
    document.getElementById('scroll_pos').value = window.scrollY || document.documentElement.scrollTop;
});

window.addEventListener('load', function() {
    <?php if (!empty($mensaje_ine_error)): ?>
        const seccionInes = document.getElementById('seccion_ines');
        if (seccionInes) {
            seccionInes.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    <?php endif; ?>
});

function cerrarModal() {
    const modal = document.getElementById('modalExito');
    if (modal) {
        modal.style.display = 'none';
    }
}

// VALIDACIONES EN TIEMPO REAL
function validarSoloNumeros14(i) { i.value = i.value.replace(/[^0-9]/g, '').slice(0, 14); }
function validarSoloLetras(i) { i.value = i.value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑ\s]/g, ''); }
function validarSoloNumeros10(i) { i.value = i.value.replace(/[^0-9]/g, '').slice(0, 10); }

let archivosAcumulados = [];

function manejarArchivos(archivos) {
    const jsErrorBox = document.getElementById('js-ine-error');
    jsErrorBox.style.display = 'none';

    for (let file of archivos) {
        if (!file.type.startsWith('image/')) {
            jsErrorBox.textContent = "⚠️ Error: Solo se permiten seleccionar archivos de imagen (JPG, PNG, WEBP, GIF).";
            jsErrorBox.style.display = 'block';
            continue;
        }

        if (archivosAcumulados.length < 4) {
            archivosAcumulados.push(file);
        } else {
            jsErrorBox.textContent = "⚠️ Error: Únicamente se permite un máximo de 4 imágenes de INE.";
            jsErrorBox.style.display = 'block';
            break;
        }
    }
    render();
}

function render() {
    const list = document.getElementById('ine-preview-list');
    list.innerHTML = '';
    archivosAcumulados.forEach((file, index) => {
        const div = document.createElement('div');
        div.className = 'preview-item';
        div.innerHTML = `<img src="${URL.createObjectURL(file)}"><button type="button" class="remove-btn" onclick="eliminar(${index})">✕</button>`;
        list.appendChild(div);
    });
    actualizarInput();
}

function eliminar(index) {
    archivosAcumulados.splice(index, 1);
    render();
}

function actualizarInput() {
    const dt = new DataTransfer();
    archivosAcumulados.forEach(f => dt.items.add(f));
    document.getElementById('ine_files').files = dt.files;
}
</script>
</body>
</html>