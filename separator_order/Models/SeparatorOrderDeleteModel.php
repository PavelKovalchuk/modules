<?php
/**
 * Created by PhpStorm.
 * User: pkovalchuk
 * Date: 22.01.2018
 * Time: 14:09
 */
require_once(DIR_WS_CLASSES.'separator_order/Models/SeparatorOrderAbstractModel.php');
require_once (DIR_WS_CLASSES . "ManagersAttractToOrders.php");

class SeparatorOrderDeleteModel extends SeparatorOrderAbstractModel
{
    //use SeparatorOrderTrait;

    protected $isDeleteNeeded = false;

    protected $isDeleteCompleted = false;

    //items to delete from the order
    protected $ordersProductsIdToDelete = [];

    //items of products to update in DB after deleting them in the order
    protected $productsIdToUpdate = [];

    //container for connections between productsIdToUpdate as products_name
    protected $productsNames = [];

    public function __construct($order_id)
    {
        $this->setOrderId(intval($order_id));

        if(!$this->getOrderId() ){

            $this->addErrorsMessages('Ошибка в инициализации данных для удаления');

        }

    }

    public function deleteProductsProcess($data_to_delete)
    {
        if(!$data_to_delete){
            return;
        }

        if($this->isDeleteNeeded() === false){
            return;
        }

        $this->parseIncomingDataToDelete($data_to_delete);

        if( $this->isDeleteNeeded() === false ){

            return false;
        }

        //Удаление товаров с заказа
        $delete_step_result = $this->deleteProductsStepDelete();
        if($delete_step_result === false){

            return false;

        }

        //Перерасчет остатка товаров
        $payments_step_result = $this->deleteProductsStepPayments();
        if($payments_step_result === false){

            return false;
        }

        //Перерасчет остатка товаров
        $balance_step_result = $this->deleteProductsStepBalance();
        if($balance_step_result === false){

            return false;

        }

        $this->deleteProductsStepManager();

        //Операция удаления завершена
        $this->setIsDeleteCompleted(true);

        return true;

    }

    protected function parseIncomingDataToDelete($data_to_delete)
    {

        $orders_products_id_arr = [];
        $products_id_arr = [];
        $products_names_arr = [];

        foreach ($data_to_delete as $group){

            foreach ($group['data'] as $data){

                $orders_products_id_arr[] = $data['orders_products_id'];
                $products_id_arr[] = $data['products_id'];
                $products_names_arr[] = $data['products_name'];

            }

        }

        if( $this->checkForArray($orders_products_id_arr) && $this->checkForArray($products_id_arr) && $this->checkForArray($products_names_arr)){
            $this->setIsDeleteNeeded(true);
        }

        $this->setOrdersProductsIdToDelete($orders_products_id_arr);
        $this->setProductsIdToUpdate($products_id_arr);
        $this->setProductsNames($products_names_arr);

        return true;

    }

    //Удаление товаров с заказа
    protected function deleteProductsStepDelete()
    {
        $delete_result =  delete_orders_products($this->getOrdersProductsIdToDelete());

        if($delete_result == -1 || !$delete_result){

            $this->addErrorsMessages('Удаление товаров выполнить не возможно.');
            return false;
        }

        $this->addSuccessMessage('<br> Удалено ' . $delete_result . ' наименований товаров: ' . implode(', ', $this->getProductsNames()) . '.' );

        return true;

    }

    //Перерасчет привлеченных средств менеджером
    protected function deleteProductsStepManager()
    {
        if( !$this->getUserIdCurrent() ){
            return false;
        }

        // добавить в сессию инф об операции редактирования
        ManagersAttractToOrders::updOrderEditSession(
            $this->getOrderId(),
            $this->getUserIdCurrent(),
            implode(",",$this->getProductsIdToUpdate()),
            ManagersAttractToOrders::TYPE_EDIT_PROD_DEL);

        // Добавление инф о привлечении сумм менеджерами
        ManagersAttractToOrders::setSumAttractsOnEditOrder($this->getOrderId(), $this->getUserIdCurrent());

    }

    //Перерасчет остатка товаров
    protected function deleteProductsStepBalance()
    {
        //Количество наименований товаров, для которых выполнен перерасчет
        $update_affected_rows = 0;

        foreach ($this->getProductsIdToUpdate() as $product_id){

            $update_qty_result = update_qty($product_id);

            if($update_qty_result){

                $update_affected_rows += 1;

            }
        }

        if(!$update_affected_rows > 0){
            $this->addErrorsMessages('Перерасчет остатка выполнить не возможно.');
            return false;
        }

        $this->addSuccessMessage('<br> Выполнен перерасчет остатка товаров.');

        return true;

    }

    //Перерасчет платежей в заказе
    protected function deleteProductsStepPayments()
    {

        $recalculate_result =  recalculate_orders_sum( strval($this->getOrderId()));

        if($recalculate_result === false){

            $this->addErrorsMessages('Перерасчет стоимости в заказе выполнить не возможно.');
            return false;
        }

        $this->addSuccessMessage('<br> Выполнен перерасчет стоимости по заказу. ');

        return true;

    }

    /**
     * @return bool
     */
    public function isDeleteCompleted()
    {
        return $this->isDeleteCompleted;
    }

    /**
     * @param bool $isDeleteCompleted
     */
    protected function setIsDeleteCompleted($isDeleteCompleted)
    {
        $this->isDeleteCompleted = $isDeleteCompleted;
    }

    /**
     * @return bool
     */
    public function isDeleteNeeded()
    {
        return $this->isDeleteNeeded;
    }

    /**
     * @param bool $isDeleteNeeded
     */
    public function setIsDeleteNeeded($isDeleteNeeded)
    {
        $this->isDeleteNeeded = $isDeleteNeeded;
    }

    /**
     * @return array
     */
    public function getOrdersProductsIdToDelete()
    {
        return $this->ordersProductsIdToDelete;
    }

    /**
     * @param array $ordersProductsIdToDelete
     */
    protected function setOrdersProductsIdToDelete($ordersProductsIdToDelete)
    {
        $this->ordersProductsIdToDelete = $ordersProductsIdToDelete;
    }

    /**
     * @return array
     */
    public function getProductsIdToUpdate()
    {
        return $this->productsIdToUpdate;
    }

    /**
     * @param array $productsIdToUpdate
     */
    protected function setProductsIdToUpdate($productsIdToUpdate)
    {
        $this->productsIdToUpdate = $productsIdToUpdate;
    }

    /**
     * @return array
     */
    public function getProductsNames()
    {
        return $this->productsNames;
    }

    /**
     * @param array $productsNames
     */
    protected function setProductsNames($productsNames)
    {
        $this->productsNames = $productsNames;
    }

}