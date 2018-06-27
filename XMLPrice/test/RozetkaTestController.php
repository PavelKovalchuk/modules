<?php
/**
 * Created by PhpStorm.
 * User: pkovalchuk
 * Date: 07.02.2018
 * Time: 16:47
 */

//require_once(DIR_FS_INC . 'XMLPrice/XMLPriceConstants.php');

require_once(DIR_FS_INC . 'XMLPrice/Rozetka/controllers/RozetkaController.php');
require_once(ROZETKA_COMPONENTS_DIR . 'RozetkaComponentCategories.php');
require_once(ROZETKA_COMPONENTS_DIR . 'RozetkaComponentOffers.php');

//For test
require_once(XMLPrice_ROOT_DIR . 'test/XMLPriceTestControllerInterface.php');
require_once(XMLPrice_ROOT_DIR . 'test/XMLPriceTestControllerTrait.php');

class RozetkaTestController extends RozetkaController implements XMLPriceTestControllerInterface
{
    use XMLPriceTestControllerTrait;

    public function __construct()
    {

        parent::__construct();

        $this->initSessionsOptions();

    }

    public function testUrlsAction($dataInputArr = false)
    {
        if(!$dataInputArr){
            return false;
        }

        if(!$this->setCounterOffset()){
            echo 'Непридвиденный Конец';
            return;
        }

        if( $this->checkForArray($dataInputArr) == false){
            echo 'Ошибка инициализации параметров';
            return;
        }

        $is_input_data_ready = $this->initDataToTest($dataInputArr);

        if(!$is_input_data_ready){
            echo 'Ошибка обработки параметров';
            return;
        }

        if(!$this->getResults()){
            echo '<br>Конец тестирования URL <br>' . get_class($this);
        }

        return true;

    }

    public function initDataToTest($dataInputArr = false)
    {
        if(!$dataInputArr){
            return false;
        }

        return parent::initData($dataInputArr);
    }


}