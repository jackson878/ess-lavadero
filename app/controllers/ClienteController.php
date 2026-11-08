<?php
/**
 * ClienteController: CRUD de clientes (RF-02).
 * Acceso: Dueño y Cajero.
 *
 *   ?c=cliente                   lista
 *   ?c=cliente&a=ver&id=N        ficha del cliente con sus vehículos y visitas
 *   ?c=cliente&a=crear           nuevo (GET formulario / POST guardar)
 *   ?c=cliente&a=editar&id=N     editar (GET / POST)
 *   ?c=cliente&a=eliminar        eliminar (POST, solo si no tiene vehículos)
 */
class ClienteController extends Controller
{
    private Cliente $modelo;

    public function __construct()
    {
        $this->requiereRol('dueno', 'cajero');
        $this->modelo = new Cliente();
    }

    public function index(): void
    {
        $this->vista('clientes/index', [
            'titulo'     => 'Clientes',
            'menuActivo' => 'cliente',
            'pestana'    => 'clientes',
            'clientes'   => $this->modelo->todos(),
        ]);
    }

    public function ver(): void
    {
        $id      = (int) ($_GET['id'] ?? 0);
        $cliente = $this->buscarOSalir($id);

        $this->vista('clientes/ver', [
            'titulo'     => $cliente['nombre'],
            'menuActivo' => 'cliente',
            'cliente'    => $cliente,
            'vehiculos'  => (new Vehiculo())->porCliente($id),
            'resumen'    => $this->modelo->resumen($id),
            'ordenes'    => $this->modelo->ordenesRecientes($id, 10),
        ]);
    }

    public function crear(): void
    {
        $datos   = ['nombre' => '', 'telefono' => ''];
        $errores = [];

        if ($this->esPost()) {
            $datos   = $this->leerFormulario();
            $errores = Cliente::validar($datos);

            if (!$errores) {
                $id = $this->modelo->crear($datos);
                Sesion::flash('exito', 'Cliente "' . $datos['nombre'] . '" registrado. Ahora agregue su vehículo.');
                $this->redirigir('cliente', 'ver', ['id' => $id]);
            }
        }

        $this->vista('clientes/formulario', [
            'titulo'     => 'Nuevo cliente',
            'menuActivo' => 'cliente',
            'datos'      => $datos,
            'errores'    => $errores,
            'id'         => null,
        ]);
    }

    public function editar(): void
    {
        $id      = (int) ($_GET['id'] ?? 0);
        $datos   = $this->buscarOSalir($id);
        $errores = [];

        if ($this->esPost()) {
            $datos   = $this->leerFormulario();
            $errores = Cliente::validar($datos);

            if (!$errores) {
                $this->modelo->actualizar($id, $datos);
                Sesion::flash('exito', 'Datos del cliente actualizados.');
                $this->redirigir('cliente', 'ver', ['id' => $id]);
            }
        }

        $this->vista('clientes/formulario', [
            'titulo'     => 'Editar cliente',
            'menuActivo' => 'cliente',
            'datos'      => $datos,
            'errores'    => $errores,
            'id'         => $id,
        ]);
    }

    public function eliminar(): void
    {
        if (!$this->esPost()) {
            $this->redirigir('cliente');
        }

        $id      = (int) ($_POST['id'] ?? 0);
        $cliente = $this->buscarOSalir($id);
        $total   = $this->modelo->contarVehiculos($id);

        if ($total > 0) {
            // Integridad: no se deja un vehículo sin dueño
            Sesion::flash('error', 'No se puede eliminar a ' . $cliente['nombre'] . ': tiene ' . $total
                . ($total === 1 ? ' vehículo registrado.' : ' vehículos registrados.')
                . ' Elimine los vehículos o asígnelos a otro cliente primero.');
            $this->redirigir('cliente', 'ver', ['id' => $id]);
        }

        $this->modelo->eliminar($id);
        Sesion::flash('exito', 'Cliente "' . $cliente['nombre'] . '" eliminado.');
        $this->redirigir('cliente');
    }

    // ------------------------------------------------------------------

    private function leerFormulario(): array
    {
        return [
            'nombre'   => preg_replace('/\s+/', ' ', $this->entrada('nombre')),
            'telefono' => preg_replace('/\D/', '', $this->entrada('telefono')),   // solo dígitos
        ];
    }

    /** Devuelve el cliente o redirige a la lista si no existe. */
    private function buscarOSalir(int $id): array
    {
        $cliente = $this->modelo->buscarPorId($id);
        if (!$cliente) {
            Sesion::flash('error', 'El cliente no existe.');
            $this->redirigir('cliente');
        }
        return $cliente;
    }
}
