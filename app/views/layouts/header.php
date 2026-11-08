<?php
/**
 * Layout - parte superior de todas las páginas internas.
 *
 * Variables que recibe:
 *   $titulo        string  texto de la pestaña del navegador
 *   $menuActivo    string  controlador resaltado en la barra lateral (ej. 'tablero')
 *   $usuarioActual array   usuario en sesión ['id', 'nombre', 'usuario', 'rol'] o null
 */
$titulo        = $titulo ?? APP_NOMBRE;
$menuActivo    = $menuActivo ?? '';
$usuarioActual = $usuarioActual ?? null;
$rolActual     = $usuarioActual['rol'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($titulo) ?> · <?= e(APP_NOMBRE) ?></title>

    <!-- Fuente Manrope, Bootstrap 5 e íconos -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <!-- Estilos propios del sistema -->
    <link href="<?= recurso('css/estilos.css') ?>" rel="stylesheet">
</head>
<body<?= !empty($autorecarga) ? ' data-autorecarga="' . (int) $autorecarga . '"' : '' ?>>

<!-- Barra superior: solo visible en celular, con el botón para abrir el menú -->
<nav class="barra-movil d-lg-none d-flex align-items-center justify-content-between px-3 py-2">
    <span class="fw-bold"><?= e(APP_NOMBRE) ?></span>
    <?php if ($usuarioActual): ?>
        <button class="btn btn-sm text-white fs-4 p-0" type="button"
                data-bs-toggle="offcanvas" data-bs-target="#menuLateral" aria-label="Abrir menú">
            <i class="bi bi-list"></i>
        </button>
    <?php endif; ?>
</nav>

<div class="app">
    <?php if ($usuarioActual): ?>
    <!-- Barra lateral: fija en computador y desplegable (offcanvas) en celular -->
    <aside class="sidebar offcanvas-lg offcanvas-start" tabindex="-1" id="menuLateral">
        <div class="sidebar-cuerpo">
            <div class="d-flex justify-content-between align-items-start">
                <a class="marca" href="<?= url() ?>">
                    <span class="marca-icono"><i class="bi bi-droplet"></i></span>
                    <span>
                        <span class="marca-nombre"><?= e(APP_NOMBRE) ?></span>
                        <span class="marca-negocio"><?= e(NEGOCIO_NOMBRE) ?></span>
                    </span>
                </a>
                <button type="button" class="btn-close btn-close-white d-lg-none"
                        data-bs-dismiss="offcanvas" data-bs-target="#menuLateral" aria-label="Cerrar"></button>
            </div>

            <!-- Opciones según el rol (el permiso real se valida en cada controlador) -->
            <ul class="menu">
                <?php foreach (menu_por_rol($rolActual) as $opcion): ?>
                    <li>
                        <a href="<?= url($opcion['c']) ?>"
                           class="<?= $opcion['c'] === $menuActivo ? 'activo' : '' ?>">
                            <?= e($opcion['texto']) ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>

            <div class="sidebar-pie">
                Sesión: <strong><?= e(rol_texto($rolActual)) ?></strong>
                <div class="small mb-3"><?= e($usuarioActual['nombre']) ?></div>
                <!-- Cerrar sesión por POST con token CSRF -->
                <form method="post" action="<?= url('auth', 'logout') ?>">
                    <?= Csrf::campo() ?>
                    <button type="submit" class="btn-salir">
                        <i class="bi bi-box-arrow-left"></i> Cerrar sesión
                    </button>
                </form>
            </div>
        </div>
    </aside>
    <?php endif; ?>

    <main class="contenido">
        <!-- Mensajes "flash" (éxito, error, aviso) que deja el controlador -->
        <?php foreach (Sesion::obtenerFlashes() as $flash): ?>
            <div class="alert alerta-<?= e($flash['tipo']) ?> alert-dismissible fade show" role="alert">
                <?= e($flash['mensaje']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
            </div>
        <?php endforeach; ?>
