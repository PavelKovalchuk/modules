<?php
/**
 * Created by PhpStorm.
 * User: pkovalchuk
 * Date: 19.09.2017
 * Time: 15:01
 */

require_once(DIR_WS_CLASSES . "analytics_orders/AnalyticsOrdersRepository.php");

class AnalyticsOrdersManager
{
    protected $sinceDate;

    protected $toDate;

    protected $sinceSeasonDate;

    protected $toSeasonDate;

    protected $default_manager_name = 'Без менеджера';

    protected $default_sum_name = 'ИТОГ';

    protected $error = false;

    protected $data_map = array(
        'group_manager' => 0,
        'manager_name' => '',
        'new_orders' => 0,
        'new_orders_amount' => 0,
        'merged_orders' => 0,
        'merged_orders_amount' => 0,
        'separeted_orders' => 0,
        'confirmed_orders' => 0,
        'confirmed_orders_amount' => 0,
        'avg_check' => 0,
        'canceled_orders' => 0,
        'canceled_orders_amount' => 0,
        'restored_orders' => 0,
        'restored_orders_amount' => 0,
        'phone_orders' => 0,
        'phone_orders_amount' => 0,
        'guarantee_orders' => 0,
        'guarantee_orders_amount' => 0,
        'coupon_orders' => 0,
        'coupon_orders_amount' => 0,
        'attract_amount' => 0

    );

    protected $statuses_not_in_confirmed = array();

    public function __construct(\DateTime $sinceDate, \DateTime $toDate, \DateTime $season_from, \DateTime $season_to, $season_name = '')
    {
        if(!defined('ANALYTICS_ORDERS_STATUSES_NOT_IN_CONFIRMED')){
           $this->setError();
        }

        $res_since = $this->setSinceDate($sinceDate);

        $res_to = $this->setToDate($toDate);

        $res_season_since = $this->setSeasonSinceDate($season_from);

        $res_season_to = $this->setSeasonToDate($season_to);

        if(!$res_since || !$res_to || !$res_season_since || !$res_season_to){
            $this->setError();
        }

        $statuses_array = explode(',', ANALYTICS_ORDERS_STATUSES_NOT_IN_CONFIRMED);

        foreach($statuses_array as $value){
            $this->statuses_not_in_confirmed[] = intval($value);
        }

    }

    public function checkError()
    {
        if($this->error == true){
            return true;
        }else{
            return false;
        }
    }

    protected function setError()
    {
        $this->error = true;
    }

    protected function getFormattedDate($date){
        if($date instanceof DateTime ){
            return $date->format('Y-m-d');
        }
        return false;
    }

    protected function setSinceDate($sinceDate)
    {
        if($sinceDate){
            $this->sinceDate = $this->getFormattedDate($sinceDate) . ' 00:00:00';
            return true;
        }

        return false;

    }

    protected function setToDate($toDate)
    {
        if($toDate){
            $this->toDate = $this->getFormattedDate($toDate) . ' 23:59:59';
            return true;
        }

        return false;

    }

    protected function setSeasonSinceDate($sinceDate)
    {
        if($sinceDate){
            $this->sinceSeasonDate = $this->getFormattedDate($sinceDate) . ' 00:00:00';
            return true;
        }

        return false;

    }

    protected function setSeasonToDate($toDate)
    {
        if($toDate){
            $this->toSeasonDate = $this->getFormattedDate($toDate) . ' 23:59:59';
            return true;
        }

        return false;

    }

    protected function getSinceDate()
    {
        if($this->sinceDate){
            return $this->sinceDate;
        }
        return false;
    }

    protected function getToDate()
    {
        if($this->toDate){
            return $this->toDate;
        }
        return false;
    }

    protected function getSeasonSinceDate()
    {
        if($this->sinceSeasonDate){
            return $this->sinceSeasonDate;
        }
        return false;
    }

    protected function getSeasonToDate()
    {
        if($this->toSeasonDate){
            return $this->toSeasonDate;
        }
        return false;
    }

