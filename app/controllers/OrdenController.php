<?php
/**
 * OrdenController: registro y seguimiento de órdenes de servicio (RF-04, RF-10).
 * Acceso: Dueño y Cajero.
 *
 *   ?c=orden                          vehículos en el lavadero + historial con filtros
 *   ?c=orden&a=crear                  registro rápido (RNF-04: menos de 1 minuto, 3 clics)
 *   ?c=orden&a=ver&id=N               detalle con historial de estados
 *   ?c=orden&a=estado                 avanzar al siguiente estado (POST)
 *   ?c=orden&a=cancelar               cancelar (POST)
 *   ?c=orden&a=lavador                reasignar lavador (POST)
 */
class OrdenController extends Controller
{
    private Orden $modelo;

    public function __construct()
    {
        $this->requiereRol('dueno', 'cajero');
        $this->modelo = new Orden();
    }

    public function index(): void
    {
        // Filtros del historial (por defecto: hoy). Se validan las fechas.
        $hoy    = date('Y-m-d');
        $desde  = $this->fechaValida($_GET['desde'] ?? '') ?? $hoy;
        $hasta  = $this->fechaValida($_GET['hasta'] ?? '') ?? $hoy;
        $estado = $_GET['estado'] ?? '';
        if ($desde > $hasta) {
            [$desde, $hasta] = [$hasta, $desde];
        }
        $estadosValidos = ['en_espera', 'en_lavado', 'terminado', 'entregado', 'cancelada'];
        if (!in_array($estado, $estadosValidos, true)) {
            $estado = '';
        }

        $historial = $this->modelo->listar($desde, $hasta, $estado ?: null);

        $this->vista('ordenes/index', [
            'titulo'      => 'Servicios',
            'menuActivo'  => 'orden',
            'activas'     => $this->modelo->activas(),
            'historial'   => $historial,
            'conteo'      => Orden::contarPorEstado($historial),
            'filtros'     => ['desde' => $desde, 'hasta' => $hasta, 'estado' => $estado],
            'estados'     => $estadosValidos,
            'autorecarga' => 60,   // la lista se actualiza sola cada minuto
        ]);
    }

    public function crear(): void
    {
        $catalogo = (new ServicioCatalogo())->activos();
        $lavadores = $this->modelo->cargaLavadores();

        $datos = [
            'placa'            => normalizar_placa((string) ($_GET['placa'] ?? '')),
            'tipo'             => 'carro',
            'marca'            => '',
            'color'            => '',
            'cliente_nombre'   => '',
            'cliente_telefono' => '',
            'servicios'        => [],
            // Se sugiere el lavador con menos vehículos pendientes
            'lavador_id'       => (int) ($lavadores[0]['id'] ?? 0),
            'observaciones'    => '',
        ];
        $errores = [];

        if ($this->esPost()) {
            $datos = $this->leerFormulario();
            [$errores, $vehiculo, $serviciosElegidos] = $this->validar($datos, $catalogo, $lavadores);

            if (!$errores) {
                $ordenId = $this->guardar($datos, $vehiculo, $serviciosElegidos);
                Sesion::flash('exito', 'Orden #' . $ordenId . ' registrada: ' . placa_formato($datos['placa'])
                    . ' por ' . pesos(array_sum(array_column($serviciosElegidos, 'precio'))) . '.');
                $this->redirigir('orden');
            }
        }

        $this->vista('ordenes/crear', [
            'titulo'     => 'Nuevo servicio',
            'menuActivo' => 'orden',
            'datos'      => $datos,
            'errores'    => $errores,
            'catalogo'   => $catalogo,
            'lavadores'  => $lavadores,
            'scripts'    => [recurso('js/orden.js')],
        ]);
    }

    public function ver(): void
    {
        $id    = (int) ($_GET['id'] ?? 0);
        $orden = $this->modelo->buscarDetalle($id);
        if (!$orden) {
            Sesion::flash('error', 'La orden no existe.');
            $this->redirigir('orden');
        }

        $this->vista('ordenes/ver', [
            'titulo'     => 'Orden #' . $id,
            'menuActivo' => 'orden',
            'orden'      => $orden,
            'detalle'    => $this->modelo->detalle($id),
            'historial'  => $this->modelo->historial($id),
            'lavadores'  => $this->modelo->cargaLavadores(),
        ]);
    }

    /** Avanza la orden a su siguiente estado (en espera -> en lavado -> terminado -> entregado). */
    public function estado(): void
    {
        if (!$this->esPost()) {
            $this->redirigir('orden');
        }

        $id    = (int) ($_POST['id'] ?? 0);
        $orden = $this->modelo->buscarDetalle($id);
        if (!$orden) {
            Sesion::flash('error', 'La orden no existe.');
            $this->redirigir('orden');
        }

        $nuevo = Orden::SIGUIENTE[$orden['estado']] ?? null;
        $error = $nuevo ? $this->modelo->cambiarEstado($id, $nuevo, $this->usuario['id'])
                        : 'La orden ya no tiene un estado siguiente.';

        if ($error) {
            Sesion::flash('error', $error);
        } else {
            Sesion::flash('exito', placa_formato($orden['placa']) . ': ' . estado_texto($nuevo) . '.');
        }
        $this->volver($id);
    }

    public function cancelar(): void
    {
        if (!$this->esPost()) {
            $this->redirigir('orden');
        }

        $id    = (int) ($_POST['id'] ?? 0);
        $error = $this->modelo->cambiarEstado($id, 'cancelada', $this->usuario['id']);

        Sesion::flash($error ? 'error' : 'exito', $error ?? 'Orden #' . $id . ' cancelada.');
        $this->volver($id);
    }

