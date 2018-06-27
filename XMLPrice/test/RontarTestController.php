<?php
/**
 * Created by PhpStorm.
 * User: pkovalchuk
 * Date: 07.02.2018
 * Time: 16:47
 */

require_once(DIR_FS_INC . 'XMLPrice/Rontar/controllers/RontarController.php');
require_once(RONTAR_COMPONENTS_DIR . 'RontarComponentCategories.php');
require_once(RONTAR_COMPONENTS_DIR . 'RontarComponentOffers.php');

//For test
require_once(XMLPrice_ROOT_DIR . 'test/XMLPriceTestControllerInterface.php');
require_once(XMLPrice_ROOT_DIR . 'test/XMLPriceTestControllerTrait.php');

class RontarTestController extends RontarController implements XMLPriceTestControllerInterface
{
    use XMLPriceTestControllerTrait;

    public function __construct()
    {

        parent::__construct();

        $this->initSessionsOptions();

    }

    public function testUrlsAction()
    {

        if(!$this->setCounterOffset()){
            echo 'Непридвиденный Конец';
            return;
        }

        $is_input_data_ready = $this->initDataToTest();

        if(!$is_input_data_ready){
            echo 'Ошибка обработки параметров';
            return;
        }

        if(!$this->getResults()){
            echo '<br>Конец тестирования URL <br>' . get_class($this);
        }

        return true;

    }

    public function initDataToTest()
    {
        return parent::initData();
    }

}