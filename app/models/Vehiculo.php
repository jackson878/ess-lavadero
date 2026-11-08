<?php
/**
 * Modelo Vehiculo: acceso a la tabla vehiculos y reglas de validación (RF-02).
 */
class Vehiculo
{
    public const TIPOS = ['carro', 'moto'];

    private PDO $db;

    public function __construct()
    {
        $this->db = Database::conectar();
    }

    /**
     * Reglas de validación. La placa debe venir ya normalizada (ABC123).
     * Formatos de Colombia:  carro ABC123   ·   moto ABC12D (o ABC12 en motos antiguas)
     */
    public static function validar(array $d): array
    {
        $errores = [];

        if (!in_array($d['tipo'], self::TIPOS, true)) {
            $errores['tipo'] = 'Seleccione si es carro o moto.';
        }

        if ($d['placa'] === '') {
            $errores['placa'] = 'Escriba la placa.';
        } elseif ($d['tipo'] === 'carro' && !preg_match('/^[A-Z]{3}\d{3}$/', $d['placa'])) {
            $errores['placa'] = 'La placa de un carro tiene 3 letras y 3 números (ej. ABC123).';
        } elseif ($d['tipo'] === 'moto' && !preg_match('/^[A-Z]{3}\d{2}[A-Z]?$/', $d['placa'])) {
            $errores['placa'] = 'La placa de una moto tiene 3 letras, 2 números y una letra (ej. ABC12D).';
        }

        if (mb_strlen($d['marca']) > 50) {
            $errores['marca'] = 'Máximo 50 caracteres.';
        }
        if (mb_strlen($d['color']) > 30) {
            $errores['color'] = 'Máximo 30 caracteres.';
        }

        return $errores;
    }

    /**
     * Todos los vehículos con su dueño, número de visitas y última visita.
     */
    public function todos(): array
    {
        $sql = "SELECT v.id, v.placa, v.tipo, v.marca, v.color,
                       c.id AS cliente_id, c.nombre AS cliente, c.telefono,
                       COUNT(o.id) AS visitas, MAX(o.fecha_ingreso) AS ultima_visita
                FROM vehiculos v
                JOIN clientes c     ON c.id = v.cliente_id
                LEFT JOIN ordenes o ON o.vehiculo_id = v.id AND o.estado <> 'cancelada'
                GROUP BY v.id, v.placa, v.tipo, v.marca, v.color, c.id, c.nombre, c.telefono
                ORDER BY v.placa";
        return $this->db->query($sql)->fetchAll();
    }

    /** Vehículos de un cliente, con sus visitas. */
    public function porCliente(int $clienteId): array
    {
        $sql = "SELECT v.id, v.placa, v.tipo, v.marca, v.color,
                       COUNT(o.id) AS visitas, MAX(o.fecha_ingreso) AS ultima_visita
                FROM vehiculos v
                LEFT JOIN ordenes o ON o.vehiculo_id = v.id AND o.estado <> 'cancelada'
                WHERE v.cliente_id = :cliente
                GROUP BY v.id, v.placa, v.tipo, v.marca, v.color
                ORDER BY v.placa";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':cliente' => $clienteId]);
        return $stmt->fetchAll();
    }

    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM vehiculos WHERE id = :id');
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Busca por placa y trae los datos del cliente (para el autocompletado).
     */
    public function buscarPorPlaca(string $placa): ?array
    {
        $sql = "SELECT v.id, v.placa, v.tipo, v.marca, v.color,
                       c.id AS cliente_id, c.nombre AS cliente_nombre, c.telefono AS cliente_telefono
                FROM vehiculos v
                JOIN clientes c ON c.id = v.cliente_id
                WHERE v.placa = :placa";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':placa' => $placa]);
        return $stmt->fetch() ?: null;
    }

    /** ¿Ya existe la placa? (se excluye el propio vehículo al editar) */
    public function existePlaca(string $placa, ?int $exceptoId = null): bool
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM vehiculos WHERE placa = :placa AND id <> :id');
        $stmt->execute([':placa' => $placa, ':id' => $exceptoId ?? 0]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function crear(array $d): int
    {
        $sql = "INSERT INTO vehiculos (cliente_id, placa, tipo, marca, color)
                VALUES (:cliente, :placa, :tipo, :marca, :color)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':cliente' => $d['cliente_id'],
            ':placa'   => $d['placa'],
            ':tipo'    => $d['tipo'],
            ':marca'   => $d['marca'] !== '' ? $d['marca'] : null,
            ':color'   => $d['color'] !== '' ? $d['color'] : null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function actualizar(int $id, array $d): void
    {
        $sql = "UPDATE vehiculos
                SET cliente_id = :cliente, placa = :placa, tipo = :tipo, marca = :marca, color = :color
                WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':cliente' => $d['cliente_id'],
            ':placa'   => $d['placa'],
            ':tipo'    => $d['tipo'],
            ':marca'   => $d['marca'] !== '' ? $d['marca'] : null,
            ':color'   => $d['color'] !== '' ? $d['color'] : null,
            ':id'      => $id,
        ]);
    }

    public function eliminar(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM vehiculos WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }

    public function contarOrdenes(int $id): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM ordenes WHERE vehiculo_id = :id');
        $stmt->execute([':id' => $id]);
        return (int) $stmt->fetchColumn();
    }
}
