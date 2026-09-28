<?php
session_start();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim($_POST['usuario'] ?? '');
    $password = trim($_POST['password'] ?? '');

    // Validación de usuario ADMIN y contraseña 12345
    if (strtoupper($usuario) === 'ADMIN' && $password === '12345') {
        $_SESSION['usuario_id'] = 1;
        $_SESSION['usuario_nombre'] = 'ADMIN';
        
        header("Location: menu_principal.php");
        exit;
    } else {
        $error = "Usuario o contraseña incorrectos.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bienvenido - CBTis No. 258</title>
    <!-- Fuentes tipográficas modernas -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --red-cbtis: #691C32;         /* Vino oficial CBTis */
            --red-hover: #4A1323;         /* Vino oscuro */
            --red-bright: #8B0000;        /* Rojo vivo para detalles */
            --white: #ffffff;
            --overlay-dark: rgba(20, 5, 10, 0.72); /* Filtro para resaltar contenido */
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
            justify-content: center;
            align-items: center;
            overflow: hidden;
            position: relative;
            background-color: #0f0508;
        }

        /* Fondo dinámico con tu imagen fondo_cbtis.jfif */
        .bg-image {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: linear-gradient(var(--overlay-dark), var(--overlay-dark)), url('fondo_cbtis.jfif');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            filter: blur(4px);
            transform: scale(1.05);
            z-index: 1;
        }

        /* Contenedor Principal (Tarjeta Minimalista Glassmorphism) */
        .card-container {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 440px;
            margin: 0 20px;
            padding: 45px 35px;
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-radius: 24px;
            border: 1px solid rgba(255, 255, 255, 0.18);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.6);
            text-align: center;
            color: var(--white);
            transition: all 0.4s ease;
        }

        /* Línea superior acentuada en rojo */
        .card-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 80%;
            height: 4px;
            background: linear-gradient(90deg, transparent, var(--red-bright), var(--white), var(--red-bright), transparent);
            border-radius: 4px;
        }

        /* Insignia / Subtítulo */
        .badge-institution {
            display: inline-block;
            font-size: 0.85rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 3px;
            color: #ff9ebb;
            margin-bottom: 12px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.5);
        }

        .main-title {
            font-size: 2.2rem;
            font-weight: 900;
            letter-spacing: -0.5px;
            margin-bottom: 8px;
            line-height: 1.2;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.5);
        }

        .subtitle {
            font-size: 0.95rem;
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 35px;
            font-weight: 400;
        }

        /* Botón Circular/Elegante "Entrar" */
        .btn-enter-container {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-top: 10px;
        }

        .btn-enter {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            border: 2px solid rgba(255, 255, 255, 0.6);
            background: linear-gradient(135deg, var(--red-cbtis) 0%, #a82346 100%);
            color: var(--white);
            font-size: 1.1rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 2px;
            cursor: pointer;
            box-shadow: 0 10px 25px rgba(105, 28, 50, 0.5);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            outline: none;
        }

        .btn-enter:hover {
            transform: scale(1.08);
            border-color: var(--white);
            box-shadow: 0 15px 35px rgba(230, 50, 80, 0.6);
            background: linear-gradient(135deg, #82223e 0%, var(--red-bright) 100%);
        }

        /* Formulario Login Animado */
        .login-form {
            display: flex;
            flex-direction: column;
            gap: 20px;
            text-align: left;
            animation: fadeInUp 0.5s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .form-group label {
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: rgba(255, 255, 255, 0.9);
        }

        .form-group input {
            width: 100%;
            padding: 14px 18px;
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.25);
            background: rgba(255, 255, 255, 0.92);
            font-size: 0.95rem;
            color: #0f172a;
            outline: none;
            transition: all 0.25s ease;
            font-weight: 600;
        }

        .form-group input:focus {
            background: var(--white);
            border-color: var(--red-bright);
            box-shadow: 0 0 0 4px rgba(219, 39, 119, 0.3);
        }

        .error-message {
            background-color: rgba(220, 38, 38, 0.85);
            color: var(--white);
            padding: 12px;
            border-radius: 10px;
            font-size: 0.85rem;
            font-weight: 700;
            border: 1px solid rgba(255, 255, 255, 0.3);
            margin-bottom: 15px;
            text-align: center;
        }

        .btn-submit {
            width: 100%;
            padding: 16px;
            border-radius: 12px;
            border: none;
            background: linear-gradient(135deg, var(--red-cbtis) 0%, #8b1538 100%);
            color: var(--white);
            font-size: 1rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 2px;
            cursor: pointer;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.4);
            transition: all 0.3s ease;
            margin-top: 10px;
        }

        .btn-submit:hover {
            background: linear-gradient(135deg, #82223e 0%, var(--red-bright) 100%);
            transform: translateY(-2px);
            box-shadow: 0 12px 25px rgba(139, 21, 56, 0.5);
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(15px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body>

    <!-- Imagen de fondo con filtro -->
    <div class="bg-image"></div>

    <!-- Contenedor Principal -->
    <div class="card-container">
        <span class="badge-institution">CBTis No. 258</span>

        <!-- PANTALLA 1: Bienvenida -->
        <?php if (empty($error) && $_SERVER['REQUEST_METHOD'] !== 'POST'): ?>
            <div id="welcomeScreen">
                <h1 class="main-title">Bienvenido al Sistema</h1>
                <p class="subtitle">Control de Pases de Salida</p>
                
                <div class="btn-enter-container">
                    <button class="btn-enter" onclick="activarLogin()">Entrar</button>
                </div>
            </div>

            <!-- PANTALLA 2 (Aparece tras dar clic en Entrar) -->
            <div id="loginScreen" style="display: none;">
                <h1 class="main-title" style="font-size: 1.7rem;">Iniciar Sesión</h1>
                <p class="subtitle">Ingresa tus datos de acceso</p>

                <form action="" method="POST" class="login-form">
                    <div class="form-group">
                        <label for="usuario">Usuario</label>
                        <input type="text" id="usuario" name="usuario" placeholder="Ingresa tu usuario" required autocomplete="off">
                    </div>

                    <div class="form-group">
                        <label for="password">Contraseña</label>
                        <input type="password" id="password" name="password" placeholder="Ingresa tu contraseña" required>
                    </div>

                    <button type="submit" class="btn-submit">Ingresar</button>
                </form>
            </div>

        <!-- VISTA SI HUBO ERROR DE CREDENCIALES -->
        <?php else: ?>
            <div>
                <h1 class="main-title" style="font-size: 1.7rem;">Iniciar Sesión</h1>
                <p class="subtitle">Ingresa tus datos de acceso</p>

                <div class="error-message">
                    <?= htmlspecialchars($error) ?>
                </div>

                <form action="" method="POST" class="login-form">
                    <div class="form-group">
                        <label for="usuario">Usuario</label>
                        <input type="text" id="usuario" name="usuario" placeholder="Ingresa tu usuario" required autocomplete="off">
                    </div>

                    <div class="form-group">
                        <label for="password">Contraseña</label>
                        <input type="password" id="password" name="password" placeholder="Ingresa tu contraseña" required>
                    </div>

                    <button type="submit" class="btn-submit">Ingresar</button>
                </form>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function activarLogin() {
            const welcomeScreen = document.getElementById('welcomeScreen');
            const loginScreen = document.getElementById('loginScreen');

            welcomeScreen.style.display = 'none';
            loginScreen.style.display = 'block';

            // Poner el foco en el campo de usuario
            setTimeout(() => {
                document.getElementById('usuario').focus();
            }, 100);
        }
    </script>

</body>
</html>