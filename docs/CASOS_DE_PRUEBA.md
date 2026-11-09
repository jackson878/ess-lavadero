# ESS-Lavadero — Casos de prueba

**Ambiente:** XAMPP (PHP 8.1.25, MariaDB 10.4.32), Chrome, base `ess_lavadero` recién cargada
con `schema.sql` y `seed.sql`.

**Usuarios:** `dueno`, `cajero`, `lavador`, `lavador2`, `lavador3`. Contraseña de todos: `Lavadero2026*`.

**Resultado obtenido:** todos los casos se ejecutaron durante el desarrollo, con pruebas
automáticas (peticiones HTTP más consultas a la base) y en el navegador. La última columna
registra el resultado de esa ejecución. Para la sustentación, repítalos a mano y marque el
resultado en una copia impresa.

---

## RF-01 Autenticación y usuarios

| ID | Caso | Pasos | Resultado esperado | Obtenido |
|---|---|---|---|---|
| CP-01 | Login correcto | Ingresar `dueno` / `Lavadero2026*` | Entra al Tablero con el mensaje "Bienvenido" | Cumple |
| CP-02 | Login incorrecto | Ingresar `dueno` / `malaclave` | "Usuario o contraseña incorrectos." No dice cuál de los dos falló | Cumple |
| CP-03 | Bloqueo por intentos | 5 contraseñas erradas seguidas | "Demasiados intentos fallidos. Espere 60 segundos." | Cumple |
| CP-04 | Página inicial por rol | Entrar como `cajero` y luego como `lavador` | El cajero llega a Servicios y el lavador a Mi cola | Cumple |
| CP-05 | Expiración de sesión | Poner `SESION_MINUTOS_EXPIRACION` en 1, esperar 1 min y hacer clic | Vuelve al login con "Su sesión se cerró por inactividad" | Cumple |
| CP-06 | Cerrar sesión | Botón "Cerrar sesión" | Vuelve al login; el botón Atrás no muestra datos | Cumple |
| CP-07 | Crear usuario | Usuarios → Nuevo, con datos válidos | Aparece en la lista y puede iniciar sesión | Cumple |
| CP-08 | Validaciones de usuario | Usuario repetido, contraseña de 3 caracteres, celular `123` | Cada campo se marca en rojo con su mensaje | Cumple |
| CP-09 | Desactivar usuario | Desactivar un usuario que tiene la sesión abierta | En su siguiente clic lo saca del sistema; ya no puede entrar | Cumple |
| CP-10 | El dueño no se bloquea a sí mismo | Intentar desactivarse o cambiarse el rol | No lo permite | Cumple |
| CP-11 | Contraseña cifrada | Revisar `usuarios.password_hash` en phpMyAdmin | Empieza por `$2y$` (bcrypt), nunca en texto plano | Cumple |

## RF-02 / RF-03 Clientes, vehículos y catálogo

| ID | Caso | Pasos | Resultado esperado | Obtenido |
|---|---|---|---|---|
| CP-12 | Consulta por placa | Vehículos → escribir `klm905` | Muestra "KLM 905 · Renault Logan · Juan Camilo Pérez" | Cumple |
| CP-13 | Placa no registrada | Escribir `ZZZ111` | "No está registrada" y enlace para registrarla | Cumple |
| CP-14 | Placa única | Registrar un vehículo con la placa `HNT482` | Aviso inmediato "ya está registrada" y error al guardar | Cumple |
| CP-15 | Formato de placa | Moto con placa `ABC123` | "La placa de una moto tiene 3 letras, 2 números y una letra" | Cumple |
| CP-16 | Vehículo con cliente nuevo | Registrar un vehículo eligiendo "Cliente nuevo" | Se crean los dos; si la placa es inválida no se crea ninguno (transacción) | Cumple |
| CP-17 | Integridad al eliminar | Eliminar un cliente con vehículos, o un vehículo con órdenes | No se permite y se explica el motivo | Cumple |
| CP-18 | Catálogo | Crear "Aspirado interior", carro, precio `$ 18.500` | Se guarda con precio 18500 | Cumple |
| CP-19 | Servicio duplicado | Crear "Polichado" para carro | "Ya existe Polichado para carro" | Cumple |
| CP-20 | Permiso del catálogo | Entrar como cajero a `?c=catalogo` | 403 Acceso denegado | Cumple |

