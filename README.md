# ESS-Lavadero

Sistema de información web para un lavadero de carros y motos. Registra los servicios, la caja
diaria, los insumos y los equipos, y le da al dueño un tablero gerencial con indicadores.

Proyecto universitario de Sistemas de Información.

| | |
|---|---|
| **Lenguaje** | PHP 8 con arquitectura MVC propia (sin framework) |
| **Base de datos** | MySQL / MariaDB (XAMPP), conexión PDO con consultas preparadas |
| **Interfaz** | HTML5, Bootstrap 5, JavaScript puro, Chart.js |
| **Reportes** | Dompdf (PDF) y PhpSpreadsheet (Excel), instalados con Composer |
| **Control de versiones** | Git |

---

## 1. Instalación

### Requisitos
- **XAMPP** con PHP 8.1 o superior y MariaDB/MySQL. Probado con PHP 8.1.25 y MariaDB 10.4.32.
- **Extensiones de PHP**: `pdo_mysql`, `mbstring`, `gd`, `zip`, `xml`, `dom`, `intl`. Vienen activas en XAMPP.
- **Composer**, para las librerías de reportes. Si no está instalado, use el que trae el proyecto:
  `php tools/composer.phar`.
- Conexión a internet en el navegador: Bootstrap, Chart.js y la fuente se cargan desde CDN.

### Pasos
1. **Copiar el proyecto** en `C:\xampp\htdocs\lavaautos`.
2. **Encender Apache y MySQL** en el panel de XAMPP.
3. **Crear la base de datos** con los datos de prueba:
   ```
   C:\xampp\mysql\bin\mysql -u root < database\schema.sql
   C:\xampp\mysql\bin\mysql -u root < database\seed.sql
   ```
   También puede hacerlo desde phpMyAdmin: *Importar* `schema.sql` y luego `seed.sql`.
   > `schema.sql` **borra y vuelve a crear** la base `ess_lavadero`.
4. **Instalar las librerías de reportes** (en la carpeta del proyecto):
   ```
   composer install
   ```
   Si no tiene Composer: `C:\xampp\php\php.exe tools\composer.phar install`
5. **Abrir** <http://localhost/lavaautos/>.

### Configuración
| Archivo | Qué se cambia |
|---|---|
| `config/config.php` | `NEGOCIO_NOMBRE`, `BASE_URL` (si usa otra carpeta), minutos de sesión, meta de tiempo de atención, ruta de `mysqldump` |
| `config/database.php` | Servidor, usuario y contraseña de MySQL |

### Datos de prueba
`seed.sql` crea 5 usuarios, 10 clientes, 15 vehículos, 8 servicios, 60 órdenes de los últimos
30 días con sus pagos y cierres de caja, 8 insumos (3 bajo el mínimo) y 5 equipos (1 con
mantenimiento vencido).

Las fechas son **relativas al día de importación**: si el tablero aparece vacío porque pasaron
varios días, vuelva a importar `seed.sql`.

## 2. Usuarios de prueba

Contraseña de todos: **`Lavadero2026*`**

| Usuario | Rol | Al entrar ve |
|---|---|---|
| `dueno` | Dueño | Tablero gerencial |
| `cajero` | Cajero | Servicios (vehículos en el lavadero) |
| `lavador` | Lavador (Carlos) | Mi cola |
| `lavador2`, `lavador3` | Lavador | Mi cola (sirven para el KPI "servicios por lavador") |

## 3. Módulos y permisos por rol

| Módulo | Ruta | Dueño | Cajero | Lavador |
|---|---|:-:|:-:|:-:|
| Tablero gerencial (8 KPI) | `?c=tablero` | ✔ | | |
| Servicios / órdenes | `?c=orden` | ✔ | ✔ | |
| Caja diaria | `?c=caja` | ✔ | ✔ | |
| Clientes y vehículos | `?c=cliente`, `?c=vehiculo` | ✔ | ✔ | |
| Catálogo de servicios | `?c=catalogo` | ✔ | | |
| Insumos | `?c=insumo` | ✔ | | |
| Equipos y mantenimiento | `?c=equipo` | ✔ | | |
| Reportes y respaldos | `?c=reporte` | ✔ | | |
| Usuarios | `?c=usuario` | ✔ | | |
| Mi cola (celular) | `?c=lavador` | | | ✔ |

