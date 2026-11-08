<?php
/**
 * LavadorController: cola de trabajo del lavador, pensada para el celular (RNF-03).
 * Acceso: SOLO Lavador. No puede ver caja, reportes ni órdenes de otros.
 *
 *   ?c=lavador              su cola de vehículos
 *   ?c=lavador&a=avanzar    empezar lavado / marcar terminado (POST)
 */
class LavadorController extends Controller
{
    private Orden $modelo;

    public function __construct()
    {
        $this->requiereRol('lavador');
        $this->modelo = new Orden();
    }

    public function index(): void
    {
        $cola = $this->modelo->colaLavador($this->usuario['id']);

        $this->vista('lavador/index', [
            'titulo'      => 'Mi cola',
            'menuActivo'  => 'lavador',
            'pendientes'  => array_values(array_filter($cola, fn($o) => in_array($o['estado'], ['en_espera', 'en_lavado'], true))),
            'terminados'  => array_values(array_filter($cola, fn($o) => in_array($o['estado'], ['terminado', 'entregado'], true))),
            'autorecarga' => 60,
        ]);
    }

    /**
     * El lavador solo puede:
     *   - pasar SUS órdenes de "en espera" a "en lavado", y
     *   - de "en lavado" a "terminado".
     * Entregar le corresponde al cajero (después de cobrar).
     */
    public function avanzar(): void
    {
        if (!$this->esPost()) {
            $this->redirigir('lavador');
        }

        $id    = (int) ($_POST['id'] ?? 0);
        $orden = $this->modelo->buscarDetalle($id);

        // Una orden de otro lavador se trata como inexistente (no se revela nada)
        if (!$orden || (int) $orden['lavador_id'] !== $this->usuario['id']) {
            Sesion::flash('error', 'Esa orden no está en su cola.');
            $this->redirigir('lavador');
        }

        $permitidos = ['en_espera' => 'en_lavado', 'en_lavado' => 'terminado'];
        $nuevo = $permitidos[$orden['estado']] ?? null;

        if ($nuevo === null) {
            Sesion::flash('error', 'Esta orden ya fue terminada.');
        } else {
            $error = $this->modelo->cambiarEstado($id, $nuevo, $this->usuario['id']);
            Sesion::flash($error ? 'error' : 'exito',
                $error ?? placa_formato($orden['placa']) . ($nuevo === 'en_lavado' ? ': lavado iniciado.' : ': ¡terminado!'));
        }

        $this->redirigir('lavador');
    }
}
