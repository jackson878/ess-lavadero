<?php
/**
 * Vista: inicio de sesión (no usa la barra lateral).
 *
 * @var string|null $error
 * @var string      $usuario
 */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Iniciar sesión · <?= e(APP_NOMBRE) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= recurso('css/estilos.css') ?>" rel="stylesheet">
</head>
<body class="pagina-login">

<div class="login-contenedor">
    <!-- Panel izquierdo con la marca (se oculta en celular) -->
    <section class="login-panel d-none d-lg-flex">
        <div class="marca mb-0">
            <span class="marca-icono"><i class="bi bi-droplet"></i></span>
            <span>
                <span class="marca-nombre"><?= e(APP_NOMBRE) ?></span>
                <span class="marca-negocio"><?= e(NEGOCIO_NOMBRE) ?></span>
            </span>
        </div>
        <div>
            <h1 class="login-lema">Servicios, caja e insumos<br>en un solo lugar.</h1>
            <p class="login-sublema">Sistema de información para el lavadero de carros y motos.</p>
        </div>
        <small class="text-white-50">Versión <?= e(APP_VERSION) ?></small>
    </section>

    <!-- Formulario -->
    <section class="login-formulario">
        <div class="login-caja">
            <div class="marca d-lg-none mb-4">
                <span class="marca-icono"><i class="bi bi-droplet"></i></span>
                <span class="marca-nombre text-dark"><?= e(APP_NOMBRE) ?></span>
            </div>

            <div class="ceja">Bienvenido</div>
            <h2 class="titulo-pagina mb-4">Iniciar sesión</h2>

            <?php foreach (Sesion::obtenerFlashes() as $flash): ?>
                <div class="alert alerta-<?= e($flash['tipo']) ?>"><?= e($flash['mensaje']) ?></div>
            <?php endforeach; ?>

            <?php if ($error): ?>
                <div class="alert alerta-error" role="alert"><?= e($error) ?></div>
            <?php endif; ?>

            <form method="post" action="<?= url('auth', 'login') ?>" novalidate>
                <?= Csrf::campo() ?>

                <div class="mb-3">
                    <label for="usuario" class="form-label">Usuario</label>
                    <input type="text" class="form-control form-control-lg" id="usuario" name="usuario"
                           value="<?= e($usuario) ?>" autocomplete="username" autocapitalize="none"
                           required autofocus>
                </div>

                <div class="mb-4">
                    <label for="password" class="form-label">Contraseña</label>
                    <div class="input-group input-group-lg">
                        <input type="password" class="form-control" id="password" name="password"
                               autocomplete="current-password" required>
                        <button class="btn btn-ess-borde" type="button" id="verClave" aria-label="Mostrar contraseña">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn btn-ess btn-lg w-100">Ingresar</button>
            </form>

            <?php if (APP_ENTORNO === 'desarrollo'): ?>
                <div class="login-ayuda mt-4">
                    <strong>Usuarios de prueba</strong> (contraseña <code>Lavadero2026*</code>):
                    <code>dueno</code>, <code>cajero</code>, <code>lavador</code>
                </div>
            <?php endif; ?>
        </div>
    </section>
</div>

<script>
    // Mostrar / ocultar la contraseña
    document.getElementById('verClave').addEventListener('click', function () {
        const campo = document.getElementById('password');
        const visible = campo.type === 'text';
        campo.type = visible ? 'password' : 'text';
        this.innerHTML = visible ? '<i class="bi bi-eye"></i>' : '<i class="bi bi-eye-slash"></i>';
    });
</script>
</body>
</html>
