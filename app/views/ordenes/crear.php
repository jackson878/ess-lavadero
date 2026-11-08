<?php
/**
 * Vista: registro rápido de un servicio (RF-04, RNF-04).
 *
 * Flujo con un vehículo ya registrado (3 clics):
 *   1. Escribir la placa -> se autocompletan vehículo y cliente.
 *   2. Clic en el servicio (o varios).
 *   3. Clic en "Registrar". El lavador viene sugerido (el de menos carga).
 *
 * @var array $datos
 * @var array $errores
 * @var array $catalogo   servicios activos de carro y moto
 * @var array $lavadores  [id, nombre, pendientes]
 */
require VIEWS_PATH . 'layouts/header.php';

// Si al enviar faltaban datos del cliente nuevo, la sección de vehículo nuevo se muestra abierta
// (JavaScript también la abre o cierra al consultar la placa)
$mostrarNuevo = isset($errores['cliente_nombre']) || isset($errores['cliente_telefono']);
?>

<div class="mb-4">
    <a href="<?= url('orden') ?>" class="enlace-volver"><i class="bi bi-arrow-left"></i> Volver a servicios</a>
    <h1 class="titulo-pagina mt-2">Nuevo servicio</h1>
</div>

<?php if ($errores): ?>
    <div class="alert alerta-error">Revise los datos marcados en rojo.</div>
<?php endif; ?>

