<?php
/**
 * Created by PhpStorm.
 * User: pkovalchuk
 * Date: 16.02.2018
 * Time: 15:07
 */

require_once(DROPSHIPPING_REPOSITORY_DIR . 'DropshippingDBRepository.php');

trait DropshippingTrait
{

    protected $systemName = 'ТТН по дропшиппингу';

    protected $dbManager;

    protected $orderId;

    protected $errorsMessages = [];

    protected $isError = false;

    protected $successMessages = [];

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
    protected function addSuccessMessages($message)
    {
        $this->successMessages[] = $message;

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
     * @param  $db_manager
     */
    protected function setDbManager( DropshippingDBRepository $db_manager)
    {
        $this->dbManager = $db_manager;
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

}

