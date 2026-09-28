<?php
require 'conexion.php';

$busqueda = trim($_GET['busqueda'] ?? '');
$pases = [];
$error_db = '';

try {
    $sql = "
        SELECT ps.*, 
               CONCAT(a.nombre, ' ', a.primer_apellido, ' ', COALESCE(a.segundo_apellido, '')) AS nombre_alumno,
               a.matricula, a.grupo,
               t.nombre AS nombre_tutor
        FROM pases_salida ps
        INNER JOIN alumnos a ON ps.alumno_id = a.matricula
        INNER JOIN tutores t ON ps.tutor_id = t.id
        WHERE 1=1
    ";

    if ($busqueda !== '') {
        $sql .= " AND (a.matricula LIKE :b OR a.nombre LIKE :b OR a.primer_apellido LIKE :b OR t.nombre LIKE :b)";
    }

    $sql .= " ORDER BY ps.fecha_salida DESC, ps.hora_salida DESC, ps.id DESC";

    $stmt = $pdo->prepare($sql);

    if ($busqueda !== '') {
        $stmt->execute(['b' => "%$busqueda%"]);
    } else {
        $stmt->execute();
    }

    $pases = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error_db = "Error en la consulta: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CBTis 258 - Historial de Pases</title>
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

        /* Encabezado idéntico al de Altas, Pases y Directorio */
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

        /* Contenedor Principal */
        .card {
            background: var(--card-glass);
            backdrop-filter: blur(14px);
            padding: 28px;
            border-radius: 22px;
            border: 1px solid var(--border-color);
            box-shadow: 0 12px 30px rgba(0,0,0,0.06);
            margin-bottom: 25px;
        }

        /* Formulario de Búsqueda */
        .filter-bar {
            display: flex;
            gap: 12px;
            margin-bottom: 22px;
        }

        .filter-bar input {
            flex: 1;
            padding: 12px 16px;
            border: 1.5px solid #cbd5e1;
            border-radius: 12px;
            font-size: 0.9rem;
            background: rgba(255, 255, 255, 0.95);
            font-weight: 600;
            outline: none;
        }

        .filter-bar input:focus {
            border-color: var(--cbtis-vino);
            box-shadow: 0 0 0 4px rgba(105, 28, 50, 0.12);
        }

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

        /* Tabla Estilizada */
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

        .badge-id {
            background: rgba(105, 28, 50, 0.1);
            color: var(--cbtis-vino);
            padding: 4px 10px;
            border-radius: 8px;
            font-weight: 800;
            font-size: 0.8rem;
            display: inline-block;
        }

        .badge-grupo {
            background: #f1f5f9;
            border: 1px solid #cbd5e1;
            padding: 3px 8px;
            border-radius: 6px;
            font-weight: 800;
            font-size: 0.8rem;
        }

        /* Alerta de Error */
        .alert-error {
            background-color: #fee2e2;
            color: #991b1b;
            padding: 14px 20px;
            border-radius: 14px;
            font-weight: 700;
            margin-bottom: 20px;
            border: 1px solid #fca5a5;
            text-align: center;
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
                <h3>Historial General de Pases</h3>
                <p>CBTis No. 258 "Mariano Escobedo"</p>
            </div>
        </div>
        <a href="menu_principal.php" class="btn-home">⬅️ Volver al Menú</a>
    </div>

    <?php if ($error_db): ?>
        <div class="alert-error">⚠️ <?= htmlspecialchars($error_db) ?></div>
    <?php endif; ?>

    <div class="card">
        <form class="filter-bar" method="GET">
            <input type="text" name="busqueda" placeholder="🔍 Buscar por Nombre de Alumno, Matrícula o Tutor..." value="<?= htmlspecialchars($busqueda) ?>">
            <button type="submit" class="btn-search">Buscar</button>
        </form>

        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Pase ID</th>
                        <th>Fecha y Hora</th>
                        <th>Matrícula</th>
                        <th>Alumno</th>
                        <th>Grupo</th>
                        <th>Tutor Autorizado</th>
                        <th>Motivo de Salida</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($pases) > 0): ?>
                        <?php foreach ($pases as $p): ?>
                            <tr>
                                <td><span class="badge-id">Pase #<?= htmlspecialchars($p['id']) ?></span></td>
                                <td><strong><?= htmlspecialchars($p['fecha_salida']) ?></strong> <span style="color:var(--text-muted); font-size:0.8rem;"><?= htmlspecialchars($p['hora_salida']) ?></span></td>
                                <td><strong style="color:var(--cbtis-vino);"><?= htmlspecialchars($p['matricula']) ?></strong></td>
                                <td><strong><?= htmlspecialchars($p['nombre_alumno']) ?></strong></td>
                                <td><span class="badge-grupo"><?= htmlspecialchars($p['grupo']) ?></span></td>
                                <td><?= htmlspecialchars($p['nombre_tutor']) ?></td>
                                <td><?= htmlspecialchars($p['motivo']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="7" style="text-align:center; padding:25px; color:var(--text-muted); font-weight:600;">No se encontraron registros de pases de salida.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>