    protected function fillDataMap($data_array_orders)
    {
        if(!is_array($data_array_orders) || !is_array($this->data_map)){
            return false;
        }

        $result = array();

        $sum_data = $this->data_map;
        $sum_data['group_manager'] = $this->default_sum_name;
        $sum_data['manager_name'] = $this->default_sum_name;

        foreach($data_array_orders as $data_by_type){

            foreach ($data_by_type as $key => $data){

                if(!is_array($data_by_type)){
                    //exit('ERRORRRRRR'); // for testing purposes !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
                    continue;
                }

                //If Manager already exists in result array, add new values. If not - create new array for manager with map ($this->data_map)
                if(array_key_exists($key, $result)){

                    $result[$key] = array_merge($result[$key], $data);

                }else{

                    $result[$key] = array_merge($this->data_map, $data);

                    $result[$key]['manager_name'] = $this->getUserNameById($key);

                }

                //Save sum of each index in result
                foreach ($data as $index => $number){

                    if($index == 'group_manager' || $index == 'manager_name'){
                        continue;
                    }
                    $sum_data[$index] = $sum_data[$index] + $number;

                }

            }

        }

        if($sum_data['confirmed_orders'] > 0){
            $sum_data['avg_check'] = round( ($sum_data['confirmed_orders_amount'] / $sum_data['confirmed_orders']), 2) ;
        }


        $result['sum'] = $sum_data;

        return $result;

    }


    public function getReportByManagers()
    {
        $data_array_orders = array(
            $this->getNewOrders(),
            $this->getMergedOrders(),
            $this->getSeparetedOrders(),
            $this->getConfirmedOrders(),
            $this->getCanceledOrders(),
            $this->getRestoredOrders(),
            $this->getPhoneOrders(),
            $this->getGuaranteeOrders(),
            $this->getCouponOrders(),
            $this->getAttractedAmount()
        );

        $result = $this->fillDataMap($data_array_orders);

        return $result;


    }


    protected function getUserNameById($id)
    {
        if(intval($id) > 0){
            $data_name = AnalyticsOrdersRepository::getUserNameByIdDb(intval($id));
            return $data_name["manager_f_name"];
        }
        return $this->default_manager_name;


    }