<form method="post" action="<?= url('orden', 'crear') ?>" id="formOrden" novalidate>
    <?= Csrf::campo() ?>

    <div class="row g-3">
        <div class="col-lg-8">
            <!-- 1. Vehículo -->
            <div class="tarjeta mb-3">
                <h2 class="paso"><span>1</span> Vehículo</h2>
                <label for="placa" class="form-label">Placa *</label>
                <input type="text" class="form-control form-control-lg campo-placa campo-placa-grande<?= campo_invalido($errores, 'placa') ?>"
                       id="placa" name="placa" value="<?= e($datos['placa']) ?>" maxlength="8" placeholder="ABC123"
                       autocomplete="off" autofocus required
                       data-url="<?= url('vehiculo', 'buscar') ?>">
                <?= campo_error($errores, 'placa') ?>

                <!-- Vehículo encontrado: se llena con JavaScript -->
                <div id="vehiculoEncontrado" class="vehiculo-encontrado mt-3" hidden></div>

                <!-- Vehículo nuevo: se muestra si la placa no está registrada -->
                <div id="vehiculoNuevo" class="mt-3" <?= $mostrarNuevo ? '' : 'hidden' ?>>
                    <div class="aviso aviso-alerta mb-3">Placa nueva: registre el vehículo y su dueño.</div>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label d-block">Tipo *</label>
                            <div class="btn-group w-100" role="group">
                                <?php foreach (Vehiculo::TIPOS as $tipo): ?>
                                    <input type="radio" class="btn-check" name="tipo" id="tipo-<?= $tipo ?>" value="<?= $tipo ?>"
                                           <?= $datos['tipo'] === $tipo ? 'checked' : '' ?>>
                                    <label class="btn btn-ess-borde selector-tipo" for="tipo-<?= $tipo ?>">
                                        <i class="bi <?= $tipo === 'moto' ? 'bi-scooter' : 'bi-car-front' ?>"></i> <?= ucfirst($tipo) ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label for="marca" class="form-label">Marca</label>
                            <input type="text" class="form-control" id="marca" name="marca" value="<?= e($datos['marca']) ?>" maxlength="50" placeholder="Mazda 3">
                        </div>
                        <div class="col-md-4">
                            <label for="color" class="form-label">Color</label>
                            <input type="text" class="form-control" id="color" name="color" value="<?= e($datos['color']) ?>" maxlength="30" placeholder="Blanco">
                        </div>
                        <div class="col-md-7">
                            <label for="cliente_nombre" class="form-label">Nombre del cliente *</label>
                            <input type="text" class="form-control<?= campo_invalido($errores, 'cliente_nombre') ?>" id="cliente_nombre"
                                   name="cliente_nombre" value="<?= e($datos['cliente_nombre']) ?>" maxlength="100">
                            <?= campo_error($errores, 'cliente_nombre') ?>
                        </div>
                        <div class="col-md-5">
                            <label for="cliente_telefono" class="form-label">Celular *</label>
                            <input type="tel" class="form-control<?= campo_invalido($errores, 'cliente_telefono') ?>" id="cliente_telefono"
                                   name="cliente_telefono" value="<?= e($datos['cliente_telefono']) ?>" maxlength="13"
                                   inputmode="numeric" placeholder="3001234567">
                            <?= campo_error($errores, 'cliente_telefono') ?: '<div class="form-text">Si el celular ya existe, el vehículo se asigna a ese cliente.</div>' ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. Servicios -->
            <div class="tarjeta mb-3">
                <h2 class="paso"><span>2</span> Servicios</h2>
                <?php if (isset($errores['servicios'])): ?>
                    <div class="text-danger small mb-2"><?= e($errores['servicios']) ?></div>
                <?php endif; ?>
                <div class="row g-2">
                    <?php foreach ($catalogo as $s): ?>
                        <div class="col-sm-6 col-xl-3" data-tipo-servicio="<?= e($s['tipo_vehiculo']) ?>"
                             <?= $s['tipo_vehiculo'] !== $datos['tipo'] ? 'hidden' : '' ?>>
                            <input type="checkbox" class="btn-check" name="servicios[]" id="serv-<?= (int) $s['id'] ?>"
                                   value="<?= (int) $s['id'] ?>" data-precio="<?= (int) $s['precio'] ?>"
                                   data-duracion="<?= (int) $s['duracion_min'] ?>"
                                   <?= in_array((int) $s['id'], $datos['servicios'], true) ? 'checked' : '' ?>>
                            <label class="opcion-servicio" for="serv-<?= (int) $s['id'] ?>">
                                <span class="fw-bold d-block"><?= e($s['nombre']) ?></span>
                                <span class="d-block"><?= pesos($s['precio']) ?></span>
                                <span class="small text-secondary"><i class="bi bi-clock"></i> <?= e(duracion_texto((int) $s['duracion_min'])) ?></span>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- 3. Lavador -->
            <div class="tarjeta mb-3">
                <h2 class="paso"><span>3</span> Lavador</h2>
                <?php if (!$lavadores): ?>
                    <div class="alert alerta-error mb-0">No hay lavadores activos. El dueño debe crear o activar uno en Usuarios.</div>
                <?php endif; ?>
                <div class="d-flex flex-wrap gap-2">
                    <?php foreach ($lavadores as $i => $l): ?>
                        <input type="radio" class="btn-check" name="lavador_id" id="lav-<?= (int) $l['id'] ?>"
                               value="<?= (int) $l['id'] ?>" <?= (int) $datos['lavador_id'] === (int) $l['id'] ? 'checked' : '' ?>>
                        <label class="opcion-lavador" for="lav-<?= (int) $l['id'] ?>">
                            <span class="fw-bold"><?= e(explode(' ', $l['nombre'])[0]) ?></span>
                            <span class="small text-secondary d-block">
                                <?= (int) $l['pendientes'] ?> en cola<?= $i === 0 ? ' · sugerido' : '' ?>
                            </span>
                        </label>
                    <?php endforeach; ?>
                </div>
                <?= isset($errores['lavador_id']) ? '<div class="text-danger small mt-2">' . e($errores['lavador_id']) . '</div>' : '' ?>

                <label for="observaciones" class="form-label mt-3">Observaciones</label>
                <input type="text" class="form-control" id="observaciones" name="observaciones"
                       value="<?= e($datos['observaciones']) ?>" maxlength="255" placeholder="Ej.: tiene un rayón en la puerta">
            </div>
        </div>

        <!-- Resumen (fijo a la derecha en computador) -->
        <div class="col-lg-4">
            <div class="tarjeta resumen-orden">
                <h2 class="tarjeta-titulo">Resumen</h2>
                <div id="resumenVehiculo" class="text-secondary mb-3">Escriba la placa…</div>
                <ul id="resumenServicios" class="list-unstyled mb-3"></ul>
                <div class="d-flex justify-content-between align-items-baseline border-top pt-3">
                    <span class="text-secondary">Total</span>
                    <span class="kpi-valor mb-0" id="resumenTotal">$ 0</span>
                </div>
                <div class="text-secondary small mb-3" id="resumenTiempo">Tiempo estimado: —</div>
                <button type="submit" class="btn btn-ess btn-lg w-100">Registrar servicio</button>
            </div>
        </div>
    </div>
</form>

<?php require VIEWS_PATH . 'layouts/footer.php'; ?>