El permiso se valida **en el servidor**, en el constructor de cada controlador
(`$this->requiereRol('dueno', 'cajero')`). Si alguien escribe la URL a mano, recibe
*403 Acceso denegado*; ocultar el botón del menú es solo una ayuda visual.

## 4. Arquitectura

### Estructura de carpetas
```
lavaautos/
├── index.php              Front controller: único punto de entrada
├── config/                config.php (constantes) y database.php (conexión PDO)
├── app/
│   ├── core/              Controller (base), Sesion, Csrf, Exportador (PDF/Excel)
│   ├── controllers/       Un controlador por módulo (UsuarioController, OrdenController…)
│   ├── models/            Consultas SQL y reglas de negocio (Orden, Caja, Insumo…)
│   ├── helpers/           funciones.php: formato de pesos, fechas, placas, insignias…
│   └── views/             layouts/ + una carpeta por módulo + reportes/pdf/
├── public/                css/estilos.css, js/ (app, placa, orden, caja, tablero)
├── database/              schema.sql, seed.sql, respaldo.bat
├── docs/                  CASOS_DE_PRUEBA.md
├── backups/               Respaldos generados (no se suben a Git)
├── vendor/                Librerías de Composer (no se suben a Git)
└── tools/                 composer.phar local (no se sube a Git)
```

### Cómo se atiende una petición
```
Navegador ──► index.php?c=orden&a=crear
                │ 1. Carga configuración, conexión, funciones y autocargador
                │ 2. Sesion::iniciar()      → cookie segura y expiración por inactividad
                │ 3. Si es POST: Csrf::esValido()  → si falla, 403
                │ 4. Valida c y a (solo letras) y que la acción sea un método público
                │    escrito en ese controlador
                ▼
         OrdenController::__construct()  → requiereRol('dueno', 'cajero')
         OrdenController::crear()        → valida datos y usa los modelos
                ▼
         Modelos (Orden, Vehiculo, Cliente…)  → PDO con consultas preparadas
                ▼
         Vista app/views/ordenes/crear.php    → HTML escapado con e()
```

### Base de datos
13 tablas en `database/schema.sql`, con claves foráneas e índices:
`usuarios`, `clientes`, `vehiculos`, `servicios_catalogo`, `ordenes`, `orden_detalle`,
`orden_estados` (historial con fecha y hora), `pagos`, `caja_aperturas_cierres`, `insumos`,
`movimientos_insumo`, `equipos`, `mantenimientos`.

Reglas importantes del modelo:
- `orden_detalle.precio` guarda el precio del momento: cambiar el catálogo no altera ventas pasadas.
- `pagos.orden_id` es único: una orden se cobra una sola vez.
- `caja_aperturas_cierres.fecha` es única: una caja por día.
- `insumos.stock_actual` solo cambia con movimientos, así que siempre cuadra con el historial.

## 5. Requisitos cubiertos

| Requisito | Dónde está |
|---|---|
| RF-01 Autenticación y usuarios | `AuthController`, `UsuarioController`, `app/core/Sesion.php` |
| RF-02 Clientes y vehículos, búsqueda por placa | `ClienteController`, `VehiculoController` (`a=buscar` devuelve JSON), `public/js/placa.js` |
| RF-03 Catálogo de servicios | `CatalogoController` |
| RF-04 Órdenes con estados e historial | `OrdenController`, `LavadorController`, `models/Orden.php` |
| RF-05 Caja: apertura, cobro y cierre | `CajaController`, `models/Caja.php`, `models/Pago.php` |
| RF-06 Tablero con 8 KPI y filtro de fechas | `TableroController`, `models/Tablero.php`, `public/js/tablero.js` |
| RF-07 Insumos con stock mínimo | `InsumoController`, `models/Insumo.php` |
| RF-08 Reportes PDF y Excel | `ReporteController`, `app/core/Exportador.php`, `views/reportes/pdf/` |
| RF-09 Equipos y mantenimiento | `EquipoController`, `models/Equipo.php` |
| RF-10 Aviso por WhatsApp | `whatsapp_url()` en `funciones.php`; botón en Servicios y en el detalle de la orden |
| RNF-01 Respuesta en menos de 2 s | Consultas por rango con índices; el tablero responde en unos 0,05 s con los datos de prueba |
| RNF-02 Seguridad | Ver la sección 6 |
| RNF-03 Responsive | Bootstrap 5; menú desplegable en celular; vista del lavador con botones grandes |
| RNF-04 Registrar un servicio en menos de 1 min y 3 clics | `ordenes/crear.php`: placa con autocompletado, clic en el servicio y clic en Registrar (lavador sugerido) |
| RNF-05 Permisos estrictos | `requiereRol()` en cada controlador |
| RNF-06 Respaldo | Botón en *Reportes* y script `database/respaldo.bat` |

