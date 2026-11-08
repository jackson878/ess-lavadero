<?php
/**
 * ESS-Lavadero - Funciones de ayuda usadas en todas las vistas.
 */

/**
 * Escapa un texto antes de mostrarlo en HTML (RNF-02, evita XSS).
 */
function e($texto): string
{
    return htmlspecialchars((string) $texto, ENT_QUOTES, 'UTF-8');
}

/**
 * Formatea un valor en pesos colombianos: 25000 -> "$ 25.000"
 */
function pesos($valor): string
{
    return '$ ' . number_format((float) $valor, 0, ',', '.');
}

/**
 * Construye una URL interna del sistema: url('orden', 'crear') -> index.php?c=orden&a=crear
 */
function url(string $controlador = '', string $accion = '', array $extra = []): string
{
    $parametros = [];
    if ($controlador !== '') {
        $parametros['c'] = $controlador;
    }
    if ($accion !== '') {
        $parametros['a'] = $accion;
    }
    $parametros += $extra;

    return BASE_URL . 'index.php' . ($parametros ? '?' . http_build_query($parametros) : '');
}

/**
 * URL de un archivo dentro de /public (css, js, img).
 */
function recurso(string $ruta): string
{
    return BASE_URL . 'public/' . ltrim($ruta, '/');
}

/** Nombres de los días y meses en español (sin depender de la configuración del servidor). */
const DIAS_SEMANA   = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];
const DIAS_CORTOS   = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];
const MESES         = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio',
                       'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];

/**
 * Fecha larga en español: "sábado 7 de noviembre"
 */
function fecha_larga(?string $fecha = null): string
{
    $t = $fecha ? strtotime($fecha) : time();
    return DIAS_SEMANA[(int) date('w', $t)] . ' ' . date('j', $t) . ' de ' . MESES[(int) date('n', $t) - 1];
}

/**
 * Muestra la placa con un espacio para leerla mejor: "KLM482" -> "KLM 482"
 */
function placa_formato(string $placa): string
{
    return strlen($placa) === 6 ? substr($placa, 0, 3) . ' ' . substr($placa, 3) : $placa;
}

/**
 * Texto e insignia de color para el estado de una orden.
 */
function estado_texto(string $estado): string
{
    $textos = [
        'en_espera' => 'En espera',
        'en_lavado' => 'En lavado',
        'terminado' => 'Terminado',
        'entregado' => 'Entregado',
        'cancelada' => 'Cancelada',
    ];
    return $textos[$estado] ?? $estado;
}

function estado_badge(string $estado): string
{
    return '<span class="estado estado-' . e($estado) . '">' . e(estado_texto($estado)) . '</span>';
}

/**
 * Nombre visible de cada rol.
 */
function rol_texto(string $rol): string
{
    return ['dueno' => 'Dueño', 'cajero' => 'Cajero', 'lavador' => 'Lavador'][$rol] ?? $rol;
}

/**
 * Insignia de color para el rol.
 */
function rol_badge(string $rol): string
{
    return '<span class="rol rol-' . e($rol) . '">' . e(rol_texto($rol)) . '</span>';
}

/**
 * Página a la que llega cada rol después de iniciar sesión: [controlador, acción].
 * (Cajero y Lavador tendrán su pantalla propia en las fases 4 y 5.)
 */
function ruta_inicio(string $rol): array
{
    return match ($rol) {
        'dueno' => ['tablero', 'index'],
        default => ['inicio', 'index'],
    };
}

/**
 * Texto y clase CSS para comparar un KPI de hoy con el mismo día de la semana pasada.
 *  - 'cantidad'   -> "▲ 4 más que el sábado pasado"
 *  - 'porcentaje' -> "▲ 12 % vs. semana pasada"
 *
 * @return array{0:string, 1:string} [texto, clase]
 */
function comparacion_kpi(float $hoy, float $antes, string $tipo): array
{
    $dia = DIAS_SEMANA[(int) date('w')];

    if ($tipo === 'cantidad') {
        $diferencia = (int) ($hoy - $antes);
        if ($diferencia === 0) {
            return ['Igual que el ' . $dia . ' pasado', 'neutra'];
        }
        $texto = ($diferencia > 0 ? '▲ ' : '▼ ') . abs($diferencia)
               . ($diferencia > 0 ? ' más' : ' menos') . ' que el ' . $dia . ' pasado';
        return [$texto, $diferencia > 0 ? 'sube' : 'baja'];
    }

    if ($antes <= 0) {
        return ['Sin ventas el ' . $dia . ' pasado', 'neutra'];
    }
    $porcentaje = (int) round(($hoy - $antes) / $antes * 100);
    return [($porcentaje >= 0 ? '▲ ' : '▼ ') . abs($porcentaje) . ' % vs. semana pasada',
            $porcentaje >= 0 ? 'sube' : 'baja'];
}

/**
 * Opciones de la barra lateral según el rol.
 * Ocultar opciones es solo comodidad visual: el permiso real se valida
 * en cada controlador (Fase 2, RNF-05).
 *
 * @return array<int, array{c:string, texto:string}>
 */
function menu_por_rol(string $rol): array
{
    $menus = [
        'dueno' => [
            ['c' => 'tablero',   'texto' => 'Tablero'],
            ['c' => 'orden',     'texto' => 'Servicios'],
            ['c' => 'caja',      'texto' => 'Caja diaria'],
            ['c' => 'cliente',   'texto' => 'Clientes y vehículos'],
            ['c' => 'catalogo',  'texto' => 'Catálogo'],
            ['c' => 'insumo',    'texto' => 'Insumos'],
            ['c' => 'equipo',    'texto' => 'Equipos'],
            ['c' => 'reporte',   'texto' => 'Reportes'],
            ['c' => 'usuario',   'texto' => 'Usuarios'],
        ],
        'cajero' => [
            ['c' => 'orden',     'texto' => 'Servicios'],
            ['c' => 'caja',      'texto' => 'Caja diaria'],
            ['c' => 'cliente',   'texto' => 'Clientes y vehículos'],
        ],
        'lavador' => [
            ['c' => 'lavador',   'texto' => 'Mi cola'],
        ],
    ];
    return $menus[$rol] ?? [];
}
