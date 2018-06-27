<?php

/**
 * Class Category
 */
class Category
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var int
     */
    private $parentId;

    /**
     * @var string
     */
    private $name;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     *
     * @return Category
     */
    public function setId($id)
    {
        if($id > 0){

            $this->id = $id;
        }

        return $this;
    }

    /**
     * @return int
     */
    public function getParentId()
    {
        return $this->parentId;
    }

    /**
     * @param int $parentId
     *
     * @return Category
     */
    public function setParentId($parentId)
    {
        if($parentId > 0){

            $this->parentId = $parentId;
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return Category
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }
}
