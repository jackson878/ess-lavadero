<?php
/**
 * CatalogoController: catálogo de servicios con precio y duración (RF-03).
 * Acceso: SOLO Dueño (el cajero ve los servicios al crear la orden, pero
 * no puede cambiar precios).
 *
 *   ?c=catalogo                  lista
 *   ?c=catalogo&a=crear          nuevo servicio
 *   ?c=catalogo&a=editar&id=N    editar
 *   ?c=catalogo&a=estado         activar / desactivar (POST)
 */
class CatalogoController extends Controller
{
    private ServicioCatalogo $modelo;

    public function __construct()
    {
        $this->requiereRol('dueno');
        $this->modelo = new ServicioCatalogo();
    }

    public function index(): void
    {
        $this->vista('catalogo/index', [
            'titulo'     => 'Catálogo de servicios',
            'menuActivo' => 'catalogo',
            'servicios'  => $this->modelo->todos(),
        ]);
    }

    public function crear(): void
    {
        $datos   = ['nombre' => '', 'tipo_vehiculo' => 'carro', 'precio' => 0, 'duracion_min' => 30,
                    'descripcion' => '', 'activo' => 1];
        $errores = [];

        if ($this->esPost()) {
            $datos   = $this->leerFormulario();
            $errores = $this->validar($datos, null);

            if (!$errores) {
                $this->modelo->crear($datos);
                Sesion::flash('exito', 'Servicio "' . $datos['nombre'] . '" agregado al catálogo.');
                $this->redirigir('catalogo');
            }
        }

        $this->mostrarFormulario('Nuevo servicio', $datos, $errores, null);
    }

    public function editar(): void
    {
        $id     = (int) ($_GET['id'] ?? 0);
        $actual = $this->modelo->buscarPorId($id);
        if (!$actual) {
            Sesion::flash('error', 'El servicio no existe.');
            $this->redirigir('catalogo');
        }

        $datos   = $actual;
        $errores = [];

        if ($this->esPost()) {
            $datos   = $this->leerFormulario();
            $errores = $this->validar($datos, $id);

            if (!$errores) {
                $this->modelo->actualizar($id, $datos);
                Sesion::flash('exito', 'Servicio "' . $datos['nombre'] . '" actualizado. '
                    . 'Las órdenes anteriores conservan el precio con que se cobraron.');
                $this->redirigir('catalogo');
            }
        }

        $this->mostrarFormulario('Editar servicio', $datos, $errores, $id);
    }

    public function estado(): void
    {
        if (!$this->esPost()) {
            $this->redirigir('catalogo');
        }

        $id       = (int) ($_POST['id'] ?? 0);
        $servicio = $this->modelo->buscarPorId($id);

        if (!$servicio) {
            Sesion::flash('error', 'El servicio no existe.');
        } else {
            $activar = !(int) $servicio['activo'];
            $this->modelo->cambiarEstado($id, $activar);
            Sesion::flash('exito', 'Servicio "' . $servicio['nombre'] . '" '
                . ($activar ? 'activado.' : 'desactivado: ya no aparecerá al registrar órdenes.'));
        }

        $this->redirigir('catalogo');
    }

    // ------------------------------------------------------------------

    private function leerFormulario(): array
    {
        return [
            'nombre'        => preg_replace('/\s+/', ' ', $this->entrada('nombre')),
            'tipo_vehiculo' => $this->entrada('tipo_vehiculo'),
            // "25.000" o "$ 25.000" -> 25000 (en pesos no se usan decimales)
            'precio'        => (int) preg_replace('/\D/', '', $this->entrada('precio')),
            'duracion_min'  => (int) $this->entrada('duracion_min'),
            'descripcion'   => $this->entrada('descripcion'),
            'activo'        => isset($_POST['activo']) ? 1 : 0,
        ];
    }

    private function validar(array $d, ?int $id): array
    {
        $errores = ServicioCatalogo::validar($d);

        if (!isset($errores['nombre']) && !isset($errores['tipo_vehiculo'])
            && $this->modelo->existeNombre($d['nombre'], $d['tipo_vehiculo'], $id)) {
            $errores['nombre'] = 'Ya existe "' . $d['nombre'] . '" para ' . $d['tipo_vehiculo'] . '.';
        }

        return $errores;
    }

    private function mostrarFormulario(string $titulo, array $datos, array $errores, ?int $id): void
    {
        $this->vista('catalogo/formulario', [
            'titulo'     => $titulo,
            'menuActivo' => 'catalogo',
            'datos'      => $datos,
            'errores'    => $errores,
            'id'         => $id,
        ]);
    }
}
