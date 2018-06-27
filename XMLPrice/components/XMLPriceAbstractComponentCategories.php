<?php
/**
 * Created by PhpStorm.
 * User: pkovalchuk
 * Date: 21.12.2017
 * Time: 14:43
 */

require_once(XMLPrice_COMPONENTS_DIR . 'XMLPriceAbstractComponent.php');
require_once(XMLPrice_MODELS_DIR . 'Category.php');

abstract class XMLPriceAbstractComponentCategories extends XMLPriceAbstractComponent
{
    protected $parentsId = [];

    /**
     * @param XMLPriceDBCategoriesRepositoryAbstract $offers_request
     * @return bool
     */
    protected function performSettingParents( XMLPriceDBCategoriesRepositoryAbstract $offers_request)
    {
        if(count($this->getParentsId()) > 0){

            $data_parents = $this->getDbManager()->getCategoriesFromDb( $offers_request );

            if(!$data_parents){
                return false;
            }

            $this->performSettingChildren($data_parents);

        }

    }

    protected function performSettingChildren($data)
    {
        if(!$data){
            return false;
        }

        foreach ($data as $key => $value){

            if(intval($value["parent_id"]) > 0){

                $this->setParentsId($value["parent_id"]);

            }

            $this->addChild( intval($value["categories_id"]), (new Category())
                ->setId(intval($value["categories_id"]))
                ->setName($value["categories_name"])
                ->setParentId(intval($value["parent_id"]))
            );

        }
    }


    /**
     * @return array
     */
    protected function getParentsId()
    {
        return $this->parentsId;
    }

    /**
     * @param array $parentsId
     */
    protected function setParentsId($parentsId)
    {
        $this->parentsId[] = $parentsId;
    }

}