<?php
/**
 * Created by PhpStorm.
 * User: pkovalchuk
 * Date: 06.09.2017
 * Time: 16:44
 */

require_once 'PHPExcel.php';

// Подключаем класс для вывода данных в формате excel
require_once('PHPExcel/Writer/Excel5.php');


abstract class ExcelPrivatbank
{
     protected static $log_file_path = 'backup/privatbank_log_status.log';

    protected $sinceDate;

    protected $toDate;

    public function __construct(\DateTime $sinceDate, \DateTime $toDate)
    {
        $this->setSinceDate($sinceDate);

        $this->setToDate($toDate);

    }

    public function setSinceDate($sinceDate)
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

    public function setToDate($toDate)
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

    public static function getFormattedDate($date){
        if($date instanceof DateTime ){
            return $date->format('d.m.Y');
        }
        return false;
    }


    /**
     * @param $data SimpleXMLElement
     * @param $sinceDate  DateTime
     * @param $toDate DateTime
     * @return excel file for download
     */
    public static function getHistoryExcelFile($data, DateTime $sinceDate, DateTime $toDate)
    {

        // Создаем объект класса PHPExcel
        $xls = new PHPExcel();

        $locale = 'ru';
        $validLocale = PHPExcel_Settings::setLocale($locale);
        if (!$validLocale) {
            return 'Unable to set locale to '.$locale." - reverting to en_us<br />\n";
        }
        // Устанавливаем индекс активного листа
        $xls->setActiveSheetIndex(0);
        // Получаем активный лист
        $sheet = $xls->getActiveSheet();


        // Подписываем лист
        $sheet->setTitle('Выписка');


        // Вставляем текст в ячейку A1
        $sheet->setCellValue("A1", ' Выписка по вашим картам за период ' . self::getFormattedDate($sinceDate) . '-' . self::getFormattedDate($toDate));
        $sheet->getStyle('A1')->getFill()->setFillType(
            PHPExcel_Style_Fill::FILL_SOLID);
        $sheet->getStyle('A1')->getFill()->getStartColor()->setRGB('35b5b2');
        // Объединяем ячейки
        $sheet->mergeCells('A1:I1');


        //Header of column names
        $sheet->setCellValue("A2", 'Дата'); //trandate
        $sheet->setCellValue("B2", 'Время'); //trantime
        $sheet->setCellValue("C2", 'Детали (описание транзакции)'); //description
        $sheet->setCellValue("D2", 'Сумма транзакции'); //amount
        $sheet->setCellValue("E2", 'Валюта'); //amount
        $sheet->setCellValue("F2", 'Движение по карте в валюте карты'); //cardamount
        $sheet->setCellValue("G2", 'Валюта'); //cardamount
        $sheet->setCellValue("H2", 'Сумма остатка после транзакции'); //rest
        $sheet->setCellValue("I2", ' Валюта'); //rest

        $sheet->getRowDimension(2)->setRowHeight(50);


        //Autoresize column
        foreach(range('A','I') as $column_id){
            $sheet->getColumnDimension($column_id)->setAutoSize(true);


        }

        //Color of column names
        foreach (range('A', 'I') as $column_name){
            $sheet->getStyle($column_name . '2')->getFill()->setFillType(
                PHPExcel_Style_Fill::FILL_SOLID);
            $sheet->getStyle($column_name . '2')->getFill()->getStartColor()->setRGB('befcb2');
        }


        //Put data in cells

        $result = [];
        foreach ($data->statement as $statement) {
            $result[] = self::getParsedStatement($statement);
        }


        foreach($result as $key => $value)
        {
            $index = $key+3;
            $sheet->setCellValue("A".$index, $value['date']);
            $sheet->setCellValue("B".$index, $value['time']);
            $sheet->setCellValue("C".$index, $value['description']);
            $sheet->setCellValue("D".$index, $value['transaction_amount']);
            $sheet->setCellValue("E".$index, $value['transaction_currency']);
            $sheet->setCellValue("F".$index, $value['movement_amount']);
            $sheet->setCellValue("G".$index, $value['movement_currency']);
            $sheet->setCellValue("H".$index, $value['balance_amount']);
            $sheet->setCellValue("I".$index, $value['balance_currency']);

        }

        //Logging
        self::logToFile('OK', self::getFormattedDate($sinceDate), self::getFormattedDate($toDate));

        // Выводим содержимое файла
        self::downloadFile($xls);

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
        $sheet->setCellValue("A1", 'Во время загрузки данных произошли проблемы: ');
        $sheet->setCellValue("A3", (string)$message);

        //Logging
        self::logToFile('Error: ' . $message, self::getFormattedDate($sinceDate), self::getFormattedDate($toDate));

        // Выводим содержимое файла
        self::downloadFile($xls);
    }

    /**
     * Возвращает загружемый файл
     * @param $xls object PHPExcel
     */
    protected function downloadFile($xls)
    {
        $objWriter = PHPExcel_IOFactory::createWriter($xls, 'Excel5');

        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="Statements_' . date('Y-m-d__H-i-s') .'.xls"');
        header('Content-Transfer-Encoding: binary');
        header('Cache-Control: must-revalidate');

        ob_clean();
        flush();

        $objWriter->save('php://output');
        exit();
    }

    /**
     * Парсит ответ от Приватбанка, возвращает массив данных
     * @param SimpleXMLElement $statement
     * @return array
     */
    protected static function getParsedStatement(\SimpleXMLElement $statement)
    {
        list($transactionAmount, $transactionCurrency) = explode(' ', (string)$statement['amount']);
        list($movementAmount, $movementCurrency) = explode(' ', (string)$statement['cardamount']);
        list($balanceAmount, $balanceCurrency) = explode(' ', (string)$statement['rest']);

        $date = new DateTime((string)$statement['trandate']);

        $response = array(
            'date' => (string)$date->format('d.m.Y'),
            'time' => (string)$statement['trantime'],
            'description' => (string)$statement['description'],
            'transaction_amount' => floatval($transactionAmount),
            'transaction_currency' => (string)$transactionCurrency,
            'movement_amount' => floatval($movementAmount),
            'movement_currency' => (string)$movementCurrency,
            'balance_amount' => floatval($balanceAmount),
            'balance_currency' => (string)$balanceCurrency,
        );

        return $response;

    }

    /**
     * Запись логов в файл
     * @param $msg
     */
    public function logToFile($msg, $sinceDateFormatted = false, $toDateFormatted = false)
   {

       // open file
       $fd = fopen(DIR_FS_CATALOG . self::$log_file_path, "a");
       // append date/time to message
       $str_date = "[" . date("Y/m/d H:i:s", mktime()) . "] ";
       $str_user = " by user " . "[id: ". $_SESSION['user_id'] . "] "  . "[" . $_SESSION['user_first_name'] . " "
           . $_SESSION['user_last_name'] . " " . $_SESSION['user_grade'] . "]: ";

       $str_request = '';

       if($sinceDateFormatted && $toDateFormatted){
           $str_request = " [requested period: " . $sinceDateFormatted . " - " . $toDateFormatted . "] ";
       }


       $str =   $str_date . $str_user . $str_request . $msg;
       // write string
       fwrite($fd, $str . "\n");
       // close file
       fclose($fd);
       return true;
   }



}