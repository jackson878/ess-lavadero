<?php
/**
 * Modelo Tablero: consultas de los indicadores (KPI) del tablero gerencial.
 *
 * Todas las fechas se reciben como 'Y-m-d'. Los rangos se consultan como
 * [desde 00:00, hasta + 1 día 00:00) para aprovechar los índices de fecha
 * (no se usa DATE(columna) en el WHERE porque eso impide usar el índice).
 */
class Tablero
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::conectar();
    }

    /** Devuelve el día siguiente a $fecha ('Y-m-d'), usado como límite exclusivo. */
    private function diaSiguiente(string $fecha): string
    {
        return date('Y-m-d', strtotime($fecha . ' +1 day'));
    }

    /**
     * KPI 1: número de servicios (órdenes no canceladas) que ingresaron en un rango.
     */
    public function serviciosEnRango(string $desde, string $hasta): int
    {
        $sql = "SELECT COUNT(*) FROM ordenes
                WHERE fecha_ingreso >= :desde AND fecha_ingreso < :hasta
                  AND estado <> 'cancelada'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':desde' => $desde, ':hasta' => $this->diaSiguiente($hasta)]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * KPI 2: ingresos (pagos recibidos) en un rango.
     */
    public function ingresosEnRango(string $desde, string $hasta): float
    {
        $sql = "SELECT COALESCE(SUM(valor), 0) FROM pagos
                WHERE fecha_hora >= :desde AND fecha_hora < :hasta";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':desde' => $desde, ':hasta' => $this->diaSiguiente($hasta)]);
        return (float) $stmt->fetchColumn();
    }

    /**
     * KPI 2 (gráfica): ingresos agrupados por día. Devuelve ['Y-m-d' => total].
     */
    public function ingresosPorDia(string $desde, string $hasta): array
    {
        $sql = "SELECT DATE(fecha_hora) AS dia, SUM(valor) AS total
                FROM pagos
                WHERE fecha_hora >= :desde AND fecha_hora < :hasta
                GROUP BY DATE(fecha_hora)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':desde' => $desde, ':hasta' => $this->diaSiguiente($hasta)]);

        $resultado = [];
        foreach ($stmt->fetchAll() as $fila) {
            $resultado[$fila['dia']] = (float) $fila['total'];
        }
        return $resultado;
    }

    /**
     * KPI 3: ticket promedio = valor promedio pagado por orden.
     */
    public function ticketPromedio(string $desde, string $hasta): float
    {
        $sql = "SELECT COALESCE(AVG(valor), 0) FROM pagos
                WHERE fecha_hora >= :desde AND fecha_hora < :hasta";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':desde' => $desde, ':hasta' => $this->diaSiguiente($hasta)]);
        return (float) $stmt->fetchColumn();
    }

    /**
     * KPI 5: servicios más vendidos (cuántas veces aparece cada servicio en las órdenes).
     */
    public function serviciosMasVendidos(string $desde, string $hasta, int $limite = 4): array
    {
        $sql = "SELECT s.nombre, s.tipo_vehiculo, COUNT(*) AS cantidad
                FROM orden_detalle d
                JOIN ordenes o            ON o.id = d.orden_id
                JOIN servicios_catalogo s ON s.id = d.servicio_id
                WHERE o.fecha_ingreso >= :desde AND o.fecha_ingreso < :hasta
                  AND o.estado <> 'cancelada'
                GROUP BY s.id, s.nombre, s.tipo_vehiculo
                ORDER BY cantidad DESC, s.nombre
                LIMIT :limite";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':desde', $desde);
        $stmt->bindValue(':hasta', $this->diaSiguiente($hasta));
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * KPI 7: tiempo promedio de atención en minutos (de "en espera" a "terminado").
     * Devuelve null si no hay órdenes terminadas en el rango.
     */
    public function tiempoPromedioAtencion(string $desde, string $hasta): ?float
    {
        $sql = "SELECT AVG(TIMESTAMPDIFF(MINUTE, espera.fecha_hora, fin.fecha_hora))
                FROM orden_estados espera
                JOIN orden_estados fin
                  ON fin.orden_id = espera.orden_id AND fin.estado = 'terminado'
                WHERE espera.estado = 'en_espera'
                  AND fin.fecha_hora >= :desde AND fin.fecha_hora < :hasta";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':desde' => $desde, ':hasta' => $this->diaSiguiente($hasta)]);
        $valor = $stmt->fetchColumn();
        return $valor === null ? null : (float) $valor;
    }

    /**
     * KPI 8: insumos activos por debajo del stock mínimo.
     */
    public function insumosBajoMinimo(): array
    {
        $sql = "SELECT nombre, stock_actual, stock_minimo, unidad
                FROM insumos
                WHERE activo = 1 AND stock_actual < stock_minimo
                ORDER BY nombre";
        return $this->db->query($sql)->fetchAll();
    }

    /**
     * Vehículos que están ahora en el lavadero (aún no entregados).
     */
    public function vehiculosEnLavadero(): array
    {
        $sql = "SELECT o.id, o.estado, o.fecha_ingreso, v.placa, v.tipo,
                       SUBSTRING_INDEX(u.nombre, ' ', 1) AS lavador,
                       GROUP_CONCAT(s.nombre ORDER BY d.id SEPARATOR ' + ') AS servicios
                FROM ordenes o
                JOIN vehiculos v          ON v.id = o.vehiculo_id
                JOIN usuarios u           ON u.id = o.lavador_id
                JOIN orden_detalle d      ON d.orden_id = o.id
                JOIN servicios_catalogo s ON s.id = d.servicio_id
                WHERE o.estado IN ('en_espera', 'en_lavado', 'terminado')
                GROUP BY o.id, o.estado, o.fecha_ingreso, v.placa, v.tipo, u.nombre
                ORDER BY o.fecha_ingreso";
        return $this->db->query($sql)->fetchAll();
    }
}
