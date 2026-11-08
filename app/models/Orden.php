<?php
/**
 * Modelo Orden: órdenes de servicio, su detalle y su historial de estados (RF-04).
 *
 * Flujo de estados:
 *     en_espera  ->  en_lavado  ->  terminado  ->  entregado
 *         \______________/
 *               cancelada   (solo antes de terminar)
 *
 * Cada cambio se guarda en orden_estados con fecha, hora y usuario.
 */
class Orden
{
    /** Estado siguiente de cada estado (el flujo normal) */
    public const SIGUIENTE = [
        'en_espera' => 'en_lavado',
        'en_lavado' => 'terminado',
        'terminado' => 'entregado',
    ];

    /** Estados desde los que se puede cancelar */
    public const CANCELABLES = ['en_espera', 'en_lavado'];

    /** Estados de una orden que sigue en el lavadero */
    public const ACTIVOS = ['en_espera', 'en_lavado', 'terminado'];

    private PDO $db;

    public function __construct()
    {
        $this->db = Database::conectar();
    }

    /**
     * Crea la orden con su detalle y el primer registro del historial.
     * Debe llamarse DENTRO de una transacción (la abre el controlador,
     * porque en el mismo paso puede crear también el cliente y el vehículo).
     *
     * @param array $servicios filas del catálogo [id, precio] ya validadas
     */
    public function crear(int $vehiculoId, int $lavadorId, int $creadoPor, array $servicios, string $observaciones): int
    {
        // El total se calcula en el servidor con los precios del catálogo,
        // nunca con valores enviados desde el navegador.
        $total = array_sum(array_column($servicios, 'precio'));

        $stmt = $this->db->prepare(
            "INSERT INTO ordenes (vehiculo_id, lavador_id, creado_por, estado, total, observaciones)
             VALUES (:vehiculo, :lavador, :creado_por, 'en_espera', :total, :obs)"
        );
        $stmt->execute([
            ':vehiculo'   => $vehiculoId,
            ':lavador'    => $lavadorId,
            ':creado_por' => $creadoPor,
            ':total'      => $total,
            ':obs'        => $observaciones !== '' ? $observaciones : null,
        ]);
        $ordenId = (int) $this->db->lastInsertId();

        // Detalle: se copia el precio del momento
        $detalle = $this->db->prepare(
            'INSERT INTO orden_detalle (orden_id, servicio_id, precio) VALUES (:orden, :servicio, :precio)'
        );
        foreach ($servicios as $s) {
            $detalle->execute([':orden' => $ordenId, ':servicio' => $s['id'], ':precio' => $s['precio']]);
        }

        $this->registrarEstado($ordenId, 'en_espera', $creadoPor);
        return $ordenId;
    }