    /**
     * Группируем по stationary_manager_id
     *
     * В графе Новые заказы учитываем заказы, созданные через магазин и через админку
     *
     *   Получаем новые заказы и оборот по ним, которые принадлежат одному сезону, сумма товаров больше нуля.
     *   Исключаем разделенные заказы (дочерние) в количестве штук.
     *   Учитываем удаленные(отмененные).
     *
     *
     * Считаем оборот.
     *   Если есть сумма со скидкой - берем ее.
     *   Если суммы со скидкой нет - берем Стоимость товара.
     *   Если есть дочерний заказ - добавляем оборот дочернего заказа.
     *   Гарантия не учитывается, т.к Н.Штепа - "Гарантия - это наш долг клиенту, он на сумму заказа не влияет, а влияет только на сумму которую клиент должен нам заплатить"
     *
     * @return array|bool|mixed
     */
    public function getNewOrders()
    {
        $since = $this->getSinceDate();
        $to = $this->getToDate();
        $sinceSeason =  $this->getSeasonSinceDate();
        $toSeason = $this->getSeasonToDate();
        //Получаем новые заказы БЕЗ учета разделенных И БЕЗ учета присоединенных - Простые
        $data = AnalyticsOrdersRepository::getNewOrdersRawDb($since, $to, $sinceSeason, $toSeason);

        //Получаем Корневые разделенные заказы среди новых и сумму платежей дочерних заказов.
        $data_divided = AnalyticsOrdersRepository::getDividedOrdersWithChildrenInNewDb($since, $to, $sinceSeason, $toSeason);

        //Сравниваем, есть ли менеджеры, которые не имеют простых заказов, но имеют разделенные
        $managers_data_divided = array_diff_key($data_divided, $data);

        //Добавляем к каждому менеджеру Главвные разделенные заказы (штуки заказов) и оборот Главного + оборотдочернего
        foreach ($data as $man => $row){

            if($data_divided[$man]){
                $data[$man]['new_orders'] = intval($row['new_orders']) + intval($data_divided[$man]['new_root_divided_orders']);
                $data[$man]['new_orders_amount'] = floatval($row['new_orders_amount'])
                                                    + floatval($data_divided[$man]['new_root_divided_orders_payments'])
                                                    + floatval($data_divided[$man]['all_child_divided_orders_payments']);
            }

        }

        //Добавляем в результирующий набор данные о менеджерах, которые не имеют простых заказов, но имеют разделенные
        if ($managers_data_divided){
            foreach ($managers_data_divided as $man => $row){

               $data[$man]['group_manager'] = $row["group_manager"];
               $data[$man]['new_orders'] = $row["new_root_divided_orders"];
               $data[$man]['new_orders_amount'] = floatval($row["new_root_divided_orders_payments"]) + floatval($row["all_child_divided_orders_payments"]);

            }
        }

        //Получаем заказы, которые были созданы в указанный период, И были присоединены к другим существующим заказам
        // Их оборот не учитываем, т.к оборот присоединенного заказа принадлежит заказу, к которому присоединили

        $data_merged = AnalyticsOrdersRepository::getChildMergedOrdersInNewDb($since, $to, $sinceSeason, $toSeason);
        //var_dump($data_merged);
        //Сравниваем, есть ли менеджеры, которые не имеют простых заказов, но имеют присоединенные
        $managers_data_merged = array_diff_key($data_merged, $data);

        //Добавляем к каждому менеджеру количество присоединенных заказов
        foreach ($data as $man => $row){

            if($data_merged[$man]){
                $data[$man]['new_orders'] = intval($row['new_orders']) + intval($data_merged[$man]['merged_new_orders']);

            }

        }

        //Добавляем в результирующий набор данные о менеджерах, которые не имеют простых заказов, но имеют присоединенные
        if ($managers_data_merged){
            foreach ($managers_data_merged as $man => $row){

                $data[$man]['group_manager'] = $row["group_manager"];
                $data[$man]['new_orders'] = $row["merged_new_orders"];

            }
        }

        //Получаем заказы, которые были созданы в указанный период, И были удалены
        $data_canceled = AnalyticsOrdersRepository::getCanceledOrdersInNewDb($since, $to, $sinceSeason, $toSeason);

        //Сравниваем, есть ли менеджеры, которые не имеют простых заказов, но имеют удаленные
        $managers_data_canceled = array_diff_key($data_canceled, $data);

        //Добавляем к каждому менеджеру количество удаленных заказов
        foreach ($data as $man => $row){

            if($data_canceled[$man]){
                $data[$man]['new_orders'] = intval($row['new_orders']) + intval($data_canceled[$man]['canceled_orders']);
                $data[$man]['new_orders_amount'] = floatval($row['new_orders_amount'])
                    + floatval($data_canceled[$man]['canceled_orders_payments']);

            }

        }

        //Добавляем в результирующий набор данные о менеджерах, которые не имеют простых заказов, но имеют удаленные
        if ($managers_data_canceled){
            foreach ($managers_data_canceled as $man => $row){

                $data[$man]['group_manager'] = $row["group_manager"];
                $data[$man]['new_orders'] = $row["canceled_orders"];
                $data[$man]['new_orders_amount'] = floatval($row["canceled_orders_payments"]);

            }
        }


        return $data;
    }

    /**
     * Получаем Присоедененные заказы и оборот по ним, которые принадлежат одному сезону, сумма товаров больше нуляю.
     *  Это заказы, которые были присоеденены к ранее совершенным заказам и оброт по ним больше нуля
     * Группируем по менеджеру, который закреплен за Основным заказом (тот, к которому присоединили)
     *
     * Допустим ситуация.
     *      К заказу (далее - А) присоединили другой заказ (Б).
     *      Но потом заказ А удалили.
     *      Следует ли учитывать заказ Б в графе Присоединенные?
     *      Да следует учесть Б в присоединенные.
     *
     * Считаем оборот.
     *   Если есть сумма со скидкой - берем ее.
     *   Если суммы со скидкой нет - берем Стоимость товара.
     *
     */
    public function getMergedOrders()
    {
       $data = AnalyticsOrdersRepository::getMergedOrdersDb($this->getSinceDate(), $this->getToDate(), $this->getSeasonSinceDate(), $this->getSeasonToDate());

        return $data;
    }


