# ESS-Lavadero

Sistema de información web para un lavadero de carros y motos: servicios, caja diaria,
insumos, equipos y tablero gerencial con indicadores.

**Tecnología:** PHP 8 (MVC propio), MySQL/MariaDB (PDO), Bootstrap 5, JavaScript, Chart.js,
Dompdf y PhpSpreadsheet (Composer).

> Este README se completa en la Fase 8. Por ahora explica cómo dejar lista la base de datos.

## Instalación (Fase 1)

1. Copie el proyecto en `C:\xampp\htdocs\lavaautos` y encienda **Apache** y **MySQL** en XAMPP.
2. Cree la base de datos y cargue los datos de prueba:
   - **phpMyAdmin:** Importar `database/schema.sql` y luego `database/seed.sql`, o
   - **Consola:**
     ```
     C:\xampp\mysql\bin\mysql -u root < database\schema.sql
     C:\xampp\mysql\bin\mysql -u root < database\seed.sql
     ```
3. Abra <http://localhost/lavaautos/>: debe ver "Conexión PDO correcta" y los registros por tabla.

Si usa otra carpeta, cambie `BASE_URL` en `config/config.php`. Las credenciales de MySQL están en
`config/database.php`.

## Usuarios de prueba

Contraseña de todos: `Lavadero2026*`

| Usuario    | Rol     |
|------------|---------|
| `dueno`    | Dueño   |
| `cajero`   | Cajero  |
| `lavador`  | Lavador |
| `lavador2` | Lavador |
| `lavador3` | Lavador |

## Estructura

```
config/       config.php (constantes) y database.php (conexión PDO)
app/          controllers/, models/, views/ (layouts/ y una carpeta por módulo)
public/       css/, js/, img/
database/     schema.sql (estructura) y seed.sql (datos de prueba)
backups/      respaldos generados con mysqldump
index.php     front controller
```