    /** Cambia el lavador de una orden que aún no está terminada. */
    public function lavador(): void
    {
        if (!$this->esPost()) {
            $this->redirigir('orden');
        }

        $id        = (int) ($_POST['id'] ?? 0);
        $lavadorId = (int) ($_POST['lavador_id'] ?? 0);
        $orden     = $this->modelo->buscarDetalle($id);
        $validos   = array_map('intval', array_column($this->modelo->cargaLavadores(), 'id'));

        if (!$orden || !in_array($orden['estado'], ['en_espera', 'en_lavado'], true)) {
            Sesion::flash('error', 'Solo se puede cambiar el lavador de una orden en espera o en lavado.');
        } elseif (!in_array($lavadorId, $validos, true)) {
            Sesion::flash('error', 'Seleccione un lavador activo.');
        } else {
            $this->modelo->cambiarLavador($id, $lavadorId);
            Sesion::flash('exito', 'Lavador actualizado.');
        }
        $this->volver($id);
    }

    // ------------------------------------------------------------------

    private function leerFormulario(): array
    {
        $servicios = array_map('intval', (array) ($_POST['servicios'] ?? []));

        return [
            'placa'            => normalizar_placa($this->entrada('placa')),
            'tipo'             => $this->entrada('tipo'),
            'marca'            => $this->entrada('marca'),
            'color'            => $this->entrada('color'),
            'cliente_nombre'   => preg_replace('/\s+/', ' ', $this->entrada('cliente_nombre')),
            'cliente_telefono' => preg_replace('/\D/', '', $this->entrada('cliente_telefono')),
            'servicios'        => array_values(array_unique($servicios)),
            'lavador_id'       => (int) ($_POST['lavador_id'] ?? 0),
            'observaciones'    => mb_substr($this->entrada('observaciones'), 0, 255),
        ];
    }

    /**
     * Valida el formulario. Si la placa ya existe se usa ese vehículo; si
     * no, se validan los datos del vehículo y del cliente nuevos.
     *
     * @return array [errores, vehiculo existente o null, servicios elegidos con su precio]
     */
    private function validar(array &$d, array $catalogo, array $lavadores): array
    {
        $errores  = [];
        $vehiculo = $d['placa'] !== '' ? (new Vehiculo())->buscarPorPlaca($d['placa']) : null;

        if ($vehiculo) {
            $d['tipo'] = $vehiculo['tipo'];   // el tipo lo manda el vehículo registrado
            $activa = $this->modelo->ordenActivaDeVehiculo((int) $vehiculo['id']);
            if ($activa) {
                $errores['placa'] = 'Este vehículo ya está en el lavadero (orden #' . $activa . ').';
            }
        } else {
            $errores += Vehiculo::validar($d);
            foreach (Cliente::validar(['nombre' => $d['cliente_nombre'], 'telefono' => $d['cliente_telefono']]) as $campo => $msg) {
                $errores['cliente_' . $campo] = $msg;
            }
        }

        // Los servicios deben existir, estar activos y ser del tipo del vehículo
        $elegidos = array_values(array_filter($catalogo, fn($s) =>
            in_array((int) $s['id'], $d['servicios'], true) && $s['tipo_vehiculo'] === $d['tipo']));
        if (!$elegidos) {
            $errores['servicios'] = 'Seleccione al menos un servicio.';
        } elseif (count($elegidos) !== count($d['servicios'])) {
            $errores['servicios'] = 'Hay servicios que no corresponden al tipo de vehículo.';
        }

        if (!in_array($d['lavador_id'], array_map('intval', array_column($lavadores, 'id')), true)) {
            $errores['lavador_id'] = 'Seleccione el lavador.';
        }

        return [$errores, $vehiculo, $elegidos];
    }

    /**
     * Guarda todo en una sola TRANSACCIÓN: cliente (si es nuevo), vehículo
     * (si es nuevo) y la orden con su detalle e historial.
     */
    private function guardar(array $d, ?array $vehiculo, array $servicios): int
    {
        $db = Database::conectar();
        $db->beginTransaction();
        try {
            if ($vehiculo) {
                $vehiculoId = (int) $vehiculo['id'];
            } else {
                // Si ya existe un cliente con ese celular, se le asigna el vehículo (no se duplica)
                $clientes  = new Cliente();
                $existente = $clientes->buscarPorTelefono($d['cliente_telefono']);
                $clienteId = $existente ? (int) $existente['id']
                    : $clientes->crear(['nombre' => $d['cliente_nombre'], 'telefono' => $d['cliente_telefono']]);

                $vehiculoId = (new Vehiculo())->crear($d + ['cliente_id' => $clienteId]);
            }

            $ordenId = $this->modelo->crear($vehiculoId, $d['lavador_id'], $this->usuario['id'],
                $servicios, $d['observaciones']);

            $db->commit();
            return $ordenId;
        } catch (Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }

    /** Fecha 'Y-m-d' válida o null. */
    private function fechaValida(string $fecha): ?string
    {
        $d = DateTime::createFromFormat('Y-m-d', $fecha);
        return $d && $d->format('Y-m-d') === $fecha ? $fecha : null;
    }

    /** Vuelve a la página desde donde se hizo la acción (lista o detalle). */
    private function volver(int $id): never
    {
        if (($_POST['volver'] ?? '') === 'ver') {
            $this->redirigir('orden', 'ver', ['id' => $id]);
        }
        $this->redirigir('orden');
    }
}
