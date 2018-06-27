<?php
/**
 * Created by PhpStorm.
 * User: pkovalchuk
 * Date: 15.03.2018
 * Time: 14:53
 */

/**
 * На сервер будет отправлен POST запрос с двумя параметрами data и signature.
 * URL API в Вашем магазине для уведомлений об изменении статуса платежа (сервер->сервер).
 * https://www.liqpay.ua/documentation/api/callback
 *
 */

require_once('includes/application_top.php');
require_once(DIR_FS_INC.'LiqPay/controllers/LiqPayController.php');


$controller = LiqPayController::getInstance();
//LOG IN FILE
$log_stage = 'LiqPayController';
$controller->setLogsFile("LiqPay_callback_results.txt");

//Test ONLY!
//$_POST['data'] = 'eyJhY3Rpb24iOiJwYXkiLCJwYXltZW50X2lkIjo2NTMyNDM3ODMsInN0YXR1cyI6InN1Y2Nlc3MiLCJ2ZXJzaW9uIjozLCJ0eXBlIjoiYnV5IiwicGF5dHlwZSI6ImNhcmQiLCJwdWJsaWNfa2V5IjoiaTI0ODMxMzk2NDYiLCJhY3FfaWQiOjQxNDk2Mywib3JkZXJfaWQiOiIyIiwibGlxcGF5X29yZGVyX2lkIjoiNzU3QTdPVVoxNTIxNTUzODU2NTAxMjM1IiwiZGVzY3JpcHRpb24iOiI1Njg4MCIsInNlbmRlcl9waG9uZSI6IjM4MDY2MjAwMjA0MCIsInNlbmRlcl9jYXJkX21hc2syIjoiNTE2ODc0KjAzIiwic2VuZGVyX2NhcmRfYmFuayI6InBiIiwic2VuZGVyX2NhcmRfdHlwZSI6Im1jIiwic2VuZGVyX2NhcmRfY291bnRyeSI6ODA0LCJpcCI6IjE5My4yNDMuMTU2LjI2IiwiYW1vdW50IjoxLjAsImN1cnJlbmN5IjoiVUFIIiwic2VuZGVyX2NvbW1pc3Npb24iOjAuMCwicmVjZWl2ZXJfY29tbWlzc2lvbiI6MC4wMywiYWdlbnRfY29tbWlzc2lvbiI6MC4wLCJhbW91bnRfZGViaXQiOjEuMCwiYW1vdW50X2NyZWRpdCI6MS4wLCJjb21taXNzaW9uX2RlYml0IjowLjAsImNvbW1pc3Npb25fY3JlZGl0IjowLjAzLCJjdXJyZW5jeV9kZWJpdCI6IlVBSCIsImN1cnJlbmN5X2NyZWRpdCI6IlVBSCIsInNlbmRlcl9ib251cyI6MC4wLCJhbW91bnRfYm9udXMiOjAuMCwiYXV0aGNvZGVfZGViaXQiOiIyMzYwMDEiLCJhdXRoY29kZV9jcmVkaXQiOiI3Mjc4MDgiLCJycm5fZGViaXQiOiIwMDA4MzE2MDk0NzEiLCJycm5fY3JlZGl0IjoiMDAwODMxNjA5NDc4IiwibXBpX2VjaSI6IjciLCJpc18zZHMiOmZhbHNlLCJjcmVhdGVfZGF0ZSI6MTUyMTU1Mzg3NDA5OCwiZW5kX2RhdGUiOjE1MjE1NTM4NzQwOTgsInRyYW5zYWN0aW9uX2lkIjo2NTMyNDM3ODN9';
//$_POST['signature'] = 'M2TgZHGeqjr21gZY1E8q16MzRYo=';

if( ! isset($_POST['data']) || empty($_POST['data']) || !isset($_POST['signature']) || empty($_POST['signature'])){

    exit;
}

$data = $_POST['data'];
$signature = $_POST['signature'];


/**
 * Прием оплаты может только осуществляться на живом сервере.
 * Для тестирования  - раскоментировать $_POST['data'] и $_POST['signature']
 * Подставить свои значения.
 * Тестовые значения можно взять тут backup/LiqPay_callback_results.txt
 */

if(!$controller->isLiveServer()){
    return;
}

$controller->handleCallback($data, $signature);

if($controller->isError()){

    //LOG IN FILE
    $controller->logInFile($log_stage, 'callback_error', $controller->outputMessage(false, false) );

}else{
    //LOG IN FILE
    $controller->logInFile($log_stage, 'callback_success', $controller->outputMessage(false, false) );

}

