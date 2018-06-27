<?php
/**
 * Created by PhpStorm.
 * User: pkovalchuk
 * Date: 12.01.2018
 * Time: 14:05
 */

require_once(DIR_WS_CLASSES.'separator_order/SeparatorOrderRepository.php');

trait SeparatorOrderTrait
{

    protected $dbManager;

    protected $viewsManager;

    protected $langId;

    protected $langName;

    protected $systemName = 'Система деления заказов';

    protected $systemNameShort = 'СДЗ';

    protected $orderId;

    protected $errorsMessages = [];

    protected $isError = false;

    protected $successMessage = '';

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
        $order_id = intval($order_id);

        if(is_int( $order_id )){

            $this->orderId = $order_id;

        }else{

            $this->orderId = false;

        }

    }

    /**
     * @return SeparatorOrderRepository
     */
    protected function getDbManager()
    {
        return $this->dbManager;
    }

    /**
     * @param SeparatorOrderRepository $db_manager
     */
    protected function setDbManager( SeparatorOrderRepository $db_manager)
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

    /**
     * @return string
     */
    public function getSystemNameShort()
    {
        return $this->systemNameShort;
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
     * @return string
     */
    public function getSuccessMessage()
    {
        return $this->successMessage;
    }

    /**
     * @param string $successMessage
     */
    protected function addSuccessMessage($successMessage)
    {
        $this->successMessage .= $successMessage;
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

}