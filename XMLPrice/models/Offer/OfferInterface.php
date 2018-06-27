<?php

/**
 * Interface Offer
 */
interface OfferInterface
{
    /**
     * @return string
     */
    public function getId();

    /**
     * @return string
     */
    public function getType();

    /**
     * @return bool
     */
    public function isAvailable();

    /**
     * @return array
     */
    public function getParams();

    /**
     * @return array
     */
    public function toArray();

    /**
     * @return bool
     */
    public function isAvailableAtrrNeeded();
}
