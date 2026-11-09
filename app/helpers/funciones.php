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
 * Normaliza una placa: mayúsculas y sin espacios ni guiones. "abc-123" -> "ABC123"
 */
function normalizar_placa(string $placa): string
{
    return preg_replace('/[^A-Z0-9]/', '', strtoupper($placa));
}

/**
 * Teléfono legible: "3104567821" -> "310 456 7821"
 */
function telefono_formato(?string $telefono): string
{
    if (!$telefono) {
        return '—';
    }
    return strlen($telefono) === 10
        ? substr($telefono, 0, 3) . ' ' . substr($telefono, 3, 3) . ' ' . substr($telefono, 6)
        : $telefono;
}

/**
 * Duración legible: 30 -> "30 min", 90 -> "1 h 30 min"
 */
function duracion_texto(int $minutos): string
{
    if ($minutos < 60) {
        return $minutos . ' min';
    }
    $horas = intdiv($minutos, 60);
    $resto = $minutos % 60;
    return $horas . ' h' . ($resto ? ' ' . $resto . ' min' : '');
}

/**
 * Insignia con ícono para el tipo de vehículo (carro / moto).
 */
function tipo_badge(string $tipo): string
{
    $icono = $tipo === 'moto' ? 'bi-scooter' : 'bi-car-front';
    return '<span class="tipo tipo-' . e($tipo) . '"><i class="bi ' . $icono . '"></i> '
         . e(ucfirst($tipo)) . '</span>';
}

/**
 * Ayudas para formularios con Bootstrap:
 *   campo_invalido($errores, 'nombre') -> ' is-invalid' si el campo tiene error
 *   campo_error($errores, 'nombre')    -> el mensaje de error debajo del campo
 */
function campo_invalido(array $errores, string $campo): string
{
    return isset($errores[$campo]) ? ' is-invalid' : '';
}

function campo_error(array $errores, string $campo): string
{
    return isset($errores[$campo])
        ? '<div class="invalid-feedback">' . e($errores[$campo]) . '</div>'
        : '';
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
 */
function ruta_inicio(string $rol): array
{
    return match ($rol) {
        'dueno'   => ['tablero', 'index'],
        'cajero'  => ['orden', 'index'],
        'lavador' => ['lavador', 'index'],
        default   => ['inicio', 'index'],
    };
}

/**
 * Enlace de WhatsApp para avisar que el vehículo está listo (RF-10).
 * Devuelve null si el teléfono no es un celular (WhatsApp solo funciona con celulares).
 *   https://wa.me/573104567821?text=Su%20veh%C3%ADculo%20KLM%20905%20est%C3%A1%20listo
 */
function whatsapp_url(?string $telefono, string $placa): ?string
{
    if (!$telefono || !preg_match('/^3\d{9}$/', $telefono)) {
        return null;
    }
    $mensaje = 'Su vehículo ' . placa_formato($placa) . ' está listo';
    return 'https://wa.me/' . WHATSAPP_PREFIJO . $telefono . '?text=' . rawurlencode($mensaje);
}

/**
 * Tiempo transcurrido desde una fecha: "8 min", "1 h 25 min"
 */
function tiempo_desde(string $fecha): string
{
    $minutos = max(0, (int) floor((time() - strtotime($fecha)) / 60));
    return duracion_texto($minutos);
}

/**
 * Cantidad sin ceros sobrantes: 12.00 -> "12", 1.50 -> "1,5"
 */
function cantidad_formato($cantidad): string
{
    return rtrim(rtrim(number_format((float) $cantidad, 2, ',', '.'), '0'), ',');
}

/**
 * Cantidad con su unidad en singular o plural: (3, 'unidad') -> "3 unidades", (1, 'litro') -> "1 litro"
 */
function cantidad_unidad($cantidad, string $unidad): string
{
    $plurales = ['litro' => 'litros', 'galón' => 'galones', 'unidad' => 'unidades',
                 'paquete' => 'paquetes', 'caja' => 'cajas', 'kg' => 'kg'];
    $texto = (float) $cantidad == 1 ? $unidad : ($plurales[$unidad] ?? $unidad);
    return cantidad_formato($cantidad) . ' ' . $texto;
}

/**
 * Lee una cantidad escrita con coma o punto decimal: "1,5" -> 1.5
 * Devuelve null si no es un número.
 */
function leer_cantidad(string $texto): ?float
{
    $texto = str_replace(',', '.', trim($texto));
    return is_numeric($texto) ? round((float) $texto, 2) : null;
}

/**
 * ¿Es una fecha válida con formato AAAA-MM-DD?
 */
function fecha_valida(string $fecha): bool
{
    $d = DateTime::createFromFormat('Y-m-d', $fecha);
    return $d !== false && $d->format('Y-m-d') === $fecha;
}

/**
 * Nombre visible de los tipos y estados de equipo.
 */
function equipo_tipo_texto(string $tipo): string
{
    return ucfirst($tipo);
}

function equipo_estado_texto(string $estado): string
{
    return ['operativo' => 'Operativo', 'en_mantenimiento' => 'En mantenimiento',
            'fuera_de_servicio' => 'Fuera de servicio'][$estado] ?? $estado;
}

/**
 * Insignia de la alerta de mantenimiento según los días que faltan.
 */
function mantenimiento_badge($dias): string
{
    $alerta = Equipo::alerta($dias);
    $texto = match ($alerta) {
        'vencido'   => 'Vencido hace ' . abs((int) $dias) . ' ' . (abs((int) $dias) === 1 ? 'día' : 'días'),
        'proximo'   => (int) $dias === 0 ? 'Hoy' : 'En ' . (int) $dias . ' ' . ((int) $dias === 1 ? 'día' : 'días'),
        'al_dia'    => 'En ' . (int) $dias . ' días',
        default     => 'Sin programar',
    };
    return '<span class="alerta-mant alerta-' . $alerta . '">' . e($texto) . '</span>';
}

/**
 * Nombre e ícono de cada medio de pago.
 */
function metodo_texto(string $metodo): string
{
    return ['efectivo' => 'Efectivo', 'transferencia' => 'Nequi / transferencia', 'tarjeta' => 'Tarjeta'][$metodo] ?? $metodo;
}

function metodo_icono(string $metodo): string
{
    return ['efectivo' => 'bi-cash-coin', 'transferencia' => 'bi-phone', 'tarjeta' => 'bi-credit-card'][$metodo] ?? 'bi-wallet2';
}

/**
 * Texto del botón que lleva la orden a su siguiente estado.
 */
function texto_siguiente_estado(string $estado): string
{
    return [
        'en_espera' => 'Empezar lavado',
        'en_lavado' => 'Marcar terminado',
        'terminado' => 'Entregar',
    ][$estado] ?? '';
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