    /**
     * Получаем разделенные заказы, которые принадлежат одному сезону и Оборот больше нуля.
     *  заказы полученные после разделения новых.
     *  Например, есть заказ. И мы от него отделяем другой заказ, будет - разделенные = 2
     *
     * Группируем по менеджеру, который закреплен заказамаи
     *
     * @return array
     */
    public function getSeparetedOrders()
    {
        $data = AnalyticsOrdersRepository::getSeparetedOrdersDb($this->getSinceDate(), $this->getToDate(), $this->getSeasonSinceDate(), $this->getSeasonToDate());

        return $data;

    }




    /**
     * Получаем подтвержденные заказы, которые принадлежат одному сезону, сумма товаров больше нулю.
     *    Это новые, которые перешли в статус Ждем предоплату и выше, учитывая Присоедененные, НЕ УЧИТЫВАЯ Разделенных.
     *    Статусы, которые не пренадлежат состоянию Подтвержденные храняться в БД в таблице configuration.
     *       configuration_key : ANALYTICS_ORDERS_STATUSES_NOT_IN_CONFIRMED
     *          Саженцы: Офис-Новый, Офис-Обзвонить новый, Офис-Объединить, Офис-Недозвон (Обзвонить новый)
     *          Семена: Новый обзвонить, Недозвон
     *     Фильтруем заказы, которые имеют статусы, отличные от перечисленных в константе ANALYTICS_ORDERS_STATUSES_NOT_IN_CONFIRMED .
     * Разделенные (дочерние отделенные) не являются Подтвержденными, Их Родитель - является. По-этому сумма дочерних входит в сумму Родителя, а количество штук - нет.
     *
     * Группируем по менеджеру, который закреплен за заказамаи
     *
     * Считаем Средний чек
     *
     * Пример. Допустим, есть заказ А  - созданный через админку , не содержит сумму гарантии. Оборот 1000 грн.
     * От него отделяется заказ Б. Оборот 200 грн.
     * Ответ - подтвержденный =1; оборот = 1000
     *
     * @return array|bool
     */
    public function getConfirmedOrders()
    {
        $since = $this->getSinceDate();
        $to = $this->getToDate();
        $sinceSeason =  $this->getSeasonSinceDate();
        $toSeason = $this->getSeasonToDate();

        //Тут получаем все Подтвержденные заказы За исключением Разделенных Корневых - Простые
        $data = AnalyticsOrdersRepository::getConfirmedOrdersRawDb($since, $to, $sinceSeason, $toSeason, $this->statuses_not_in_confirmed);


        //НЕ УЧИТЫВАЯ Разделенных

        //Получаем Корневые разделенные заказы среди Подтвержденных и сумму платежей дочерних заказов.
        $data_divided = AnalyticsOrdersRepository::getDividedOrdersWithChildrenInConfirmedDb($since, $to, $sinceSeason, $toSeason, $this->statuses_not_in_confirmed);

        //Сравниваем, есть ли менеджеры, которые не имеют простых заказов, но имеют разделенные
        $managers_data_divided = array_diff_key($data_divided, $data);

        //Добавляем к каждому менеджеру Главвные разделенные заказы (штуки заказов) и оборот Главного + оборотдочернего
        foreach ($data as $man => $row){

            if($data_divided[$man]){
                $data[$man]['confirmed_orders'] = intval($row['confirmed_orders']) + intval($data_divided[$man]['confirmed_root_divided_orders']);
                $data[$man]['confirmed_orders_amount'] = floatval($row['confirmed_orders_amount'])
                    + floatval($data_divided[$man]['confirmed_root_divided_orders_payments'])
                    + floatval($data_divided[$man]['all_child_divided_orders_payments']);
            }

        }

        //Добавляем в результирующий набор данные о менеджерах, которые не имеют простых заказов, но имеют разделенные
        if ($managers_data_divided){
            foreach ($managers_data_divided as $man => $row){

                $data[$man]['group_manager'] = $row["group_manager"];
                $data[$man]['confirmed_orders'] = $row["confirmed_root_divided_orders"];
                $data[$man]['confirmed_orders_amount'] = floatval($row["confirmed_root_divided_orders_payments"]) + floatval($row["all_child_divided_orders_payments"]);

            }
        }




        // учитывая Присоедененные
        //Получаем заказы, которые были созданы в указанный период, И были присоединены к другим существующим заказам
        // Их оборот не учитываем, т.к оборот присоединенного заказа принадлежит заказу, к которому присоединили
       //Группируем Присоединенные заказы по менеджеру главного заказа

        $data_merged = AnalyticsOrdersRepository::getChildMergedOrdersInConfirmedDb($since, $to, $sinceSeason, $toSeason, $this->statuses_not_in_confirmed);

        //Сравниваем, есть ли менеджеры, которые не имеют простых заказов, но имеют присоединенные
        $managers_data_merged = array_diff_key($data_merged, $data);

        //Добавляем к каждому менеджеру количество присоединенных заказов
        foreach ($data as $man => $row){

            if($data_merged[$man]){
                $data[$man]['confirmed_orders'] = intval($row['confirmed_orders']) + intval($data_merged[$man]['merged_confirmed_orders']);

            }

        }

        //Добавляем в результирующий набор данные о менеджерах, которые не имеют простых заказов, но имеют присоединенные
        if ($managers_data_merged){
            foreach ($managers_data_merged as $man => $row){

                $data[$man]['group_manager'] = $row["group_manager"];
                $data[$man]['confirmed_orders'] = $row["merged_confirmed_orders"];

            }
        }

        //Считаем и добавляем Средний чек
        foreach ($data as $man => $row){

           $data[$man]['avg_check'] = round(floatval($row['confirmed_orders_amount']) / intval($row['confirmed_orders']), 2);

        }



        return $data;
    }

