<?php
/**
 * AuthController: inicio y cierre de sesión (RF-01).
 *
 *   index.php?c=auth&a=login   (GET muestra el formulario, POST valida)
 *   index.php?c=auth&a=logout  (solo POST, con token CSRF)
 */
class AuthController extends Controller
{
    /** Intentos fallidos permitidos antes de bloquear el formulario */
    private const MAX_INTENTOS = 5;
    private const SEGUNDOS_BLOQUEO = 60;

    public function login(): void
    {
        // Si ya inició sesión, no tiene sentido volver a ver el login
        if (Sesion::usuario() !== null) {
            [$c, $a] = ruta_inicio(Sesion::usuario()['rol']);
            $this->redirigir($c, $a);
        }

        $error   = null;
        $usuario = '';

        if ($this->esPost()) {
            $usuario = strtolower($this->entrada('usuario'));
            $clave   = (string) ($_POST['password'] ?? '');

            $bloqueadoHasta = $_SESSION['login_bloqueado_hasta'] ?? 0;
            if ($bloqueadoHasta > time()) {
                $error = 'Demasiados intentos fallidos. Espere ' . ($bloqueadoHasta - time()) . ' segundos.';
            } elseif ($usuario === '' || $clave === '') {
                $error = 'Escriba su usuario y su contraseña.';
            } else {
                $modelo = new Usuario();
                $fila   = $modelo->buscarPorUsuario($usuario);

                // Se verifica la clave aunque el usuario no exista (con un hash falso)
                // para que el tiempo de respuesta no revele qué usuarios existen.
                $hash     = $fila['password_hash'] ?? password_hash('clave-falsa', PASSWORD_BCRYPT);
                $claveOk  = password_verify($clave, $hash);

                if ($fila && $claveOk && (int) $fila['activo'] === 1) {
                    unset($_SESSION['login_intentos'], $_SESSION['login_bloqueado_hasta']);

                    // Si el costo de bcrypt cambió, se vuelve a cifrar la clave
                    if (password_needs_rehash($fila['password_hash'], PASSWORD_BCRYPT)) {
                        $modelo->actualizarClave((int) $fila['id'], $clave);
                    }

                    $modelo->registrarAcceso((int) $fila['id']);
                    Sesion::iniciarUsuario($fila);   // aquí se regenera el id de sesión
                    Sesion::flash('exito', 'Bienvenido, ' . $fila['nombre'] . '.');

                    [$c, $a] = ruta_inicio($fila['rol']);
                    $this->redirigir($c, $a);
                }

                // Mensaje genérico: no se dice si falló el usuario o la contraseña
                $error = 'Usuario o contraseña incorrectos.';
                $_SESSION['login_intentos'] = ($_SESSION['login_intentos'] ?? 0) + 1;
                if ($_SESSION['login_intentos'] >= self::MAX_INTENTOS) {
                    $_SESSION['login_bloqueado_hasta'] = time() + self::SEGUNDOS_BLOQUEO;
                    $_SESSION['login_intentos'] = 0;
                    $error = 'Demasiados intentos fallidos. Espere ' . self::SEGUNDOS_BLOQUEO . ' segundos.';
                }
            }
        }

        $this->vista('auth/login', [
            'titulo'  => 'Iniciar sesión',
            'error'   => $error,
            'usuario' => $usuario,
        ]);
    }

    public function logout(): void
    {
        // Solo por POST: así un enlace o imagen de otra página no puede cerrar la sesión
        if (!$this->esPost()) {
            $this->redirigir();
        }

        Sesion::cerrar();
        Sesion::iniciar();   // sesión nueva y vacía, solo para mostrar el mensaje
        Sesion::flash('exito', 'Sesión cerrada correctamente.');
        $this->redirigir('auth', 'login');
    }
}
