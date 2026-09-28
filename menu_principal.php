<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menú Principal - CBTis No. 258</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --cbtis-vino: #691C32;
            --cbtis-vino-dark: #3A0B18;
            --cbtis-vino-hover: #8B0000;
            --bg-gray: #e2e8f0; /* Fondo gris base suave */
            --panel-glass: rgba(255, 255, 255, 0.45); /* Transparencia para el panel derecho */
            --card-glass: rgba(255, 255, 255, 0.85); /* Transparencia para las tarjetas */
            --text-dark: #0f172a;
            --text-muted: #475569;
            --border-color: rgba(255, 255, 255, 0.6);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Montserrat', sans-serif;
        }

        body {
            height: 100vh;
            width: 100vw;
            display: flex;
            overflow: hidden;
            /* Fondo base con degradado gris elegante */
            background: linear-gradient(135deg, #cbd5e1 0%, #94a3b8 100%);
        }

        /* --- COLUMNA IZQUIERDA --- */
        .left-panel {
            width: 45%;
            height: 100%;
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 40px 35px;
            color: #ffffff;
            background: radial-gradient(circle at 50% 40%, #7A1E3A 0%, var(--cbtis-vino-dark) 100%);
            overflow: hidden;
        }

        .left-panel::before {
            content: "";
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background-image: radial-gradient(rgba(255, 255, 255, 0.08) 1px, transparent 1px);
            background-size: 24px 24px;
            pointer-events: none;
            opacity: 0.6;
        }

        .brand-header {
            position: relative;
            z-index: 5;
        }

        .brand-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(255, 255, 255, 0.1);
            padding: 4px 12px;
            border-radius: 20px;
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255, 255, 255, 0.15);
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: 1.5px;
            color: #ffb6c1;
            margin-bottom: 8px;
            text-transform: uppercase;
        }

        .status-dot {
            width: 6px;
            height: 6px;
            background-color: #22c55e;
            border-radius: 50%;
            box-shadow: 0 0 8px #22c55e;
        }

        .brand-header h1 {
            font-size: 2.2rem;
            font-weight: 900;
            line-height: 1.1;
            text-shadow: 0 4px 10px rgba(0,0,0,0.3);
        }

        .illustration-container {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: auto 0;
            height: 480px;
            z-index: 2;
        }

        .glow-effect {
            position: absolute;
            width: 320px;
            height: 320px;
            background: radial-gradient(circle, rgba(255, 77, 109, 0.25) 0%, rgba(0,0,0,0) 70%);
            border-radius: 50%;
            z-index: 1;
            animation: pulseGlow 3s ease-in-out infinite alternate;
        }

        .mascot-img {
            height: 100%;
            max-height: 450px;
            object-fit: contain;
            filter: drop-shadow(0 20px 30px rgba(0, 0, 0, 0.6));
            z-index: 2;
            transform: scale(1.08);
            transition: transform 0.3s ease;
        }

        .mascot-img:hover {
            transform: scale(1.12);
        }

        .pass-ticket {
            position: absolute;
            right: 5px;
            top: 32%;
            background: #ffffff;
            color: var(--text-dark);
            padding: 18px 22px;
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
            border-left: 6px solid var(--cbtis-vino);
            transform: rotate(6deg);
            z-index: 4;
            width: 215px;
            animation: floatTicket 4s ease-in-out infinite;
        }

        .pass-ticket .pass-header {
            font-size: 0.65rem;
            font-weight: 800;
            color: var(--cbtis-vino);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 4px;
        }

        .pass-ticket .pass-title {
            font-size: 0.95rem;
            font-weight: 800;
            margin-bottom: 8px;
        }

        .pass-ticket .pass-status {
            display: inline-block;
            background: #dcfce7;
            color: #15803d;
            font-size: 0.7rem;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 20px;
        }

        @keyframes floatTicket {
            0%, 100% { transform: rotate(6deg) translateY(0px); }
            50% { transform: rotate(8deg) translateY(-8px); }
        }

        @keyframes pulseGlow {
            0% { transform: scale(0.9); opacity: 0.5; }
            100% { transform: scale(1.1); opacity: 0.9; }
        }

        .left-footer {
            position: relative;
            font-size: 0.75rem;
            color: rgba(255, 255, 255, 0.65);
            line-height: 1.5;
            z-index: 5;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding-top: 15px;
        }

        /* --- COLUMNA DERECHA (Con Transparencia y Efecto Cristal / Glassmorphism) --- */
        .right-panel {
            width: 55%;
            height: 100%;
            background: var(--panel-glass);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 60px 80px;
            overflow-y: auto;
            border-left: 1px solid rgba(255, 255, 255, 0.4);
        }

        .menu-header {
            margin-bottom: 35px;
        }

        .menu-header .subtitle {
            font-size: 0.8rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: var(--cbtis-vino);
            margin-bottom: 6px;
        }

        .menu-header h2 {
            font-size: 2.2rem;
            font-weight: 900;
            color: #0f172a;
            letter-spacing: -0.5px;
            margin-bottom: 8px;
        }

        .menu-header p {
            font-size: 0.95rem;
            color: #334155;
            font-weight: 600;
        }

        .menu-grid {
            display: flex;
            flex-direction: column;
            gap: 18px;
            max-width: 560px;
        }

        .menu-card {
            position: relative;
            display: flex;
            align-items: center;
            padding: 20px 24px;
            background: var(--card-glass);
            backdrop-filter: blur(8px);
            border-radius: 18px;
            border: 1px solid var(--border-color);
            text-decoration: none;
            overflow: hidden;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
        }

        .menu-card::before {
            content: "";
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 5px;
            background: var(--cbtis-vino);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .menu-card:hover {
            transform: translateY(-3px) translateX(4px);
            background: #ffffff;
            border-color: rgba(105, 28, 50, 0.3);
            box-shadow: 0 16px 32px rgba(105, 28, 50, 0.15);
        }

        .menu-card:hover::before {
            opacity: 1;
        }

        .card-icon {
            width: 54px;
            height: 54px;
            min-width: 54px;
            border-radius: 14px;
            background: rgba(241, 245, 249, 0.9);
            color: var(--text-dark);
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 20px;
            transition: all 0.3s ease;
        }

        .menu-card:hover .card-icon {
            background: var(--cbtis-vino);
            color: #ffffff;
            transform: scale(1.05);
        }

        .card-content {
            flex: 1;
        }

        .card-title-row {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 4px;
        }

        .card-content h4 {
            font-size: 1.05rem;
            font-weight: 800;
            color: var(--text-dark);
        }

        .card-content p {
            font-size: 0.85rem;
            color: var(--text-muted);
            line-height: 1.4;
            font-weight: 500;
        }

        .card-badge {
            font-size: 0.65rem;
            font-weight: 800;
            text-transform: uppercase;
            padding: 3px 8px;
            border-radius: 6px;
            background: #ffe4e6;
            color: var(--cbtis-vino);
            letter-spacing: 0.5px;
        }

        .card-arrow {
            font-size: 1.2rem;
            color: var(--text-muted);
            opacity: 0.5;
            transition: all 0.3s ease;
            margin-left: 10px;
        }

        .menu-card:hover .card-arrow {
            opacity: 1;
            color: var(--cbtis-vino);
            transform: translateX(5px);
        }

        .menu-card.primary-card {
            background: linear-gradient(135deg, rgba(105, 28, 50, 0.95) 0%, rgba(58, 11, 24, 0.95) 100%);
            border-color: rgba(255, 255, 255, 0.2);
            color: #ffffff;
        }

        .menu-card.primary-card .card-icon {
            background: rgba(255, 255, 255, 0.15);
            color: #ffffff;
        }

        .menu-card.primary-card .card-content h4 {
            color: #ffffff;
        }

        .menu-card.primary-card .card-content p {
            color: rgba(255, 255, 255, 0.8);
        }

        .menu-card.primary-card .card-arrow {
            color: #ffffff;
            opacity: 0.7;
        }

        .menu-card.primary-card:hover {
            box-shadow: 0 16px 35px rgba(105, 28, 50, 0.35);
        }

        .menu-card.primary-card:hover .card-icon {
            background: #ffffff;
            color: var(--cbtis-vino);
        }

        @media (max-width: 900px) {
            body { flex-direction: column; overflow-y: auto; }
            .left-panel, .right-panel { width: 100%; height: auto; }
            .left-panel { padding: 40px 20px; }
            .right-panel { padding: 40px 24px; }
            .illustration-container { height: 320px; }
            .mascot-img { max-height: 280px; }
        }
    </style>
</head>
<body>

    <!-- LADO IZQUIERDO: Mascota Cobra -->
    <div class="left-panel">
        <div class="brand-header">
            <div class="brand-badge">
                <span class="status-dot"></span>
                CBTis No. 258
            </div>
            <h1>Pases de Salida</h1>
        </div>

        <div class="illustration-container">
            <div class="glow-effect"></div>

            <!-- Imagen de la cobra -->
            <img src="mascota_cobra.png" alt="Cobra CBTis 258" class="mascot-img">

            <!-- Pase de Salida Flotante -->
            <div class="pass-ticket">
                <div class="pass-header">CBTis No. 258</div>
                <div class="pass-title">Pase de Salida</div>
                <div class="pass-status">✓ AUTORIZADO</div>
            </div>
        </div>

        <div class="left-footer">
            <strong>2026 © CBTis No. 258 "Mariano Escobedo de la Peña"</strong><br>
            Sistema de Control Escolar Digital
        </div>
    </div>

    <!-- LADO DERECHO: Panel Principal Transparente (Glassmorphism) -->
    <div class="right-panel">
        <div class="menu-header">
            <div class="subtitle">Módulo de Administración</div>
            <h2>Panel Principal</h2>
            <p>Selecciona una acción para continuar en el sistema:</p>
        </div>

        <div class="menu-grid">
            <!-- 1. Generar Pase -->
            <a href="generar_pase.php" class="menu-card primary-card">
                <div class="card-icon">🎫</div>
                <div class="card-content">
                    <div class="card-title-row">
                        <h4>Generar Pase de Salida</h4>
                        <span class="card-badge" style="background: rgba(255,255,255,0.2); color: #fff;">Principal</span>
                    </div>
                    <p>Emitir e imprimir pases oficiales de salida para alumnos.</p>
                </div>
                <div class="card-arrow">➔</div>
            </a>

            <!-- 2. Directorio -->
            <a href="lista_alumnos.php" class="menu-card">
                <div class="card-icon">👥</div>
                <div class="card-content">
                    <div class="card-title-row">
                        <h4>Información de Alumnos</h4>
                    </div>
                    <p>Consultar matrícula, estados (Activo, Dual, Baja) y expedientes.</p>
                </div>
                <div class="card-arrow">➔</div>
            </a>

            <!-- 3. Registro y Vinculación -->
            <a href="altas.php" class="menu-card">
                <div class="card-icon">⚙️</div>
                <div class="card-content">
                    <div class="card-title-row">
                        <h4>Registro de alumnos y tutores</h4>
                    </div>
                    <p>Alta de nuevos estudiantes y tutores legales.</p>
                </div>
                <div class="card-arrow">➔</div>
            </a>

            <!-- 4. Historial -->
            <a href="reportes.php" class="menu-card">
                <div class="card-icon">📊</div>
                <div class="card-content">
                    <div class="card-title-row">
                        <h4>Historial</h4>
                    </div>
                    <p>Auditoría de pases emitidos, registros de fecha y reportes del plantel.</p>
                </div>
                <div class="card-arrow">➔</div>
            </a>
        </div>
    </div>

</body>
</html>