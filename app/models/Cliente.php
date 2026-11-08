<?php
/**
 * Modelo Cliente: acceso a la tabla clientes y reglas de validación (RF-02).
 */
class Cliente
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::conectar();
    }

    /**
     * Reglas de validación de un cliente. Devuelve [campo => mensaje].
     * Se usa al crear/editar clientes y al registrar un vehículo con cliente nuevo.
     */
    public static function validar(array $d): array
    {
        $errores = [];

        if (mb_strlen($d['nombre']) < 3 || mb_strlen($d['nombre']) > 100) {
            $errores['nombre'] = 'Escriba el nombre del cliente (entre 3 y 100 caracteres).';
        }

        // Celular (3xx xxx xxxx) o fijo con indicativo nacional (60x xxx xxxx)
        if (!preg_match('/^(3\d{9}|60\d{8})$/', $d['telefono'])) {
            $errores['telefono'] = 'Escriba un teléfono de 10 dígitos (celular que empiece por 3).';
        }

        return $errores;
    }

    /**
     * Lista de clientes con sus placas y la fecha de su última visita.
     */
    public function todos(): array
    {
        $sql = "SELECT c.id, c.nombre, c.telefono, c.creado_en,
                       COUNT(DISTINCT v.id) AS total_vehiculos,
                       GROUP_CONCAT(DISTINCT v.placa ORDER BY v.placa SEPARATOR ', ') AS placas,
                       MAX(o.fecha_ingreso) AS ultima_visita
                FROM clientes c
                LEFT JOIN vehiculos v ON v.cliente_id = c.id
                LEFT JOIN ordenes o   ON o.vehiculo_id = v.id AND o.estado <> 'cancelada'
                GROUP BY c.id, c.nombre, c.telefono, c.creado_en
                ORDER BY c.nombre";
        return $this->db->query($sql)->fetchAll();
    }

    /** Lista corta para los <select> (id, nombre, teléfono). */
    public function listaSimple(): array
    {
        return $this->db->query('SELECT id, nombre, telefono FROM clientes ORDER BY nombre')->fetchAll();
    }

    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM clientes WHERE id = :id');
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function crear(array $d): int
    {
        $stmt = $this->db->prepare('INSERT INTO clientes (nombre, telefono) VALUES (:nombre, :telefono)');
        $stmt->execute([':nombre' => $d['nombre'], ':telefono' => $d['telefono']]);
        return (int) $this->db->lastInsertId();
    }

    public function actualizar(int $id, array $d): void
    {
        $stmt = $this->db->prepare('UPDATE clientes SET nombre = :nombre, telefono = :telefono WHERE id = :id');
        $stmt->execute([':nombre' => $d['nombre'], ':telefono' => $d['telefono'], ':id' => $id]);
    }

    public function eliminar(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM clientes WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }

    public function contarVehiculos(int $id): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM vehiculos WHERE cliente_id = :id');
        $stmt->execute([':id' => $id]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Resumen del cliente: visitas y total pagado.
     */
    public function resumen(int $id): array
    {
        $sql = "SELECT COUNT(DISTINCT o.id)      AS visitas,
                       COALESCE(SUM(p.valor), 0)  AS total_pagado,
                       MAX(o.fecha_ingreso)       AS ultima_visita
                FROM vehiculos v
                JOIN ordenes o     ON o.vehiculo_id = v.id AND o.estado <> 'cancelada'
                LEFT JOIN pagos p  ON p.orden_id = o.id
                WHERE v.cliente_id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    /**
     * Últimas órdenes de todos los vehículos del cliente.
     */
    public function ordenesRecientes(int $id, int $limite = 10): array
    {
        $sql = "SELECT o.id, o.fecha_ingreso, o.estado, o.total, v.placa, v.tipo,
                       GROUP_CONCAT(s.nombre ORDER BY d.id SEPARATOR ' + ') AS servicios
                FROM ordenes o
                JOIN vehiculos v          ON v.id = o.vehiculo_id
                JOIN orden_detalle d      ON d.orden_id = o.id
                JOIN servicios_catalogo s ON s.id = d.servicio_id
                WHERE v.cliente_id = :id
                GROUP BY o.id, o.fecha_ingreso, o.estado, o.total, v.placa, v.tipo
                ORDER BY o.fecha_ingreso DESC
                LIMIT :limite";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