    /**
     * удаленные заказы
     * Которые принадлежат одному сезону, сумма товаров больше нуля. Созданы в текущем сезоне
     * не количество удалений, а количество удаленных заказов (т.е. если заказ удален дважды, то количество удаленных заказов = 1)
     * Группируем по менеджеру, который закреплен заказамаи
     * @return array|bool
     */
    public function getCanceledOrders()
    {
        //Сначала проверяем берем заказы, которые не были 2 и более раз удалены - Простые заказы
        $data = AnalyticsOrdersRepository::getCanceledOrdersDb($this->getSinceDate(), $this->getToDate(), $this->getSeasonSinceDate(), $this->getSeasonToDate());

        //Берем заказы, которые были 2 и более раз удалены(восстановлены - потом удалены). По таблице orders_changes - дублированные
        $data_duplicated = AnalyticsOrdersRepository::getCanceledOrdersDuplicatedDb($this->getSinceDate(), $this->getToDate(), $this->getSeasonSinceDate(), $this->getSeasonToDate());

        //Сравниваем, есть ли менеджеры, которые не имеют простых заказов, но имеют дублированные
        $managers_data_duplicated = array_diff_key($data_duplicated, $data);

        //Добавляем к каждому менеджеру количество дублированных удаленных заказов
        foreach ($data as $man => $row){

            if($data_duplicated[$man]){
                $data[$man]['canceled_orders'] = intval($row['canceled_orders']) + intval($data_duplicated[$man]['duplicated_canceled_orders']);
                $data[$man]['canceled_orders_amount'] = floatval($row["canceled_orders_amount"]) + floatval($data_duplicated[$man]["duplicated_canceled_orders_amount"]);

            }

        }

       //Добавляем в результирующий набор данные о менеджерах, которые не имеют простых удаленных заказов, но имеют дублированных удаленных заказов
       if ($managers_data_duplicated){
            foreach ($managers_data_duplicated as $man => $row){

                $data[$man]['group_manager'] = $row["group_manager"];
                $data[$man]['canceled_orders'] = $row["duplicated_canceled_orders"];
                $data[$man]['canceled_orders_amount'] = $row["duplicated_canceled_orders_amount"];

            }
        }

        return $data;
    }


