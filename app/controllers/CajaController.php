<?php
/**
 * CajaController: caja diaria con apertura, cobro y cierre (RF-05).
 * Acceso: Dueño y Cajero (el Lavador NO puede ver la caja).
 *
 *   ?c=caja                       caja de hoy: apertura, órdenes por cobrar y cobros
 *   ?c=caja&a=abrir               abrir con base inicial (POST)
 *   ?c=caja&a=cobrar&id=N         cobrar la orden N (GET formulario / POST)
 *   ?c=caja&a=cerrar[&id=N]       cierre: esperado vs. contado (GET / POST)
 *   ?c=caja&a=historial           cajas anteriores
 *   ?c=caja&a=ver&id=N            detalle de una caja
 */
class CajaController extends Controller
{
    private Caja $caja;

    public function __construct()
    {
        $this->requiereRol('dueno', 'cajero');
        $this->caja = new Caja();
    }

    public function index(): void
    {
        $hoy = $this->caja->deHoy();

        $this->vista('caja/index', [
            'titulo'     => 'Caja diaria',
            'menuActivo' => 'caja',
            'pestana'    => 'hoy',
            'caja'       => $hoy,
            'anterior'   => $this->caja->abiertaAnterior(),
            'totales'    => $hoy ? $this->caja->totales((int) $hoy['id']) : null,
            'pagos'      => $hoy ? (new Pago())->porCaja((int) $hoy['id']) : [],
            'porCobrar'  => (new Orden())->porCobrar(),
        ]);
    }

    public function abrir(): void
    {
        if (!$this->esPost()) {
            $this->redirigir('caja');
        }

        $base = $this->leerPesos('base_inicial');

        if ($this->caja->deHoy()) {
            Sesion::flash('error', 'La caja de hoy ya fue abierta.');
        } elseif ($anterior = $this->caja->abiertaAnterior()) {
            Sesion::flash('error', 'Antes de abrir, cierre la caja del ' . date('d/m/Y', strtotime($anterior['fecha'])) . ' que quedó abierta.');
        } elseif ($base < 0 || $base > 10000000) {
            Sesion::flash('error', 'La base inicial debe estar entre $ 0 y $ 10.000.000.');
        } else {
            try {
                $this->caja->abrir($this->usuario['id'], $base);
                Sesion::flash('exito', 'Caja abierta con una base de ' . pesos($base) . '.');
            } catch (PDOException $e) {
                // UNIQUE(fecha): otro usuario la abrió al mismo tiempo
                Sesion::flash('error', 'La caja de hoy ya fue abierta por otro usuario.');
            }
        }

        $this->redirigir('caja');
    }

    public function cobrar(): void
    {
        $id     = (int) ($_GET['id'] ?? 0);
        $orden  = (new Orden())->buscarDetalle($id);
        $cajaHoy = $this->caja->deHoy();

        // Validaciones previas: la orden debe estar terminada, sin cobrar, y la caja abierta
        if (!$orden) {
            Sesion::flash('error', 'La orden no existe.');
            $this->redirigir('caja');
        }
        if ((int) $orden['pagado']) {
            Sesion::flash('aviso', 'La orden #' . $id . ' ya fue cobrada.');
            $this->redirigir('orden', 'ver', ['id' => $id]);
        }
        if ($orden['estado'] !== 'terminado') {
            Sesion::flash('error', 'Solo se cobran órdenes terminadas. La orden #' . $id . ' está "' . estado_texto($orden['estado']) . '".');
            $this->redirigir('orden', 'ver', ['id' => $id]);
        }
        if (!$cajaHoy || $cajaHoy['estado'] !== 'abierta') {
            Sesion::flash('error', $cajaHoy ? 'La caja de hoy ya está cerrada; no se pueden registrar más cobros.'
                                             : 'Abra la caja del día antes de cobrar.');
            $this->redirigir('caja');
        }

        $datos   = ['metodo' => 'efectivo', 'referencia' => '', 'entregar' => 1];
        $errores = [];

        if ($this->esPost()) {
            $datos = [
                'metodo'     => $this->entrada('metodo'),
                'referencia' => mb_substr($this->entrada('referencia'), 0, 50),
                'entregar'   => isset($_POST['entregar']) ? 1 : 0,
            ];

            if (!in_array($datos['metodo'], Caja::METODOS, true)) {
                $errores['metodo'] = 'Seleccione el medio de pago.';
            }
            if ($datos['referencia'] !== '' && !preg_match('/^[A-Za-z0-9\- ]+$/', $datos['referencia'])) {
                $errores['referencia'] = 'Use solo letras, números y guiones.';
            }

            if (!$errores) {
                $error = $this->registrarCobro($id, (int) $cajaHoy['id'], $datos);
                if ($error === null) {
                    Sesion::flash('exito', 'Cobrados ' . pesos($orden['total']) . ' (' . metodo_texto($datos['metodo']) . ') a '
                        . placa_formato($orden['placa']) . ($datos['entregar'] ? '. Vehículo entregado.' : '.'));
                    $this->redirigir(($_POST['volver'] ?? '') === 'orden' ? 'orden' : 'caja');
                }
                Sesion::flash('error', $error);
                $this->redirigir('caja');
            }
        }

        $this->vista('caja/cobrar', [
            'titulo'     => 'Cobrar orden #' . $id,
            'menuActivo' => 'caja',
            'orden'      => $orden,
            'detalle'    => (new Orden())->detalle($id),
            'datos'      => $datos,
            'errores'    => $errores,
            'volver'     => $_GET['volver'] ?? 'caja',
            'scripts'    => [recurso('js/caja.js')],
        ]);
    }

