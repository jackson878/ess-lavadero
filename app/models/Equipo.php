<?php
/**
 * Modelo Equipo: hidrolavadoras, aspiradoras, compresor... y su
 * historial de mantenimientos (RF-09).
 *
 * Alertas de mantenimiento:
 *   vencido  -> la fecha del próximo mantenimiento ya pasó
 *   proximo  -> faltan 7 días o menos
 *   al_dia   -> faltan más de 7 días
 */
class Equipo
{
    public const TIPOS   = ['hidrolavadora', 'aspiradora', 'compresor', 'pulidora', 'otro'];
    public const ESTADOS = ['operativo', 'en_mantenimiento', 'fuera_de_servicio'];
    public const DIAS_AVISO = 7;

    private PDO $db;

    public function __construct()
    {
        $this->db = Database::conectar();
    }

    /** Reglas de validación del equipo. */
    public static function validar(array $d): array
    {
        $errores = [];

        if (mb_strlen($d['nombre']) < 3 || mb_strlen($d['nombre']) > 100) {
            $errores['nombre'] = 'Escriba el nombre del equipo (entre 3 y 100 caracteres).';
        }
        if (!in_array($d['tipo'], self::TIPOS, true)) {
            $errores['tipo'] = 'Seleccione el tipo de equipo.';
        }
        if (!in_array($d['estado'], self::ESTADOS, true)) {
            $errores['estado'] = 'Seleccione el estado.';
        }
        if (mb_strlen($d['marca']) > 50) {
            $errores['marca'] = 'Máximo 50 caracteres.';
        }
        if (mb_strlen($d['serial']) > 50) {
            $errores['serial'] = 'Máximo 50 caracteres.';
        }
        if ($d['fecha_compra'] !== '' && (!fecha_valida($d['fecha_compra']) || $d['fecha_compra'] > date('Y-m-d'))) {
            $errores['fecha_compra'] = 'Escriba una fecha válida que no sea futura.';
        }
        if ($d['fecha_proximo_mant'] !== '' && !fecha_valida($d['fecha_proximo_mant'])) {
            $errores['fecha_proximo_mant'] = 'Escriba una fecha válida.';
        }

        return $errores;
    }

    /** Equipos con los días que faltan para su próximo mantenimiento. */
    public function todos(): array
    {
        $sql = "SELECT e.*,
                       DATEDIFF(e.fecha_proximo_mant, CURDATE()) AS dias_para_mant,
                       (SELECT COUNT(*) FROM mantenimientos m WHERE m.equipo_id = e.id) AS total_mant,
                       (SELECT COALESCE(SUM(m.costo), 0) FROM mantenimientos m WHERE m.equipo_id = e.id) AS costo_mant
                FROM equipos e
                ORDER BY (e.fecha_proximo_mant IS NULL), e.fecha_proximo_mant, e.nombre";
        return $this->db->query($sql)->fetchAll();
    }

    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT e.*, DATEDIFF(e.fecha_proximo_mant, CURDATE()) AS dias_para_mant FROM equipos e WHERE e.id = :id'
        );
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function existeSerial(string $serial, ?int $exceptoId = null): bool
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM equipos WHERE serial = :serial AND id <> :id');
        $stmt->execute([':serial' => $serial, ':id' => $exceptoId ?? 0]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function crear(array $d): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO equipos (nombre, tipo, marca, serial, estado, fecha_compra, fecha_proximo_mant)
             VALUES (:nombre, :tipo, :marca, :serial, :estado, :compra, :proximo)'
        );
        $stmt->execute($this->parametros($d));
        return (int) $this->db->lastInsertId();
    }

    public function actualizar(int $id, array $d): void
    {
        $stmt = $this->db->prepare(
            'UPDATE equipos SET nombre = :nombre, tipo = :tipo, marca = :marca, serial = :serial,
                    estado = :estado, fecha_compra = :compra, fecha_proximo_mant = :proximo
             WHERE id = :id'
        );
        $stmt->execute($this->parametros($d) + [':id' => $id]);
    }

    private function parametros(array $d): array
    {
        return [
            ':nombre'  => $d['nombre'],
            ':tipo'    => $d['tipo'],
            ':marca'   => $d['marca'] !== '' ? $d['marca'] : null,
            ':serial'  => $d['serial'] !== '' ? $d['serial'] : null,   // NULL no choca con el UNIQUE
            ':estado'  => $d['estado'],
            ':compra'  => $d['fecha_compra'] !== '' ? $d['fecha_compra'] : null,
            ':proximo' => $d['fecha_proximo_mant'] !== '' ? $d['fecha_proximo_mant'] : null,
        ];
    }

    /** Historial de mantenimientos de un equipo. */
    public function mantenimientos(int $equipoId): array
    {
        $sql = "SELECT m.*, u.nombre AS usuario
                FROM mantenimientos m
                JOIN usuarios u ON u.id = m.usuario_id
                WHERE m.equipo_id = :equipo
                ORDER BY m.fecha DESC, m.id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':equipo' => $equipoId]);
        return $stmt->fetchAll();
    }

    /**
     * Registra un mantenimiento y actualiza en el equipo la fecha del
     * último y del próximo mantenimiento, y su estado. Todo en una transacción.
     */
    public function registrarMantenimiento(int $equipoId, array $d, int $usuarioId): void
    {
        $this->db->beginTransaction();
        try {
            $this->db->prepare(
                'INSERT INTO mantenimientos (equipo_id, fecha, tipo, descripcion, costo, proxima_fecha, usuario_id)
                 VALUES (:equipo, :fecha, :tipo, :descripcion, :costo, :proxima, :usuario)'
            )->execute([
                ':equipo'      => $equipoId,
                ':fecha'       => $d['fecha'],
                ':tipo'        => $d['tipo'],
                ':descripcion' => $d['descripcion'],
                ':costo'       => $d['costo'],
                ':proxima'     => $d['proxima_fecha'] !== '' ? $d['proxima_fecha'] : null,
                ':usuario'     => $usuarioId,
            ]);

            // El "último mantenimiento" solo avanza (si se registra uno viejo, no retrocede)
            $this->db->prepare(
                'UPDATE equipos
                 SET fecha_ultimo_mant  = GREATEST(COALESCE(fecha_ultimo_mant, :fecha1), :fecha2),
                     fecha_proximo_mant = :proxima,
                     estado             = :estado
                 WHERE id = :id'
            )->execute([
                ':fecha1'  => $d['fecha'],
                ':fecha2'  => $d['fecha'],
                ':proxima' => $d['proxima_fecha'] !== '' ? $d['proxima_fecha'] : null,
                ':estado'  => $d['estado_equipo'],
                ':id'      => $equipoId,
            ]);

            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /** Clasifica la alerta según los días que faltan: vencido, proximo, al_dia o sin_fecha. */
    public static function alerta($dias): string
    {
        if ($dias === null) {
            return 'sin_fecha';
        }
        $dias = (int) $dias;
        return $dias < 0 ? 'vencido' : ($dias <= self::DIAS_AVISO ? 'proximo' : 'al_dia');
    }
}
