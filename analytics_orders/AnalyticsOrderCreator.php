<?php
/**
 * Created by PhpStorm.
 * User: pkovalchuk
 * Date: 27.09.2017
 * Time: 11:28
 */
require_once(DIR_WS_CLASSES . "analytics_orders/AnalyticsOrdersAbstract.php");
require_once(DIR_WS_CLASSES . "analytics_orders/AnalyticsOrdersManager.php");

class AnalyticsOrderCreator extends AnalyticsOrdersAbstract
{


    protected $data_headers = array(
        'A' => 'Менеджер',

        'B' => 'Новые',
        'C' => 'Подтвержденные',
        'D' => 'Присоедененные',
        'E' => 'Разделенные',
        'F' => 'Отмененные заказы',
        'G' => 'Восст. Заказы',
        'H' => 'Заказы через телефон',
        'I' => 'Заказы по гарантии',
        'J' => 'Заказы через ПП',

        'K' => 'Новые',
        'L' => 'Подтвержденные',
        'M' => 'Отмененные заказы',
        'N' => 'Восст. Заказы',
        'O' => 'Заказы через телефон',
        'P' => 'Заказы по гарантии',
        'Q' => 'Заказы через ПП',

        'R' => 'Средний чек',
        'S' => 'Допродажи',


    );

    protected $analytics_manager;

    public function __construct(\DateTime $sinceDate, \DateTime $toDate, \DateTime $season_from, \DateTime $season_to)
    {
        if(!$sinceDate || !$toDate){
            self::getErrorMessageExcelFile('Неверные даты для отчета');
        }
        $this->setSinceDate($sinceDate);

        $this->setToDate($toDate);

        $this->setSeasonSinceDate($season_from);

        $this->setSeasonToDate($season_to);

        //Создаем обьект для получения статистики
        $this->analytics_manager = new AnalyticsOrdersManager($this->getSinceDate(), $this->getToDate(), $this->getSeasonSinceDate(), $this->getSeasonToDate());

        if($this->analytics_manager->checkError()){
            self::getErrorMessageExcelFile('Не удалось получить данные для отчета');
        }


    }


    /**
     *
     * @return excel file for download
     */
    public function getReportExcelFile( $season_name = false )
    {

        // Создаем объект класса PHPExcel
        $xls = new PHPExcel();


        $styleArray = array(
            'borders' => array(
                'allborders' => array(
                    'style' => PHPExcel_Style_Border::BORDER_THIN
                )
            )
        );
        $xls->getDefaultStyle()->applyFromArray($styleArray);

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
        $sheet->setTitle('Статистика по заказам');


        // Вставляем текст в ячейку A1 - Superheader

        $super_header = 'Статистика по заказам за период: ' . $this->getFormattedDate($this->getSinceDate()) . '-' . $this->getFormattedDate($this->getToDate());

        $sheet->setCellValue("A1", $super_header);
        $sheet->getStyle('A1')->getFill()->setFillType(
            PHPExcel_Style_Fill::FILL_SOLID);
        $sheet->getStyle('A1')->getFill()->getStartColor()->setRGB('8778b9');
        // Объединяем ячейки
        $sheet->mergeCells('A1:S1');
        $sheet->getStyle("A1")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A1:S1')->getFont()->setBold(true);

        //Subsuperheader - Количество, шт
        $sheet->setCellValue("B2", 'Количество, шт ');
        $sheet->mergeCells('B2:J2');
        $sheet->getStyle("B2")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('B2:J2')->getFont()->setBold(true);

        //Subsuperheader - Оборот, грн
        $sheet->setCellValue("K2", 'Оборот, грн');
        $sheet->mergeCells('K2:Q2');
        $sheet->getStyle("K2")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('K2:Q2')->getFont()->setBold(true);

        //Subsuperheader - Сумма, грн
        $sheet->setCellValue("R2", 'Сумма, грн');
        $sheet->mergeCells('R2:S2');
        $sheet->getStyle("R2")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('B2:S2')->getFont()->setBold(true);


        //Header of column names

        $start_line = 3;

        foreach ($this->data_headers as $key => $value){
            $sheet->setCellValue($key . $start_line, $value);
        }

        $sheet->getRowDimension($start_line)->setRowHeight(40);
        $sheet->getStyle('A' . $start_line . ':' . 'S' . $start_line )->getFont()->setBold(true);


        //Autoresize column
        foreach(range('A','S') as $column_id){
            $sheet->getColumnDimension($column_id)->setAutoSize(true);
        }


        //Get data from Database

        $data_report = $this->analytics_manager->getReportByManagers();


        //Put data in cells

        $line = $start_line;

        foreach($data_report as $manager_id => $data)
        {
            $line++;

            $sheet->setCellValue("A".$line, $data['manager_name']);

            $sheet->setCellValue("B".$line, $data['new_orders']);
            $sheet->setCellValue("C".$line, $data['confirmed_orders']);
            $sheet->setCellValue("D".$line, $data['merged_orders']);
            $sheet->setCellValue("E".$line, $data['separeted_orders']);
            $sheet->setCellValue("F".$line, $data['canceled_orders']);
            $sheet->setCellValue("G".$line, $data['restored_orders']);
            $sheet->setCellValue("H".$line, $data['phone_orders']);
            $sheet->setCellValue("I".$line, $data['guarantee_orders']);
            $sheet->setCellValue("J".$line, $data['coupon_orders']);

            $sheet->setCellValue("K".$line, $data['new_orders_amount']);
            $sheet->setCellValue("L".$line, $data['confirmed_orders_amount']);
            $sheet->setCellValue("M".$line, $data['canceled_orders_amount']);
            $sheet->setCellValue("N".$line, $data['restored_orders_amount']);
            $sheet->setCellValue("O".$line, $data['phone_orders_amount']);
            $sheet->setCellValue("P".$line, $data['guarantee_orders_amount']);
            $sheet->setCellValue("Q".$line, $data['coupon_orders_amount']);

            $sheet->setCellValue("R".$line, $data['avg_check']);
            $sheet->setCellValue("S".$line, $data['attract_amount']);

        }

        //Season's data
        if($season_name){
            $season_header_line = $line + 2;
            $season_header =' В выборках принимают участие заказы сезона ' . $season_name;
            $sheet->setCellValue("A".$season_header_line, $season_header);
        }



        //Paint a result line
        $sheet->getStyle('A' . $line . ':' . 'S' . $line )->getFill()->setFillType(
            PHPExcel_Style_Fill::FILL_SOLID);
        $sheet->getStyle('A' . $line . ':' . 'S' . $line)->getFill()->getStartColor()->setRGB('e8e825');
        $sheet->getStyle('A' . $line . ':' . 'S' . $line )->getFont()->setBold(true);

        //Paint a 'Средний чек' column
        $start_line_paint = $start_line + 1;
        $sheet->getStyle('R' . $start_line_paint . ':' . 'R' . $line )->getFill()->setFillType(
            PHPExcel_Style_Fill::FILL_SOLID);
        $sheet->getStyle('R' . $start_line_paint . ':' . 'R' . $line)->getFill()->getStartColor()->setRGB('e8e825');



        // Выводим содержимое файла
        self::downloadFile($xls);

    }



}