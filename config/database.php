<?php
/**
 * ESS-Lavadero - Conexión a la base de datos con PDO.
 *
 * Se usa el patrón Singleton: la conexión se crea UNA sola vez por
 * petición y todos los modelos la reutilizan con Database::conectar().
 *
 * Seguridad (RNF-02):
 *  - ATTR_EMULATE_PREPARES = false -> las consultas preparadas las
 *    resuelve el motor MySQL, lo que evita inyección SQL.
 *  - ERRMODE_EXCEPTION -> cualquier error de SQL lanza una excepción.
 */

// Credenciales de XAMPP por defecto (usuario root sin contraseña)
define('DB_HOST', '127.0.0.1');
define('DB_PUERTO', '3306');
define('DB_NOMBRE', 'ess_lavadero');
define('DB_USUARIO', 'root');
define('DB_CLAVE', '');
define('DB_CHARSET', 'utf8mb4');

class Database
{
    /** @var PDO|null Única instancia de la conexión */
    private static ?PDO $conexion = null;

    /** Evita que se cree con "new Database()" */
    private function __construct()
    {
    }

    /**
     * Devuelve la conexión PDO (la crea la primera vez).
     *
     * @throws PDOException si no se puede conectar
     */
    public static function conectar(): PDO
    {
        if (self::$conexion === null) {
            $dsn = 'mysql:host=' . DB_HOST
                 . ';port=' . DB_PUERTO
                 . ';dbname=' . DB_NOMBRE
                 . ';charset=' . DB_CHARSET;

            $opciones = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // errores como excepciones
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // filas como arreglos asociativos
                PDO::ATTR_EMULATE_PREPARES   => false,                  // preparadas reales
            ];

            self::$conexion = new PDO($dsn, DB_USUARIO, DB_CLAVE, $opciones);

            // La base de datos usa la misma hora de Colombia que PHP (UTC-5)
            self::$conexion->exec("SET time_zone = '-05:00'");
        }

        return self::$conexion;
    }
}
