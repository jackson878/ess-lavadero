<?php
/**
 * Modelo ServicioCatalogo: servicios que vende el lavadero (RF-03).
 */
class ServicioCatalogo
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::conectar();
    }

    /** Reglas de validación. Devuelve [campo => mensaje]. */
    public static function validar(array $d): array
    {
        $errores = [];

        if (mb_strlen($d['nombre']) < 3 || mb_strlen($d['nombre']) > 100) {
            $errores['nombre'] = 'Escriba el nombre del servicio (entre 3 y 100 caracteres).';
        }
        if (!in_array($d['tipo_vehiculo'], Vehiculo::TIPOS, true)) {
            $errores['tipo_vehiculo'] = 'Seleccione el tipo de vehículo.';
        }
        if ($d['precio'] < 1000 || $d['precio'] > 5000000) {
            $errores['precio'] = 'El precio debe estar entre $ 1.000 y $ 5.000.000.';
        }
        if ($d['duracion_min'] < 5 || $d['duracion_min'] > 480) {
            $errores['duracion_min'] = 'La duración debe estar entre 5 y 480 minutos.';
        }
        if (mb_strlen($d['descripcion']) > 255) {
            $errores['descripcion'] = 'Máximo 255 caracteres.';
        }

        return $errores;
    }

    /**
     * Catálogo completo con cuántas veces se vendió cada servicio en los últimos 30 días.
     */
    public function todos(): array
    {
        $sql = "SELECT s.*, COALESCE(v.vendidos, 0) AS vendidos_30
                FROM servicios_catalogo s
                LEFT JOIN (
                    SELECT d.servicio_id, COUNT(*) AS vendidos
                    FROM orden_detalle d
                    JOIN ordenes o ON o.id = d.orden_id
                    WHERE o.estado <> 'cancelada'
                      AND o.fecha_ingreso >= CURDATE() - INTERVAL 30 DAY
                    GROUP BY d.servicio_id
                ) v ON v.servicio_id = s.id
                ORDER BY s.activo DESC, s.tipo_vehiculo, s.precio";
        return $this->db->query($sql)->fetchAll();
    }

    /** Servicios activos para un tipo de vehículo (se usará al crear órdenes). */
    public function activosPorTipo(string $tipo): array
    {
        $sql = "SELECT id, nombre, precio, duracion_min
                FROM servicios_catalogo
                WHERE activo = 1 AND tipo_vehiculo = :tipo
                ORDER BY precio";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':tipo' => $tipo]);
        return $stmt->fetchAll();
    }

    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM servicios_catalogo WHERE id = :id');
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }

    /** ¿Ya existe un servicio con ese nombre para ese tipo de vehículo? */
    public function existeNombre(string $nombre, string $tipo, ?int $exceptoId = null): bool
    {
        $sql = "SELECT COUNT(*) FROM servicios_catalogo
                WHERE nombre = :nombre AND tipo_vehiculo = :tipo AND id <> :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':nombre' => $nombre, ':tipo' => $tipo, ':id' => $exceptoId ?? 0]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function crear(array $d): int
    {
        $sql = "INSERT INTO servicios_catalogo (nombre, tipo_vehiculo, precio, duracion_min, descripcion, activo)
                VALUES (:nombre, :tipo, :precio, :duracion, :descripcion, :activo)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($this->parametros($d));
        return (int) $this->db->lastInsertId();
    }

    public function actualizar(int $id, array $d): void
    {
        $sql = "UPDATE servicios_catalogo
                SET nombre = :nombre, tipo_vehiculo = :tipo, precio = :precio,
                    duracion_min = :duracion, descripcion = :descripcion, activo = :activo
                WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($this->parametros($d) + [':id' => $id]);
    }

    /**
     * Activa o desactiva. Los servicios no se borran porque las órdenes
     * pasadas los referencian; un servicio inactivo ya no aparece al cobrar.
     */
    public function cambiarEstado(int $id, bool $activo): void
    {
        $stmt = $this->db->prepare('UPDATE servicios_catalogo SET activo = :activo WHERE id = :id');
        $stmt->execute([':activo' => $activo ? 1 : 0, ':id' => $id]);
    }

    private function parametros(array $d): array
    {
        return [
            ':nombre'      => $d['nombre'],
            ':tipo'        => $d['tipo_vehiculo'],
            ':precio'      => $d['precio'],
            ':duracion'    => $d['duracion_min'],
            ':descripcion' => $d['descripcion'] !== '' ? $d['descripcion'] : null,
            ':activo'      => $d['activo'],
        ];
    }
}
