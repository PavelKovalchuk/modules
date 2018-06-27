<?php
/**
 * Created by PhpStorm.
 * User: pkovalchuk
 * Date: 07.02.2018
 * Time: 13:38
 */

require_once(DIR_FS_INC . 'XMLPrice/XMLPriceConstants.php');
require_once(RONTAR_CONTROLLERS_DIR . 'RontarController.php');
require_once(YOTTOS_COMPONENTS_DIR . 'YottosComponentCategories.php');
require_once(YOTTOS_COMPONENTS_DIR . 'YottosComponentOffers.php');

/**
 * Class YottosController
 * Модуль отнаследован от Rontar, за исключением ip адресов.
 * Если нужно внести изменения для Yottos - в директории Yottos
 * создать необходимый файл и\или метод , и в нем править.
 * НЕ ИЗМЕНЯТЬ Rontar ДЛЯ ЦЕЛЕЙ ИЗМЕНЕНИЯ Yottos!!!!!
 *
 * Точка получения XML - [домен]/yml_xml_price_yottos.php
 */
class YottosController extends RontarController
{
    protected $allowedIp = [
        '212.113.34.130',
        '212.113.34.131',
        '212.113.34.132',
        '212.113.34.133',
        '212.113.34.134',
        '212.113.34.135',
        '212.113.34.136',
        '212.113.34.137',
        '212.113.34.138',
        '212.113.34.139',
        '212.113.34.140',
        '212.113.34.141',
        '212.113.34.142',
        '212.113.34.143',
        '212.113.34.144',
        '212.113.34.145',
        '212.113.34.146',
        '212.113.34.147',
        '212.113.34.148',
        '212.113.34.149',
        '212.113.34.150',
        '212.113.34.151',
        '212.113.34.152',
        '212.113.34.153',
        '212.113.34.154',
        '212.113.34.155',
        '212.113.34.156',
        '95.69.249.86',
        '37.57.27.229',

        '193.243.156.26',
        '127.0.0.1'
    ];
    /**
     * @param astring $categoriesId
     */
    protected function setCategoriesCollection($categoriesId)
    {
        $this->categoriesCollection = (new YottosComponentCategories($this->getDbManager()))->setCategoriesId($categoriesId)->setChildren();
    }

    /**
     * @param string $categoriesId
     */
    protected function setOffersCollection($categoriesId = false)
    {

        $this->offersCollection = (new YottosComponentOffers($this->getDbManager()))->setChildren();
    }

}