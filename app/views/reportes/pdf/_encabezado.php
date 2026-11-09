<?php
/**
 * Fragmento común de los PDF: estilos y encabezado con el nombre del negocio.
 * Dompdf entiende CSS 2.1 básico; por eso se usan tablas en vez de flexbox.
 *
 * @var string $titulo
 */
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title><?= e($titulo) ?></title>
<style>
    @page { margin: 28px 32px 48px; }
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 10px; color: #12222C; }
    .encabezado { width: 100%; border-bottom: 3px solid #0E2A3A; padding-bottom: 8px; margin-bottom: 14px; }
    .encabezado td { vertical-align: bottom; }
    .negocio { font-size: 15px; font-weight: bold; color: #0E2A3A; }
    .sistema { font-size: 9px; color: #5F6F78; }
    .acento { color: #C98A12; }
    .titulo { font-size: 13px; font-weight: bold; text-align: right; }
    .generado { font-size: 8px; color: #5F6F78; text-align: right; }
    h2 { font-size: 11px; color: #0E2A3A; margin: 16px 0 6px; text-transform: uppercase; letter-spacing: 1px; }
    table.datos { width: 100%; border-collapse: collapse; }
    table.datos th { background: #0E2A3A; color: #fff; font-size: 8.5px; text-align: left; padding: 5px 6px; }
    table.datos td { padding: 4px 6px; border-bottom: 1px solid #DFE6E9; }
    table.datos tr:nth-child(even) td { background: #F6F8F9; }
    table.datos tr.total td { font-weight: bold; border-top: 2px solid #0E2A3A; background: #fff; }
    .der, table.datos th.der, table.datos td.der { text-align: right; }
    .centro, table.datos td.centro { text-align: center; }
    .tarjetas { width: 100%; border-collapse: separate; border-spacing: 6px 0; margin: 0 -6px; }
    .tarjetas td { border: 1px solid #DFE6E9; border-radius: 6px; padding: 8px 10px; width: 25%; }
    .etiqueta { font-size: 8px; color: #5F6F78; text-transform: uppercase; }
    .valor { font-size: 15px; font-weight: bold; margin-top: 2px; }
    .rojo { color: #B42318; }
    .verde { color: #1E7A4C; }
    .nota { font-size: 8.5px; color: #5F6F78; margin-top: 8px; }
    .firma { width: 100%; margin-top: 48px; }
    .firma td { width: 50%; text-align: center; padding: 0 30px; }
    .linea { border-top: 1px solid #12222C; padding-top: 4px; font-size: 9px; }
</style>
</head>
<body>
<table class="encabezado">
    <tr>
        <td>
            <div class="negocio"><?= e(NEGOCIO_NOMBRE) ?></div>
            <div class="sistema"><span class="acento">■</span> <?= e(APP_NOMBRE) ?> · Sistema de información</div>
        </td>
        <td>
            <div class="titulo"><?= e($titulo) ?></div>
            <div class="generado">Generado el <?= date('d/m/Y H:i') ?></div>
        </td>
    </tr>
</table>
