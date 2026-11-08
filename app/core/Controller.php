<?php
/**
 * Controller: clase base de la que heredan todos los controladores.
 *
 * Ofrece:
 *  - vista()        muestra una vista pasándole datos.
 *  - redirigir()    envía al navegador a otra ruta.
 *  - requiereLogin() / requiereRol()  CONTROL DE ACCESO EN EL SERVIDOR (RNF-05).
 *    Ocultar un botón no basta: si alguien escribe la URL a mano, estas
 *    funciones lo detienen.
 */
abstract class Controller
{
    /** Usuario autenticado (lo llena requiereLogin) */
    protected ?array $usuario = null;

    /**
     * Muestra una vista de app/views. Ejemplo: $this->vista('usuarios/index', ['usuarios' => $lista]);
     * Cada clave del arreglo se convierte en una variable dentro de la vista.
     */
    protected function vista(string $_vista, array $_variables = []): void
    {
        // Nombres con guion bajo para que no choquen con las variables de la vista
        // (por ejemplo, el formulario de usuarios recibe una variable llamada $datos).
        $_variables['usuarioActual'] = $this->usuario ?? Sesion::usuario();
        extract($_variables, EXTR_SKIP);
        require VIEWS_PATH . $_vista . '.php';
    }

    /** Redirige a otra ruta del sistema y termina la petición. */
    protected function redirigir(string $controlador = '', string $accion = '', array $extra = []): never
    {
        header('Location: ' . url($controlador, $accion, $extra));
        exit;
    }

    /** ¿La petición llegó por POST (envío de formulario)? */
    protected function esPost(): bool
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }

    /** Lee un campo de texto del formulario, sin espacios al inicio/fin. */
    protected function entrada(string $campo): string
    {
        return trim((string) ($_POST[$campo] ?? ''));
    }

    /**
     * Exige que haya una sesión iniciada. Además vuelve a leer el usuario
     * en la base de datos: si el Dueño lo desactivó o le cambió el rol,
     * el cambio aplica de inmediato, sin esperar a que cierre sesión.
     */
    protected function requiereLogin(): array
    {
        $sesion = Sesion::usuario();
        if ($sesion === null) {
            $this->redirigir('auth', 'login');
        }

        $fila = (new Usuario())->buscarPorId($sesion['id']);
        if (!$fila || !(int) $fila['activo']) {
            Sesion::cerrar();
            Sesion::iniciar();
            Sesion::flash('error', 'Su usuario está inactivo. Consulte con el dueño del lavadero.');
            $this->redirigir('auth', 'login');
        }

        Sesion::actualizarUsuario($fila);
        return $this->usuario = Sesion::usuario();
    }

    /**
     * Exige sesión Y uno de los roles indicados. Si el rol no está
     * permitido responde 403 (acceso denegado) y detiene la ejecución.
     * Ejemplo: $this->requiereRol('dueno', 'cajero');
     */
    protected function requiereRol(string ...$roles): array
    {
        $usuario = $this->requiereLogin();

        if (!in_array($usuario['rol'], $roles, true)) {
            http_response_code(403);
            $this->vista('errores/error', [
                'titulo'  => 'Acceso denegado',
                'codigo'  => 403,
                'mensaje' => 'Su rol (' . rol_texto($usuario['rol']) . ') no tiene permiso para entrar a esta sección.',
            ]);
            exit;
        }

        return $usuario;
    }
}
