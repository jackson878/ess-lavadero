<?php
/**
 * ESS-Lavadero - Front controller (punto de entrada ÚNICO del sistema).
 *
 * Toda petición pasa por aquí. La URL indica qué ejecutar:
 *     index.php?c=usuario&a=editar&id=3
 *         c = controlador  -> app/controllers/UsuarioController.php
 *         a = acción       -> método editar() de esa clase
 *
 * Pasos:
 *   1. Cargar configuración, conexión, funciones y el autocargador de clases.
 *   2. Iniciar la sesión segura.
 *   3. Si la petición es POST, validar el token CSRF.
 *   4. Validar c y a, crear el controlador y ejecutar la acción.
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/app/helpers/funciones.php';

// ---------------------------------------------------------------
// 1. Autocargador: al usar "new UsuarioController()" PHP busca el
//    archivo con ese nombre en estas carpetas, sin escribir require.
// ---------------------------------------------------------------
spl_autoload_register(function (string $clase): void {
    foreach (['core', 'controllers', 'models'] as $carpeta) {
        $archivo = APP_PATH . $carpeta . DIRECTORY_SEPARATOR . $clase . '.php';
        if (is_file($archivo)) {
            require_once $archivo;
            return;
        }
    }
});

/**
 * Muestra una página de error (404, 403, 500) con el diseño del sistema.
 */
function mostrar_error(int $codigo, string $titulo, string $mensaje): never
{
    http_response_code($codigo);
    $usuarioActual = Sesion::usuario();
    require VIEWS_PATH . 'errores/error.php';
    exit;
}

// ---------------------------------------------------------------
// 2. Sesión
// ---------------------------------------------------------------
Sesion::iniciar();

$controlador = $_GET['c'] ?? '';
$accion      = $_GET['a'] ?? 'index';

// Sin controlador: si hay sesión va a su página de inicio; si no, al login
if ($controlador === '') {
    $usuario = Sesion::usuario();
    if ($usuario === null) {
        $controlador = 'auth';
        $accion      = 'login';
    } else {
        [$c, $a] = ruta_inicio($usuario['rol']);
        header('Location: ' . url($c, $a));
        exit;
    }
}

// ---------------------------------------------------------------
// 3. CSRF: ningún formulario POST se procesa sin un token válido
// ---------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !Csrf::esValido($_POST['csrf_token'] ?? null)) {
    if ($controlador === 'auth') {
        Sesion::flash('error', 'El formulario expiró. Intente ingresar de nuevo.');
        header('Location: ' . url('auth', 'login'));
        exit;
    }
    mostrar_error(403, 'Formulario vencido',
        'El formulario expiró o no es válido. Vuelva a la página anterior, recárguela e intente de nuevo.');
}

// ---------------------------------------------------------------
// 4. Enrutamiento seguro
//    - Solo letras en c y a (evita rutas como ../../archivo).
//    - Solo se pueden llamar métodos públicos escritos en ESE
//      controlador (no los heredados de Controller ni __construct).
// ---------------------------------------------------------------
if (!preg_match('/^[a-z]+$/', $controlador) || !preg_match('/^[a-zA-Z]+$/', $accion)) {
    mostrar_error(404, 'Página no encontrada', 'La dirección no es válida.');
}

$clase = ucfirst($controlador) . 'Controller';
if (!is_file(APP_PATH . 'controllers' . DIRECTORY_SEPARATOR . $clase . '.php')) {
    mostrar_error(404, 'Página no encontrada', 'Esta sección no existe o todavía no está disponible.');
}

try {
    $metodo = new ReflectionMethod($clase, $accion);
    $accionValida = $metodo->isPublic()
        && !$metodo->isStatic()
        && $metodo->getDeclaringClass()->getName() === $clase
        && !str_starts_with($accion, '__');
} catch (ReflectionException) {
    $accionValida = false;
}

if (!$accionValida) {
    mostrar_error(404, 'Página no encontrada', 'La acción solicitada no existe.');
}

try {
    $objeto = new $clase();
    $objeto->$accion();
} catch (PDOException $e) {
    $detalle = APP_ENTORNO === 'desarrollo' ? ' Detalle: ' . $e->getMessage() : '';
    mostrar_error(500, 'Error de base de datos',
        'No se pudo completar la operación. Verifique que MySQL esté encendido en XAMPP.' . $detalle);
}
