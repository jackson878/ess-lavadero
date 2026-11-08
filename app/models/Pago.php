<?php
/**
 * Modelo Pago: cobro de una orden terminada (RF-05).
 * Cada orden tiene como máximo un pago (UNIQUE en orden_id).
 */
class Pago
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::conectar();
    }

    /**
     * Registra el cobro. Debe llamarse dentro de una transacción
     * (la abre el controlador junto con la entrega del vehículo).
     */
    public function crear(int $ordenId, int $cajaId, string $metodo, float $valor, ?string $referencia, int $usuarioId): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO pagos (orden_id, caja_id, metodo, valor, referencia, usuario_id, fecha_hora)
             VALUES (:orden, :caja, :metodo, :valor, :referencia, :usuario, NOW())'
        );
        $stmt->execute([
            ':orden'      => $ordenId,
            ':caja'       => $cajaId,
            ':metodo'     => $metodo,
            ':valor'      => $valor,
            ':referencia' => $referencia ?: null,
            ':usuario'    => $usuarioId,
        ]);
        return (int) $this->db->lastInsertId();
    }

    /** Pago de una orden, o null si no se ha cobrado. */
    public function deOrden(int $ordenId): ?array
    {
        $sql = "SELECT p.*, u.nombre AS cobro
                FROM pagos p JOIN usuarios u ON u.id = p.usuario_id
                WHERE p.orden_id = :orden";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':orden' => $ordenId]);
        return $stmt->fetch() ?: null;
    }

    /** Cobros registrados en una caja, del más reciente al más antiguo. */
    public function porCaja(int $cajaId): array
    {
        $sql = "SELECT p.id, p.orden_id, p.metodo, p.valor, p.referencia, p.fecha_hora,
                       v.placa, c.nombre AS cliente, u.nombre AS cobro
                FROM pagos p
                JOIN ordenes o   ON o.id = p.orden_id
                JOIN vehiculos v ON v.id = o.vehiculo_id
                JOIN clientes c  ON c.id = v.cliente_id
                JOIN usuarios u  ON u.id = p.usuario_id
                WHERE p.caja_id = :caja
                ORDER BY p.fecha_hora DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':caja' => $cajaId]);
        return $stmt->fetchAll();
    }
}
