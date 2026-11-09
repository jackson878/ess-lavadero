<?php
/**
 * Vista: ficha del equipo con historial de mantenimientos y formulario para registrar uno.
 *
 * @var array $equipo
 * @var array $mantenimientos
 * @var array $m        valores del formulario de mantenimiento
 * @var array $errores
 */
require VIEWS_PATH . 'layouts/header.php';
?>

<div class="mb-4">
    <a href="<?= url('equipo') ?>" class="enlace-volver"><i class="bi bi-arrow-left"></i> Volver a equipos</a>
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mt-2">
        <div>
            <div class="ceja"><?= e(equipo_tipo_texto($equipo['tipo'])) ?></div>
            <h1 class="titulo-pagina"><?= e($equipo['nombre']) ?></h1>
            <p class="text-secondary mb-0 mt-1">
                <?= e($equipo['marca'] ?? 'Sin marca') ?><?= $equipo['serial'] ? ' · Serial ' . e($equipo['serial']) : '' ?>
                <?= $equipo['fecha_compra'] ? ' · Comprado el ' . e(date('d/m/Y', strtotime($equipo['fecha_compra']))) : '' ?>
            </p>
        </div>
        <a class="btn btn-ess-borde" href="<?= url('equipo', 'editar', ['id' => $equipo['id']]) ?>"><i class="bi bi-pencil"></i> Editar</a>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-sm-4">
        <div class="tarjeta">
            <div class="kpi-etiqueta">Estado</div>
            <div class="mt-2"><span class="estado-equipo estado-equipo-<?= e($equipo['estado']) ?> fs-6"><?= e(equipo_estado_texto($equipo['estado'])) ?></span></div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="tarjeta">
            <div class="kpi-etiqueta">Último mantenimiento</div>
            <div class="kpi-valor fs-3"><?= $equipo['fecha_ultimo_mant'] ? e(date('d/m/Y', strtotime($equipo['fecha_ultimo_mant']))) : '—' ?></div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="tarjeta">
            <div class="kpi-etiqueta">Próximo mantenimiento</div>
            <div class="kpi-valor fs-3"><?= $equipo['fecha_proximo_mant'] ? e(date('d/m/Y', strtotime($equipo['fecha_proximo_mant']))) : '—' ?></div>
            <?= mantenimiento_badge($equipo['dias_para_mant']) ?>
        </div>
    </div>
</div>

<div class="row g-3">
    <!-- Registrar mantenimiento -->
    <div class="col-lg-5">
        <div class="tarjeta">
            <h2 class="tarjeta-titulo">Registrar mantenimiento</h2>
            <?php if ($errores): ?>
                <div class="alert alerta-error">Revise los campos marcados en rojo.</div>
            <?php endif; ?>
            <form method="post" action="<?= url('equipo', 'mantenimiento') ?>" novalidate>
                <?= Csrf::campo() ?>
                <input type="hidden" name="equipo_id" value="<?= (int) $equipo['id'] ?>">
                <div class="row g-3">
                    <div class="col-sm-6">
                        <label for="fecha" class="form-label">Fecha *</label>
                        <input type="date" class="form-control<?= campo_invalido($errores, 'fecha') ?>" id="fecha" name="fecha"
                               value="<?= e($m['fecha']) ?>" max="<?= date('Y-m-d') ?>" required>
                        <?= campo_error($errores, 'fecha') ?>
                    </div>
                    <div class="col-sm-6">
                        <label for="tipo" class="form-label">Tipo *</label>
                        <select class="form-select<?= campo_invalido($errores, 'tipo') ?>" id="tipo" name="tipo">
                            <option value="preventivo" <?= $m['tipo'] === 'preventivo' ? 'selected' : '' ?>>Preventivo</option>
                            <option value="correctivo" <?= $m['tipo'] === 'correctivo' ? 'selected' : '' ?>>Correctivo (reparación)</option>
                        </select>
                        <?= campo_error($errores, 'tipo') ?>
                    </div>
                    <div class="col-12">
                        <label for="descripcion" class="form-label">¿Qué se hizo? *</label>
                        <textarea class="form-control<?= campo_invalido($errores, 'descripcion') ?>" id="descripcion" name="descripcion"
                                  rows="2" maxlength="255" placeholder="Cambio de aceite de la bomba y empaques"><?= e($m['descripcion']) ?></textarea>
                        <?= campo_error($errores, 'descripcion') ?>
                    </div>
                    <div class="col-sm-6">
                        <label for="costo" class="form-label">Costo</label>
                        <div class="input-group has-validation">
                            <span class="input-group-text">$</span>
                            <input type="text" class="form-control<?= campo_invalido($errores, 'costo') ?>" id="costo" name="costo"
                                   value="<?= $m['costo'] !== '' && $m['costo'] !== 0 ? e(number_format((float) $m['costo'], 0, ',', '.')) : '' ?>"
                                   inputmode="numeric" data-formato-pesos placeholder="0">
                            <?= campo_error($errores, 'costo') ?>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <label for="proxima_fecha" class="form-label">Próximo mantenimiento</label>
                        <input type="date" class="form-control<?= campo_invalido($errores, 'proxima_fecha') ?>" id="proxima_fecha"
                               name="proxima_fecha" value="<?= e($m['proxima_fecha']) ?>">
                        <?= campo_error($errores, 'proxima_fecha') ?>
                    </div>
                    <div class="col-12">
                        <label for="estado_equipo" class="form-label">Estado del equipo después *</label>
                        <select class="form-select<?= campo_invalido($errores, 'estado_equipo') ?>" id="estado_equipo" name="estado_equipo">
                            <?php foreach (Equipo::ESTADOS as $s): ?>
                                <option value="<?= e($s) ?>" <?= $m['estado_equipo'] === $s ? 'selected' : '' ?>><?= e(equipo_estado_texto($s)) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?= campo_error($errores, 'estado_equipo') ?>
                    </div>
                </div>
                <button type="submit" class="btn btn-ess w-100 mt-3">Registrar mantenimiento</button>
            </form>
        </div>
    </div>

    <!-- Historial -->
    <div class="col-lg-7">
        <div class="tarjeta">
            <h2 class="tarjeta-titulo">Historial de mantenimientos</h2>
            <?php if (!$mantenimientos): ?>
                <p class="text-secondary mb-0">Este equipo aún no tiene mantenimientos registrados.</p>
            <?php else: ?>
                <ol class="linea-tiempo">
                    <?php foreach ($mantenimientos as $mt): ?>
                        <li class="<?= $mt['tipo'] === 'correctivo' ? 'mant-correctivo' : 'mant-preventivo' ?>">
                            <div class="d-flex justify-content-between gap-2">
                                <strong><?= e(date('d/m/Y', strtotime($mt['fecha']))) ?> · <?= e(ucfirst($mt['tipo'])) ?></strong>
                                <span class="fw-semibold text-nowrap"><?= pesos($mt['costo']) ?></span>
                            </div>
                            <div><?= e($mt['descripcion']) ?></div>
                            <div class="small text-secondary">
                                Registró <?= e(explode(' ', $mt['usuario'])[0]) ?>
                                <?= $mt['proxima_fecha'] ? ' · Próximo: ' . e(date('d/m/Y', strtotime($mt['proxima_fecha']))) : '' ?>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ol>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require VIEWS_PATH . 'layouts/footer.php'; ?>
