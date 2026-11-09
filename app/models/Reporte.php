<?php
/**
 * Modelo Reporte: datos de los reportes en PDF y Excel (RF-08).
 *
 *   1. Ventas por rango de fechas (cobros recibidos)
 *   2. Cierre de caja (usa los modelos Caja y Pago)
 *   3. Servicios por lavador
 */
class Reporte
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::conectar();
    }

    private function rango(string $desde, string $hasta): array
    {
        return [':desde' => $desde, ':hasta' => date('Y-m-d', strtotime($hasta . ' +1 day'))];
    }

    /** 1. Cada cobro del rango con su orden, vehículo, cliente y servicios. */
    public function ventas(string $desde, string $hasta): array
    {
        $stmt = $this->db->prepare(
            "SELECT p.fecha_hora, p.orden_id, p.metodo, p.valor, p.referencia,
                    v.placa, v.tipo, c.nombre AS cliente, u.nombre AS lavador,
                    (SELECT GROUP_CONCAT(s.nombre ORDER BY d.id SEPARATOR ' + ')
                       FROM orden_detalle d JOIN servicios_catalogo s ON s.id = d.servicio_id
                      WHERE d.orden_id = o.id) AS servicios
             FROM pagos p
             JOIN ordenes o   ON o.id = p.orden_id
             JOIN vehiculos v ON v.id = o.vehiculo_id
             JOIN clientes c  ON c.id = v.cliente_id
             JOIN usuarios u  ON u.id = o.lavador_id
             WHERE p.fecha_hora >= :desde AND p.fecha_hora < :hasta
             ORDER BY p.fecha_hora"
        );
        $stmt->execute($this->rango($desde, $hasta));
        return $stmt->fetchAll();
    }

    /** 1. Totales de ventas por día y medio de pago. */
    public function ventasPorDia(string $desde, string $hasta): array
    {
        $stmt = $this->db->prepare(
            "SELECT DATE(fecha_hora) AS dia, COUNT(*) AS cobros,
                    SUM(IF(metodo = 'efectivo', valor, 0))      AS efectivo,
                    SUM(IF(metodo = 'transferencia', valor, 0)) AS transferencia,
                    SUM(IF(metodo = 'tarjeta', valor, 0))       AS tarjeta,
                    SUM(valor) AS total
             FROM pagos
             WHERE fecha_hora >= :desde AND fecha_hora < :hasta
             GROUP BY DATE(fecha_hora)
             ORDER BY dia"
        );
        $stmt->execute($this->rango($desde, $hasta));
        return $stmt->fetchAll();
    }

    /** 3. Resumen por lavador: servicios, carros, motos, facturado y tiempo promedio. */
    public function lavadores(string $desde, string $hasta): array
    {
        $stmt = $this->db->prepare(
            "SELECT u.nombre,
                    COUNT(o.id)                      AS servicios,
                    COALESCE(SUM(v.tipo = 'carro'), 0) AS carros,
                    COALESCE(SUM(v.tipo = 'moto'), 0)  AS motos,
                    COALESCE(SUM(o.total), 0)        AS facturado,
                    AVG(t.minutos)                   AS tiempo
             FROM usuarios u
             LEFT JOIN ordenes o   ON o.lavador_id = u.id AND o.estado <> 'cancelada'
                                  AND o.fecha_ingreso >= :desde AND o.fecha_ingreso < :hasta
             LEFT JOIN vehiculos v ON v.id = o.vehiculo_id
             LEFT JOIN (SELECT e1.orden_id, TIMESTAMPDIFF(MINUTE, e1.fecha_hora, e2.fecha_hora) AS minutos
                        FROM orden_estados e1
                        JOIN orden_estados e2 ON e2.orden_id = e1.orden_id AND e2.estado = 'terminado'
                        WHERE e1.estado = 'en_espera') t ON t.orden_id = o.id
             WHERE u.rol = 'lavador'
             GROUP BY u.id, u.nombre
             ORDER BY servicios DESC, u.nombre"
        );
        $stmt->execute($this->rango($desde, $hasta));
        return $stmt->fetchAll();
    }

    /** 3. Detalle: cuántas veces hizo cada lavador cada servicio. */
    public function lavadoresDetalle(string $desde, string $hasta): array
    {
        $stmt = $this->db->prepare(
            "SELECT u.nombre AS lavador, s.nombre AS servicio, s.tipo_vehiculo,
                    COUNT(*) AS cantidad, SUM(d.precio) AS facturado
             FROM orden_detalle d
             JOIN ordenes o            ON o.id = d.orden_id
             JOIN usuarios u           ON u.id = o.lavador_id
             JOIN servicios_catalogo s ON s.id = d.servicio_id
             WHERE o.estado <> 'cancelada' AND o.fecha_ingreso >= :desde AND o.fecha_ingreso < :hasta
             GROUP BY u.id, u.nombre, s.id, s.nombre, s.tipo_vehiculo
             ORDER BY u.nombre, cantidad DESC"
        );
        $stmt->execute($this->rango($desde, $hasta));
        return $stmt->fetchAll();
    }
}