    /**
     * Востановленные: востановленные из незавершенных + востановленные из удаленных
     * Которые созданы в текущем сезоне, сумма товаров больше нуля.
     * Которые на момент отчета являются живыми, т.е не удаленными.
     * Группируем по менеджеру, который закреплен за заказамаи
     * @return array|bool
     */
    public function getRestoredOrders()
    {
        //Сначала проверяем берем заказы, которые не были 2 и более раз восстановлены- Простые заказы
        $data = AnalyticsOrdersRepository::getRestoredOrdersOriginalDb($this->getSinceDate(), $this->getToDate(), $this->getSeasonSinceDate(), $this->getSeasonToDate());

        //Берем заказы, которые были 2 и более раз восстановлены (восстановлены - потом удалены - потом опять восстановлены. Менеджеры могут такое творить). По таблице orders_changes - дублированные
        $data_duplicated = AnalyticsOrdersRepository::getRestoredOrdersDuplicatedDb($this->getSinceDate(), $this->getToDate(), $this->getSeasonSinceDate(), $this->getSeasonToDate());

        //Сравниваем, есть ли менеджеры, которые не имеют простых заказов, но имеют дублированные
        $managers_data_duplicated = array_diff_key($data_duplicated, $data);

        //Добавляем к каждому менеджеру количество дублированных восстановленные  заказов
        foreach ($data as $man => $row){

            if($data_duplicated[$man]){
                $data[$man]['restored_orders'] = intval($row['restored_orders']) + intval($data_duplicated[$man]['duplicated_restored_orders']);
                $data[$man]['restored_orders_amount'] = floatval($row["restored_orders_amount"]) + floatval($data_duplicated[$man]["duplicated_restored_orders_amount"]);

            }

        }

       //Добавляем в результирующий набор данные о менеджерах, которые не имеют простых восстановленных заказов, но имеют дублированных восстановленные заказов
       if ($managers_data_duplicated){
           foreach ($managers_data_duplicated as $man => $row){

               $data[$man]['group_manager'] = $row["group_manager"];
               $data[$man]['restored_orders'] = $row["duplicated_restored_orders"];
               $data[$man]['restored_orders_amount'] = $row["duplicated_restored_orders_amount"];

           }
       }

        return $data;
    }


    /**
     * Заказы через телефон
     *  заказы созданные через админку, не содержат сумму гарантии
     *
     * Группируем по менеджеру, который закреплен за заказамаи
     *
     * @return array|bool
     */
    public function getPhoneOrders()
    {
        $since = $this->getSinceDate();
        $to = $this->getToDate();
        //Получаем заказы, созданные через админку и оборот по ним, учитывая Сумму со скидкой или просто Сумму
        // Допустим, есть заказ А  - Оборот 1000 грн.
        //От него отделяется заказ Б. Оборот 200 грн.
        // заказы через телефон = 1; оборот 1000

        //Получаем только заказы, которые были созданы через админку - Простые.
        // Не учитываются отделенные заказы (Корневые и Дочерние) и их ОБОРОТ

        $data = AnalyticsOrdersRepository::getPhoneOrdersRawDb($since, $to);

        // Получаем отделенные Телефонные заказы (Корневые и Дочерние)и их ОБОРОТ
        $data_divided = AnalyticsOrdersRepository::getPhoneDividedOrdersWithChildrenDb($since, $to);

        //Сравниваем, есть ли менеджеры, которые не имеют простых заказов, но имеют разделенные
        $managers_data = array_diff_key($data_divided, $data);


        //Добавляем к каждому менеджеру Главные разделенные заказы (штуки заказов) и оборот Главного + оборот дочернего
        //Вычитаем сумму гарантии Простых заказов, сумму гарантии Главных разделенных заказов и сумму гарантии Дочерних закахов

        foreach ($data as $man => $row){

            $data[$man]['phone_orders_amount'] = floatval($row['phone_orders_amount']) - floatval($row['phone_orders_guarantee']);

            if($data_divided[$man]){
                $data[$man]['phone_orders'] = intval($row['phone_orders']) + intval($data_divided[$man]['phone_root_divided_orders']);
                $data[$man]['phone_orders_amount'] = floatval($row['phone_orders_amount'])
                                                        + floatval($data_divided[$man]['phone_root_divided_orders_payments'])
                                                        + floatval($data_divided[$man]['all_phone_child_divided_orders_payments'])
                                                        - floatval($row['phone_orders_guarantee'])
                                                        - floatval($data_divided[$man]['phone_root_divided_orders_guarantee'])
                                                        - floatval($data_divided[$man]['all_child_divided_orders_guarantee'])
                ;
            }

        }

        //Добавляем в результирующий набор данные о менеджерах, которые не имеют Телефонные простых заказов, но имеют Телефонные разделенные
        if ($managers_data){
            foreach ($managers_data as $man => $row){
               $data[$man]['group_manager'] = $row["group_manager"];
               $data[$man]['phone_orders'] = intval($row["phone_root_divided_orders"]);
               $data[$man]['phone_orders_amount'] = floatval($row["phone_root_divided_orders_payments"])
                                                            + floatval($row["all_phone_child_divided_orders_payments"])
                                                            - floatval($row['phone_root_divided_orders_guarantee'])
                                                            - floatval($row['all_child_divided_orders_guarantee'])
                    ;

            }
        }

        return $data;
    }


