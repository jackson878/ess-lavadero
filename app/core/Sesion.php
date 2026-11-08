<?php
/**
 * Sesion: manejo seguro de la sesión del usuario (RF-01, RNF-02).
 *
 *  - Cookie de sesión HttpOnly y SameSite=Lax (JavaScript no la puede leer).
 *  - Modo estricto: PHP rechaza ids de sesión que no creó él mismo.
 *  - session_regenerate_id(true) al iniciar sesión (evita fijación de sesión).
 *  - Expiración por inactividad (SESION_MINUTOS_EXPIRACION en config.php).
 *  - Mensajes "flash": avisos que se muestran una sola vez tras redirigir.
 */
class Sesion
{
    /**
     * Arranca la sesión con parámetros seguros y controla la inactividad.
     * Se llama una vez al inicio de cada petición (index.php).
     */
    public static function iniciar(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        session_name(SESION_NOMBRE);
        session_set_cookie_params([
            'lifetime' => 0,                                       // se borra al cerrar el navegador
            'path'     => parse_url(BASE_URL, PHP_URL_PATH) ?: '/',
            'httponly' => true,
            'secure'   => !empty($_SERVER['HTTPS']),
            'samesite' => 'Lax',
        ]);
        session_start();

        self::controlarExpiracion();
    }

    /**
     * Si el usuario lleva más de N minutos sin actividad, se borra su sesión.
     */
    private static function controlarExpiracion(): void
    {
        if (!isset($_SESSION['usuario'])) {
            return;
        }

        $limiteSegundos = SESION_MINUTOS_EXPIRACION * 60;
        $ultimo = $_SESSION['ultimo_movimiento'] ?? 0;

        if (time() - $ultimo > $limiteSegundos) {
            $_SESSION = [];
            session_regenerate_id(true);
            self::flash('aviso', 'Su sesión se cerró por inactividad. Ingrese de nuevo.');
            return;
        }

        $_SESSION['ultimo_movimiento'] = time();
    }

    /**
     * Guarda en la sesión los datos del usuario que acaba de autenticarse.
     */
    public static function iniciarUsuario(array $usuario): void
    {
        // Nuevo id de sesión: un id robado ANTES del login ya no sirve
        session_regenerate_id(true);
        unset($_SESSION['csrf_token']);       // también se renueva el token CSRF

        self::actualizarUsuario($usuario);
        $_SESSION['ultimo_movimiento'] = time();
    }

    /**
     * Refresca nombre y rol del usuario en sesión (por si el Dueño los cambió).
     */
    public static function actualizarUsuario(array $usuario): void
    {
        $_SESSION['usuario'] = [
            'id'      => (int) $usuario['id'],
            'nombre'  => $usuario['nombre'],
            'usuario' => $usuario['usuario'],
            'rol'     => $usuario['rol'],
        ];
    }

    /**
     * Usuario autenticado o null si no hay sesión.
     *
     * @return array{id:int, nombre:string, usuario:string, rol:string}|null
     */
    public static function usuario(): ?array
    {
        return $_SESSION['usuario'] ?? null;
    }

    /**
     * Cierra la sesión por completo: borra datos, cookie y archivo de sesión.
     */
    public static function cerrar(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires'  => time() - 3600,
                'path'     => $p['path'],
                'secure'   => $p['secure'],
                'httponly' => $p['httponly'],
                'samesite' => $p['samesite'],
            ]);
        }

        session_destroy();
    }

    /**
     * Guarda un mensaje para mostrarlo en la siguiente página.
     * Tipos: exito, error, aviso.
     */
    public static function flash(string $tipo, string $mensaje): void
    {
        $_SESSION['flash'][] = ['tipo' => $tipo, 'mensaje' => $mensaje];
    }

    /**
     * Devuelve los mensajes pendientes y los borra (se muestran una sola vez).
     */
    public static function obtenerFlashes(): array
    {
        $mensajes = $_SESSION['flash'] ?? [];
        unset($_SESSION['flash']);
        return $mensajes;
    }
}
