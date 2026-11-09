<?php
/**
 * ReporteController: reportes en PDF y Excel (RF-08) y respaldo de la base de datos (RNF-06).
 * Acceso: SOLO Dueño.
 *
 *   ?c=reporte                                         página de reportes y respaldos
 *   ?c=reporte&a=ventas&desde=..&hasta=..&formato=pdf|excel
 *   ?c=reporte&a=cierre&id=N&formato=pdf|excel
 *   ?c=reporte&a=lavadores&desde=..&hasta=..&formato=pdf|excel
 *   ?c=reporte&a=respaldar                             genera un mysqldump (POST)
 *   ?c=reporte&a=descargar&archivo=..                  descarga un respaldo
 */
class ReporteController extends Controller
{
    /** Carpeta donde se guardan los respaldos (protegida con .htaccess) */
    private const CARPETA_RESPALDOS = ROOT_PATH . 'backups' . DIRECTORY_SEPARATOR;

    public function __construct()
    {
        $this->requiereRol('dueno');
    }

    public function index(): void
    {
        $this->vista('reportes/index', [
            'titulo'      => 'Reportes',
            'menuActivo'  => 'reporte',
            'cajas'       => (new Caja())->historial(30),
            'respaldos'   => $this->listarRespaldos(),
            'hayLibrerias'=> class_exists(\Dompdf\Dompdf::class) && class_exists(\PhpOffice\PhpSpreadsheet\Spreadsheet::class),
            'hoy'         => date('Y-m-d'),
            'hace30'      => date('Y-m-d', strtotime('-29 days')),
        ]);
    }

    /** Reporte 1: ventas por rango de fechas. */
    public function ventas(): void
    {
        [$desde, $hasta] = $this->leerRango();
        $r       = new Reporte();
        $ventas  = $r->ventas($desde, $hasta);
        $porDia  = $r->ventasPorDia($desde, $hasta);
        $total   = array_sum(array_column($ventas, 'valor'));
        $titulo  = 'Ventas del ' . date('d/m/Y', strtotime($desde)) . ' al ' . date('d/m/Y', strtotime($hasta));
        $archivo = 'ventas_' . $desde . '_' . $hasta;

        $porMetodo = ['efectivo' => 0, 'transferencia' => 0, 'tarjeta' => 0];
        foreach ($ventas as $v) {
            $porMetodo[$v['metodo']] += (float) $v['valor'];
        }

        if ($this->formato() === 'excel') {
            $this->requiereLibrerias();
            Exportador::excel([
                [
                    'nombre'      => 'Resumen por día',
                    'titulo'      => $titulo,
                    'encabezados' => ['Fecha', 'Cobros', 'Efectivo', 'Nequi / transferencia', 'Tarjeta', 'Total'],
                    'filas'       => array_map(fn($d) => [date('d/m/Y', strtotime($d['dia'])), (int) $d['cobros'],
                                        (float) $d['efectivo'], (float) $d['transferencia'], (float) $d['tarjeta'], (float) $d['total']], $porDia),
                    'pesos'       => [2, 3, 4, 5],
                    'totales'     => ['Total', count($ventas), $porMetodo['efectivo'], $porMetodo['transferencia'], $porMetodo['tarjeta'], $total],
                ],
                [
                    'nombre'      => 'Detalle de cobros',
                    'titulo'      => $titulo . ' — detalle',
                    'encabezados' => ['Fecha y hora', 'Orden', 'Placa', 'Tipo', 'Cliente', 'Servicios', 'Lavador', 'Medio de pago', 'Referencia', 'Valor'],
                    'filas'       => array_map(fn($v) => [date('d/m/Y H:i', strtotime($v['fecha_hora'])), (int) $v['orden_id'],
                                        placa_formato($v['placa']), ucfirst($v['tipo']), $v['cliente'], $v['servicios'], $v['lavador'],
                                        metodo_texto($v['metodo']), (string) $v['referencia'], (float) $v['valor']], $ventas),
                    'pesos'       => [9],
                    'totales'     => ['Total', '', '', '', '', '', '', '', count($ventas) . ' cobros', $total],
                ],
            ], $archivo);
        }

        $this->requiereLibrerias();
        Exportador::pdf('reportes/pdf/ventas', compact('titulo', 'ventas', 'porDia', 'porMetodo', 'total'), $archivo, 'landscape');
    }

