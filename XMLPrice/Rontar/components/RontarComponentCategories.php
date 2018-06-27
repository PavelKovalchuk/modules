<?php
/**
 * Created by PhpStorm.
 * User: pkovalchuk
 * Date: 07.02.2018
 * Time: 12:16
 */

require_once(XMLPrice_COMPONENTS_DIR . 'XMLPriceAbstractComponentCategories.php');
require_once(RONTAR_REPOSITORY_DIR . 'RontarCategoriesRepository.php');

class RontarComponentCategories extends XMLPriceAbstractComponentCategories
{

    public function __construct($db_manager)
    {

        parent::__construct($db_manager);

    }

    public function setChildren()
    {
        $offers_request = new RontarCategoriesRepository();

        $data = $this->getDbManager()->getCategoriesFromDb( $offers_request );

        if(!$data){
            return false;
        }

        $this->performSettingChildren($data);

        $this->performSettingParents($offers_request);

        return $this;

    }
}