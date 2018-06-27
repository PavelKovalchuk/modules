<?php
/**
 * Created by PhpStorm.
 * User: pkovalchuk
 * Date: 02.03.2018
 * Time: 15:16
 */

class RequestHelperInvoiceCancel extends RequestHelperAbstract
{

    public function getRequestData()
    {
        $response =  array(
            'action'    => $this->getAction(),
            'version'   => $this->getVersion(),
            'order_id'  => $this->getGeneratedOrderId(),
        );

        return $response;

    }
}