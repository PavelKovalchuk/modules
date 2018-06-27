<?php
/**
 * Created by PhpStorm.
 * User: pkovalchuk
 * Date: 16.02.2018
 * Time: 15:07
 */

require_once (DIR_FS_DOCUMENT_ROOT. 'admin/includes/classes/OrdersAlarmsControl.php');

trait LiqPayTrait
{

    // используется во избежание отправки инвойсов с реальными номерами во время тестирования
    protected $liveServer = 'www.greenmarket.com.ua';

    protected $systemName = 'LiqPay Сервис';

    protected $commissionPercent = 0;

    protected $orderId;

    protected $errorsMessages = [];

    protected $isError = false;

    protected $successMessages = [];

    // названия файла для логирования в файл
    protected $logsFile;

    protected $datetimeFormat = "Y-m-d H:i:s";


    /**
     * @return string
     */
    public function getDatetimeFormat()
    {
        return $this->datetimeFormat;
    }

    // Метод записи в БД и вывода на экран аларма "Проблема" для единичного заказа
    protected function setOrderAlarmTrouble($order_id, $alarm_txt)
    {
        if(!$order_id || !$alarm_txt || !is_string($alarm_txt)){
            return false;
        }

        $result = false;
        $alarms_control = new OrdersAlarmsControl(false);
        if($alarms_control->setSingleAlarm($order_id, 4, 0, $alarm_txt)){
            $result = true;
        }
        return $result;
    }

    // Метод записи в БД и вывода на экран аларма "Проблема" для цепочки заказов
    protected function setOrdersChainAlarmTrouble($order_chain, $alarm_txt)
    {
        if(!$this->checkForArray($order_chain) || !is_string($alarm_txt)){
            return false;
        }

        $result = true;
        foreach ($order_chain as $order_id){
            $order_result = $this->setOrderAlarmTrouble($order_id, $alarm_txt);
            if(!$order_result){
                $result = false;
            }
        }

        return $result;

    }

    //Запись комментария в "Комментарий менеджера"
    protected function logManagerComment($text, $orders_in_chain = false)
    {
        $orders_in_chain = ($orders_in_chain) ? $orders_in_chain : $this->getOrdersChain();

        if(!$this->checkForArray($orders_in_chain) || !$text || !is_string($text)){
            return false;
        }

        //Логируем во все заказы цепочки
        foreach ( $orders_in_chain as $key => $order ){
             add_msg_to_managers_comment($order, $text . date('d-m-y в H:i' . '.'));
        }

        return true;

    }


    //Запись комментария в "Комментарий" заказа
    protected function logResults($orders_in_chain = false)
    {
        $orders_in_chain = ($orders_in_chain) ? $orders_in_chain : $this->getOrdersChain();

        if(!$this->checkForArray($orders_in_chain)){
            return false;
        }

        //Логируем и успех и ошибки, т.к. есть необязательные операции, которые могут завершиться ошибкой
        $messages = array_merge($this->getSuccessMessages(), $this->getErrorsMessages());
        $manager_info = $this->getCurrentManagerInfo();

        if(empty($messages)){
            $this->addErrorsMessages('Нет информации для логирования.');
            return false;
        }

        $text = implode(' ', $messages);

        $result = true;

        //Логируем во все заказы цепочки
        foreach ( $orders_in_chain as $order ){

            $result = upd_last_comment_to_order_log($order, "LiqPay инвойс: " . $text . $manager_info  . date('d-m-y в H:i'));

            if(!$result){
                $result = false;
            }

        }

        if(!$result){
            $this->addErrorsMessages('Логирования результатов произошло с ошибками.');
            return false;
        }

        return true;
    }

    //Проверка, является ли текущий сервер Продуктовым "Живым"
    public function isLiveServer()
    {
        if($this->getLiveServer() != $_SERVER['SERVER_NAME']){
            return false;
        }
        return true;
    }

    /**
     * @return string
     */
    public function getLiveServer()
    {
        return $this->liveServer;
    }

    //Запись системной иформации в файл (файл устанавливаетя - $this->setLogsFile())
    public function logInFile($log_stage, $param, $string)
    {

        if(!$string || !$param || !$log_stage){
            return false;
        }

        if(!is_string($string)){
            return false;
        }

        $stage = '[! ' . $log_stage . ' !]';
        $key = ( $this->getOrderId() ) ? '[shop order_id: ' . $this->getOrderId() . '] ': '';
        $key .=  $param . ': ';
        $file = $this->getLogsFile();

        if(empty($file)){
            return false;
        }

        $logFileName = LIQPAY_LOGS_DIR . $file;

        $cur_time = (new DateTime())->format($this->getDatetimeFormat());

        file_put_contents($logFileName,"$cur_time: $stage $key: $string\n", FILE_APPEND);

        return true;

    }

