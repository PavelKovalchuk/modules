<?php
/**
 * Created by PhpStorm.
 * User: pkovalchuk
 * Date: 15.06.2018
 * Time: 14:47
 */


class InvoiceOrderPlaceholder
{

    private static $placeholders = [
        'customer' => 'Заказчик: ',
        'phone' => 'Телефон: ',
        'date_generated' => 'Дата: ',
        'shop_name' => 'GreenMarket',
        'shop_site' => 'www.greenmarket.com.ua',
        'invoice_title' => 'Заказ № ',
        'product_position' => '№',
        'products_model' => 'Товар',
        'products_name' => 'Наименование товара',
        'products_boxes' => 'Кол-во',
        'table_product_code' => 'Артикул',
        'products_price' => 'Цена, грн.',
        'final_price' => 'Сумма, грн.',
        'seeds_header' => 'Семена',
        'total_title_with_seeds' => 'Итого без стоимости семян: ',
        'total_title_for_seeds' => 'Остаток стоимости семян: ',
        'total_title_total' => 'Итого: ',
        'total_title_prepayment' => 'Предоплата: ',
        'total_title_discount' => 'Сумма со скидкой ',
        'total_title_guarantee' => 'Гарантия: ',
        'footer_amount_start' => '<b>Сумма прописью:</b> ',
        'footer_amount_finish' => '. Без НДС.',
        'footer_respect' => 'С уважением, администрация GreenMarket.',

    ];

    private static $table_product_maps = [
        'main' => [
            [
                'header' => 'product_position',
                'width' => '3%'
            ],
            [
                'header' => 'products_model',
                'width' => '23%'
            ],
            [
                'header' => 'products_name',
                'width' => '40%'
            ],
            [
                'header' => 'products_boxes',
                'width' => '7%'
            ],
            [
                'header' => 'products_price',
                'width' => '12%'
            ],
            [
                'header' => 'final_price',
                'width' => '15%'
            ],
        ]
    ];

    /**
     * @return array
     */
    public static function getPlaceholders()
    {
        return self::$placeholders;
    }

    /**
     * @return array
     */
    public static function getTableProductMaps()
    {
        return self::$table_product_maps;
    }

    public static function getPlaceholderByKey($key)
    {
        if(!isset(self::getPlaceholders()[$key])){
            return false;
        }

        return self::getPlaceholders()[$key];
    }

    public static function getTableProductMap($map)
    {
        if(!isset(self::getTableProductMaps()[$map])){
            return false;
        }

        return self::getTableProductMaps()[$map];
    }

}