    /** Reporte 2: cierre de caja de un día. */
    public function cierre(): void
    {
        $id   = (int) ($_GET['id'] ?? 0);
        $caja = (new Caja())->buscarPorId($id);
        if (!$caja) {
            Sesion::flash('error', 'Seleccione una caja válida.');
            $this->redirigir('reporte');
        }

        $totales = (new Caja())->totales($id);
        $pagos   = array_reverse((new Pago())->porCaja($id));   // en orden de hora
        $fecha   = date('d/m/Y', strtotime($caja['fecha']));
        $titulo  = 'Cierre de caja del ' . $fecha;
        $archivo = 'cierre_caja_' . $caja['fecha'];
        $cerrada = $caja['estado'] === 'cerrada';
        $esperado = $cerrada ? (float) $caja['efectivo_esperado'] : (float) $caja['base_inicial'] + $totales['efectivo'];

        if ($this->formato() === 'excel') {
            $this->requiereLibrerias();
            $resumen = [
                ['Estado', $cerrada ? 'Cerrada' : 'Abierta (sin cerrar)'],
                ['Abrió', $caja['abrio'] . ' · ' . date('H:i', strtotime($caja['fecha_apertura']))],
                ['Cerró', $cerrada ? $caja['cerro'] . ' · ' . date('H:i', strtotime($caja['fecha_cierre'])) : '—'],
                ['Base inicial', (float) $caja['base_inicial']],
                ['Ventas en efectivo', $totales['efectivo']],
                ['Ventas Nequi / transferencia', $totales['transferencia']],
                ['Ventas con tarjeta', $totales['tarjeta']],
                ['Total ventas (' . $totales['cantidad'] . ' cobros)', $totales['total']],
                ['Efectivo esperado (base + efectivo)', $esperado],
                ['Efectivo contado', $cerrada ? (float) $caja['efectivo_contado'] : ''],
                ['Diferencia', $cerrada ? (float) $caja['diferencia'] : ''],
                ['Observaciones', (string) $caja['observaciones']],
            ];
            Exportador::excel([
                ['nombre' => 'Cierre', 'titulo' => $titulo, 'encabezados' => ['Concepto', 'Valor'],
                 'filas' => $resumen, 'pesos' => [1]],
                ['nombre' => 'Cobros', 'titulo' => $titulo . ' — cobros',
                 'encabezados' => ['Hora', 'Orden', 'Placa', 'Cliente', 'Medio de pago', 'Referencia', 'Valor'],
                 'filas' => array_map(fn($p) => [date('H:i', strtotime($p['fecha_hora'])), (int) $p['orden_id'], placa_formato($p['placa']),
                                $p['cliente'], metodo_texto($p['metodo']), (string) $p['referencia'], (float) $p['valor']], $pagos),
                 'pesos' => [6], 'totales' => ['Total', '', '', '', '', count($pagos) . ' cobros', $totales['total']]],
            ], $archivo);
        }

        $this->requiereLibrerias();
        Exportador::pdf('reportes/pdf/cierre', compact('titulo', 'caja', 'totales', 'pagos', 'esperado', 'cerrada'), $archivo);
    }

    /** Reporte 3: servicios por lavador. */
    public function lavadores(): void
    {
        [$desde, $hasta] = $this->leerRango();
        $r         = new Reporte();
        $lavadores = $r->lavadores($desde, $hasta);
        $detalle   = $r->lavadoresDetalle($desde, $hasta);
        $titulo    = 'Servicios por lavador del ' . date('d/m/Y', strtotime($desde)) . ' al ' . date('d/m/Y', strtotime($hasta));
        $archivo   = 'lavadores_' . $desde . '_' . $hasta;
        $totalServ = array_sum(array_column($lavadores, 'servicios'));
        $totalFact = array_sum(array_column($lavadores, 'facturado'));

        if ($this->formato() === 'excel') {
            $this->requiereLibrerias();
            Exportador::excel([
                ['nombre' => 'Resumen', 'titulo' => $titulo,
                 'encabezados' => ['Lavador', 'Servicios', 'Carros', 'Motos', 'Facturado', 'Tiempo promedio (min)', '% de los servicios'],
                 'filas' => array_map(fn($l) => [$l['nombre'], (int) $l['servicios'], (int) $l['carros'], (int) $l['motos'],
                                (float) $l['facturado'], $l['tiempo'] === null ? '' : (int) round($l['tiempo']),
                                number_format($totalServ ? $l['servicios'] / $totalServ * 100 : 0, 1, ',', '.') . ' %'], $lavadores),
                 'pesos' => [4], 'totales' => ['Total', $totalServ, '', '', $totalFact, '', '100 %']],
                ['nombre' => 'Detalle por servicio', 'titulo' => $titulo . ' — detalle',
                 'encabezados' => ['Lavador', 'Servicio', 'Vehículo', 'Cantidad', 'Facturado'],
                 'filas' => array_map(fn($d) => [$d['lavador'], $d['servicio'], ucfirst($d['tipo_vehiculo']), (int) $d['cantidad'],
                                (float) $d['facturado']], $detalle),
                 'pesos' => [4]],
            ], $archivo);
        }

        $this->requiereLibrerias();
        Exportador::pdf('reportes/pdf/lavadores', compact('titulo', 'lavadores', 'detalle', 'totalServ', 'totalFact'), $archivo);
    }

    // ------------------------------------------------------------------
    // Respaldo de la base de datos (RNF-06)
    // ------------------------------------------------------------------

