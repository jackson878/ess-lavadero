<?php
/**
 * Exportador: genera los reportes en PDF (Dompdf) y en Excel (PhpSpreadsheet) (RF-08).
 *
 * Los controladores le pasan datos ya consultados; esta clase solo se
 * encarga del formato del archivo y de enviarlo al navegador para descargar.
 */

use Dompdf\Dompdf;
use Dompdf\Options;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class Exportador
{
    /** Formato de pesos colombianos en Excel: $ 25.000 */
    private const FORMATO_PESOS = '"$ "#,##0';

    /**
     * Convierte una vista HTML de app/views/reportes/pdf en un PDF y lo descarga.
     *
     * @param string $vista   ej. 'reportes/pdf/ventas'
     * @param array  $datos   variables para la vista
     * @param string $archivo nombre del archivo sin extensión
     */
    public static function pdf(string $vista, array $datos, string $archivo, string $orientacion = 'portrait'): void
    {
        // 1. Se arma el HTML con la plantilla del reporte
        extract($datos, EXTR_SKIP);
        ob_start();
        require VIEWS_PATH . $vista . '.php';
        $html = ob_get_clean();

        // 2. Dompdf lo convierte en PDF (sin acceso a archivos remotos, por seguridad)
        $opciones = new Options();
        $opciones->set('isRemoteEnabled', false);
        $opciones->set('defaultFont', 'DejaVu Sans');   // soporta tildes y ñ

        $pdf = new Dompdf($opciones);
        $pdf->loadHtml($html, 'UTF-8');
        $pdf->setPaper('letter', $orientacion);
        $pdf->render();

        // 3. Número de página al pie de cada hoja
        $lienzo = $pdf->getCanvas();
        $fuente = $pdf->getFontMetrics()->getFont('DejaVu Sans');
        $lienzo->page_text($lienzo->get_width() - 120, $lienzo->get_height() - 30,
            'Página {PAGE_NUM} de {PAGE_COUNT}', $fuente, 8, [0.4, 0.45, 0.5]);

        $pdf->stream(self::nombreSeguro($archivo) . '.pdf', ['Attachment' => true]);
        exit;
    }

    /**
     * Crea un libro de Excel con una o varias hojas y lo descarga.
     *
     * Cada hoja es un arreglo:
     *   [
     *     'nombre'      => 'Ventas',
     *     'titulo'      => 'Ventas del 01/11/2026 al 08/11/2026',
     *     'encabezados' => ['Fecha', 'Placa', 'Valor'],
     *     'filas'       => [[...], [...]],
     *     'pesos'       => [2],              // índices de columnas con formato $
     *     'totales'     => ['', 'Total', 125000],   // opcional
     *   ]
     */
    public static function excel(array $hojas, string $archivo): void
    {
        $libro = new Spreadsheet();
        $libro->getProperties()->setCreator(APP_NOMBRE)->setTitle($hojas[0]['titulo'] ?? APP_NOMBRE);
        $libro->removeSheetByIndex(0);

        foreach ($hojas as $h) {
            $hoja = $libro->createSheet();
            $hoja->setTitle(mb_substr($h['nombre'], 0, 31));   // Excel permite máximo 31 caracteres
            $columnas = count($h['encabezados']);
            $ultima   = self::letra($columnas);

            // Encabezado del reporte: negocio, título y fecha de generación
            $hoja->setCellValue('A1', NEGOCIO_NOMBRE . ' · ' . APP_NOMBRE);
            $hoja->setCellValue('A2', $h['titulo']);
            $hoja->setCellValue('A3', 'Generado el ' . date('d/m/Y H:i'));
            $hoja->getStyle('A1')->getFont()->setBold(true)->setSize(14)->getColor()->setRGB('0E2A3A');
            $hoja->getStyle('A2')->getFont()->setBold(true)->setSize(12);
            $hoja->getStyle('A3')->getFont()->setItalic(true)->getColor()->setRGB('5F6F78');

            // Fila de encabezados de la tabla (azul petróleo con letra blanca)
            $fila = 5;
            $hoja->fromArray($h['encabezados'], null, 'A' . $fila);
            $hoja->getStyle("A{$fila}:{$ultima}{$fila}")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0E2A3A']],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
            ]);

            // Datos
            $inicio = $fila + 1;
            if ($h['filas']) {
                $hoja->fromArray($h['filas'], null, 'A' . $inicio, true);
            }
            $fin = $inicio + max(count($h['filas']), 1) - 1;

            // Fila de totales
            if (!empty($h['totales'])) {
                $fin++;
                $hoja->fromArray($h['totales'], null, 'A' . $fin, true);
                $hoja->getStyle("A{$fin}:{$ultima}{$fin}")->applyFromArray([
                    'font'    => ['bold' => true],
                    'borders' => ['top' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '0E2A3A']]],
                ]);
            }

            // Formato de pesos en las columnas indicadas
            foreach ($h['pesos'] ?? [] as $indice) {
                $col = self::letra($indice + 1);
                $hoja->getStyle("{$col}{$inicio}:{$col}{$fin}")->getNumberFormat()->setFormatCode(self::FORMATO_PESOS);
            }

            // Bordes suaves, ancho automático y encabezado fijo al desplazarse
            $hoja->getStyle("A{$fila}:{$ultima}{$fin}")->getBorders()->getAllBorders()
                 ->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('DFE6E9');
            for ($i = 1; $i <= $columnas; $i++) {
                $hoja->getColumnDimension(self::letra($i))->setAutoSize(true);
            }
            $hoja->freezePane('A' . $inicio);
        }

        $libro->setActiveSheetIndex(0);

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . self::nombreSeguro($archivo) . '.xlsx"');
        header('Cache-Control: max-age=0');
        (new Xlsx($libro))->save('php://output');
        exit;
    }

    /** Letra de la columna de Excel: 1 -> A, 2 -> B ... */
    private static function letra(int $numero): string
    {
        return \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($numero);
    }

    /** Nombre de archivo sin caracteres raros: "Cierre 08/11" -> "Cierre_08-11" */
    private static function nombreSeguro(string $nombre): string
    {
        $nombre = strtr($nombre, ['/' => '-', ' ' => '_']);
        return preg_replace('/[^A-Za-z0-9_\-]/', '', iconv('UTF-8', 'ASCII//TRANSLIT', $nombre)) ?: 'reporte';
    }
}
