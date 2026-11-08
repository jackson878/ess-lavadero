<?php
/**
 * Modelo Usuario: acceso a la tabla usuarios.
 * Todas las consultas usan sentencias preparadas (RNF-02).
 */
class Usuario
{
    public const ROLES = ['dueno', 'cajero', 'lavador'];

    private PDO $db;

    public function __construct()
    {
        $this->db = Database::conectar();
    }

    /** Lista de todos los usuarios: activos primero, luego por rol y nombre. */
    public function todos(): array
    {
        $sql = "SELECT id, nombre, usuario, rol, telefono, activo, ultimo_acceso
                FROM usuarios
                ORDER BY activo DESC, FIELD(rol, 'dueno', 'cajero', 'lavador'), nombre";
        return $this->db->query($sql)->fetchAll();
    }

    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM usuarios WHERE id = :id');
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function buscarPorUsuario(string $usuario): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM usuarios WHERE usuario = :usuario');
        $stmt->execute([':usuario' => $usuario]);
        return $stmt->fetch() ?: null;
    }

    /** ¿Ya existe ese nombre de usuario? (se puede excluir un id al editar) */
    public function existeUsuario(string $usuario, ?int $exceptoId = null): bool
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM usuarios WHERE usuario = :usuario AND id <> :id');
        $stmt->execute([':usuario' => $usuario, ':id' => $exceptoId ?? 0]);
        return (int) $stmt->fetchColumn() > 0;
    }

    /** Crea un usuario. La contraseña se guarda con bcrypt, nunca en texto plano. */
    public function crear(array $d): int
    {
        $sql = "INSERT INTO usuarios (nombre, usuario, password_hash, rol, telefono, activo)
                VALUES (:nombre, :usuario, :hash, :rol, :telefono, :activo)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':nombre'   => $d['nombre'],
            ':usuario'  => $d['usuario'],
            ':hash'     => password_hash($d['password'], PASSWORD_BCRYPT),
            ':rol'      => $d['rol'],
            ':telefono' => $d['telefono'] !== '' ? $d['telefono'] : null,
            ':activo'   => $d['activo'],
        ]);
        return (int) $this->db->lastInsertId();
    }

    /** Actualiza los datos. Si $d['password'] viene vacío, la contraseña no cambia. */
    public function actualizar(int $id, array $d): void
    {
        $sql = "UPDATE usuarios
                SET nombre = :nombre, usuario = :usuario, rol = :rol,
                    telefono = :telefono, activo = :activo
                WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':nombre'   => $d['nombre'],
            ':usuario'  => $d['usuario'],
            ':rol'      => $d['rol'],
            ':telefono' => $d['telefono'] !== '' ? $d['telefono'] : null,
            ':activo'   => $d['activo'],
            ':id'       => $id,
        ]);

        if ($d['password'] !== '') {
            $this->actualizarClave($id, $d['password']);
        }
    }

    public function actualizarClave(int $id, string $clave): void
    {
        $stmt = $this->db->prepare('UPDATE usuarios SET password_hash = :hash WHERE id = :id');
        $stmt->execute([':hash' => password_hash($clave, PASSWORD_BCRYPT), ':id' => $id]);
    }

    /** Activa o desactiva (no se borra: el usuario tiene órdenes y pagos asociados). */
    public function cambiarEstado(int $id, bool $activo): void
    {
        $stmt = $this->db->prepare('UPDATE usuarios SET activo = :activo WHERE id = :id');
        $stmt->execute([':activo' => $activo ? 1 : 0, ':id' => $id]);
    }

    public function registrarAcceso(int $id): void
    {
        $stmt = $this->db->prepare('UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }
}