## RF-04 / RF-10 Órdenes de servicio y WhatsApp

| ID | Caso | Pasos | Resultado esperado | Obtenido |
|---|---|---|---|---|
| CP-21 | Registro rápido (RNF-04) | Nuevo servicio → placa `EZQ158` → clic en "Lavado completo" → Registrar | Orden creada con 2 clics y en menos de 1 minuto; total $ 45.000 | Cumple |
| CP-22 | Total calculado en el servidor | Registrar dos servicios | Total = suma de los precios del catálogo | Cumple |
| CP-23 | Vehículo ya en el lavadero | Escribir la placa de un vehículo con orden activa | Aviso inmediato; no deja registrar | Cumple |
| CP-24 | Servicio de otro tipo | Enviar un servicio de moto para un carro | Rechazado | Cumple |
| CP-25 | Flujo del lavador | Como `lavador2`: Empezar lavado → Marcar terminado | Pasa a "en lavado" y luego a "terminado" | Cumple |
| CP-26 | Historial con hora | Ver el detalle de la orden | Cada cambio aparece con fecha, hora, usuario y minutos transcurridos | Cumple |
| CP-27 | Cola ajena | Un lavador intenta mover la orden de otro | "Esa orden no está en su cola" | Cumple |
| CP-28 | El lavador no entrega | Intentar avanzar una orden terminada | No se permite | Cumple |
| CP-29 | WhatsApp | Orden terminada → "Avisar por WhatsApp" | Abre `wa.me/57{celular}` con "Su vehículo {placa} está listo" | Cumple |
| CP-30 | Cancelar | Cancelar una orden en espera; intentar cancelar una terminada | La primera se cancela; la segunda no se permite | Cumple |
| CP-31 | Vista del lavador en celular (RNF-03) | Abrir Mi cola a 390 px de ancho | Tarjetas y botones grandes, sin desplazamiento horizontal | Cumple |

## RF-05 Caja diaria

| ID | Caso | Pasos | Resultado esperado | Obtenido |
|---|---|---|---|---|
| CP-32 | Entregar sin cobrar | Botón Entregar en una orden terminada sin pago | "Primero cobre la orden en la caja" | Cumple |
| CP-33 | Cobrar sin caja abierta | Intentar cobrar | Redirige a la caja para abrirla | Cumple |
| CP-34 | Apertura | Abrir con base `100.000` | Caja abierta; no se puede abrir otra el mismo día | Cumple |
| CP-35 | Cobro en efectivo | Cobrar con "Entregar ahora" y recibido `50.000` | Vueltas calculadas; pago registrado por el total real; orden entregada | Cumple |
| CP-36 | Cobro doble | Cobrar otra vez la misma orden | No se permite | Cumple |
| CP-37 | Nequi sin entregar | Cobrar por transferencia con referencia, sin entregar | Pago guardado; la orden sigue terminada y luego se puede entregar | Cumple |
| CP-38 | Cierre con diferencia | Contado = esperado − 2.000, sin observación | Exige explicar la diferencia | Cumple |
| CP-39 | Cierre correcto | Escribir la observación y cerrar | Guarda esperado, contado y diferencia de −2.000 (faltante) | Cumple |
| CP-40 | Caja cerrada | Intentar cobrar después del cierre | "La caja de hoy ya está cerrada" | Cumple |
| CP-41 | Caja anterior olvidada | Dejar abierta la caja de ayer e intentar abrir la de hoy | Pide cerrar primero la anterior | Cumple |
| CP-42 | El lavador no ve la caja | Lavador entra a `?c=caja` | 403 | Cumple |

## RF-06 Tablero gerencial

