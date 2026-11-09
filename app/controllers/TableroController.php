<?php
/**
 * TableroController: tablero gerencial con los 8 KPI y filtro por rango de fechas (RF-06).
 * Acceso: SOLO Dueño.
 *
 *   ?c=tablero                                   últimos 30 días
 *   ?c=tablero&rango=7|30|hoy|mes|mes_anterior   atajos
 *   ?c=tablero&desde=AAAA-MM-DD&hasta=AAAA-MM-DD rango libre
 *   &agrupar=dia|mes                             agrupación de las series
 */
class TableroController extends Controller
{
    /** Rango máximo permitido (para que la pantalla responda rápido, RNF-01) */
    private const MAX_DIAS = 366;

    public function __construct()
    {
        // Control de acceso en el servidor: se valida antes de cualquier acción
        $this->requiereRol('dueno');
    }

    public function index(): void
    {
        [$desde, $hasta, $rango] = $this->leerRango();
        $dias = (int) ((strtotime($hasta) - strtotime($desde)) / 86400) + 1;

        // Por día si el rango es corto; por mes si es largo (el usuario puede cambiarlo)
        $agrupar = $_GET['agrupar'] ?? ($dias > 62 ? 'mes' : 'dia');
        if (!in_array($agrupar, ['dia', 'mes'], true)) {
            $agrupar = 'dia';
        }

        // Periodo anterior de la misma duración, para comparar
        $antHasta = date('Y-m-d', strtotime($desde . ' -1 day'));
        $antDesde = date('Y-m-d', strtotime($antHasta . ' -' . ($dias - 1) . ' days'));

        $t        = new Tablero();
        $actual   = $t->resumen($desde, $hasta);
        $anterior = $t->resumen($antDesde, $antHasta);

        // Series con todos los periodos del rango (los que no tienen datos van en 0)
        $periodos  = $this->periodos($desde, $hasta, $agrupar);
        $servicios = $t->serieServicios($desde, $hasta, $agrupar);
        $ingresos  = $t->serieIngresos($desde, $hasta, $agrupar);
        $tiempo    = $t->serieTiempo($desde, $hasta, $agrupar);

        $series = ['etiquetas' => array_values($periodos), 'servicios' => [], 'ingresos' => [], 'tiempo' => []];
        foreach (array_keys($periodos) as $clave) {
            $series['servicios'][] = $servicios[$clave] ?? 0;
            $series['ingresos'][]  = $ingresos[$clave] ?? 0;
            $series['tiempo'][]    = $tiempo[$clave] ?? null;   // null = sin órdenes terminadas ese periodo
        }

        $masVendidos = $t->masVendidos($desde, $hasta, 8);
        $lavadores   = $t->porLavador($desde, $hasta);
        $tipos       = $t->porTipoVehiculo($desde, $hasta);
        $insumos     = $t->insumosBajoMinimo();

        $this->vista('tablero/index', [
            'titulo'      => 'Tablero',
            'menuActivo'  => 'tablero',
            'desde'       => $desde,
            'hasta'       => $hasta,
            'rango'       => $rango,
            'agrupar'     => $agrupar,
            'actual'      => $actual,
            'anterior'    => $anterior,
            'series'      => $series,
            'tipos'       => $tipos,
            'masVendidos' => $masVendidos,
            'lavadores'   => $lavadores,
            'insumos'     => $insumos,
            'equipos'     => $t->equiposPorMantener(),
            'enLavadero'  => $t->vehiculosEnLavadero(),
            // Todo lo que necesitan las gráficas, en un solo objeto JSON
            'graficas'    => [
                'series'      => $series,
                'agrupar'     => $agrupar,
                'meta'        => META_TIEMPO_ATENCION_MIN,
                'tipos'       => ['etiquetas' => ['Carro', 'Moto'], 'valores' => [$tipos['carro'], $tipos['moto']]],
                'masVendidos' => [
                    'etiquetas' => array_map(fn($s) => $s['nombre'] . ' · ' . $s['tipo_vehiculo'], $masVendidos),
                    'valores'   => array_map(fn($s) => (int) $s['cantidad'], $masVendidos),
                    'facturado' => array_map(fn($s) => (float) $s['facturado'], $masVendidos),
                ],
                'lavadores'   => [
                    'etiquetas' => array_map(fn($l) => explode(' ', $l['nombre'])[0], $lavadores),
                    'valores'   => array_map(fn($l) => (int) $l['servicios'], $lavadores),
                    'facturado' => array_map(fn($l) => (float) $l['facturado'], $lavadores),
                ],
                'insumos'     => [
                    'etiquetas' => array_map(fn($i) => $i['nombre'], $insumos),
                    'stock'     => array_map(fn($i) => (float) $i['stock_actual'], $insumos),
                    'minimo'    => array_map(fn($i) => (float) $i['stock_minimo'], $insumos),
                    'unidades'  => array_map(fn($i) => $i['unidad'], $insumos),
                ],
            ],
            'scripts'     => [
                'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js',
                recurso('js/tablero.js'),
            ],
        ]);
    }

