<?php
/**
 * Modelo Tablero: consultas de los 8 indicadores (KPI) del tablero gerencial (RF-06).
 *
 *   KPI 1  Servicios por día y por mes
 *   KPI 2  Ingresos por día y por mes
 *   KPI 3  Ticket promedio (valor promedio por servicio)
 *   KPI 4  Servicios por tipo de vehículo (carro vs. moto)
 *   KPI 5  Servicios más vendidos
 *   KPI 6  Servicios por lavador
 *   KPI 7  Tiempo promedio de atención (de "en espera" a "terminado")
 *   KPI 8  Insumos por debajo del stock mínimo
 *
 * Definiciones:
 *   - Un "servicio" es una orden que ingresó en el rango y no fue cancelada.
 *   - Los "ingresos" son los pagos recibidos en el rango (lo que entró a caja).
 *
 * Las fechas llegan como 'Y-m-d'. Los rangos se consultan como
 * [desde 00:00, hasta + 1 día 00:00) para aprovechar los índices de fecha.
 */
class Tablero
{
    /** Cómo se agrupa cada serie: por día o por mes (expresiones fijas, nunca del usuario) */
    private const AGRUPAR = [
        'dia' => '%Y-%m-%d',
        'mes' => '%Y-%m',
    ];

    private PDO $db;

    public function __construct()
    {
        $this->db = Database::conectar();
    }

    /** Parámetros del rango: hasta es exclusivo (día siguiente). */
    private function rango(string $desde, string $hasta): array
    {
        return [':desde' => $desde, ':hasta' => date('Y-m-d', strtotime($hasta . ' +1 day'))];
    }

    private function formato(string $agrupar): string
    {
        return self::AGRUPAR[$agrupar] ?? self::AGRUPAR['dia'];
    }

    /**
     * Tarjetas: KPI 1 (total servicios), KPI 2 (total ingresos), KPI 3 (ticket) y KPI 7 (tiempo).
     */
    public function resumen(string $desde, string $hasta): array
    {
        $p = $this->rango($desde, $hasta);

        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM ordenes
             WHERE fecha_ingreso >= :desde AND fecha_ingreso < :hasta AND estado <> 'cancelada'"
        );
        $stmt->execute($p);
        $servicios = (int) $stmt->fetchColumn();

        $stmt = $this->db->prepare(
            'SELECT COALESCE(SUM(valor), 0) AS ingresos, COUNT(*) AS cobros, AVG(valor) AS ticket
             FROM pagos WHERE fecha_hora >= :desde AND fecha_hora < :hasta'
        );
        $stmt->execute($p);
        $pagos = $stmt->fetch();

