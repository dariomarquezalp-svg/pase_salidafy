<?php
require 'conexion.php';

$mensaje = '';
$tipo_mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'actualizar_estatus') {
        $matricula = $_POST['alumno_id'];
        $nuevo_estatus = $_POST['estatus'];

        try {
            $stmt = $pdo->prepare("UPDATE alumnos SET estatus = :estatus WHERE matricula = :matricula");
            $stmt->execute(['estatus' => $nuevo_estatus, 'matricula' => $matricula]);
            $mensaje = "Estatus del alumno actualizado con éxito.";
            $tipo_mensaje = "exito";
        } catch (PDOException $e) {
            $mensaje = "Error al actualizar: " . $e->getMessage();
            $tipo_mensaje = "error";
        }
    } elseif ($accion === 'guardar_edicion') {
        $matricula_original = $_POST['id'];
        $matricula = trim($_POST['matricula']);
        $nombre = trim($_POST['nombre']);
        $primer_apellido = trim($_POST['primer_apellido']);
        $segundo_apellido = trim($_POST['segundo_apellido']);
        $grupo = trim($_POST['grupo']);
        $estatus = $_POST['estatus'];

        try {
            $stmt = $pdo->prepare("
                UPDATE alumnos 
                SET matricula = :matricula, nombre = :nombre, primer_apellido = :p_app, 
                    segundo_apellido = :s_app, grupo = :grupo, estatus = :estatus
                WHERE matricula = :matricula_orig
            ");
            $stmt->execute([
                'matricula' => $matricula,
                'nombre' => $nombre,
                'p_app' => $primer_apellido,
                's_app' => $segundo_apellido,
                'grupo' => $grupo,
                'estatus' => $estatus,
                'matricula_orig' => $matricula_original
            ]);
            $mensaje = "Datos del alumno guardados correctamente.";
            $tipo_mensaje = "exito";
        } catch (PDOException $e) {
            $mensaje = "Error al guardar edición: " . $e->getMessage();
            $tipo_mensaje = "error";
        }
    }
}

$busqueda = trim($_GET['busqueda'] ?? '');
$filtro_estatus = $_GET['filtro_estatus'] ?? 'TODOS';

$sql = "SELECT * FROM alumnos WHERE 1=1";
$params = [];

if ($busqueda !== '') {
    $sql .= " AND (matricula LIKE :b OR nombre LIKE :b OR primer_apellido LIKE :b)";
    $params['b'] = "%$busqueda%";
}

if ($filtro_estatus !== 'TODOS') {
    $sql .= " AND estatus = :f_estatus";
    $params['f_estatus'] = $filtro_estatus;
}

