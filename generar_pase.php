<?php
error_reporting(0);
ini_set('display_errors', 0);

require 'conexion.php';

if (isset($_GET['action'])) {
    header('Content-Type: application/json; charset=utf-8');

    if ($_GET['action'] === 'buscar_alumnos') {
        $q = trim($_GET['q'] ?? '');
        if (strlen($q) >= 1) {
            try {
                $stmtA = $pdo->prepare("
                    SELECT * FROM alumnos 
                    WHERE matricula LIKE :q OR nombre LIKE :q OR primer_apellido LIKE :q
                    LIMIT 10
                ");
                $stmtA->execute(['q' => '%' . $q . '%']);
                $alumnosRaw = $stmtA->fetchAll(PDO::FETCH_ASSOC);
                
                $alumnos = [];
                foreach ($alumnosRaw as $a) {
                    $nombre_completo = trim(($a['nombre'] ?? '') . ' ' . ($a['primer_apellido'] ?? '') . ' ' . ($a['segundo_apellido'] ?? ''));
                    
                    $alumnos[] = [
                        'id'              => $a['matricula'] ?? '',
                        'matricula'       => $a['matricula'] ?? '',
                        'grupo'           => $a['grupo'] ?? '',
                        'nombre_completo' => $nombre_completo
                    ];
                }
                echo json_encode($alumnos);
            } catch (Exception $e) {
                echo json_encode([]);
            }
        } else {
            echo json_encode([]);
        }
        exit;
    }

    if ($_GET['action'] === 'obtener_autorizados') {
        $alumno_ref = trim($_GET['alumno_id'] ?? '');

        try {
            if (!empty($alumno_ref)) {
                $stmt = $pdo->prepare("
                    SELECT 
                        t.id,
                        t.nombre,
                        t.telefono,
                        t.ine_foto AS foto_ine,
                        ta.parentesco
                    FROM tutores t
                    INNER JOIN tutor_alumno ta ON ta.tutor_id = t.id
                    WHERE ta.alumno_id = :ref
                ");
                $stmt->execute(['ref' => $alumno_ref]);
                $tutoresRaw = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if (count($tutoresRaw) > 0) {
                    $autorizados = [];
                    foreach ($tutoresRaw as $t) {
                        $autorizados[] = [
                            'id'         => $t['id'] ?? 0,
                            'nombre'     => $t['nombre'] ?? 'Sin nombre registrado',
                            'telefono'   => $t['telefono'] ?? 'Sin teléfono',
                            'foto_ine'   => $t['foto_ine'] ?? '',
                            'parentesco' => $t['parentesco'] ?? 'Persona Autorizada'
                        ];
                    }
                    echo json_encode(['status' => 'success', 'data' => $autorizados]);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Este alumno no tiene personas autorizadas asignadas en la BD.']);
                }
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Selecciona un alumno de la lista.']);
            }
        } catch (PDOException $e) {
            echo json_encode(['status' => 'error', 'message' => 'Error de consulta: ' . $e->getMessage()]);
        }
        exit;
    }
}

$mensaje_alerta = '';
$tipo_alerta = '';
$pase_generado = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $alumno_ref_post = !empty($_POST['alumno_id_hidden']) ? trim($_POST['alumno_id_hidden']) : '';
    $matricula_post  = trim($_POST['matricula_alumno'] ?? '');
    $tutor_id        = !empty($_POST['autorizado_id']) ? intval($_POST['autorizado_id']) : null;
    $fecha           = $_POST['fecha'] ?? date('Y-m-d');
    $hora            = $_POST['hora'] ?? date('H:i');

    $motivo_select   = trim($_POST['motivo_select'] ?? '');
    $motivo_detalle  = trim($_POST['motivo_detalle'] ?? '');

    $motivo = ($motivo_select === 'otro' && !empty($motivo_detalle)) ? "Otro: " . $motivo_detalle : ucfirst($motivo_select);

    try {
        if (empty($alumno_ref_post) && empty($matricula_post)) {
            $mensaje_alerta = "Error: Debes ingresar la matrícula y seleccionar un alumno de la lista.";
            $tipo_alerta = "error";
        } elseif (!$tutor_id) {
            $mensaje_alerta = "Error: Debe seleccionar una persona autorizada.";
            $tipo_alerta = "error";
        } else {
            $real_alumno_id = !empty($matricula_post) ? $matricula_post : $alumno_ref_post;

            $stmtP = $pdo->prepare("
                INSERT INTO pases_salida (alumno_id, tutor_id, fecha_salida, hora_salida, motivo) 
                VALUES (:alumno_id, :tutor_id, :fecha, :hora, :motivo)
            ");
            $stmtP->execute([
                'alumno_id' => $real_alumno_id,
                'tutor_id'  => $tutor_id,
                'fecha'     => $fecha,
                'hora'      => $hora,
                'motivo'    => $motivo
            ]);

            $pase_id =$pdo->lastInsertId();

            $stmtDetalle =$pdo->prepare("
                SELECT ps.*, 
                       CONCAT(a.nombre, ' ', a.primer_apellido, ' ', COALESCE(a.segundo_apellido, '')) AS nombre_alumno,
                       a.matricula, a.grupo,
                       COALESCE(t.nombre, 'Persona Autorizada') AS nombre_tutor
                FROM pases_salida ps
                INNER JOIN alumnos a ON ps.alumno_id = a.matricula
                LEFT JOIN tutores t ON ps.tutor_id = t.id
                WHERE ps.id = :id
            ");
            $stmtDetalle->execute(['id' =>$pase_id]);
            $pase_generado =$stmtDetalle->fetch(PDO::FETCH_ASSOC);

            $stmtCount =$pdo->prepare("SELECT COUNT(*) FROM pases_salida WHERE alumno_id = :alumno_id");
            $stmtCount->execute(['alumno_id' =>$real_alumno_id]);
            $pase_generado['numero_pase_alumno'] = (int)$stmtCount->fetchColumn();

            if ($pase_generado['numero_pase_alumno'] > 3) {
                $mensaje_alerta = "⚠️ ADVERTENCIA: Pase #" . $pase_generado['numero_pase_alumno'] . ". Excede el límite de 3 pases permitidos.";
                $tipo_alerta = "advertencia";
            } else {
                $mensaje_alerta = "✓ Pase de salida #" . $pase_generado['numero_pase_alumno'] . " registrado con éxito.";
                $tipo_alerta = "exito";
            }
        }
    } catch (PDOException $e) {$mensaje_alerta = "Error de Base de Datos: " . $e->getMessage();$tipo_alerta = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CBTis 258 - Pase de Salida</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        :root {
            --cbtis-vino: #691C32;
            --cbtis-vino-dark: #3A0B18;
            --cbtis-beige: #C2A269;
            --card-glass: rgba(255, 255, 255, 0.92);
            --text-dark: #0f172a;
            --text-muted: #475569;
            --border-color: rgba(0, 0, 0, 0.1);
        }

        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Montserrat', sans-serif; }

        body {
            min-height: 100vh; width: 100%;
            background: linear-gradient(135deg, #cbd5e1 0%, #94a3b8 100%);
            background-attachment: fixed; color: var(--text-dark); padding: 30px 20px;
            display: flex; justify-content: center;
        }

        .container { width: 100%; max-width: 1100px; position: relative; z-index: 2; }

        /* Estilo idéntico al menú de altas.php */
        .nav-bar {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 25px; background: var(--card-glass); backdrop-filter: blur(14px);
            padding: 14px 24px; border-radius: 18px; border: 1px solid var(--border-color);
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
        }

        .nav-brand { display: flex; align-items: center; gap: 12px; }

        .cobra-avatar {
            width: 46px; height: 46px; border-radius: 50%;
            background: radial-gradient(circle, #7A1E3A 0%, var(--cbtis-vino-dark) 100%);
            padding: 3px; border: 2px solid var(--cbtis-beige);
            display: flex; align-items: center; justify-content: center;
        }

        .cobra-avatar img { width: 100%; height: 100%; object-fit: contain; }

        .nav-title h3 { font-size: 1.1rem; font-weight: 800; color: var(--cbtis-vino); }
        .nav-title p { font-size: 0.75rem; color: var(--text-muted); font-weight: 600; }

        .btn-home {
            background: var(--cbtis-vino); color: white; text-decoration: none;
            padding: 10px 18px; border-radius: 12px; font-weight: 700; font-size: 0.85rem;
            transition: all 0.3s ease;
        }

        .btn-home:hover { background: var(--cbtis-vino-dark); }

        .alert-box { padding: 14px 20px; border-radius: 14px; font-weight: 700; text-align: center; margin-bottom: 25px; }
        .alert-box.exito { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
        .alert-box.advertencia { background: #fef2f2; color: #991b1b; border: 2px solid #ef4444; }
        .alert-box.error { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }

        .grid-main { display: grid; grid-template-columns: 1.2fr 0.8fr; gap: 25px; align-items: start; }
        @media (max-width: 900px) { .grid-main { grid-template-columns: 1fr; } }

        .card {
            background: var(--card-glass); backdrop-filter: blur(14px);
            padding: 26px; border-radius: 22px; border: 1px solid var(--border-color);
            box-shadow: 0 12px 30px rgba(0,0,0,0.06); margin-bottom: 25px;
        }

        .card-section-title {
            font-size: 1.15rem; font-weight: 800; color: var(--cbtis-vino);
            margin-bottom: 18px; display: flex; align-items: center; gap: 10px;
            border-bottom: 2px solid rgba(0, 0, 0, 0.05); padding-bottom: 10px;
        }

        .group { display: flex; flex-direction: column; gap: 6px; margin-bottom: 16px; position: relative; }
        .group label { font-size: 0.8rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase; }

        .group input, .group textarea, .group select {
            padding: 12px 14px; border: 1.5px solid #cbd5e1; border-radius: 12px;
            font-size: 0.92rem; background: rgba(255, 255, 255, 0.95); font-weight: 600; outline: none;
        }

        .group input:focus, .group textarea:focus, .group select:focus {
            border-color: var(--cbtis-vino); box-shadow: 0 0 0 4px rgba(105, 28, 50, 0.12);
        }

        .suggestions-box {
            position: absolute; top: 100%; left: 0; right: 0;
            background: #ffffff; border: 2px solid var(--cbtis-vino); border-radius: 12px;
            max-height: 220px; overflow-y: auto; z-index: 999; box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            display: none; margin-top: 4px;
        }

        .suggestion-item {
            padding: 12px 16px; cursor: pointer; font-size: 0.9rem; font-weight: 600;
            border-bottom: 1px solid #f1f5f9; color: var(--text-dark);
        }
        .suggestion-item:hover { background: #f8fafc; color: var(--cbtis-vino); font-weight: 700; }

        .alumno-confirmado-box {
            background: #e0f2fe; border: 1px solid #7dd3fc; color: #0369a1;
            padding: 10px 14px; border-radius: 10px; font-weight: 700; font-size: 0.85rem;
            margin-top: 6px; display: none;
        }

        .autorizados-container {
            border: 2px dashed #cbd5e1; border-radius: 16px; padding: 16px;
            background: rgba(255, 255, 255, 0.6); margin-top: 6px;
        }

        .aut-card-item {
            background: #ffffff; border: 2px solid #e2e8f0; border-radius: 14px;
            padding: 14px; margin-bottom: 12px; cursor: pointer; transition: all 0.2s ease;
        }
        .aut-card-item:last-child { margin-bottom: 0; }
        
        .aut-card-item.selected {
            border-color: #059669; background: #f0fdf4; box-shadow: 0 4px 12px rgba(5, 150, 105, 0.12);
        }

        .aut-badge { background: var(--cbtis-vino); color: white; padding: 3px 8px; border-radius: 6px; font-size: 0.72rem; font-weight: 800; display: inline-block; margin-bottom: 6px; }

        .ine-image-preview {
            width: 100%; max-height: 180px; object-fit: contain; border-radius: 8px;
            border: 1px solid #cbd5e1; margin-top: 8px; background: #f8fafc; display: block;
        }

        .btn-submit {
            background: linear-gradient(135deg, var(--cbtis-vino) 0%, var(--cbtis-vino-dark) 100%);
            color: white; border: none; padding: 15px; font-weight: 800; border-radius: 14px;
            cursor: pointer; width: 100%; font-size: 1rem; box-shadow: 0 8px 20px rgba(105, 28, 50, 0.3);
            margin-top: 15px;
        }

        .actions-grid { display: flex; gap: 12px; margin-top: 18px; }
        .btn-print { flex: 1; background: #0284c7; color: white; border: none; padding: 12px; font-weight: 800; border-radius: 12px; cursor: pointer; }
        .btn-download-pdf { flex: 1; background: #059669; color: white; border: none; padding: 12px; font-weight: 800; border-radius: 12px; cursor: pointer; }

        .pass-card {
            background: white; border: 2px solid var(--cbtis-vino); border-radius: 18px;
            overflow: hidden; box-shadow: 0 10px 25px rgba(0,0,0,0.08);
        }

        .pass-head {
            background: var(--cbtis-vino); color: white; padding: 16px;
            text-align: center; border-bottom: 4px solid var(--cbtis-beige);
        }

        .pass-head h3 { font-size: 1.2rem; font-weight: 900; }
        .pass-head p { font-size: 0.78rem; color: var(--cbtis-beige); font-weight: 800; }

        .pass-body { padding: 20px; font-size: 0.88rem; background: #fdfbf7; }

        .pass-row {
            display: flex; justify-content: space-between; align-items: center;
            border-bottom: 1px dashed #cbd5e1; padding: 8px 0; color: var(--text-dark);
        }

        .signatures {
            display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 30px;
            text-align: center; font-size: 0.72rem; font-weight: 800; color: var(--text-muted);
        }

        .sig-box { border-top: 1.5px solid #334155; padding-top: 6px; }

        @media print {
            body { background: white; padding: 0; }
            .nav-bar, .alert-box, .actions-grid, .btn-submit, .form-card { display: none !important; }
            .grid-main { display: block; }
            .pass-card { border: 2px solid #000; max-width: 100%; box-shadow: none; border-radius: 0; }
            .pass-head { background: #691C32 !important; -webkit-print-color-adjust: exact; color: white !important; }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="nav-bar">
        <div class="nav-brand">
            <div class="cobra-avatar">
                <img src="mascota_cobra.png" alt="CBTis" onerror="this.style.display='none'">
            </div>
            <div class="nav-title">
                <h3>Módulo de Pases de Salida</h3>
                <p>CBTis No. 258 "Mariano Escobedo"</p>
            </div>
        </div>
        <a href="menu_principal.php" class="btn-home">⬅️ Volver al Menú</a>
    </div>

    <?php if ($mensaje_alerta): ?>
        <div class="alert-box <?= $tipo_alerta ?>"><?= htmlspecialchars($mensaje_alerta) ?></div>
    <?php endif; ?>

    <div class="grid-main">
        <div class="card form-card">
            <div class="card-section-title">📄 Generar Registro</div>
            <form method="POST">
                <input type="hidden" name="alumno_id_hidden" id="alumno_id_hidden" value="">
                <input type="hidden" name="autorizado_id" id="autorizado_id_input" value="">

                <div class="group">
                    <label>Matrícula del Alumno</label>
                    <input type="text" name="matricula_alumno" id="input_matricula_alumno" placeholder="Escribe los números de la matrícula..." maxlength="20" autocomplete="off" required>
                    <div class="suggestions-box" id="suggestionsBox"></div>
                    <div class="alumno-confirmado-box" id="infoAlumnoConfirmado"></div>
                </div>

                <div class="group">
                    <label>Persona Autorizada e INE</label>
                    <div class="autorizados-container" id="contenedorAutorizados">
                        <p style="font-size:0.82rem; color:var(--text-muted); font-weight:600; text-align:center;">
                            Escribe y selecciona la matrícula del alumno para cargar a sus personas autorizadas y la foto de su INE.
                        </p>
                    </div>
                </div>

                <div style="display:flex; gap:12px;">
                    <div class="group" style="flex:1;">
                        <label>Fecha</label>
                        <input type="date" name="fecha" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="group" style="flex:1;">
                        <label>Hora</label>
                        <input type="time" name="hora" value="<?= date('H:i') ?>" required>
                    </div>
                </div>

                <div class="group">
                    <label>Motivo de Salida</label>
                    <select name="motivo_select" id="select_motivo" onchange="evaluarMotivo(this.value)" required>
                        <option value="" disabled selected>-- Selecciona un motivo --</option>
                        <option value="salud">Salud</option>
                        <option value="situación personal">Situación personal</option>
                        <option value="otro">Otro</option>
                    </select>
                </div>

                <div class="group" id="group_motivo_detalle" style="display: none;">
                    <label>Especifica la razón (Otro)</label>
                    <textarea name="motivo_detalle" id="input_motivo_detalle" rows="2" placeholder="Escribe el motivo detallado de la salida..."></textarea>
                </div>

                <button type="submit" class="btn-submit">💾 Registrar e Imprimir Pase</button>
            </form>

            <?php if ($pase_generado): ?>
                <div class="actions-grid">
                    <button onclick="window.print()" class="btn-print">🖨️ Imprimir</button>
                    <button onclick="descargarPDF()" class="btn-download-pdf">📥 Descargar PDF</button>
                </div>
            <?php endif; ?>
        </div>

        <div>
            <?php if ($pase_generado): ?>
                <div class="pass-card" id="areaDescargaPDF">
                    <div class="pass-head">
                        <h3>CBTis No. 258</h3>
                        <p>PASE DE SALIDA OFICIAL</p>
                    </div>
                    <div class="pass-body">
                        <div class="pass-row">
                            <span>Subnúmero de Pase:</span>
                            <strong style="color:var(--cbtis-vino);">PASE #<?= $pase_generado['numero_pase_alumno'] ?></strong>
                        </div>
                        <div class="pass-row">
                            <span>Alumno:</span>
                            <strong><?= htmlspecialchars($pase_generado['nombre_alumno']) ?></strong>
                        </div>
                        <div class="pass-row">
                            <span>Matrícula / Grupo:</span>
                            <strong><?= htmlspecialchars($pase_generado['matricula']) ?> (<?= htmlspecialchars($pase_generado['grupo']) ?>)</strong>
                        </div>
                        <div class="pass-row">
                            <span>Fecha / Hora:</span>
                            <strong><?= $pase_generado['fecha_salida'] ?> - <?=$pase_generado['hora_salida'] ?></strong>
                        </div>
                        <div class="pass-row">
                            <span>Persona Responsable / Tutor:</span>
                            <strong><?= htmlspecialchars($pase_generado['nombre_tutor']) ?></strong>
                        </div>
                        <div style="margin-top:12px; background:#fff; padding:10px; border:1px solid #cbd5e1; border-radius:10px;">
                            <span style="font-size:0.75rem; font-weight:800; color:var(--text-muted); text-transform:uppercase; display:block; margin-bottom:2px;">Motivo:</span>
                            <p style="font-weight:600; color:var(--text-dark);"><?= htmlspecialchars($pase_generado['motivo']) ?></p>
                        </div>

                        <?php if ($pase_generado['numero_pase_alumno'] > 3): ?>
                            <div style="background:#fee2e2; color:#991b1b; padding:10px; border-radius:10px; font-size:0.78rem; font-weight:800; text-align:center; margin-top:14px;">
                                🚨 Advertencia: El alumno ha superado el límite de 3 pases permitidos.
                            </div>
                        <?php endif; ?>

                        <div class="signatures">
                            <div class="sig-box">Firma del Autorizado</div>
                            <div class="sig-box">Sello u Orientación</div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="card" style="text-align:center; color:var(--text-muted); font-weight:600; padding:40px 20px;">
                    📌 Ingresa los números de la matrícula del alumno para desplegar la lista e INE de las personas autorizadas.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
const inputMatricula = document.getElementById('input_matricula_alumno');
const alumnoIdHidden = document.getElementById('alumno_id_hidden');
const suggestionsBox = document.getElementById('suggestionsBox');
const autorizadoIdInput = document.getElementById('autorizado_id_input');
const infoAlumnoConfirmado = document.getElementById('infoAlumnoConfirmado');
const contenedorAutorizados = document.getElementById('contenedorAutorizados');

function evaluarMotivo(valor) {
    const groupDetalle = document.getElementById('group_motivo_detalle');
    const inputDetalle = document.getElementById('input_motivo_detalle');

    if (valor === 'otro') {
        groupDetalle.style.display = 'flex';
        inputDetalle.setAttribute('required', 'required');
        inputDetalle.focus();
    } else {
        groupDetalle.style.display = 'none';
        inputDetalle.removeAttribute('required');
        inputDetalle.value = '';
    }
}

inputMatricula.addEventListener('input', function() {
    const q = this.value.trim();
    if (q.length < 1) {
        suggestionsBox.style.display = 'none';
        infoAlumnoConfirmado.style.display = 'none';
        alumnoIdHidden.value = '';
        autorizadoIdInput.value = '';
        contenedorAutorizados.innerHTML = '<p style="font-size:0.82rem; color:var(--text-muted); font-weight:600; text-align:center;">Escribe y selecciona la matrícula del alumno...</p>';
        return;
    }

    fetch(`?action=buscar_alumnos&q=${encodeURIComponent(q)}`)
        .then(res => res.json())
        .then(alumnos => {
            suggestionsBox.innerHTML = '';
            if (!alumnos || alumnos.length === 0) {
                suggestionsBox.style.display = 'none';
                return;
            }

            alumnos.forEach(a => {
                const item = document.createElement('div');
                item.className = 'suggestion-item';
                item.innerHTML = `<strong>${a.matricula}</strong> - ${a.nombre_completo} (${a.grupo})`;
                
                item.addEventListener('click', () => {
                    inputMatricula.value = a.matricula;
                    alumnoIdHidden.value = a.matricula;
                    suggestionsBox.style.display = 'none';

                    infoAlumnoConfirmado.textContent = `👤 Alumno: ${a.nombre_completo} - Grupo: ${a.grupo}`;
                    infoAlumnoConfirmado.style.display = 'block';

                    cargarPersonasAutorizadas(a.matricula);
                });
                suggestionsBox.appendChild(item);
            });
            suggestionsBox.style.display = 'block';
        })
        .catch(err => {
            console.error("Error al buscar alumnos:", err);
        });
});

function cargarPersonasAutorizadas(alumnoRef) {
    contenedorAutorizados.innerHTML = '<p style="font-size:0.85rem; color:var(--text-muted); font-weight:700; text-align:center;">Cargando personas autorizadas e INE...</p>';

    fetch(`?action=obtener_autorizados&alumno_id=${encodeURIComponent(alumnoRef)}`)
        .then(res => res.json())
        .then(response => {
            contenedorAutorizados.innerHTML = '';

            if (response.status === 'success' && response.data.length > 0) {
                response.data.forEach((aut, index) => {
                    const card = document.createElement('div');
                    card.className = `aut-card-item ${index === 0 ? 'selected' : ''}`;
                    card.id = `aut_card_${aut.id}`;

                    if (index === 0) {
                        autorizadoIdInput.value = aut.id;
                    }

                    let htmlIne = '';
                    if (aut.foto_ine) {
                        htmlIne = `<img src="${aut.foto_ine}" class="ine-image-preview" alt="INE Autorizado" onerror="this.src='https://via.placeholder.com/350x180?text=Foto+INE+No+Encontrada'">`;
                    } else {
                        htmlIne = '<p style="font-size:0.75rem; color:#ef4444; font-weight:700; margin-top:6px;">⚠️ Sin foto de INE registrada</p>';
                    }

                    card.innerHTML = `
                        <div class="aut-badge">${aut.parentesco}</div>
                        <strong style="display:block; font-size:0.95rem; color:var(--cbtis-vino);">${aut.nombre}</strong>
                        <p style="font-size:0.82rem; color:var(--text-muted); font-weight:600;">📞 Teléfono: ${aut.telefono}</p>
                        ${htmlIne}
                    `;

                    card.addEventListener('click', () => {
                        document.querySelectorAll('.aut-card-item').forEach(c => c.classList.remove('selected'));
                        card.classList.add('selected');
                        autorizadoIdInput.value = aut.id;
                    });

                    contenedorAutorizados.appendChild(card);
                });
            } else {
                contenedorAutorizados.innerHTML = `<p style="font-size:0.85rem; color:#ef4444; font-weight:700; text-align:center;">${response.message || 'No se encontraron personas autorizadas.'}</p>`;
                autorizadoIdInput.value = '';
            }
        })
        .catch(err => {
            contenedorAutorizados.innerHTML = '<p style="font-size:0.85rem; color:#ef4444; font-weight:700; text-align:center;">Error de conexión al obtener autorizados.</p>';
        });
}

document.addEventListener('click', (e) => {
    if (!inputMatricula.contains(e.target) && !suggestionsBox.contains(e.target)) {
        suggestionsBox.style.display = 'none';
    }
});
</script>

<?php if ($pase_generado): ?>
<script>
function descargarPDF() {
    const elemento = document.getElementById('areaDescargaPDF');
    const opciones = {
        margin:       10,
        filename:     'Pase_Salida_<?= htmlspecialchars($pase_generado['matricula']) ?>.pdf',
        image:        { type: 'jpeg', quality: 0.98 },
        html2canvas:  { scale: 2 },
        jsPDF:        { unit: 'mm', format: 'a5', orientation: 'portrait' }
    };
    html2pdf().set(opciones).from(elemento).save();
}
</script>
<?php endif; ?>

</body>
</html>