    /** Genera un respaldo con mysqldump y lo guarda en /backups. */
    public function respaldar(): void
    {
        if (!$this->esPost()) {
            $this->redirigir('reporte');
        }

        if (!is_file(MYSQLDUMP_PATH)) {
            Sesion::flash('error', 'No se encontró mysqldump en ' . MYSQLDUMP_PATH . '. Revise MYSQLDUMP_PATH en config/config.php.');
            $this->redirigir('reporte');
        }

        $archivo = self::CARPETA_RESPALDOS . DB_NOMBRE . '_' . date('Y-m-d_His') . '.sql';

        // Cada parte del comando va escapada con escapeshellarg (evita inyección de comandos).
        // La clave se pasa por variable de entorno, no en la línea de comandos.
        $comando = escapeshellarg(MYSQLDUMP_PATH)
            . ' --host=' . escapeshellarg(DB_HOST)
            . ' --port=' . escapeshellarg(DB_PUERTO)
            . ' --user=' . escapeshellarg(DB_USUARIO)
            . ' --default-character-set=utf8mb4 --single-transaction --routines --add-drop-table'
            . ' --databases ' . escapeshellarg(DB_NOMBRE)
            . ' --result-file=' . escapeshellarg($archivo);

        $proceso = proc_open($comando, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $tuberias, null,
            DB_CLAVE !== '' ? ['MYSQL_PWD' => DB_CLAVE] + getenv() : null);

        $error = '';
        $codigo = 1;
        if (is_resource($proceso)) {
            stream_get_contents($tuberias[1]);
            $error = trim(stream_get_contents($tuberias[2]));
            fclose($tuberias[1]);
            fclose($tuberias[2]);
            $codigo = proc_close($proceso);
        }

        if ($codigo === 0 && is_file($archivo) && filesize($archivo) > 0) {
            Sesion::flash('exito', 'Respaldo creado: ' . basename($archivo) . ' (' . $this->tamano(filesize($archivo)) . ').');
        } else {
            if (is_file($archivo)) {
                unlink($archivo);
            }
            Sesion::flash('error', 'No se pudo crear el respaldo. ' . ($error !== '' ? 'Detalle: ' . $error : 'Verifique que MySQL esté encendido.'));
        }
        $this->redirigir('reporte');
    }

    /** Descarga un respaldo. Solo se aceptan nombres con el formato generado por el sistema. */
    public function descargar(): void
    {
        $nombre = (string) ($_GET['archivo'] ?? '');

        // Lista blanca del nombre: impide pedir archivos de otras carpetas (../config/...)
        if (!preg_match('/^' . preg_quote(DB_NOMBRE, '/') . '_\d{4}-\d{2}-\d{2}_\d{6}\.sql$/', $nombre)
            || !is_file(self::CARPETA_RESPALDOS . $nombre)) {
            Sesion::flash('error', 'El respaldo no existe.');
            $this->redirigir('reporte');
        }

        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename="' . $nombre . '"');
        header('Content-Length: ' . filesize(self::CARPETA_RESPALDOS . $nombre));
        readfile(self::CARPETA_RESPALDOS . $nombre);
        exit;
    }

    // ------------------------------------------------------------------

    /** Rango de fechas validado (por defecto, últimos 30 días). */
    private function leerRango(): array
    {
        $desde = (string) ($_GET['desde'] ?? '');
        $hasta = (string) ($_GET['hasta'] ?? '');
        if (!fecha_valida($desde) || !fecha_valida($hasta)) {
            return [date('Y-m-d', strtotime('-29 days')), date('Y-m-d')];
        }
        return $desde <= $hasta ? [$desde, $hasta] : [$hasta, $desde];
    }

    private function formato(): string
    {
        return ($_GET['formato'] ?? 'pdf') === 'excel' ? 'excel' : 'pdf';
    }

    /** Si no se ejecutó "composer install", se avisa en vez de mostrar un error fatal. */
    private function requiereLibrerias(): void
    {
        if (!class_exists(\Dompdf\Dompdf::class) || !class_exists(\PhpOffice\PhpSpreadsheet\Spreadsheet::class)) {
            Sesion::flash('error', 'Faltan las librerías de reportes. Ejecute "composer install" en la carpeta del proyecto.');
            $this->redirigir('reporte');
        }
    }

    private function listarRespaldos(): array
    {
        $archivos = glob(self::CARPETA_RESPALDOS . DB_NOMBRE . '_*.sql') ?: [];
        rsort($archivos);   // el más reciente primero (el nombre lleva la fecha)
        return array_map(fn($a) => [
            'nombre' => basename($a),
            'tamano' => $this->tamano(filesize($a)),
            'fecha'  => date('d/m/Y H:i', filemtime($a)),
        ], array_slice($archivos, 0, 15));
    }

    private function tamano(int $bytes): string
    {
        return $bytes >= 1048576 ? round($bytes / 1048576, 1) . ' MB' : max(1, round($bytes / 1024)) . ' KB';
    }
}