        return [
            'servicios' => $servicios,
            'ingresos'  => (float) $pagos['ingresos'],
            'cobros'    => (int) $pagos['cobros'],
            'ticket'    => $pagos['ticket'] === null ? 0.0 : (float) $pagos['ticket'],
            'tiempo'    => $this->tiempoPromedio($desde, $hasta),
        ];
    }

    /**
     * KPI 7: minutos promedio entre "en espera" y "terminado", para las
     * órdenes que se terminaron en el rango. Null si no hay ninguna.
     */
    public function tiempoPromedio(string $desde, string $hasta): ?float
    {
        $stmt = $this->db->prepare(
            "SELECT AVG(TIMESTAMPDIFF(MINUTE, espera.fecha_hora, fin.fecha_hora))
             FROM orden_estados espera
             JOIN orden_estados fin ON fin.orden_id = espera.orden_id AND fin.estado = 'terminado'
             WHERE espera.estado = 'en_espera'
               AND fin.fecha_hora >= :desde AND fin.fecha_hora < :hasta"
        );
        $stmt->execute($this->rango($desde, $hasta));
        $valor = $stmt->fetchColumn();
        return $valor === null ? null : (float) $valor;
    }

    /** KPI 1: servicios agrupados por día o por mes. Devuelve [periodo => cantidad]. */
    public function serieServicios(string $desde, string $hasta, string $agrupar): array
    {
        $f = $this->formato($agrupar);
        $stmt = $this->db->prepare(
            "SELECT DATE_FORMAT(fecha_ingreso, '$f') AS periodo, COUNT(*) AS valor
             FROM ordenes
             WHERE fecha_ingreso >= :desde AND fecha_ingreso < :hasta AND estado <> 'cancelada'
             GROUP BY periodo"
        );
        $stmt->execute($this->rango($desde, $hasta));
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_KEY_PAIR));
    }

    /** KPI 2: ingresos agrupados por día o por mes. Devuelve [periodo => pesos]. */
    public function serieIngresos(string $desde, string $hasta, string $agrupar): array
    {
        $f = $this->formato($agrupar);
        $stmt = $this->db->prepare(
            "SELECT DATE_FORMAT(fecha_hora, '$f') AS periodo, SUM(valor) AS valor
             FROM pagos
             WHERE fecha_hora >= :desde AND fecha_hora < :hasta
             GROUP BY periodo"
        );
        $stmt->execute($this->rango($desde, $hasta));
        return array_map('floatval', $stmt->fetchAll(PDO::FETCH_KEY_PAIR));
    }

    /** KPI 7: tiempo promedio de atención por día o por mes. Devuelve [periodo => minutos]. */
    public function serieTiempo(string $desde, string $hasta, string $agrupar): array
    {
        $f = $this->formato($agrupar);
        $stmt = $this->db->prepare(
            "SELECT DATE_FORMAT(fin.fecha_hora, '$f') AS periodo,
                    AVG(TIMESTAMPDIFF(MINUTE, espera.fecha_hora, fin.fecha_hora)) AS valor
             FROM orden_estados espera
             JOIN orden_estados fin ON fin.orden_id = espera.orden_id AND fin.estado = 'terminado'
             WHERE espera.estado = 'en_espera'
               AND fin.fecha_hora >= :desde AND fin.fecha_hora < :hasta
             GROUP BY periodo"
        );
        $stmt->execute($this->rango($desde, $hasta));
        return array_map(fn($v) => round((float) $v), $stmt->fetchAll(PDO::FETCH_KEY_PAIR));
    }

    /** KPI 4: servicios por tipo de vehículo. Devuelve ['carro' => n, 'moto' => n]. */
    public function porTipoVehiculo(string $desde, string $hasta): array
    {
        $stmt = $this->db->prepare(
            "SELECT v.tipo, COUNT(*) AS cantidad
             FROM ordenes o JOIN vehiculos v ON v.id = o.vehiculo_id
             WHERE o.fecha_ingreso >= :desde AND o.fecha_ingreso < :hasta AND o.estado <> 'cancelada'
             GROUP BY v.tipo"
        );
        $stmt->execute($this->rango($desde, $hasta));
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_KEY_PAIR)) + ['carro' => 0, 'moto' => 0];
    }

    /** KPI 5: servicios más vendidos (veces que aparecen en las órdenes) y lo que facturaron. */
    public function masVendidos(string $desde, string $hasta, int $limite = 8): array
    {
        $stmt = $this->db->prepare(
            "SELECT s.nombre, s.tipo_vehiculo, COUNT(*) AS cantidad, SUM(d.precio) AS facturado
             FROM orden_detalle d
             JOIN ordenes o            ON o.id = d.orden_id
             JOIN servicios_catalogo s ON s.id = d.servicio_id
             WHERE o.fecha_ingreso >= :desde AND o.fecha_ingreso < :hasta AND o.estado <> 'cancelada'
             GROUP BY s.id, s.nombre, s.tipo_vehiculo
             ORDER BY cantidad DESC, facturado DESC
             LIMIT :limite"
        );
        foreach ($this->rango($desde, $hasta) as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /** KPI 6: servicios por lavador, con lo facturado y su tiempo promedio. */
    public function porLavador(string $desde, string $hasta): array
    {
        $stmt = $this->db->prepare(
            "SELECT u.nombre, COUNT(o.id) AS servicios, COALESCE(SUM(o.total), 0) AS facturado,
                    AVG(t.minutos) AS tiempo
             FROM usuarios u
             LEFT JOIN ordenes o ON o.lavador_id = u.id
                    AND o.fecha_ingreso >= :desde AND o.fecha_ingreso < :hasta AND o.estado <> 'cancelada'
             -- Minutos de cada orden entre 'en espera' y 'terminado'
             LEFT JOIN (SELECT e1.orden_id, TIMESTAMPDIFF(MINUTE, e1.fecha_hora, e2.fecha_hora) AS minutos
                        FROM orden_estados e1
                        JOIN orden_estados e2 ON e2.orden_id = e1.orden_id AND e2.estado = 'terminado'
                        WHERE e1.estado = 'en_espera') t ON t.orden_id = o.id
             WHERE u.rol = 'lavador'
             GROUP BY u.id, u.nombre
             HAVING servicios > 0 OR MAX(u.activo) = 1
             ORDER BY servicios DESC, u.nombre"
        );
        $stmt->execute($this->rango($desde, $hasta));
        return $stmt->fetchAll();
    }

    /** KPI 8: insumos activos por debajo del stock mínimo (no depende del rango: es el estado actual). */
    public function insumosBajoMinimo(): array
    {
        return $this->db->query(
            'SELECT nombre, stock_actual, stock_minimo, unidad
             FROM insumos
             WHERE activo = 1 AND stock_actual < stock_minimo
             ORDER BY (stock_actual / NULLIF(stock_minimo, 0)), nombre'
        )->fetchAll();
    }

    /** Equipos con mantenimiento vencido o en los próximos 7 días (alerta complementaria). */
    public function equiposPorMantener(): int
    {
        return (int) $this->db->query(
            'SELECT COUNT(*) FROM equipos
             WHERE fecha_proximo_mant IS NOT NULL AND fecha_proximo_mant <= CURDATE() + INTERVAL 7 DAY'
        )->fetchColumn();
    }

    /** Vehículos que están ahora en el lavadero (aún no entregados). */
    public function vehiculosEnLavadero(): array
    {
        return $this->db->query(
            "SELECT o.id, o.estado, o.fecha_ingreso, v.placa, v.tipo,
                    SUBSTRING_INDEX(u.nombre, ' ', 1) AS lavador,
                    GROUP_CONCAT(s.nombre ORDER BY d.id SEPARATOR ' + ') AS servicios
             FROM ordenes o
             JOIN vehiculos v          ON v.id = o.vehiculo_id
             JOIN usuarios u           ON u.id = o.lavador_id
             JOIN orden_detalle d      ON d.orden_id = o.id
             JOIN servicios_catalogo s ON s.id = d.servicio_id
             WHERE o.estado IN ('en_espera', 'en_lavado', 'terminado')
             GROUP BY o.id, o.estado, o.fecha_ingreso, v.placa, v.tipo, u.nombre
             ORDER BY o.fecha_ingreso"
        )->fetchAll();
    }
}
