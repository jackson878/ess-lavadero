<?php
/**
 * EquipoController: equipos del lavadero y su mantenimiento (RF-09).
 * Acceso: SOLO Dueño.
 *
 *   ?c=equipo                    lista con alertas de mantenimiento
 *   ?c=equipo&a=crear            nuevo equipo
 *   ?c=equipo&a=editar&id=N      editar
 *   ?c=equipo&a=ver&id=N         ficha con historial y registro de mantenimiento
 *   ?c=equipo&a=mantenimiento    registrar mantenimiento (POST)
 */
class EquipoController extends Controller
{
    private Equipo $modelo;

    public function __construct()
    {
        $this->requiereRol('dueno');
        $this->modelo = new Equipo();
    }

    public function index(): void
    {
        $equipos = $this->modelo->todos();
        $conteo  = ['vencido' => 0, 'proximo' => 0];
        foreach ($equipos as $eq) {
            $alerta = Equipo::alerta($eq['dias_para_mant']);
            if (isset($conteo[$alerta])) {
                $conteo[$alerta]++;
            }
        }

        $this->vista('equipos/index', [
            'titulo'     => 'Equipos',
            'menuActivo' => 'equipo',
            'equipos'    => $equipos,
            'conteo'     => $conteo,
        ]);
    }

    public function ver(): void
    {
        $equipo = $this->buscarOSalir((int) ($_GET['id'] ?? 0));
        $this->mostrarFicha($equipo, $this->mantenimientoVacio(), []);
    }

    public function crear(): void
    {
        $datos   = ['nombre' => '', 'tipo' => 'hidrolavadora', 'marca' => '', 'serial' => '',
                    'estado' => 'operativo', 'fecha_compra' => '', 'fecha_proximo_mant' => ''];
        $errores = [];

        if ($this->esPost()) {
            $datos   = $this->leerFormulario();
            $errores = $this->validar($datos, null);
            if (!$errores) {
                $id = $this->modelo->crear($datos);
                Sesion::flash('exito', 'Equipo "' . $datos['nombre'] . '" registrado.');
                $this->redirigir('equipo', 'ver', ['id' => $id]);
            }
        }

        $this->mostrarFormulario('Nuevo equipo', $datos, $errores, null);
    }

    public function editar(): void
    {
        $id      = (int) ($_GET['id'] ?? 0);
        $datos   = $this->buscarOSalir($id);
        $errores = [];

        if ($this->esPost()) {
            $datos   = $this->leerFormulario();
            $errores = $this->validar($datos, $id);
            if (!$errores) {
                $this->modelo->actualizar($id, $datos);
                Sesion::flash('exito', 'Equipo actualizado.');
                $this->redirigir('equipo', 'ver', ['id' => $id]);
            }
        }

        $this->mostrarFormulario('Editar equipo', $datos, $errores, $id);
    }

    public function mantenimiento(): void
    {
        if (!$this->esPost()) {
            $this->redirigir('equipo');
        }

        $id     = (int) ($_POST['equipo_id'] ?? 0);
        $equipo = $this->buscarOSalir($id);

        $m = [
            'fecha'         => $this->entrada('fecha'),
            'tipo'          => $this->entrada('tipo'),
            'descripcion'   => preg_replace('/\s+/', ' ', $this->entrada('descripcion')),
            'costo'         => (int) preg_replace('/\D/', '', $this->entrada('costo')),
            'proxima_fecha' => $this->entrada('proxima_fecha'),
            'estado_equipo' => $this->entrada('estado_equipo'),
        ];
        $errores = [];

        if (!fecha_valida($m['fecha']) || $m['fecha'] > date('Y-m-d')) {
            $errores['fecha'] = 'Escriba la fecha en que se hizo (no puede ser futura).';
        }
        if (!in_array($m['tipo'], ['preventivo', 'correctivo'], true)) {
            $errores['tipo'] = 'Seleccione preventivo o correctivo.';
        }
        if (mb_strlen($m['descripcion']) < 5 || mb_strlen($m['descripcion']) > 255) {
            $errores['descripcion'] = 'Describa qué se hizo (entre 5 y 255 caracteres).';
        }
        if ($m['costo'] > 50000000) {
            $errores['costo'] = 'El costo no es válido.';
        }
        if ($m['proxima_fecha'] !== '' && (!fecha_valida($m['proxima_fecha']) || ($m['fecha'] !== '' && $m['proxima_fecha'] <= $m['fecha']))) {
            $errores['proxima_fecha'] = 'Debe ser una fecha posterior a la del mantenimiento.';
        }
        if (!in_array($m['estado_equipo'], Equipo::ESTADOS, true)) {
            $errores['estado_equipo'] = 'Seleccione el estado del equipo.';
        }

        if (!$errores) {
            $this->modelo->registrarMantenimiento($id, $m, $this->usuario['id']);
            Sesion::flash('exito', 'Mantenimiento registrado'
                . ($m['proxima_fecha'] !== '' ? '. Próximo: ' . date('d/m/Y', strtotime($m['proxima_fecha'])) . '.' : '.'));
            $this->redirigir('equipo', 'ver', ['id' => $id]);
        }

        $this->mostrarFicha($equipo, $m, $errores);
    }

    // ------------------------------------------------------------------

    private function leerFormulario(): array
    {
        return [
            'nombre'             => preg_replace('/\s+/', ' ', $this->entrada('nombre')),
            'tipo'               => $this->entrada('tipo'),
            'marca'              => $this->entrada('marca'),
            'serial'             => strtoupper($this->entrada('serial')),
            'estado'             => $this->entrada('estado'),
            'fecha_compra'       => $this->entrada('fecha_compra'),
            'fecha_proximo_mant' => $this->entrada('fecha_proximo_mant'),
        ];
    }

    private function validar(array $d, ?int $id): array
    {
        $errores = Equipo::validar($d);
        if ($d['serial'] !== '' && !isset($errores['serial']) && $this->modelo->existeSerial($d['serial'], $id)) {
            $errores['serial'] = 'Ya hay un equipo con ese serial.';
        }
        return $errores;
    }

    /** Valores por defecto del formulario de mantenimiento: hoy, preventivo, próximo en 3 meses. */
    private function mantenimientoVacio(): array
    {
        return [
            'fecha'         => date('Y-m-d'),
            'tipo'          => 'preventivo',
            'descripcion'   => '',
            'costo'         => '',
            'proxima_fecha' => date('Y-m-d', strtotime('+90 days')),
            'estado_equipo' => 'operativo',
        ];
    }

    private function mostrarFicha(array $equipo, array $m, array $errores): void
    {
        $this->vista('equipos/ver', [
            'titulo'          => $equipo['nombre'],
            'menuActivo'      => 'equipo',
            'equipo'          => $equipo,
            'mantenimientos'  => $this->modelo->mantenimientos((int) $equipo['id']),
            'm'               => $m,
            'errores'         => $errores,
        ]);
    }

    private function mostrarFormulario(string $titulo, array $datos, array $errores, ?int $id): void
    {
        $this->vista('equipos/formulario', [
            'titulo'     => $titulo,
            'menuActivo' => 'equipo',
            'datos'      => $datos,
            'errores'    => $errores,
            'id'         => $id,
        ]);
    }

    private function buscarOSalir(int $id): array
    {
        $equipo = $this->modelo->buscarPorId($id);
        if (!$equipo) {
            Sesion::flash('error', 'El equipo no existe.');
            $this->redirigir('equipo');
        }
        return $equipo;
    }
}
