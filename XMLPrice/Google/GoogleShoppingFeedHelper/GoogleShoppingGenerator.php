<?php

require_once(GOOGLE_HELPER_DIR . 'Feed.php');

class GoogleShoppingGenerator
{
    /**
     * Feed container
     * @var Feed
     */
    public static $container = null;

    /**
     * Return feed container
     * @return Feed
     */
    public static function container()
    {
        if (is_null(static::$container)) {
            static::$container = new Feed;
        }

        return static::$container;
    }

    /**
     * @param string $name
     * @param array $arguments
     * @return mixed
     */
    public static function __callStatic($name, $arguments)
    {
        return call_user_func_array(array(static::container(), $name), $arguments);
    }

    public static function generate(ShopInfo $shopInfo, $currencies, $offers)
    {
        self::title($shopInfo->getName());
        self::link($shopInfo->getUrl());
        self::description($shopInfo->getDescription());
        self::setIso4217CountryCode($currencies[0]->getId());

        foreach( $offers as $offer ) {

            $item = self::createItem();
            $item->id($offer->getId());
            $item->title($offer->getName());
            $item->description($offer->getDescription());
            $item->brand('GreenMarket');
            $item->price($offer->getPrice());
            $item->identifier_exists('no');
            $item->link($offer->getUrl());
            $item->image_link($offer->getPictures()[0]);
            $item->condition('new');
            $item->availability($offer->getAvailable());
        }

        // boolean value indicates output to browser
        self::asRss(true);
    }
}
