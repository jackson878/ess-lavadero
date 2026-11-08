<?php
/**
 * Csrf: protección contra falsificación de peticiones (RNF-02).
 *
 * Cada sesión tiene un token aleatorio. Todo formulario POST lo envía en
 * un campo oculto y el front controller (index.php) lo compara antes de
 * ejecutar cualquier acción. Una página externa no conoce el token, así
 * que no puede enviar formularios en nombre del usuario.
 */
class Csrf
{
    /** Devuelve el token de la sesión (lo crea si no existe). */
    public static function token(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /** Campo oculto listo para poner dentro de un <form method="post">. */
    public static function campo(): string
    {
        return '<input type="hidden" name="csrf_token" value="' . e(self::token()) . '">';
    }

    /** Compara el token recibido con el de la sesión (en tiempo constante). */
    public static function esValido(?string $tokenRecibido): bool
    {
        return is_string($tokenRecibido)
            && !empty($_SESSION['csrf_token'])
            && hash_equals($_SESSION['csrf_token'], $tokenRecibido);
    }
}