| ID | Caso | Pasos | Resultado esperado | Obtenido |
|---|---|---|---|---|
| CP-43 | KPI 1, 2, 3 y 7 | Tablero con 30 días; comparar con SQL en phpMyAdmin | Servicios 60, ingresos, ticket y tiempo iguales a la consulta directa | Cumple |
| CP-44 | KPI 4, 5, 6 y 8 | Revisar las gráficas y "Ver los datos en tabla" | Carro/moto, más vendidos, por lavador e insumos coinciden con la base | Cumple |
| CP-45 | Filtros | Atajos 7 días, Este mes, Mes anterior; rango libre | Las gráficas muestran el periodo pedido | Cumple |
| CP-46 | Agrupación por mes | Rango 01/01 al día de hoy | Barras por mes (ene … nov) | Cumple |
| CP-47 | Fechas inválidas | `?c=tablero&desde=x&hasta=2026-99-99` | Vuelve a los últimos 30 días sin error | Cumple |
| CP-48 | Tiempo de respuesta (RNF-01) | Medir la carga del tablero | Menos de 2 s (≈ 0,05 s con los datos de prueba) | Cumple |
| CP-49 | Solo el dueño | Cajero entra a `?c=tablero` | 403 | Cumple |

## RF-07 / RF-09 Insumos y equipos

| ID | Caso | Pasos | Resultado esperado | Obtenido |
|---|---|---|---|---|
| CP-50 | Alerta de stock | Abrir Insumos | Ambientador, Cera líquida y Toallas en rojo con "Bajo mínimo" | Cumple |
| CP-51 | Salida con decimales | Salida de `2,5` litros de shampoo | El stock baja de 12 a 9,5 | Cumple |
| CP-52 | Salida mayor al stock | Salida de 50 | "No hay suficiente stock" | Cumple |
| CP-53 | Stock coherente | Comparar el stock con la suma de los movimientos | Entradas − salidas = stock, en todos los insumos | Cumple |
| CP-54 | Alerta de mantenimiento | Abrir Equipos | Aspiradora "Vencido", compresor "En 5 días" | Cumple |
| CP-55 | Registrar mantenimiento | Mantenimiento de hoy con próxima fecha en 90 días | Se actualizan el último y el próximo mantenimiento; sale de la alerta | Cumple |
| CP-56 | Fechas de mantenimiento | Fecha futura, o próxima fecha igual a la fecha | Rechazadas | Cumple |

## RF-08 / RNF-06 Reportes y respaldo

| ID | Caso | Pasos | Resultado esperado | Obtenido |
|---|---|---|---|---|
| CP-57 | Ventas PDF / Excel | Reportes → Ventas, 30 días | PDF con resumen y detalle; Excel con 2 hojas, totales iguales a la base y formato $ | Cumple |
| CP-58 | Cierre de caja PDF / Excel | Reportes → Cierre de caja, ayer | Base, esperado, contado, diferencia, cobros y firmas | Cumple |
| CP-59 | Servicios por lavador | Reportes → Por lavador | Resumen por lavador y detalle por servicio | Cumple |
| CP-60 | Respaldo desde el sistema | Reportes → Crear respaldo ahora | Archivo `.sql` en `backups/` con las 13 tablas, descargable | Cumple |
| CP-61 | Respaldo por script | Ejecutar `database\respaldo.bat` | "[OK] Respaldo creado" | Cumple |
| CP-62 | Respaldos protegidos | Abrir `http://localhost/lavaautos/backups/` o el `.sql` directamente | 403; tampoco se puede descargar `../config/database.php` | Cumple |

## RNF-02 / RNF-05 Seguridad y permisos

| ID | Caso | Pasos | Resultado esperado | Obtenido |
|---|---|---|---|---|
| CP-63 | CSRF | Enviar un formulario POST sin token (por ejemplo, con `curl`) | 403 "Formulario vencido"; no se guarda nada | Cumple |
| CP-64 | XSS | Crear un cliente llamado `<script>alert(1)</script>` | Se muestra como texto; no se ejecuta | Cumple |
| CP-65 | Rutas manipuladas | `?c=../config`, `?c=usuario&a=requiereLogin` | 404 | Cumple |
| CP-66 | Inyección en filtros | `?c=orden&hasta=x' OR 1=1--` | La fecha se ignora y se usa la de hoy | Cumple |
| CP-67 | Carpetas internas | Abrir `/config/database.php`, `/app/`, `/vendor/` | 403 | Cumple |
| CP-68 | Id de sesión | Comparar la cookie antes y después del login | El id cambia (`session_regenerate_id`) | Cumple |