    // ------------------------------------------------------------------

    /**
     * Lee el rango de la URL: un atajo (?rango=) o fechas libres (?desde=&hasta=).
     * Todo se valida; si algo no es válido se usan los últimos 30 días.
     *
     * @return array{0:string, 1:string, 2:string} [desde, hasta, atajo activo o '']
     */
    private function leerRango(): array
    {
        $hoy   = date('Y-m-d');
        $rango = $_GET['rango'] ?? '';

        $atajos = [
            'hoy'          => [$hoy, $hoy],
            '7'            => [date('Y-m-d', strtotime('-6 days')), $hoy],
            '30'           => [date('Y-m-d', strtotime('-29 days')), $hoy],
            'mes'          => [date('Y-m-01'), $hoy],
            'mes_anterior' => [date('Y-m-01', strtotime('first day of last month')), date('Y-m-t', strtotime('last day of last month'))],
        ];
        if (isset($atajos[$rango])) {
            return [...$atajos[$rango], $rango];
        }

        $desde = (string) ($_GET['desde'] ?? '');
        $hasta = (string) ($_GET['hasta'] ?? '');
        if (fecha_valida($desde) && fecha_valida($hasta)) {
            if ($desde > $hasta) {
                [$desde, $hasta] = [$hasta, $desde];
            }
            $hasta = min($hasta, $hoy);
            // Se limita el rango para que la consulta siga siendo rápida
            if ((strtotime($hasta) - strtotime($desde)) / 86400 >= self::MAX_DIAS) {
                $desde = date('Y-m-d', strtotime($hasta . ' -' . (self::MAX_DIAS - 1) . ' days'));
            }
            return [$desde, $hasta, ''];
        }

        return [...$atajos['30'], '30'];
    }

    /**
     * Todos los periodos del rango, para que las gráficas muestren también
     * los días (o meses) sin datos. Devuelve [clave SQL => etiqueta].
     *   día: '2026-11-08' => 'dom 08/11'      mes: '2026-11' => 'nov 2026'
     */
    private function periodos(string $desde, string $hasta, string $agrupar): array
    {
        $periodos = [];
        if ($agrupar === 'mes') {
            $f = strtotime(date('Y-m-01', strtotime($desde)));
            while ($f <= strtotime($hasta)) {
                $periodos[date('Y-m', $f)] = mb_substr(MESES[(int) date('n', $f) - 1], 0, 3) . ' ' . date('Y', $f);
                $f = strtotime('+1 month', $f);
            }
        } else {
            for ($f = strtotime($desde); $f <= strtotime($hasta); $f = strtotime('+1 day', $f)) {
                $periodos[date('Y-m-d', $f)] = mb_strtolower(DIAS_CORTOS[(int) date('w', $f)]) . ' ' . date('d/m', $f);
            }
        }
        return $periodos;
    }
}