    /**
     * Cambia el estado validando que la transición sea permitida.
     * Usa SELECT ... FOR UPDATE para que dos clics simultáneos no
     * avancen la orden dos veces.
     *
     * Regla de caja (RF-05): una orden solo se ENTREGA si ya fue cobrada.
     *
     * Si ya hay una transacción abierta (por ejemplo, al cobrar y entregar
     * en un mismo paso) se usa esa; si no, abre la suya.
     *
     * @return string|null mensaje de error, o null si todo salió bien
     */
    public function cambiarEstado(int $ordenId, string $nuevo, int $usuarioId): ?string
    {
        $propia = !$this->db->inTransaction();
        if ($propia) {
            $this->db->beginTransaction();
        }

        try {
            $stmt = $this->db->prepare(
                'SELECT estado, (SELECT COUNT(*) FROM pagos WHERE orden_id = o.id) AS pagado
                 FROM ordenes o WHERE id = :id FOR UPDATE'
            );
            $stmt->execute([':id' => $ordenId]);
            $fila = $stmt->fetch();

            $error = null;
            if (!$fila) {
                $error = 'La orden no existe.';
            } else {
                $actual    = $fila['estado'];
                $permitido = ($nuevo === 'cancelada' && in_array($actual, self::CANCELABLES, true))
                          || (self::SIGUIENTE[$actual] ?? null) === $nuevo;

                if (!$permitido) {
                    $error = 'La orden está "' . estado_texto($actual) . '" y no puede pasar a "' . estado_texto($nuevo) . '".';
                } elseif ($nuevo === 'entregado' && !(int) $fila['pagado']) {
                    $error = 'Primero cobre la orden en la caja; luego podrá entregar el vehículo.';
                }
            }

            if ($error !== null) {
                if ($propia) {
                    $this->db->rollBack();
                }
                return $error;
            }

            $upd = $this->db->prepare('UPDATE ordenes SET estado = :estado WHERE id = :id');
            $upd->execute([':estado' => $nuevo, ':id' => $ordenId]);
            $this->registrarEstado($ordenId, $nuevo, $usuarioId);

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

    /**
     * Bloquea la orden para cobrarla (dentro de una transacción) y devuelve
     * su estado, total y si ya tiene pago. Así dos cajeros no cobran la
     * misma orden al mismo tiempo.
     */
    public function bloquearParaCobro(int $ordenId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, estado, total, (SELECT COUNT(*) FROM pagos WHERE orden_id = o.id) AS pagado
             FROM ordenes o WHERE id = :id FOR UPDATE'
        );
        $stmt->execute([':id' => $ordenId]);
        return $stmt->fetch() ?: null;
    }

    /** Órdenes terminadas que aún no se han cobrado. */
    public function porCobrar(): array
    {
        $sql = self::SELECT_LISTADO . "
                WHERE o.estado = 'terminado'
                  AND NOT EXISTS (SELECT 1 FROM pagos p WHERE p.orden_id = o.id)
                ORDER BY o.fecha_ingreso";
        return $this->db->query($sql)->fetchAll();
    }

    /** Guarda una línea en el historial con la hora actual. */
    private function registrarEstado(int $ordenId, string $estado, int $usuarioId): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO orden_estados (orden_id, estado, usuario_id, fecha_hora) VALUES (:orden, :estado, :usuario, NOW())'
        );
        $stmt->execute([':orden' => $ordenId, ':estado' => $estado, ':usuario' => $usuarioId]);
    }

    public function cambiarLavador(int $ordenId, int $lavadorId): void
    {
        $stmt = $this->db->prepare('UPDATE ordenes SET lavador_id = :lavador WHERE id = :id');
        $stmt->execute([':lavador' => $lavadorId, ':id' => $ordenId]);
    }

    /**
     * Columnas comunes de los listados: orden + vehículo + cliente + lavador + servicios.
     */
    private const SELECT_LISTADO = "
        SELECT o.id, o.estado, o.total, o.fecha_ingreso, o.observaciones, o.lavador_id,
               v.id AS vehiculo_id, v.placa, v.tipo, v.marca, v.color,
               c.id AS cliente_id, c.nombre AS cliente, c.telefono,
               u.nombre AS lavador, cr.nombre AS creado_por,
               (SELECT GROUP_CONCAT(s.nombre ORDER BY d.id SEPARATOR ' + ')
                  FROM orden_detalle d JOIN servicios_catalogo s ON s.id = d.servicio_id
                 WHERE d.orden_id = o.id) AS servicios,
               (SELECT SUM(s.duracion_min)
                  FROM orden_detalle d JOIN servicios_catalogo s ON s.id = d.servicio_id
                 WHERE d.orden_id = o.id) AS duracion_estimada,
               (SELECT MAX(e.fecha_hora) FROM orden_estados e
                 WHERE e.orden_id = o.id AND e.estado = o.estado) AS fecha_estado,
               EXISTS (SELECT 1 FROM pagos p WHERE p.orden_id = o.id) AS pagado
        FROM ordenes o
        JOIN vehiculos v ON v.id = o.vehiculo_id
        JOIN clientes c  ON c.id = v.cliente_id
        JOIN usuarios u  ON u.id = o.lavador_id
        JOIN usuarios cr ON cr.id = o.creado_por";

    /**
     * Órdenes que ingresaron en un rango de fechas, opcionalmente filtradas por estado.
     */
    public function listar(string $desde, string $hasta, ?string $estado = null): array
    {
        $sql = self::SELECT_LISTADO . "
                WHERE o.fecha_ingreso >= :desde AND o.fecha_ingreso < :hasta"
             . ($estado ? ' AND o.estado = :estado' : '') . "
                ORDER BY o.fecha_ingreso DESC";
        $stmt = $this->db->prepare($sql);
        $params = [':desde' => $desde, ':hasta' => date('Y-m-d', strtotime($hasta . ' +1 day'))];
        if ($estado) {
            $params[':estado'] = $estado;
        }
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Órdenes que siguen en el lavadero (en espera, en lavado o terminadas sin entregar),
     * sin importar el día en que ingresaron.
     */
    public function activas(): array
    {
        $sql = self::SELECT_LISTADO . "
                WHERE o.estado IN ('en_espera', 'en_lavado', 'terminado')
                ORDER BY FIELD(o.estado, 'terminado', 'en_lavado', 'en_espera'), o.fecha_ingreso";
        return $this->db->query($sql)->fetchAll();
    }

    /**
     * Cola del lavador: sus órdenes activas + las que terminó hoy.
     */
    public function colaLavador(int $lavadorId): array
    {
        $sql = self::SELECT_LISTADO . "
                WHERE o.lavador_id = :lavador
                  AND (o.estado IN ('en_espera', 'en_lavado')
                       OR (o.estado IN ('terminado', 'entregado') AND o.fecha_ingreso >= CURDATE()))
                ORDER BY FIELD(o.estado, 'en_lavado', 'en_espera', 'terminado', 'entregado'), o.fecha_ingreso";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':lavador' => $lavadorId]);
        return $stmt->fetchAll();
    }

    /** Una orden con todos sus datos. */
    public function buscarDetalle(int $id): ?array
    {
        $stmt = $this->db->prepare(self::SELECT_LISTADO . ' WHERE o.id = :id');
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }

    /** Servicios de la orden con el precio cobrado. */
    public function detalle(int $id): array
    {
        $sql = "SELECT s.nombre, s.duracion_min, d.precio
                FROM orden_detalle d
                JOIN servicios_catalogo s ON s.id = d.servicio_id
                WHERE d.orden_id = :id
                ORDER BY d.id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetchAll();
    }

    /** Historial de estados con la hora y quién hizo el cambio. */
    public function historial(int $id): array
    {
        $sql = "SELECT e.estado, e.fecha_hora, u.nombre AS usuario
                FROM orden_estados e
                JOIN usuarios u ON u.id = e.usuario_id
                WHERE e.orden_id = :id
                ORDER BY e.fecha_hora, e.id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetchAll();
    }

    /**
     * Si el vehículo ya tiene una orden activa, devuelve su id (no puede
     * estar dos veces en el lavadero al mismo tiempo).
     */
    public function ordenActivaDeVehiculo(int $vehiculoId): ?int
    {
        $stmt = $this->db->prepare(
            "SELECT id FROM ordenes
             WHERE vehiculo_id = :vehiculo AND estado IN ('en_espera', 'en_lavado', 'terminado')
             LIMIT 1"
        );
        $stmt->execute([':vehiculo' => $vehiculoId]);
        $id = $stmt->fetchColumn();
        return $id === false ? null : (int) $id;
    }

    /**
     * Lavadores activos con cuántos vehículos tienen pendientes (en espera
     * o en lavado). Sirve para sugerir al que tiene menos carga.
     */
    public function cargaLavadores(): array
    {
        $sql = "SELECT u.id, u.nombre,
                       COUNT(o.id) AS pendientes
                FROM usuarios u
                LEFT JOIN ordenes o
                       ON o.lavador_id = u.id AND o.estado IN ('en_espera', 'en_lavado')
                WHERE u.rol = 'lavador' AND u.activo = 1
                GROUP BY u.id, u.nombre
                ORDER BY pendientes, u.nombre";
        return $this->db->query($sql)->fetchAll();
    }

    /** Cuántas órdenes hay en cada estado dentro de una lista (para los contadores). */
    public static function contarPorEstado(array $ordenes): array
    {
        $conteo = array_fill_keys(['en_espera', 'en_lavado', 'terminado', 'entregado', 'cancelada'], 0);
        foreach ($ordenes as $o) {
            $conteo[$o['estado']]++;
        }
        return $conteo;
    }
}
