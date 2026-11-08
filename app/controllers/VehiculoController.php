<?php
/**
 * VehiculoController: CRUD de vehículos y búsqueda por placa (RF-02).
 * Acceso: Dueño y Cajero.
 *
 *   ?c=vehiculo                         lista + consulta rápida por placa
 *   ?c=vehiculo&a=crear[&cliente_id=N]  nuevo (con cliente existente o nuevo)
 *   ?c=vehiculo&a=editar&id=N           editar
 *   ?c=vehiculo&a=eliminar              eliminar (POST, solo si no tiene órdenes)
 *   ?c=vehiculo&a=buscar&placa=ABC123   JSON para el autocompletado por placa
 */
class VehiculoController extends Controller
{
    private Vehiculo $modelo;

    public function __construct()
    {
        $this->requiereRol('dueno', 'cajero');
        $this->modelo = new Vehiculo();
    }

    public function index(): void
    {
        $this->vista('vehiculos/index', [
            'titulo'     => 'Vehículos',
            'menuActivo' => 'cliente',
            'pestana'    => 'vehiculos',
            'vehiculos'  => $this->modelo->todos(),
            'scripts'    => [recurso('js/placa.js')],
        ]);
    }

    public function crear(): void
    {
        $datos = [
            'placa'            => normalizar_placa((string) ($_GET['placa'] ?? '')),
            'tipo'             => 'carro',
            'marca'            => '',
            'color'            => '',
            'cliente_id'       => (int) ($_GET['cliente_id'] ?? 0),
            'modo_cliente'     => 'existente',
            'cliente_nombre'   => '',
            'cliente_telefono' => '',
        ];
        $errores = [];

        if ($this->esPost()) {
            $datos   = $this->leerFormulario();
            $errores = $this->validar($datos, null);

            if (!$errores) {
                $clienteId = $this->guardar($datos, null);
                Sesion::flash('exito', 'Vehículo ' . placa_formato($datos['placa']) . ' registrado.');
                $this->redirigir('cliente', 'ver', ['id' => $clienteId]);
            }
        }

        $this->mostrarFormulario('Nuevo vehículo', $datos, $errores, null);
    }

    public function editar(): void
    {
        $id     = (int) ($_GET['id'] ?? 0);
        $actual = $this->modelo->buscarPorId($id);
        if (!$actual) {
            Sesion::flash('error', 'El vehículo no existe.');
            $this->redirigir('vehiculo');
        }

        $datos   = $actual + ['modo_cliente' => 'existente', 'cliente_nombre' => '', 'cliente_telefono' => ''];
        $errores = [];

        if ($this->esPost()) {
            $datos   = $this->leerFormulario();
            $errores = $this->validar($datos, $id);

            if (!$errores) {
                $clienteId = $this->guardar($datos, $id);
                Sesion::flash('exito', 'Vehículo ' . placa_formato($datos['placa']) . ' actualizado.');
                $this->redirigir('cliente', 'ver', ['id' => $clienteId]);
            }
        }

        $this->mostrarFormulario('Editar vehículo', $datos, $errores, $id);
    }

    public function eliminar(): void
    {
        if (!$this->esPost()) {
            $this->redirigir('vehiculo');
        }

        $id       = (int) ($_POST['id'] ?? 0);
        $vehiculo = $this->modelo->buscarPorId($id);

        if (!$vehiculo) {
            Sesion::flash('error', 'El vehículo no existe.');
            $this->redirigir('vehiculo');
        }

        $ordenes = $this->modelo->contarOrdenes($id);
        if ($ordenes > 0) {
            // Se conserva para no perder el historial de ventas
            Sesion::flash('error', 'No se puede eliminar ' . placa_formato($vehiculo['placa']) . ': tiene '
                . $ordenes . ' órdenes en el historial.');
        } else {
            $this->modelo->eliminar($id);
            Sesion::flash('exito', 'Vehículo ' . placa_formato($vehiculo['placa']) . ' eliminado.');
        }

        $this->redirigir('cliente', 'ver', ['id' => $vehiculo['cliente_id']]);
    }

