<?php
/**
 * ESS-Lavadero - Configuración general de la aplicación.
 *
 * Aquí se definen las constantes que usa todo el sistema.
 * Si cambia la carpeta del proyecto o el nombre del negocio,
 * solo debe modificar este archivo.
 */

// ---------------------------------------------------------------
// Datos del sistema y del negocio
// ---------------------------------------------------------------
define('APP_NOMBRE', 'ESS-Lavadero');
define('APP_VERSION', '1.0.0');
define('NEGOCIO_NOMBRE', 'Lava Autos');              // nombre del lavadero (barra lateral, login y reportes)

// ---------------------------------------------------------------
// Entorno: 'desarrollo' muestra errores en pantalla; 'produccion' los oculta
// ---------------------------------------------------------------
define('APP_ENTORNO', 'desarrollo');

// ---------------------------------------------------------------
// Rutas
//   BASE_URL  = dirección web del proyecto (termina en /)
//   ROOT_PATH = ruta física en disco de la carpeta del proyecto
// ---------------------------------------------------------------
define('BASE_URL', 'http://localhost/lavaautos/');
define('ROOT_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
define('APP_PATH', ROOT_PATH . 'app' . DIRECTORY_SEPARATOR);
define('VIEWS_PATH', APP_PATH . 'views' . DIRECTORY_SEPARATOR);

// ---------------------------------------------------------------
// Sesión: minutos de inactividad antes de cerrar la sesión (RF-01)
// ---------------------------------------------------------------
define('SESION_MINUTOS_EXPIRACION', 30);
define('SESION_NOMBRE', 'ESSLAVADERO');

// ---------------------------------------------------------------
// Datos regionales: Colombia
// ---------------------------------------------------------------
date_default_timezone_set('America/Bogota');
define('WHATSAPP_PREFIJO', '57');                     // indicativo de Colombia (RF-10)

// ---------------------------------------------------------------
// Metas del negocio para el tablero (RF-06)
// ---------------------------------------------------------------
define('META_TIEMPO_ATENCION_MIN', 60);               // minutos de "en espera" a "terminado"

// ---------------------------------------------------------------
// Ruta del mysqldump de XAMPP para el respaldo (RNF-06)
// ---------------------------------------------------------------
define('MYSQLDUMP_PATH', 'C:\\xampp\\mysql\\bin\\mysqldump.exe');

// ---------------------------------------------------------------
// Manejo de errores según el entorno
// ---------------------------------------------------------------
if (APP_ENTORNO === 'desarrollo') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}