    public function cerrar(): void
    {
        // Por defecto la de hoy; con ?id= se puede cerrar una caja anterior olvidada
        $id   = (int) ($_GET['id'] ?? ($this->caja->deHoy()['id'] ?? 0));
        $caja = $this->caja->buscarPorId($id);

        if (!$caja || $caja['estado'] !== 'abierta') {
            Sesion::flash('error', 'No hay una caja abierta para cerrar.');
            $this->redirigir('caja');
        }

        $totales  = $this->caja->totales($id);
        $esperado = (float) $caja['base_inicial'] + $totales['efectivo'];
        $datos    = ['efectivo_contado' => '', 'observaciones' => ''];
        $errores  = [];

        if ($this->esPost()) {
            $datos = [
                'efectivo_contado' => trim((string) ($_POST['efectivo_contado'] ?? '')),
                'observaciones'    => mb_substr($this->entrada('observaciones'), 0, 255),
            ];
            $contado = $this->leerPesos('efectivo_contado');

            if ($datos['efectivo_contado'] === '') {
                $errores['efectivo_contado'] = 'Escriba cuánto efectivo hay en la caja.';
            } elseif ($contado > 50000000) {
                $errores['efectivo_contado'] = 'El valor no es válido.';
            } elseif ($contado != $esperado && $datos['observaciones'] === '') {
                // Control: toda diferencia debe quedar explicada
                $errores['observaciones'] = 'Hay una diferencia de ' . pesos($contado - $esperado) . '. Explique el motivo.';
            }

            if (!$errores) {
                if ($this->caja->cerrar($id, $this->usuario['id'], $contado, $datos['observaciones'])) {
                    $dif = $contado - $esperado;
                    Sesion::flash($dif == 0 ? 'exito' : 'aviso', 'Caja cerrada. '
                        . ($dif == 0 ? 'El efectivo cuadra exactamente.'
                                     : ($dif < 0 ? 'Faltante de ' . pesos(-$dif) . '.' : 'Sobrante de ' . pesos($dif) . '.')));
                } else {
                    Sesion::flash('error', 'La caja ya había sido cerrada.');
                }
                $this->redirigir('caja', 'ver', ['id' => $id]);
            }
        }

        $this->vista('caja/cerrar', [
            'titulo'     => 'Cerrar caja',
            'menuActivo' => 'caja',
            'caja'       => $caja,
            'totales'    => $totales,
            'esperado'   => $esperado,
            'pendientes' => count((new Orden())->porCobrar()),
            'datos'      => $datos,
            'errores'    => $errores,
            'scripts'    => [recurso('js/caja.js')],
        ]);
    }

    public function historial(): void
    {
        $this->vista('caja/historial', [
            'titulo'     => 'Historial de caja',
            'menuActivo' => 'caja',
            'pestana'    => 'historial',
            'cajas'      => $this->caja->historial(60),
        ]);
    }

    public function ver(): void
    {
        $id   = (int) ($_GET['id'] ?? 0);
        $caja = $this->caja->buscarPorId($id);
        if (!$caja) {
            Sesion::flash('error', 'La caja no existe.');
            $this->redirigir('caja', 'historial');
        }

        $this->vista('caja/ver', [
            'titulo'     => 'Caja del ' . date('d/m/Y', strtotime($caja['fecha'])),
            'menuActivo' => 'caja',
            'caja'       => $caja,
            'totales'    => $this->caja->totales($id),
            'pagos'      => (new Pago())->porCaja($id),
        ]);
    }

    // ------------------------------------------------------------------

    /**
     * Registra el pago y, si se pidió, entrega el vehículo; todo en UNA
     * transacción. Devuelve un mensaje de error o null si salió bien.
     */
    private function registrarCobro(int $ordenId, int $cajaId, array $datos): ?string
    {
        $db     = Database::conectar();
        $ordenes = new Orden();

        $db->beginTransaction();
        try {
            // Se bloquea la orden: si dos cajeros cobran a la vez, el segundo espera y ve que ya está paga
            $orden = $ordenes->bloquearParaCobro($ordenId);
            if (!$orden || $orden['estado'] !== 'terminado' || (int) $orden['pagado']) {
                $db->rollBack();
                return 'La orden #' . $ordenId . ' ya no está pendiente de cobro.';
            }

            // El valor es el total de la orden (calculado en el servidor al crearla)
            (new Pago())->crear($ordenId, $cajaId, $datos['metodo'], (float) $orden['total'],
                $datos['referencia'], $this->usuario['id']);

            if ($datos['entregar']) {
                $error = $ordenes->cambiarEstado($ordenId, 'entregado', $this->usuario['id']);
                if ($error) {
                    $db->rollBack();
                    return $error;
                }
            }

            $db->commit();
            return null;
        } catch (Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }

    /** Lee un valor en pesos escrito como "100.000" o "$ 100000" y lo devuelve como entero. */
    private function leerPesos(string $campo): int
    {
        return (int) preg_replace('/\D/', '', (string) ($_POST[$campo] ?? ''));
    }
}
