<?php
/**
 * Created by PhpStorm.
 * User: pkovalchuk
 * Date: 22.01.2018
 * Time: 16:23
 */

require_once(DIR_WS_CLASSES.'separator_order/Models/SeparatorOrderAbstractModel.php');

class SeparatorOrderLogModel extends SeparatorOrderAbstractModel
{
    protected $isLoggingCompleted = false;

    public function __construct()
    {

        $this->setUserFirstName($_SESSION['user_first_name']);
        $this->setUserLastName($_SESSION['user_last_name']);

    }

    public function logResult($main_order_id, $message)
    {
        if(!is_string($message) || !is_int($main_order_id)){
            return false;
        }

        $log_message = $this->getSystemNameShort() . ': ' . $message . $this->getManagerInfo() . ' ' . date('d-m-y в H:i');

        $result = upd_last_comment_to_order_log(strval($main_order_id), strip_tags($log_message));

        //add_msg_to_managers_comment(strval($main_order_id), strip_tags($message));

        return $result;
    }

    /**
     * @return bool
     */
    public function isLoggingCompleted()
    {
        return $this->isLoggingCompleted;
    }

    protected function getManagerInfo()
    {
        $result = '';

        if($this->getUserIdCurrent()){
            $result .= ' Менеджер: ';
        }

        if( $this->getUserFirstName() ){
            $result .= ' ' . $this->getUserFirstName();
        }

        if( $this->getUserLastName() ){
            $result .= ' ' . $this->getUserLastName() . '.';
        }

        return $result;
    }
    /**
     * @param bool $isLoggingCompleted
     */
    protected function setIsLoggingCompleted($isLoggingCompleted)
    {
        $this->isLoggingCompleted = $isLoggingCompleted;
    }



}