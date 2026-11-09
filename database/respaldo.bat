@echo off
REM =====================================================================
REM  ESS-Lavadero - Respaldo de la base de datos (RNF-06)
REM
REM  Genera una copia completa de ess_lavadero en la carpeta backups\
REM  con el nombre ess_lavadero_AAAA-MM-DD_HHMMSS.sql
REM
REM  Uso:   doble clic, o desde la consola:  database\respaldo.bat
REM  Programado: Programador de tareas de Windows -> Crear tarea básica
REM              -> Diaria -> Iniciar un programa -> este archivo.
REM
REM  Para restaurar:  C:\xampp\mysql\bin\mysql -u root < backups\ARCHIVO.sql
REM =====================================================================

setlocal

set MYSQLDUMP=C:\xampp\mysql\bin\mysqldump.exe
set BASE=ess_lavadero
set USUARIO=root
set CARPETA=%~dp0..\backups

REM Fecha y hora sin depender de la configuración regional de Windows
for /f %%i in ('powershell -NoProfile -Command "Get-Date -Format yyyy-MM-dd_HHmmss"') do set FECHA=%%i

if not exist "%MYSQLDUMP%" (
    echo [ERROR] No se encontro mysqldump en %MYSQLDUMP%
    exit /b 1
)
if not exist "%CARPETA%" mkdir "%CARPETA%"

set ARCHIVO=%CARPETA%\%BASE%_%FECHA%.sql
echo Creando respaldo de %BASE% ...

"%MYSQLDUMP%" --user=%USUARIO% --default-character-set=utf8mb4 --single-transaction --routines --add-drop-table --databases %BASE% --result-file="%ARCHIVO%"

if errorlevel 1 (
    echo [ERROR] No se pudo crear el respaldo. Verifique que MySQL este encendido en XAMPP.
    if exist "%ARCHIVO%" del "%ARCHIVO%"
    exit /b 1
)

echo [OK] Respaldo creado: %ARCHIVO%
endlocal