$sql .= " ORDER BY primer_apellido ASC, nombre ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$alumnos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_activos = $pdo->query("SELECT COUNT(*) FROM alumnos WHERE estatus = 'Activo'")->fetchColumn();
$total_dual = $pdo->query("SELECT COUNT(*) FROM alumnos WHERE estatus = 'Dual'")->fetchColumn();
$total_bajas = $pdo->query("SELECT COUNT(*) FROM alumnos WHERE estatus = 'Baja'")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CBTis 258 - Directorio de Alumnos</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --cbtis-vino: #691C32;
            --cbtis-vino-dark: #3A0B18;
            --cbtis-beige: #C2A269;
            --card-glass: rgba(255, 255, 255, 0.90);
            --text-dark: #0f172a;
            --text-muted: #475569;
            --border-color: rgba(0, 0, 0, 0.08);
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

        .container { width: 100%; max-width: 1150px; position: relative; z-index: 2; }

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
            width: 46px; height: 46px; border-radius: 50%;
            background: radial-gradient(circle, #7A1E3A 0%, var(--cbtis-vino-dark) 100%);
            padding: 3px; border: 2px solid var(--cbtis-beige);
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

        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
            gap: 18px;
            margin-bottom: 25px;
        }

        .metric-card {
            background: var(--card-glass);
            backdrop-filter: blur(14px);
            padding: 20px;
            border-radius: 18px;
            border: 1px solid var(--border-color);
            box-shadow: 0 10px 25px rgba(0,0,0,0.04);
            border-left: 6px solid var(--cbtis-vino);
            transition: transform 0.2s ease;
        }

        .metric-card:hover { transform: translateY(-3px); }
        .metric-card.activos { border-left-color: #10b981; }
        .metric-card.dual { border-left-color: #3b82f6; }
        .metric-card.bajas { border-left-color: #ef4444; }

        .metric-title { font-size: 0.75rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; }
        .metric-value { font-size: 1.8rem; font-weight: 900; margin-top: 6px; color: var(--text-dark); }

        .main-card {
            background: var(--card-glass);
            backdrop-filter: blur(14px);
            padding: 28px;
            border-radius: 22px;
            border: 1px solid var(--border-color);
            box-shadow: 0 12px 30px rgba(0,0,0,0.06);
            margin-bottom: 25px;
        }

        .filter-bar {
            display: flex;
            gap: 12px;
            margin-bottom: 22px;
            flex-wrap: wrap;
        }

        .filter-bar input, .filter-bar select {
            padding: 12px 16px;
            border: 1.5px solid #cbd5e1;
            border-radius: 12px;
            font-size: 0.9rem;
            background: rgba(255, 255, 255, 0.95);
            font-weight: 600;
            outline: none;
        }

        .filter-bar input:focus, .filter-bar select:focus {
            border-color: var(--cbtis-vino);
            box-shadow: 0 0 0 4px rgba(105, 28, 50, 0.12);
        }

        .filter-bar input { flex: 2; min-width: 240px; }
        .filter-bar select { flex: 1; min-width: 180px; }

        .btn-search {
            background: linear-gradient(135deg, var(--cbtis-vino) 0%, var(--cbtis-vino-dark) 100%);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 12px;
            font-weight: 800;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 4px 12px rgba(105, 28, 50, 0.2);
        }

        .btn-search:hover { opacity: 0.95; transform: translateY(-1px); }

        .btn-reset {
            background: #f1f5f9;
            color: var(--text-dark);
            border: 1.5px solid #cbd5e1;
            padding: 12px 18px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 0.9rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
        }

        .btn-reset:hover { background: #e2e8f0; }

        .table-responsive { overflow-x: auto; border-radius: 14px; border: 1px solid #e2e8f0; }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.88rem;
            text-align: left;
            background: rgba(255, 255, 255, 0.7);
        }

        th, td { padding: 14px 16px; border-bottom: 1px solid #e2e8f0; }

        th {
            background: #f8fafc;
            font-weight: 800;
            color: var(--cbtis-vino);
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
        }

        tr:hover { background: rgba(255, 255, 255, 0.95); }

        .badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 800;
            display: inline-block;
        }

        .badge.Activo { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
        .badge.Dual { background: #dbeafe; color: #1e40af; border: 1px solid #bfdbfe; }
        .badge.Baja { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }

        .status-select {
            padding: 6px 10px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 0.82rem;
            border: 1.5px solid #cbd5e1;
            outline: none;
            cursor: pointer;
            background: #ffffff;
        }

        .btn-edit {
            background: #f1f5f9;
            color: var(--text-dark);
            border: 1px solid #cbd5e1;
            padding: 7px 14px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 0.8rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-edit:hover { background: #e2e8f0; border-color: #94a3b8; }

        .alert-box {
            padding: 14px 20px;
            border-radius: 14px;
            font-weight: 700;
            text-align: center;
            margin-bottom: 25px;
            font-size: 0.9rem;
        }

        .alert-box.exito { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
        .alert-box.error { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }

        .modal {
            display: none;
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(15, 23, 42, 0.65);
            backdrop-filter: blur(8px);
            justify-content: center;
            align-items: center;
            z-index: 1000;
            padding: 20px;
        }

        .modal-content {
            background: #ffffff;
            border-radius: 22px;
            padding: 30px;
            width: 100%;
            max-width: 480px;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
            border: 1px solid rgba(255, 255, 255, 0.8);
            animation: popIn 0.3s ease;
        }

        @keyframes popIn {
            0% { opacity: 0; transform: scale(0.9); }
            100% { opacity: 1; transform: scale(1); }
        }

        .modal-content h3 {
            font-size: 1.25rem;
            font-weight: 800;
            color: var(--cbtis-vino);
            margin-bottom: 18px;
            border-bottom: 2px solid #f1f5f9;
            padding-bottom: 10px;
        }

        .form-group { margin-bottom: 14px; }
        .form-group label { display: block; font-size: 0.78rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase; margin-bottom: 5px; }

        .form-group input, .form-group select {
            width: 100%;
            padding: 10px 14px;
            border: 1.5px solid #cbd5e1;
            border-radius: 10px;
            font-size: 0.9rem;
            font-weight: 600;
            outline: none;
        }

        .form-group input:focus, .form-group select:focus {
            border-color: var(--cbtis-vino);
            box-shadow: 0 0 0 3px rgba(105, 28, 50, 0.12);
        }

        .modal-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 22px; }

        .btn-cancel {
            background: #f1f5f9;
            color: var(--text-dark);
            border: 1px solid #cbd5e1;
            padding: 10px 18px;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 700;
            font-size: 0.85rem;
        }

        .btn-save {
            background: var(--cbtis-vino);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 800;
            font-size: 0.85rem;
            transition: all 0.2s ease;
        }

        .btn-save:hover { background: var(--cbtis-vino-dark); }
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
                <h3>Directorio de Alumnos</h3>
                <p>CBTis No. 258 "Mariano Escobedo"</p>
            </div>
        </div>
        <a href="menu_principal.php" class="btn-home">⬅️ Volver al Menú</a>
    </div>

    <div class="metrics-grid">
        <div class="metric-card activos">
            <div class="metric-title">Alumnos Activos</div>
            <div class="metric-value"><?= $total_activos ?></div>
        </div>
        <div class="metric-card dual">
            <div class="metric-title">Modelo Dual</div>
            <div class="metric-value"><?= $total_dual ?></div>
        </div>
        <div class="metric-card bajas">
            <div class="metric-title">Dados de Baja</div>
            <div class="metric-value"><?= $total_bajas ?></div>
        </div>
        <div class="metric-card">
            <div class="metric-title">Total Registrados</div>
            <div class="metric-value"><?= count($alumnos) ?></div>
        </div>
    </div>

    <?php if ($mensaje): ?>
        <div class="alert-box <?= $tipo_mensaje ?>"><?= htmlspecialchars($mensaje) ?></div>
    <?php endif; ?>

    <div class="main-card">
        <form class="filter-bar" id="formFiltro" method="GET">
            <input type="text" name="busqueda" id="inputBusqueda" placeholder="🔍 Buscar por Matrícula o Nombre..." value="<?= htmlspecialchars($busqueda) ?>">
            
            <select name="filtro_estatus" id="selectEstatus" onchange="limpiarBusquedaYSubmit()">
                <option value="TODOS" <?= $filtro_estatus === 'TODOS' ? 'selected' : '' ?>>Todos los Estatus</option>
                <option value="Activo" <?= $filtro_estatus === 'Activo' ? 'selected' : '' ?>>Solo Activos</option>
                <option value="Dual" <?= $filtro_estatus === 'Dual' ? 'selected' : '' ?>>Solo Modelo Dual</option>
                <option value="Baja" <?= $filtro_estatus === 'Baja' ? 'selected' : '' ?>>Solo Dados de Baja</option>
            </select>

            <button type="submit" class="btn-search">Filtrar</button>
            <?php if (!empty($busqueda) || $filtro_estatus !== 'TODOS'): ?>
                <a href="lista_alumnos.php" class="btn-reset" title="Restablecer Filtros">🔄 Limpiar</a>
            <?php endif; ?>
        </form>

        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Matrícula</th>
                        <th>Nombre Completo</th>
                        <th>Grupo</th>
                        <th>Estatus Actual</th>
                        <th>Cambiar Estatus</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($alumnos) > 0): ?>
                        <?php foreach ($alumnos as $a): ?>
                            <tr>
                                <td><strong style="color:var(--cbtis-vino);"><?= htmlspecialchars($a['matricula']) ?></strong></td>
                                <td><strong><?= htmlspecialchars($a['nombre'] . ' ' . $a['primer_apellido'] . ' ' . $a['segundo_apellido']) ?></strong></td>
                                <td><span style="background:#f1f5f9; border: 1px solid #cbd5e1; padding:3px 8px; border-radius:6px; font-weight:800; font-size: 0.8rem;"><?= htmlspecialchars($a['grupo']) ?></span></td>
                                <td>
                                    <span class="badge <?= $a['estatus'] ?>"><?= $a['estatus'] ?></span>
                                </td>
                                <td>
                                    <form method="POST" style="margin:0;">
                                        <input type="hidden" name="accion" value="actualizar_estatus">
                                        <input type="hidden" name="alumno_id" value="<?= $a['matricula'] ?>">
                                        <select name="estatus" class="status-select" onchange="this.form.submit()">
                                            <option value="Activo" <?= $a['estatus'] === 'Activo' ? 'selected' : '' ?>>Activo</option>
                                            <option value="Dual" <?= $a['estatus'] === 'Dual' ? 'selected' : '' ?>>Modelo Dual</option>
                                            <option value="Baja" <?= $a['estatus'] === 'Baja' ? 'selected' : '' ?>>Dado de Baja</option>
                                        </select>
                                    </form>
                                </td>
                                <td>
                                    <button class="btn-edit" onclick='abrirModal(<?= json_encode($a) ?>)'>✏️ Editar Datos</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6" style="text-align:center; padding:25px; color:var(--text-muted); font-weight:600;">No se encontraron alumnos registrados con esos criterios.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="modalEditar" class="modal">
    <div class="modal-content">
        <h3>✏️ Editar Datos del Alumno</h3>
        <form method="POST">
            <input type="hidden" name="accion" value="guardar_edicion">
            <input type="hidden" id="edit_id" name="id">

            <div class="form-group">
                <label>Matrícula</label>
                <input type="text" id="edit_matricula" name="matricula" required>
            </div>
            <div class="form-group">
                <label>Nombre(s)</label>
                <input type="text" id="edit_nombre" name="nombre" required>
            </div>
            <div class="form-group">
                <label>Primer Apellido</label>
                <input type="text" id="edit_primer_apellido" name="primer_apellido" required>
            </div>
            <div class="form-group">
                <label>Segundo Apellido</label>
                <input type="text" id="edit_segundo_apellido" name="segundo_apellido">
            </div>
            <div class="form-group">
                <label>Grupo</label>
                <input type="text" id="edit_grupo" name="grupo" required>
            </div>
            <div class="form-group">
                <label>Estatus</label>
                <select id="edit_estatus" name="estatus">
                    <option value="Activo">Activo</option>
                    <option value="Dual">Modelo Dual</option>
                    <option value="Baja">Dado de Baja</option>
                </select>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="cerrarModal()">Cancelar</button>
                <button type="submit" class="btn-save">💾 Guardar Cambios</button>
            </div>
        </form>
    </div>
</div>

<script>
function limpiarBusquedaYSubmit() {
    // Vacía el campo de texto independientemente de la opción seleccionada
    document.getElementById('inputBusqueda').value = '';
    document.getElementById('formFiltro').submit();
}

function abrirModal(alumno) {
    document.getElementById('edit_id').value = alumno.matricula;
    document.getElementById('edit_matricula').value = alumno.matricula;
    document.getElementById('edit_nombre').value = alumno.nombre;
    document.getElementById('edit_primer_apellido').value = alumno.primer_apellido;
    document.getElementById('edit_segundo_apellido').value = alumno.segundo_apellido || '';
    document.getElementById('edit_grupo').value = alumno.grupo;
    document.getElementById('edit_estatus').value = alumno.estatus;

    document.getElementById('modalEditar').style.display = 'flex';
}

function cerrarModal() {
    document.getElementById('modalEditar').style.display = 'none';
}
</script>

</body>
</html>