    /**
     * Возвращает заказы созданные через админку и сумму гарантии.
     * Если заказ создан НЕ через админку, то он не учитывается
     * Заказы Содержат сумму гарантии или их Дочерние заказы, которые содержат сумму гарантии.
     *
     * Группируем по менеджеру, который закреплен за заказамаи
     *
     *
     * @return array|bool
     */
    public function getGuaranteeOrders()
    {
        $since = $this->getSinceDate();
        $to = $this->getToDate();

        //Получаем только заказы, которые были созданы через админку и содержат сумму гарантии- Простые.
        // Не учитываются отделенные заказы (Корневые и Дочерние) и их гарантии
        $data = AnalyticsOrdersRepository::getGuaranteeOrdersRawDb($since, $to);

        // Получаем отделенные заказы (Корневые и Дочерние)и их гарантии
        $data_divided = AnalyticsOrdersRepository::getGuaranteeDividedOrdersWithChildrenDb($since, $to);

        //Сравниваем, есть ли менеджеры, которые не имеют простых заказов, но имеют разделенные
        $managers_data = array_diff_key($data_divided, $data);

        //Добавляем к каждому менеджеру Главные разделенные заказы (штуки заказов) и оборот Главного + оборот дочернего

        foreach ($data as $man => $row){

            if($data_divided[$man]){
                $data[$man]['guarantee_orders'] = intval($row['guarantee_orders']) +intval( $data_divided[$man]['guarantee_root_divided_orders']);
                $data[$man]['guarantee_orders_amount'] = floatval($row['guarantee_orders_amount'])
                                                            + floatval($data_divided[$man]['guarantee_root_divided_payments'])
                                                            + floatval($data_divided[$man]['all_guarantee_child_divided_orders'])
                ;
            }

        }

        //Добавляем в результирующий набор данные о менеджерах, которые не имеют простых заказов, но имеют  разделенные
        if ($managers_data){
            foreach ($managers_data as $man => $row){
               $data[$man]['group_manager'] = $row["group_manager"];
               $data[$man]['guarantee_orders'] = intval($row["guarantee_root_divided_orders"]);
               $data[$man]['guarantee_orders_amount'] = floatval($row["guarantee_root_divided_payments"])
                                                        + floatval($row["all_guarantee_child_divided_orders"])
                    ;
            }
        }

        return $data;

    }


