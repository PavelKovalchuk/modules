<?php
/**
 * Created by PhpStorm.
 * User: pkovalchuk
 * Date: 28.09.2017
 * Time: 17:37
 */

require_once DIR_FS_ADMIN . 'PHPExcel.php';
// Подключаем класс для вывода данных в формате excel
require_once(DIR_FS_ADMIN . 'PHPExcel/Writer/Excel5.php');

abstract class AnalyticsOrdersAbstract
{
    protected $sinceDate;

    protected $toDate;

    protected $sinceSeasonDate;

    protected $toSeasonDate;

    protected  function setSinceDate($sinceDate)
    {
        $this->sinceDate = $sinceDate;
    }

    public function getSinceDate()
    {
        if($this->sinceDate){
            return $this->sinceDate;
        }

        return false;

    }

    protected function setToDate($toDate)
    {
        $this->toDate = $toDate;
    }

    public function getToDate()
    {
        if($this->toDate){
            return $this->toDate;
        }

        return false;

    }

    protected function setSeasonSinceDate($sinceDate)
    {
         $this->sinceSeasonDate = $sinceDate;

    }

    protected function setSeasonToDate($toDate)
    {
        $this->toSeasonDate = $toDate;

    }

    public function getSeasonToDate()
    {
        if($this->toSeasonDate){
            return $this->toSeasonDate;
        }

        return false;

    }

    public function getSeasonSinceDate()
    {
        if($this->sinceSeasonDate){
            return $this->sinceSeasonDate;
        }

        return false;

    }




    public static function getFormattedDate($date){
        if($date instanceof DateTime ){
            return $date->format('d.m.Y');
        }
        return false;
    }

    /**
     * Возвращает excel файл с сообщением об ошибке
     * @param $message string
     */
    public static function getErrorMessageExcelFile($message, DateTime $sinceDate = null, DateTime $toDate = null)
    {
        $xls = new PHPExcel();

        // Устанавливаем индекс активного листа
        $xls->setActiveSheetIndex(0);
        // Получаем активный лист
        $sheet = $xls->getActiveSheet();

        // Подписываем лист
        $sheet->setTitle('Errors');

        // Вставляем текст в ячейку A1
        $title = 'Статистика по заказам за период: ' . self::getFormattedDate($sinceDate) . '-' . self::getFormattedDate($toDate);
        $sheet->setCellValue("A1", '' . 'Во время загрузки данных произошли проблемы: ');
        $sheet->setCellValue("A3", (string)$message);


        // Выводим содержимое файла
        self::downloadFile($xls);
    }

    /**
     * Возвращает загружемый файл
     * @param $xls object PHPExcel
     */
    protected static function downloadFile($xls)
    {
        $objWriter = PHPExcel_IOFactory::createWriter($xls, 'Excel5');

        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="Orders_analytics_' . date('Y-m-d__H-i-s') .'.xls"');
        header('Content-Transfer-Encoding: binary');
        header('Cache-Control: must-revalidate');

        ob_clean();
        flush();

        $objWriter->save('php://output');
        exit();
    }


}