<?php
/**
 * Modelo Insumo: inventario de insumos con entradas, salidas y stock mínimo (RF-07).
 *
 * El stock NUNCA se edita a mano: cambia solo con movimientos.
 *   entrada (compra)  -> stock + cantidad
 *   salida (consumo)  -> stock - cantidad  (no puede quedar negativo)
 * Así el stock actual siempre cuadra con el historial de movimientos.
 */
class Insumo
{
    public const UNIDADES = ['litro', 'galón', 'unidad', 'kg', 'paquete', 'caja'];

    private PDO $db;

    public function __construct()
    {
        $this->db = Database::conectar();
    }

    /** Reglas de validación de los datos del insumo. */
    public static function validar(array $d): array
    {
        $errores = [];

        if (mb_strlen($d['nombre']) < 3 || mb_strlen($d['nombre']) > 100) {
            $errores['nombre'] = 'Escriba el nombre del insumo (entre 3 y 100 caracteres).';
        }
        if (!in_array($d['unidad'], self::UNIDADES, true)) {
            $errores['unidad'] = 'Seleccione la unidad de medida.';
        }
        if ($d['stock_minimo'] === null || $d['stock_minimo'] < 0 || $d['stock_minimo'] > 99999) {
            $errores['stock_minimo'] = 'Escriba un stock mínimo válido (0 o más).';
        }
        if ($d['costo_unitario'] < 0 || $d['costo_unitario'] > 10000000) {
            $errores['costo_unitario'] = 'El costo no es válido.';
        }
        if (array_key_exists('stock_inicial', $d)
            && ($d['stock_inicial'] === null || $d['stock_inicial'] < 0 || $d['stock_inicial'] > 99999)) {
            $errores['stock_inicial'] = 'Escriba la cantidad inicial (0 o más).';
        }

        return $errores;
    }

    /** Insumos con indicador de stock bajo (los que están bajo el mínimo van primero). */
    public function todos(): array
    {
        $sql = "SELECT i.*,
                       (i.stock_actual < i.stock_minimo) AS bajo_minimo,
                       (SELECT MAX(m.fecha_hora) FROM movimientos_insumo m WHERE m.insumo_id = i.id) AS ultimo_movimiento
                FROM insumos i
                ORDER BY i.activo DESC, bajo_minimo DESC, i.nombre";
        return $this->db->query($sql)->fetchAll();
    }

    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT *, (stock_actual < stock_minimo) AS bajo_minimo FROM insumos WHERE id = :id');
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function existeNombre(string $nombre, ?int $exceptoId = null): bool
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM insumos WHERE nombre = :nombre AND id <> :id');
        $stmt->execute([':nombre' => $nombre, ':id' => $exceptoId ?? 0]);
        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Crea el insumo. Si trae stock inicial, se registra como una entrada
     * (en la misma transacción) para que quede en el historial.
     */
    public function crear(array $d, int $usuarioId): int
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare(
                'INSERT INTO insumos (nombre, unidad, stock_actual, stock_minimo, costo_unitario, activo)
                 VALUES (:nombre, :unidad, 0, :minimo, :costo, 1)'
            );
            $stmt->execute([
                ':nombre' => $d['nombre'],
                ':unidad' => $d['unidad'],
                ':minimo' => $d['stock_minimo'],
                ':costo'  => $d['costo_unitario'],
            ]);
            $id = (int) $this->db->lastInsertId();

            if ($d['stock_inicial'] > 0) {
                $this->registrarMovimiento($id, 'entrada', $d['stock_inicial'], 'Stock inicial', $usuarioId);
            }

            $this->db->commit();
            return $id;
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /** Actualiza datos descriptivos (el stock solo cambia con movimientos). */
    public function actualizar(int $id, array $d): void
    {
        $stmt = $this->db->prepare(
            'UPDATE insumos SET nombre = :nombre, unidad = :unidad, stock_minimo = :minimo,
                    costo_unitario = :costo, activo = :activo
             WHERE id = :id'
        );
        $stmt->execute([
            ':nombre' => $d['nombre'],
            ':unidad' => $d['unidad'],
            ':minimo' => $d['stock_minimo'],
            ':costo'  => $d['costo_unitario'],
            ':activo' => $d['activo'],
            ':id'     => $id,
        ]);
    }

    /**
     * Registra una entrada o salida y actualiza el stock.
     * Bloquea la fila (FOR UPDATE) para que dos movimientos simultáneos
     * no dejen el stock mal calculado.
     *
     * @return string|null mensaje de error o null si salió bien
     */
    public function registrarMovimiento(int $insumoId, string $tipo, float $cantidad, string $motivo, int $usuarioId): ?string
    {
        $propia = !$this->db->inTransaction();
        if ($propia) {
            $this->db->beginTransaction();
        }

        try {
            $stmt = $this->db->prepare('SELECT stock_actual, unidad FROM insumos WHERE id = :id FOR UPDATE');
            $stmt->execute([':id' => $insumoId]);
            $insumo = $stmt->fetch();

            $error = null;
            if (!$insumo) {
                $error = 'El insumo no existe.';
            } elseif ($tipo === 'salida' && $cantidad > (float) $insumo['stock_actual']) {
                $error = 'No hay suficiente stock: quedan ' . cantidad_unidad($insumo['stock_actual'], $insumo['unidad']) . '.';
            }

            if ($error !== null) {
                if ($propia) {
                    $this->db->rollBack();
                }
                return $error;
            }

            $this->db->prepare(
                'INSERT INTO movimientos_insumo (insumo_id, tipo, cantidad, motivo, usuario_id, fecha_hora)
                 VALUES (:insumo, :tipo, :cantidad, :motivo, :usuario, NOW())'
            )->execute([
                ':insumo'   => $insumoId,
                ':tipo'     => $tipo,
                ':cantidad' => $cantidad,
                ':motivo'   => $motivo !== '' ? $motivo : null,
                ':usuario'  => $usuarioId,
            ]);

            $signo = $tipo === 'entrada' ? '+' : '-';
            $this->db->prepare("UPDATE insumos SET stock_actual = stock_actual $signo :cantidad WHERE id = :id")
                     ->execute([':cantidad' => $cantidad, ':id' => $insumoId]);

            if ($propia) {
                $this->db->commit();
            }
            return null;
        } catch (Throwable $e) {
            if ($propia && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    /** Historial de movimientos de un insumo (los más recientes primero). */
    public function movimientos(int $insumoId, int $limite = 50): array
    {
        $sql = "SELECT m.tipo, m.cantidad, m.motivo, m.fecha_hora, u.nombre AS usuario
                FROM movimientos_insumo m
                JOIN usuarios u ON u.id = m.usuario_id
                WHERE m.insumo_id = :insumo
                ORDER BY m.fecha_hora DESC, m.id DESC
                LIMIT :limite";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':insumo', $insumoId, PDO::PARAM_INT);
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
