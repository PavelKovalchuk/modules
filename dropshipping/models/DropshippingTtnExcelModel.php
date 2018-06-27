<?php
/**
 * Created by PhpStorm.
 * User: pkovalchuk
 * Date: 16.02.2018
 * Time: 14:23
 */


require_once 'PHPExcel.php';
// Подключаем класс для вывода данных в формате excel
require_once('PHPExcel/Writer/Excel5.php');
require_once(DROPSHIPPING_ROOT_DIR . 'DropshippingTrait.php');


class DropshippingTtnExcelModel
{
    use DropshippingTrait;

    protected $sheet;

    protected $activeCells;

    protected $locale = 'ru';

    protected $startColumn;

    protected $endColumn;

    protected $sheetTitle = 'ТТН';

    public function generateFile($data_to_write)
    {
        $this->setExcelLocale();

        if(!$this->checkForArray($data_to_write)){

            $this->getErrorMessageExcelFile( 'Не совершена генерация данных для Excel файла!' );
            return false;
        }

        $start_column = $this->getStartColumn();
        $end_column = $this->getEndColumn();

        if(empty($start_column) || empty($end_column)){
            $this->getErrorMessageExcelFile( 'Не заданы координаты колонок для Excel файла!' );
            return false;
        }

        if($this->isError()){

            $this->getErrorMessageExcelFile( strip_tags( $this->createErrorsMessage() ) );
            return false;

        }

        // Создаем объект класса PHPExcel
        $xls = new PHPExcel();
        // Устанавливаем индекс активного листа
        $xls->setActiveSheetIndex(0);
        // Получаем активный лист
        $sheet = $xls->getActiveSheet();

        // Подписываем лист
        $sheet->setTitle( $this->getSheetTitle() );

        //Ячейки для записи данных
        $active_cells = $this->getStartColumn() . "1:" . $this->getEndColumn() . count($data_to_write);

        $this
            ->setActiveCells($active_cells)
            //Put data in cells
            ->setCellsData($sheet, $data_to_write )
            //Autoheight of row
            ->applyHeight($sheet)
            //Autoresize column
            ->applySize($sheet)
            //Styles
            ->applyStyles($sheet)
            // Выводим содержимое файла
            ->downloadFile($xls);

    }

    /**
     * Возвращает загружемый файл
     * @param $xls object PHPExcel
     */
    public function downloadFile($xls)
    {
        $objWriter = PHPExcel_IOFactory::createWriter($xls, 'Excel5');

        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="ТТН_' . date('Y-m-d__H-i-s') .'.xls"');
        header('Content-Transfer-Encoding: binary');
        header('Cache-Control: must-revalidate');

        ob_clean();
        flush();

        $objWriter->save('php://output');
        exit();
    }

    /**
     * Возвращает excel файл с сообщением об ошибке
     * @param $message string
     */
    public function getErrorMessageExcelFile($message)
    {
        $xls = new PHPExcel();

        // Устанавливаем индекс активного листа
        $xls->setActiveSheetIndex(0);
        // Получаем активный лист
        $sheet = $xls->getActiveSheet();

        // Подписываем лист
        $sheet->setTitle('Errors');

        // Вставляем текст в ячейку A1
        $sheet->setCellValue("A1", 'Во время загрузки данных произошли проблемы: ');
        $sheet->setCellValue("A3", (string)$message);

        // Выводим содержимое файла
        $this->downloadFile($xls);
    }

    /**
     * @param string $startColumn
     */
    public function setStartColumn($startColumn)
    {
        if(!$startColumn){
            return false;
        }

        $this->startColumn = $startColumn;

        return $this;
    }

    /**
     * @param string $endColumn
     */
    public function setEndColumn($endColumn)
    {
        if(!$endColumn){
            return false;
        }

        $this->endColumn = $endColumn;

        return $this;
    }

    /**
     * @param string $sheetTitle
     */
    public function setSheetTitle($sheetTitle)
    {
        if(!$sheetTitle){
            return false;
        }

        $this->sheetTitle = $sheetTitle;

        return $this;
    }

    protected function setCellsData(PHPExcel_Worksheet $sheet, $data_to_write)
    {
        if(!$sheet instanceof PHPExcel_Worksheet || !$this->checkForArray($data_to_write)){
            return false;
        }

        $index = 0;
        foreach($data_to_write as $key => $data)
        {
            $index ++;

            if(empty($data['header']) && empty($data['value'])){
                $sheet->mergeCells($this->getStartColumn() . $index.":".$this->getEndColumn() . $index);
                continue;
            }

            $sheet->setCellValue($this->getStartColumn() . $index, $data['header']);
            $sheet->setCellValueExplicit($this->getEndColumn() . $index, $data['value'], PHPExcel_Cell_DataType::TYPE_STRING );

        }

        return $this;

    }

    protected function applyHeight(PHPExcel_Worksheet $sheet)
    {
        if(!$sheet instanceof PHPExcel_Worksheet ){
            return false;
        }

        $sheet->getStyle( $this->getActiveCells() )->getAlignment()->setWrapText(true);

        return $this;
    }

    protected function applySize(PHPExcel_Worksheet $sheet)
    {
        if(!$sheet instanceof PHPExcel_Worksheet ){
            return false;
        }

        foreach(range($this->getStartColumn(), $this->getEndColumn()) as $column_id){
            $sheet->getColumnDimension($column_id)->setAutoSize(true);

        }

        return $this;
    }

    protected function applyStyles(PHPExcel_Worksheet $sheet)
    {
        if(!$sheet instanceof PHPExcel_Worksheet){
            return false;
        }

        $styleArray = array(
            'borders' => array(
                'allborders' => array(
                    'style' => PHPExcel_Style_Border::BORDER_THICK,
                    'color' => array('argb' => '000000'),
                ),
            ),

            'alignment' => array(
                'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER,
                'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_LEFT,
            ),

        );

        $sheet->getStyle( $this->getActiveCells() )->applyFromArray($styleArray);

        return $this;
    }

    protected function setExcelLocale()
    {

        $validLocale = PHPExcel_Settings::setLocale( $this->getLocale() );
        if (!$validLocale) {
            $this->addErrorsMessages("Unable to set locale to ". $this->getLocale() . " - reverting to en_us.");
            return false;
        }

        return true;
    }

    /**
     * @return string
     */
    protected function getLocale()
    {
        return $this->locale;
    }

    /**
     * @return string
     */
    protected function getStartColumn()
    {
        return $this->startColumn;
    }

    /**
     * @return string
     */
    protected function getEndColumn()
    {
        return $this->endColumn;
    }

    /**
     * @return string
     */
    protected function getSheetTitle()
    {
        return $this->sheetTitle;
    }

    /**
     * @return mixed
     */
    protected function getSheet()
    {
        return $this->sheet;
    }

    /**
     * @param mixed $sheet
     */
    protected function setSheet(PHPExcel_Worksheet $sheet)
    {
        if(!$sheet instanceof PHPExcel_Worksheet ){
            return false;
        }

        $this->sheet = $sheet;
    }

    /**
     * @return mixed
     */
    protected function getActiveCells()
    {
        return $this->activeCells;
    }

    /**
     * @param mixed $activeCells
     */
    protected function setActiveCells($activeCells)
    {
        $this->activeCells = $activeCells;

        return $this;
    }

}