<?php
/**
 * Created by PhpStorm.
 * User: pkovalchuk
 * Date: 02.03.2018
 * Time: 15:03
 */

class FakeResult
{
    public function getError()
    {
        $response = new stdClass();
        $response->result = "error";
        $response->status = "error";
        $response->code = "order_id_duplicate";
        $response->err_code = "order_id_duplicate";
        $response->err_description = "Duplicate order_id";

        return $response;
    }

    public function getSuccess($order_id = false)
    {
        $response = new stdClass();
        $response->result = "ok";
        $response->id = 555978;
        $response->status = "invoice_wait";
        $response->amount = floatval(1159);
        $response->currency = "UAH";
        $response->description = "56805,56817,56839";
        $response->order_id = $order_id;
        $response->token = "1519997950550522_293238949_uUoHY3K21xxRIxGdCpAP9bOiWsklbw";
        $response->href = "https://www.liqpay.ua/apipay/invoice/1519997950550522_293238949_uUoHY3K21xxRIxGdCpAP9bOiWsklbw";
        $response->receiver_type = "email";
        $response->receiver_value = "uliayanesh@gmail.com";
        $response->action = "pay";

        return $response;
    }

    public function getCancelSuccess($order_id = false)
    {
        $response = new stdClass();
        $response->result = "ok";
        $response->invoice_id = $order_id;
        return $response;
    }

    public function getCancelError($order_id = false)
    {
        $response = new stdClass();
        $response->result = "error";
        $response->invoice_id = $order_id;
        return $response;
    }

}