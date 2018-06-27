<?php
/**
 * Created by PhpStorm.
 * User: pkovalchuk
 * Date: 13.06.2018
 * Time: 11:27
 */

require_once(INVOICE_REPOSITORY_DIR . 'InvoiceOrderDBRepository.php');

trait InvoiceOrderTrait
{

    protected $dbManager;

    protected $viewsManager;

    protected $langId;

    protected $langName;

    protected $systemName = 'Система Товарных Накладных';

    protected $orderId;

    protected $errorsMessages = [];

    protected $isError = false;

    protected $successMessages = [];

    protected $userFirstName;

    protected $userLastName;

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
        $this->orderId = $this->clearOrderId($order_id);
    }

    protected function clearOrderId($order_id)
    {
        $order_id = intval($order_id);

        if(is_int( $order_id )){

            return $order_id;

        }else{

            return false;

        }
    }

    protected function clearInputStr($str)
    {
        if(empty($str) || !is_string($str)){
            return false;
        }

        return trim($str);

    }

    /**
     * @return InvoiceOrderDBRepository
     */
    protected function getDbManager()
    {
        return $this->dbManager;
    }

    /**
     * @param InvoiceOrderDBRepository $db_manager
     */
    protected function setDbManager( InvoiceOrderDBRepository $db_manager)
    {
        $this->dbManager = $db_manager;
    }

    /**
     * @return mixed
     */
    public function getLangId()
    {
        return $this->langId;
    }

    /**
     * @param mixed $lang_id
     */
    protected function setLangId($lang_id)
    {
        $lang_id = intval($lang_id);

        if(is_int( $lang_id )){

            $this->langId = $lang_id;

        }else{

            $this->langId = 1;
        }


    }

    /**
     * @return mixed
     */
    public function getLangName()
    {
        return $this->langName;
    }

    /**
     * @param mixed $langName
     */
    public function setLangName($langName)
    {
        $this->langName = $langName;
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
    protected function setSystemName($systemName)
    {
        $this->systemName = $systemName;
    }

    /**
     * @return mixed
     */
    protected function getViewsManager()
    {
        return $this->viewsManager;
    }

    /**
     * @param mixed $viewsManager
     */
    protected function setViewsManager($viewsManager)
    {
        $this->viewsManager = $viewsManager;
    }

    protected function getUserIdCurrent()
    {
        if(isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])){
            return $_SESSION['user_id'];
        }

        return false;

    }

    public function createErrorsMessage($external_errors_arr = false )
    {
        $errors_arr = ( $external_errors_arr ) ? $external_errors_arr : $this->getErrorsMessages();

        $errors = '';

        if($this->isError()){

            foreach ($errors_arr as $error){
                $errors .= '<br> ' . $error . '<hr>';
            }

        }

        return $errors;
    }

    /**
     * @return array
     */
    public function getSuccessMessage()
    {
        return $this->successMessage;
    }


    protected function addSuccessMessages($message)
    {
        $this->successMessages[] = $message;

        return true;
    }

    /**
     * @return mixed
     */
    protected function getUserFirstName()
    {
        return $this->userFirstName;
    }

    /**
     * @param mixed $userFirstName
     */
    protected function setUserFirstName($userFirstName)
    {
        $this->userFirstName = $userFirstName;
    }

    /**
     * @return mixed
     */
    protected function getUserLastName()
    {
        return $this->userLastName;
    }

    /**
     * @param mixed $userLastName
     */
    protected function setUserLastName($userLastName)
    {
        $this->userLastName = $userLastName;
    }

    protected function checkInputDataForGeneratingInvoice($order_id, $delivery_type, $table_product_map_name)
    {
        $result = true;

        if(!$order_id){
            $this->addErrorsMessages('Ошибка в получении номера заказа!');
            $result = false;
        }

        if(!$delivery_type){
            $this->addErrorsMessages('Ошибка в получении типа доставки заказа!');
            $result = false;
        }

        if(!$table_product_map_name){
            $this->addErrorsMessages('Ошибка в получении имени шаблона!');
            $result = false;
        }

        return $result;

    }

}