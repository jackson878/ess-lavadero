<?php
/**
 * TableroController: tablero gerencial con los indicadores (RF-06).
 * Acceso: SOLO Dueño.
 */
class TableroController extends Controller
{
    public function __construct()
    {
        // Control de acceso en el servidor: se valida antes de cualquier acción
        $this->requiereRol('dueno');
    }

    /** index.php?c=tablero  (opcional &dias=7|15|30) */
    public function index(): void
    {
        // Solo se aceptan 7, 15 o 30 días (nunca se confía en lo que llega por la URL)
        $dias = (int) ($_GET['dias'] ?? 7);
        if (!in_array($dias, [7, 15, 30], true)) {
            $dias = 7;
        }

        $hoy          = date('Y-m-d');
        $desde        = date('Y-m-d', strtotime('-' . ($dias - 1) . ' days'));
        $semanaPasada = date('Y-m-d', strtotime('-7 days'));   // mismo día de la semana anterior

        $tablero = new Tablero();

        // Tarjetas de indicadores
        $kpi = [
            'servicios_hoy'           => $tablero->serviciosEnRango($hoy, $hoy),
            'servicios_semana_pasada' => $tablero->serviciosEnRango($semanaPasada, $semanaPasada),
            'ingresos_hoy'            => $tablero->ingresosEnRango($hoy, $hoy),
            'ingresos_semana_pasada'  => $tablero->ingresosEnRango($semanaPasada, $semanaPasada),
            'ticket'                  => $tablero->ticketPromedio($desde, $hoy),
            'tiempo'                  => $tablero->tiempoPromedioAtencion($desde, $hoy),
        ];

        $this->vista('tablero/index', [
            'titulo'       => 'Tablero',
            'menuActivo'   => 'tablero',
            'dias'         => $dias,
            'kpi'          => $kpi,
            'semana'       => $this->datosSemana($tablero, $hoy),
            'masVendidos'  => $tablero->serviciosMasVendidos($desde, $hoy, 4),
            'insumosBajos' => $tablero->insumosBajoMinimo(),
            'enLavadero'   => $tablero->vehiculosEnLavadero(),
            'scripts'      => [
                'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js',
                recurso('js/tablero.js'),
            ],
        ]);
    }

    /**
     * Datos de la gráfica "Ingresos de la semana": los últimos 7 días,
     * incluyendo hoy y los días sin ventas (en 0).
     */
    private function datosSemana(Tablero $tablero, string $hoy): array
    {
        $inicio  = date('Y-m-d', strtotime('-6 days'));
        $porDia  = $tablero->ingresosPorDia($inicio, $hoy);
        $grafica = ['etiquetas' => [], 'valores' => [], 'indiceHoy' => 6, 'indiceMejor' => null];
        $mejor   = null;

        for ($i = 0; $i < 7; $i++) {
            $fecha = date('Y-m-d', strtotime("$inicio +$i days"));
            $w     = (int) date('w', strtotime($fecha));
            $valor = $porDia[$fecha] ?? 0;

            $grafica['etiquetas'][] = $fecha === $hoy ? 'Hoy' : DIAS_CORTOS[$w];
            $grafica['valores'][]   = $valor;

            // El "mejor día" se busca entre los días ya cerrados (sin contar hoy)
            if ($fecha !== $hoy && $valor > 0 && ($mejor === null || $valor > $mejor['valor'])) {
                $mejor = ['w' => $w, 'valor' => $valor, 'indice' => $i];
            }
        }

        $grafica['indiceMejor'] = $mejor['indice'] ?? null;
        return ['grafica' => $grafica, 'mejor' => $mejor];
    }
}
