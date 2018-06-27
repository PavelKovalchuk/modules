<?php

/**
 * Class Currency
 */
class Currency
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $rate;

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $id
     *
     * @return Currency
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return string
     */
    public function getRate()
    {
        return $this->rate;
    }

    /**
     * @param string $rate
     *
     * @return Currency
     */
    public function setRate($rate)
    {
        $this->rate = $rate;

        return $this;
    }
}