## 6. Seguridad (RNF-02)

| Medida | Implementación |
|---|---|
| Inyección SQL | Solo consultas preparadas con PDO (`ATTR_EMULATE_PREPARES = false`) |
| Contraseñas | `password_hash` / `password_verify` (bcrypt); se vuelven a cifrar si cambia el costo |
| XSS | Toda salida pasa por `e()` = `htmlspecialchars`; en JavaScript se usa `textContent` |
| CSRF | Token por sesión en cada formulario POST, validado en `index.php` con `hash_equals` |
| Sesión | Cookie HttpOnly y SameSite=Lax, modo estricto, `session_regenerate_id` al iniciar sesión, expiración por inactividad (30 min) |
| Fuerza bruta | Bloqueo de 60 s después de 5 intentos fallidos; mensaje genérico de error |
| Usuario desactivado | Se valida en cada petición; sale del sistema de inmediato |
| Rutas | El enrutador solo acepta letras y métodos públicos del controlador; nunca rutas de archivos |
| Carpetas internas | `.htaccess` con `Require all denied` en `app/`, `config/`, `database/`, `backups/`, `vendor/`, `tools/`; sin listado de directorios |
| Respaldos | Solo el Dueño, por POST con CSRF; `mysqldump` con argumentos escapados; descarga con lista blanca de nombres |
| Concurrencia | `SELECT … FOR UPDATE` al cambiar estados, cobrar y mover inventario; transacciones al guardar varias tablas |

## 7. Respaldo y restauración (RNF-06)

- **Desde el sistema**: *Reportes → Crear respaldo ahora*. El archivo queda en `backups/` y se
  puede descargar desde la misma pantalla.
- **Desde Windows**: doble clic en `database\respaldo.bat`. Para hacerlo todos los días:
  *Programador de tareas → Crear tarea básica → Diaria → Iniciar un programa → respaldo.bat*.
- **Restaurar**:
  ```
  C:\xampp\mysql\bin\mysql -u root < backups\ess_lavadero_AAAA-MM-DD_HHMMSS.sql
  ```

Guarde copias fuera del computador (USB o la nube): un respaldo en el mismo disco no protege
contra un daño del equipo.

## 8. Pruebas

La lista de casos de prueba, con pasos y resultado esperado por requisito, está en
[`docs/CASOS_DE_PRUEBA.md`](docs/CASOS_DE_PRUEBA.md).

## 9. Solución de problemas

| Problema | Solución |
|---|---|
| "No se pudo conectar a la base de datos" | Encienda MySQL en XAMPP y revise `config/database.php` |
| El tablero sale vacío | Las fechas de prueba son relativas: vuelva a importar `database/seed.sql` |
| "Faltan las librerías de reportes" | Ejecute `composer install` en la carpeta del proyecto |
| "No se encontró mysqldump" | Ajuste `MYSQLDUMP_PATH` en `config/config.php` |
| Estilos o gráficas no cargan | Revise la conexión a internet (Bootstrap y Chart.js vienen de CDN) |
| La página se ve sin estilos en otra carpeta | Cambie `BASE_URL` en `config/config.php` |
| Ver el sistema desde el celular | Misma red Wi-Fi; abra `http://IP-DEL-PC/lavaautos/` y permita Apache en el firewall de Windows |
