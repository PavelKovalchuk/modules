<?php
/**
 * Created by PhpStorm.
 * User: pkovalchuk
 * Date: 25.01.2018
 * Time: 17:17
 */

require_once(XMLPrice_COMPONENTS_DIR . 'XMLPriceAbstractComponentCategories.php');
require_once(ROZETKA_REPOSITORY_DIR . 'RozetkaCategoriesRepository.php');

class RozetkaComponentCategories extends XMLPriceAbstractComponentCategories
{

    public function __construct($db_manager)
    {

        parent::__construct($db_manager);

    }

    public function setChildren()
    {
        $offers_request = new RozetkaCategoriesRepository();

        $offers_request->setCategoriesId( $this->getCategoriesId() );

        $data = $this->getDbManager()->getCategoriesFromDb( $offers_request );

        if(!$data){
            return false;
        }

        $this->performSettingChildren($data);

        $offers_request_parents = new RozetkaCategoriesRepository();
        $parents_cats = implode(', ', array_values(array_unique($this->getParentsId())));
        $offers_request_parents->setCategoriesId($parents_cats );

        $this->performSettingParents($offers_request_parents);

        return $this;

    }

}