    public function getCurrentManagerInfo()
    {
        if(!$_SESSION['user_last_name'] && !$_SESSION['user_first_name']){
            return '';
        }

        $manager_info = 'Менеджер: ' . $_SESSION['user_last_name'] . " " . $_SESSION['user_first_name']. ". ";

        return $manager_info;
    }

    /**
     * @return bool
     */
    public function isError()
    {
        return $this->isError;
    }

    /**
     * @param bool $is_error
     */
    protected function setIsError($is_error)
    {
        $this->isError = $is_error;
    }

    /**
     * @return array
     */
    public function getErrorsMessages()
    {
        return $this->errorsMessages;
    }

    public function setErrorsMessages($message)
    {
        $this->errorsMessages = $message;
    }

    /**
     * @param string $message
     */
    protected function addErrorsMessages($message)
    {
        $this->errorsMessages[] = $message;

        $this->setIsError(true);

        return true;
    }

    /**
     * @param string $message
     */
    public function addSuccessMessages($message)
    {
        $this->successMessages[] = $message;

        return true;
    }

    /**
     * @return array
     */
    public function getSuccessMessages()
    {
        return $this->successMessages;
    }

    /**
     * @param array $successMessages
     */
    public function setSuccessMessages($successMessages)
    {
        $this->successMessages = $successMessages;
    }

    /**
     * @return mixed
     */
    protected function getOrderId()
    {
        return $this->orderId;
    }

    /**
     * @param mixed $orderId
     */
    protected function setOrderId($order_id)
    {
        $order_id = intval($order_id);

        if(is_int( $order_id ) && $order_id > 0){

            $this->orderId = $order_id;

        }else{

            $this->orderId = false;
            $this->addErrorsMessages('Не передан номер заказа.');

        }

    }

    /**
     * Обьект по работе с запросами SQL
     * @return SeparatorOrderRepository
     */
    protected function getDbManager()
    {
        return LiqPayDBRepository::getInstance();
    }

    /**
     * @param $data_arr
     * @return bool
     */
    protected function checkForArray($data_arr)
    {
        if(!is_array($data_arr) || empty($data_arr) ){
            return false;
        }

        return true;

    }

    // Возвращает все успешные сообщения и сообщения об ошибках
    protected function getFinalMessage($object)
    {
        if(!is_object($object)){
            return false;
        }

        if(!method_exists($object, 'getSuccessMessages') || !method_exists($object, 'getErrorsMessages')){
            return false;
        }

        $object_errors = $object->getErrorsMessages();

        if(empty($object_errors)){
            return $object->getSuccessMessages();
        }

        $add_bold_to_items = function($value) {
            return '<b style="color:red;">' . $value . '</b>';
        };

        $errors_messages = array_map($add_bold_to_items, $object->getErrorsMessages());

        return array_merge($object->getSuccessMessages() , $errors_messages);

    }

    // Выводит успешные сообщения или сообщения об ошибках
    public function outputMessage($external_message_arr = false, $with_html = true)
    {

        $message = '';
        $delimiter = '<br> ';

        if($this->isError()){
            $symbol = '&#9888;';
            $messages_arr = ( $external_message_arr ) ? $external_message_arr : $this->getErrorsMessages();
        }else{
            $symbol = '&#9786;';
            $messages_arr = ( $external_message_arr ) ? $external_message_arr : $this->getSuccessMessages();
        }

        if($with_html == false){
            $symbol = '';
            $delimiter = '';

        }

        foreach ($messages_arr as $entry){
            $message .= $delimiter . $symbol . ' ' . $entry;
        }

        return $message;
    }

    /**
     * @return string
     */
    public function getSystemName()
    {
        return $this->systemName;
    }

    /**
     * @param string $systemName
     */
    public function setSystemName($systemName)
    {
        $this->systemName = $systemName;
    }

    /**
     * Возвращает обьект с настройками LiqPay API
     * @return mixed
     */
    protected function getSettings()
    {
        return LiqPaySettings::getInstance();
    }

    protected function isFullArrayData($data)
    {
        if( !$this->checkForArray($data) ){
            return false;
        }

        $response = true;

        foreach ($data as $key => $value){
            if(empty($value)){
                $response = false;
                break;
            }
        }

        return $response;
    }

    /**
     * @return string
     */
    public function getLogsFile()
    {
        return $this->logsFile;
    }

    /**
     * Установка названия файла для логирования в файл.
     * @param mixed $logsFile
     */
    public function setLogsFile($logsFile)
    {
        $this->logsFile = $logsFile;
    }

    /**
     * Величина коммисии для отображения
     * @return int
     */
    public function getCommissionPercent()
    {
        return $this->commissionPercent;
    }

    /**
     * Величина коммисии для расчетов
     * @return float
     */
    public function getCommissionPercentToCalculate()
    {
        return 1 + ($this->commissionPercent/100);
    }

}

