<?php
/**
 * Vista: lista de usuarios (solo Dueño).
 *
 * @var array $usuarios
 * @var array $usuarioActual
 */
require VIEWS_PATH . 'layouts/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
    <div>
        <div class="ceja">Administración</div>
        <h1 class="titulo-pagina">Usuarios</h1>
    </div>
    <a class="btn btn-ess" href="<?= url('usuario', 'crear') ?>">+ Nuevo usuario</a>
</div>

<div class="tarjeta">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h2 class="tarjeta-titulo mb-0"><?= count($usuarios) ?> usuarios registrados</h2>
        <div class="buscador">
            <i class="bi bi-search"></i>
            <input type="search" class="form-control" placeholder="Buscar por nombre, usuario o rol"
                   data-buscar="#tablaUsuarios" aria-label="Buscar usuarios">
        </div>
    </div>

    <div class="table-responsive">
        <table class="table tabla-ess" id="tablaUsuarios">
            <thead>
            <tr>
                <th>Nombre</th>
                <th>Usuario</th>
                <th>Rol</th>
                <th>Celular</th>
                <th>Último acceso</th>
                <th>Estado</th>
                <th class="text-end">Acciones</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($usuarios as $u): ?>
                <?php $esYo = (int) $u['id'] === $usuarioActual['id']; ?>
                <tr class="<?= (int) $u['activo'] ? '' : 'fila-inactiva' ?>">
                    <td class="fw-semibold">
                        <?= e($u['nombre']) ?>
                        <?php if ($esYo): ?><span class="text-secondary small">(usted)</span><?php endif; ?>
                    </td>
                    <td><?= e($u['usuario']) ?></td>
                    <td><?= rol_badge($u['rol']) ?></td>
                    <td><?= e($u['telefono'] ?? '—') ?></td>
                    <td class="text-nowrap">
                        <?= $u['ultimo_acceso'] ? e(date('d/m/Y H:i', strtotime($u['ultimo_acceso']))) : '<span class="text-secondary">Nunca</span>' ?>
                    </td>
                    <td>
                        <?php if ((int) $u['activo']): ?>
                            <span class="punto punto-activo"></span>Activo
                        <?php else: ?>
                            <span class="punto punto-inactivo"></span>Inactivo
                        <?php endif; ?>
                    </td>
                    <td class="text-end text-nowrap">
                        <a class="btn btn-sm btn-ess-borde" href="<?= url('usuario', 'editar', ['id' => $u['id']]) ?>">
                            <i class="bi bi-pencil"></i> Editar
                        </a>
                        <?php if (!$esYo): ?>
                            <form method="post" action="<?= url('usuario', 'estado') ?>" class="d-inline"
                                  data-confirmar="¿Seguro que desea <?= (int) $u['activo'] ? 'desactivar' : 'activar' ?> a <?= e($u['nombre']) ?>?">
                                <?= Csrf::campo() ?>
                                <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                                <?php if ((int) $u['activo']): ?>
                                    <button type="submit" class="btn btn-sm btn-ess-borde text-danger">
                                        <i class="bi bi-person-slash"></i> Desactivar
                                    </button>
                                <?php else: ?>
                                    <button type="submit" class="btn btn-sm btn-ess-borde text-success">
                                        <i class="bi bi-person-check"></i> Activar
                                    </button>
                                <?php endif; ?>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require VIEWS_PATH . 'layouts/footer.php'; ?>
