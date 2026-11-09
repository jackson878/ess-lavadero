<?php
/**
 * InsumoController: inventario de insumos (RF-07).
 * Acceso: SOLO Dueño.
 *
 *   ?c=insumo                    lista con alerta de stock mínimo
 *   ?c=insumo&a=crear            nuevo insumo (con stock inicial)
 *   ?c=insumo&a=editar&id=N      editar datos (el stock no se edita aquí)
 *   ?c=insumo&a=ver&id=N         ficha con movimientos
 *   ?c=insumo&a=movimiento       registrar entrada o salida (POST)
 */
class InsumoController extends Controller
{
    private Insumo $modelo;

    public function __construct()
    {
        $this->requiereRol('dueno');
        $this->modelo = new Insumo();
    }

    public function index(): void
    {
        $insumos = $this->modelo->todos();
        $bajos   = array_filter($insumos, fn($i) => (int) $i['activo'] && (int) $i['bajo_minimo']);

        $this->vista('insumos/index', [
            'titulo'     => 'Insumos',
            'menuActivo' => 'insumo',
            'insumos'    => $insumos,
            'bajos'      => array_values($bajos),
        ]);
    }

    public function ver(): void
    {
        $id     = (int) ($_GET['id'] ?? 0);
        $insumo = $this->buscarOSalir($id);

        $this->vista('insumos/ver', [
            'titulo'      => $insumo['nombre'],
            'menuActivo'  => 'insumo',
            'insumo'      => $insumo,
            'movimientos' => $this->modelo->movimientos($id),
            'mov'         => ['tipo' => $_GET['tipo'] ?? 'entrada', 'cantidad' => '', 'motivo' => ''],
            'errores'     => [],
        ]);
    }

    public function crear(): void
    {
        $datos   = ['nombre' => '', 'unidad' => 'litro', 'stock_minimo' => '', 'costo_unitario' => 0,
                    'stock_inicial' => '', 'activo' => 1];
        $errores = [];

        if ($this->esPost()) {
            $datos   = $this->leerFormulario(true);
            $errores = $this->validar($datos, null);

            if (!$errores) {
                $id = $this->modelo->crear($datos, $this->usuario['id']);
                Sesion::flash('exito', 'Insumo "' . $datos['nombre'] . '" agregado al inventario.');
                $this->redirigir('insumo', 'ver', ['id' => $id]);
            }
        }

        $this->mostrarFormulario('Nuevo insumo', $datos, $errores, null);
    }

    public function editar(): void
    {
        $id      = (int) ($_GET['id'] ?? 0);
        $datos   = $this->buscarOSalir($id);
        $errores = [];

        if ($this->esPost()) {
            $datos   = $this->leerFormulario(false) + ['stock_actual' => $datos['stock_actual']];
            $errores = $this->validar($datos, $id);

            if (!$errores) {
                $this->modelo->actualizar($id, $datos);
                Sesion::flash('exito', 'Insumo actualizado.');
                $this->redirigir('insumo', 'ver', ['id' => $id]);
            }
        }

        $this->mostrarFormulario('Editar insumo', $datos, $errores, $id);
    }

    /** Registra una entrada (compra) o salida (consumo). */
    public function movimiento(): void
    {
        if (!$this->esPost()) {
            $this->redirigir('insumo');
        }

        $id     = (int) ($_POST['insumo_id'] ?? 0);
        $insumo = $this->buscarOSalir($id);

        $mov = [
            'tipo'     => $this->entrada('tipo'),
            'cantidad' => $this->entrada('cantidad'),
            'motivo'   => mb_substr($this->entrada('motivo'), 0, 150),
        ];
        $cantidad = leer_cantidad($mov['cantidad']);
        $errores  = [];

        if (!in_array($mov['tipo'], ['entrada', 'salida'], true)) {
            $errores['tipo'] = 'Seleccione si es entrada o salida.';
        }
        if ($cantidad === null || $cantidad <= 0 || $cantidad > 99999) {
            $errores['cantidad'] = 'Escriba una cantidad mayor que cero.';
        }

        if (!$errores) {
            $error = $this->modelo->registrarMovimiento($id, $mov['tipo'], $cantidad, $mov['motivo'], $this->usuario['id']);
            if ($error === null) {
                $actual = $this->modelo->buscarPorId($id);
                $texto  = ($mov['tipo'] === 'entrada' ? 'Entrada' : 'Salida') . ' de ' . cantidad_unidad($cantidad, $insumo['unidad'])
                        . ' registrada. Stock: ' . cantidad_unidad($actual['stock_actual'], $insumo['unidad']) . '.';
                Sesion::flash((int) $actual['bajo_minimo'] ? 'aviso' : 'exito',
                    $texto . ((int) $actual['bajo_minimo'] ? ' ¡Quedó por debajo del mínimo!' : ''));
                $this->redirigir('insumo', 'ver', ['id' => $id]);
            }
            $errores['cantidad'] = $error;
        }

        // Con errores se vuelve a mostrar la ficha con el formulario marcado
        $this->vista('insumos/ver', [
            'titulo'      => $insumo['nombre'],
            'menuActivo'  => 'insumo',
            'insumo'      => $insumo,
            'movimientos' => $this->modelo->movimientos($id),
            'mov'         => $mov,
            'errores'     => $errores,
        ]);
    }

    // ------------------------------------------------------------------

    private function leerFormulario(bool $nuevo): array
    {
        $d = [
            'nombre'         => preg_replace('/\s+/', ' ', $this->entrada('nombre')),
            'unidad'         => $this->entrada('unidad'),
            'stock_minimo'   => leer_cantidad($this->entrada('stock_minimo')),
            'costo_unitario' => (int) preg_replace('/\D/', '', $this->entrada('costo_unitario')),
            'activo'         => $nuevo ? 1 : (isset($_POST['activo']) ? 1 : 0),
        ];
        if ($nuevo) {
            $texto = $this->entrada('stock_inicial');
            $d['stock_inicial'] = $texto === '' ? 0.0 : leer_cantidad($texto);
        }
        return $d;
    }

    private function validar(array $d, ?int $id): array
    {
        $errores = Insumo::validar($d);
        if (!isset($errores['nombre']) && $this->modelo->existeNombre($d['nombre'], $id)) {
            $errores['nombre'] = 'Ya existe un insumo con ese nombre.';
        }
        return $errores;
    }

    private function mostrarFormulario(string $titulo, array $datos, array $errores, ?int $id): void
    {
        $this->vista('insumos/formulario', [
            'titulo'     => $titulo,
            'menuActivo' => 'insumo',
            'datos'      => $datos,
            'errores'    => $errores,
            'id'         => $id,
        ]);
    }

    private function buscarOSalir(int $id): array
    {
        $insumo = $this->modelo->buscarPorId($id);
        if (!$insumo) {
            Sesion::flash('error', 'El insumo no existe.');
            $this->redirigir('insumo');
        }
        return $insumo;
    }
}
