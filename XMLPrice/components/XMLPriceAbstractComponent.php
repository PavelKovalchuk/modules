<?php
/**
 * Created by PhpStorm.
 * User: pkovalchuk
 * Date: 21.12.2017
 * Time: 14:51
 */

abstract class XMLPriceAbstractComponent
{

    protected $categoriesId = [];

    protected $children = array();

    protected $db_manager;

    protected $numberChildren;

    abstract public function setChildren();


    public function __construct($db_manager)
    {
        $this->setDbManager($db_manager);

    }

    public function getChild($index)
    {
        if (! isset($this->getChildren()[$index])){
            return false;
        }
        return $this->getChildren()[$index];
    }


    public function getChildren()
    {
        if($this->children){
            return $this->children;
        }

        return false;
    }


    protected function addChild($index, $child )
    {
        $this->children[$index] = $child;
    }

    public function setNumberChildren($number_children)
    {
        $this->numberChildren = intval($number_children);

        return true;
    }

    public function getNumberChildren()
    {
        if($this->numberChildren){
            return $this->numberChildren;
        }

        return false;
    }


    public function setCategoriesId($categoriesId)
    {
        $this->categoriesId = $categoriesId;

        return $this;
    }

    public function getCategoriesId()
    {
        if($this->categoriesId){
            return $this->categoriesId;
        }

        return false;
    }

    /**
     * @return mixed
     */
    public function getDbManager()
    {
        return $this->db_manager;
    }

    /**
     * @param mixed $db_manager
     */
    public function setDbManager(XMLPriceDBRepository $db_manager)
    {
        $this->db_manager = $db_manager;
    }


}