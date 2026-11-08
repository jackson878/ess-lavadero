<?php
/**
 * Modelo Caja: apertura y cierre de la caja diaria (RF-05).
 *
 * Reglas:
 *  - Una sola caja por día (UNIQUE en la columna fecha).
 *  - Todo cobro queda amarrado a la caja abierta del día.
 *  - Al cerrar:  efectivo esperado = base inicial + ventas en efectivo
 *                diferencia        = efectivo contado - efectivo esperado
 *    (negativa = faltante, positiva = sobrante)
 */
class Caja
{
    public const METODOS = ['efectivo', 'transferencia', 'tarjeta'];

    private PDO $db;

    public function __construct()
    {
        $this->db = Database::conectar();
    }

    /** Caja de hoy (abierta o cerrada), o null si no se ha abierto. */
    public function deHoy(): ?array
    {
        $stmt = $this->db->query('SELECT * FROM caja_aperturas_cierres WHERE fecha = CURDATE()');
        return $stmt->fetch() ?: null;
    }

    /** Caja de un día anterior que quedó abierta (alguien olvidó cerrarla). */
    public function abiertaAnterior(): ?array
    {
        $stmt = $this->db->query(
            "SELECT * FROM caja_aperturas_cierres
             WHERE estado = 'abierta' AND fecha < CURDATE()
             ORDER BY fecha LIMIT 1"
        );
        return $stmt->fetch() ?: null;
    }

    public function buscarPorId(int $id): ?array
    {
        $sql = "SELECT c.*, ua.nombre AS abrio, uc.nombre AS cerro
                FROM caja_aperturas_cierres c
                JOIN usuarios ua      ON ua.id = c.usuario_apertura_id
                LEFT JOIN usuarios uc ON uc.id = c.usuario_cierre_id
                WHERE c.id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }

    /** Abre la caja de hoy con la base inicial (el dinero para dar vueltas). */
    public function abrir(int $usuarioId, int $base): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO caja_aperturas_cierres (fecha, usuario_apertura_id, fecha_apertura, base_inicial, estado)
             VALUES (CURDATE(), :usuario, NOW(), :base, 'abierta')"
        );
        $stmt->execute([':usuario' => $usuarioId, ':base' => $base]);
        return (int) $this->db->lastInsertId();
    }

    /**
     * Totales cobrados en una caja, por medio de pago.
     *
     * @return array{efectivo:float, transferencia:float, tarjeta:float, total:float, cantidad:int}
     */
    public function totales(int $cajaId): array
    {
        $stmt = $this->db->prepare(
            'SELECT metodo, SUM(valor) AS total, COUNT(*) AS cantidad
             FROM pagos WHERE caja_id = :caja GROUP BY metodo'
        );
        $stmt->execute([':caja' => $cajaId]);

        $t = ['efectivo' => 0.0, 'transferencia' => 0.0, 'tarjeta' => 0.0, 'total' => 0.0, 'cantidad' => 0];
        foreach ($stmt->fetchAll() as $fila) {
            $t[$fila['metodo']] = (float) $fila['total'];
            $t['total']        += (float) $fila['total'];
            $t['cantidad']     += (int) $fila['cantidad'];
        }
        return $t;
    }

    /**
     * Cierra la caja: guarda los totales, el efectivo esperado, el contado
     * y la diferencia. Devuelve false si ya estaba cerrada.
     */
    public function cerrar(int $cajaId, int $usuarioId, int $contado, string $observaciones): bool
    {
        $caja = $this->buscarPorId($cajaId);
        if (!$caja || $caja['estado'] !== 'abierta') {
            return false;
        }

        $t         = $this->totales($cajaId);
        $esperado  = (float) $caja['base_inicial'] + $t['efectivo'];

        $stmt = $this->db->prepare(
            "UPDATE caja_aperturas_cierres
             SET estado = 'cerrada', usuario_cierre_id = :usuario, fecha_cierre = NOW(),
                 total_efectivo = :efectivo, total_transferencia = :transferencia, total_tarjeta = :tarjeta,
                 total_ventas = :total, efectivo_esperado = :esperado, efectivo_contado = :contado,
                 diferencia = :diferencia, observaciones = :obs
             WHERE id = :id AND estado = 'abierta'"
        );
        $stmt->execute([
            ':usuario'       => $usuarioId,
            ':efectivo'      => $t['efectivo'],
            ':transferencia' => $t['transferencia'],
            ':tarjeta'       => $t['tarjeta'],
            ':total'         => $t['total'],
            ':esperado'      => $esperado,
            ':contado'       => $contado,
            ':diferencia'    => $contado - $esperado,
            ':obs'           => $observaciones !== '' ? $observaciones : null,
            ':id'            => $cajaId,
        ]);
        return $stmt->rowCount() === 1;
    }

    /** Últimas cajas para el historial. */
    public function historial(int $limite = 60): array
    {
        $sql = "SELECT c.*, ua.nombre AS abrio, uc.nombre AS cerro,
                       (SELECT COALESCE(SUM(valor), 0) FROM pagos p WHERE p.caja_id = c.id) AS ventas_actuales
                FROM caja_aperturas_cierres c
                JOIN usuarios ua      ON ua.id = c.usuario_apertura_id
                LEFT JOIN usuarios uc ON uc.id = c.usuario_cierre_id
                ORDER BY c.fecha DESC
                LIMIT :limite";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