    /**Получаем заказы, которые были оформлены По Купону и оборот по ним.
     * И заказы, присоединенненые к которым содержали купон
     * И заказы, которые были офрмлены по купону, впоследствии разделенные
     * Группируем по менеджеру, который закреплен за заказамаи
    *
    * Считаем оборот.
    *   Если есть сумма со скидкой - берем ее.
    *
    *
     * */
    public function getCouponOrders()
    {
        $since = $this->getSinceDate();
        $to = $this->getToDate();

        //Получаем только заказы, которые имеют купоны
        // Не учитываются отделенные заказы (Корневые и Дочерние)  и присоединенные.
        // Присоединеннеые не учитываются, так как они удаляются с заказов
        $data = AnalyticsOrdersRepository::getCouponOrdersRawDb($since, $to);

        //Получаем заказы, к которым были присоединены заказы, которые имеют купон
        //Родительский заказ (к которому присоединили другой заказ с купоном) не имеет отметки в таблице `coupons`.
        // По-этому эти данные присоединяем к общим показателям
        $data_merged = AnalyticsOrdersRepository::getCouponOrdersWithMergedRawDb($since, $to);


        //Сравниваем, есть ли менеджеры, которые не имеют простых заказов, но имеют заказы, к которым были присоединены заказы c купон
        $managers_data_merged = array_diff_key($data_merged, $data);

        //Добавляем к каждому менеджеру заказы, к которым были присоединены заказы c купоном и их обороты

        foreach ($data as $man => $row){

            if($data_merged[$man]){
                $data[$man]['coupon_orders'] = intval($row['coupon_orders']) +intval( $data_merged[$man]['coupon_merged_orders']);
                $data[$man]['coupon_orders_amount'] = floatval($row['coupon_orders'])
                    + floatval($data_merged[$man]['coupon_merged_orders_payments'])

                ;
            }

        }

        //Добавляем в результирующий набор данные о менеджерах, которые не имеют простых заказов, но имеют  заказы к которым были присоединены заказы c купоном и их обороты
        if ($managers_data_merged){
            foreach ($managers_data_merged as $man => $row){
                $data[$man]['group_manager'] = $row["group_manager"];
                $data[$man]['coupon_orders'] = intval($row["coupon_merged_orders"]);
                $data[$man]['coupon_orders_amount'] = floatval($row["coupon_merged_orders_payments"])

                ;
            }
        }

        // Получаем заказы, которые были разделены и которые имеют купоны
        // Тут же храняться обороты всех Дочерних заказов
        $data_divided = AnalyticsOrdersRepository::getCouponDividedOrdersWithChildrenDb($since, $to);


        //Сравниваем, есть ли менеджеры, которые не имеют простых заказов, но имеют разделенные
        $managers_data_divided = array_diff_key($data_divided, $data);

        //Добавляем к каждому менеджеру Главные разделенные заказы (штуки заказов) и оборот Главного + оборот дочернего

        foreach ($data as $man => $row){

            if($data_divided[$man]){
                $data[$man]['coupon_orders'] = intval($row['coupon_orders']) +intval( $data_divided[$man]['coupon_divided_root_orders']);
                $data[$man]['coupon_orders_amount'] = floatval($row['coupon_orders_amount'])
                    + floatval($data_divided[$man]['coupon_divided_root_orders_payments'])
                    + floatval($data_divided[$man]['all_coupon_child_divided_orders'])
                ;
            }

        }

        //Добавляем в результирующий набор данные о менеджерах, которые не имеют простых заказов, но имеют  разделенные
        if ($managers_data_divided){
            foreach ($managers_data_divided as $man => $row){
                $data[$man]['group_manager'] = $row["group_manager"];
                $data[$man]['coupon_orders'] = intval($row["coupon_divided_root_orders"]);
                $data[$man]['coupon_orders_amount'] = floatval($row["coupon_divided_root_orders_payments"])
                    + floatval($row["all_coupon_child_divided_orders"])
                ;
            }
        }



        return $data;

    }

    /**
     * Допродажи менеджеров
     * @return array|bool
     */
    public function getAttractedAmount()
    {
        $data = AnalyticsOrdersRepository::getAttractedAmountDb($this->getSinceDate(), $this->getToDate(), $this->getSeasonSinceDate(), $this->getSeasonToDate());

        return $data;

    }

}