    /**
     * Respuesta JSON para el autocompletado: al escribir la placa se
     * traen los datos del vehículo y de su dueño.
     * Ejemplo: {"encontrado":true,"vehiculo":{...},"cliente":{...}}
     */
    public function buscar(): void
    {
        $placa    = normalizar_placa((string) ($_GET['placa'] ?? ''));
        $vehiculo = strlen($placa) >= 5 ? $this->modelo->buscarPorPlaca($placa) : null;

        header('Content-Type: application/json; charset=utf-8');

        if (!$vehiculo) {
            echo json_encode(['encontrado' => false, 'placa' => $placa]);
            return;
        }

        echo json_encode([
            'encontrado' => true,
            'vehiculo'   => [
                'id'    => (int) $vehiculo['id'],
                'placa' => $vehiculo['placa'],
                'tipo'  => $vehiculo['tipo'],
                'marca' => $vehiculo['marca'],
                'color' => $vehiculo['color'],
            ],
            'cliente'    => [
                'id'       => (int) $vehiculo['cliente_id'],
                'nombre'   => $vehiculo['cliente_nombre'],
                'telefono' => $vehiculo['cliente_telefono'],
            ],
        ], JSON_UNESCAPED_UNICODE);
    }

    // ------------------------------------------------------------------

    private function leerFormulario(): array
    {
        return [
            'placa'            => normalizar_placa($this->entrada('placa')),
            'tipo'             => $this->entrada('tipo'),
            'marca'            => $this->entrada('marca'),
            'color'            => $this->entrada('color'),
            'cliente_id'       => (int) ($_POST['cliente_id'] ?? 0),
            'modo_cliente'     => $this->entrada('modo_cliente') === 'nuevo' ? 'nuevo' : 'existente',
            'cliente_nombre'   => preg_replace('/\s+/', ' ', $this->entrada('cliente_nombre')),
            'cliente_telefono' => preg_replace('/\D/', '', $this->entrada('cliente_telefono')),
        ];
    }

    /** Valida el vehículo y, según el modo, el cliente existente o el nuevo. */
    private function validar(array $d, ?int $id): array
    {
        $errores = Vehiculo::validar($d);

        if (!isset($errores['placa']) && $this->modelo->existePlaca($d['placa'], $id)) {
            $errores['placa'] = 'La placa ' . placa_formato($d['placa']) . ' ya está registrada.';
        }

        if ($d['modo_cliente'] === 'nuevo') {
            // Se reutilizan las reglas del modelo Cliente, con los nombres de campo de este formulario
            $erroresCliente = Cliente::validar(['nombre' => $d['cliente_nombre'], 'telefono' => $d['cliente_telefono']]);
            foreach ($erroresCliente as $campo => $mensaje) {
                $errores['cliente_' . $campo] = $mensaje;
            }
        } elseif (!(new Cliente())->buscarPorId($d['cliente_id'])) {
            $errores['cliente_id'] = 'Seleccione el dueño del vehículo.';
        }

        return $errores;
    }

    /**
     * Guarda el vehículo. Si el cliente es nuevo se crean ambos en una
     * TRANSACCIÓN: o se guardan los dos o ninguno.
     * Devuelve el id del cliente dueño.
     */
    private function guardar(array $d, ?int $id): int
    {
        $db = Database::conectar();
        $db->beginTransaction();
        try {
            if ($d['modo_cliente'] === 'nuevo') {
                $d['cliente_id'] = (new Cliente())->crear([
                    'nombre'   => $d['cliente_nombre'],
                    'telefono' => $d['cliente_telefono'],
                ]);
            }

            if ($id === null) {
                $this->modelo->crear($d);
            } else {
                $this->modelo->actualizar($id, $d);
            }

            $db->commit();
            return $d['cliente_id'];
        } catch (Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }

    private function mostrarFormulario(string $titulo, array $datos, array $errores, ?int $id): void
    {
        $this->vista('vehiculos/formulario', [
            'titulo'     => $titulo,
            'menuActivo' => 'cliente',
            'datos'      => $datos,
            'errores'    => $errores,
            'id'         => $id,
            'clientes'   => (new Cliente())->listaSimple(),
            'scripts'    => [recurso('js/placa.js')],
        ]);
    }
}
