<?php
/**
 * Created by PhpStorm.
 * User: pkovalchuk
 * Date: 03.01.2018
 * Time: 12:12
 */

/* Rozetka classes  */
require_once('includes/application_top.php');
require_once(DIR_FS_INC . 'XMLPrice/Rozetka/controllers/RozetkaController.php');
require_once(DIR_FS_CATALOG . 'includes/configure.php');
/* Rozetka classes  */

$dataInputArr = [

    'categories_id' => [

        '663' => [
            'sellers_id' => ['78'],
            //'category_rozetka_name' => 'Садовый декор',
            'addition_name_param'=>[
                'Производитель' => '',
            ],
        ],

        '676' => [
            'sellers_id' => ['88'],
            //'category_rozetka_name' => 'Вазоны пластиковые',
            'addition_name_param'=>[
                'Производитель'=> '',
                'Объём, л'=> '',
                'Цвет'=> '',
            ]

        ],

        '651' => [
           //Марина (Хвойные)
          'sellers_id' => ['11'],
          //'category_rozetka_name' => 'Декоративные деревья',
          'addition_name_param'=>[
              'Размер посадочного материала, В/Ш' => '',
              'Размер посадочного материала' => '',
              'Упаковка' => '',
          ],
        ],

        '652' => [
            //Марина (Хвойные)
            'sellers_id' => ['11'],
            //'category_rozetka_name' => 'Декоративные кустарники',
            'addition_name_param'=>[
                'Размер посадочного материала, В/Ш' => '',
                'Размер посадочного материала' => '',
                'Упаковка' => '',
            ],
        ],

        '605' => [
            //Марина (Хвойные)
            'sellers_id' => ['11'],
            //'category_rozetka_name' => 'Магнолия',
            'addition_name_param'=>[
                'Размер посадочного материала, В/Ш' => '',
                'Размер посадочного материала' => '',
                'Упаковка' => '',
            ],
        ],

        '649' => [
            //Марина (Хвойные)
            'sellers_id' => ['11'],
            //'category_rozetka_name' => 'Хвойные растения',
            'addition_name_param'=>[
                'Размер посадочного материала, В/Ш' => '',
                'Размер посадочного материала' => '',
                'Упаковка' => '',
            ],
        ],

       /* '623' => [
            'sellers_id' => ['85'],
            //'category_rozetka_name' => 'Удобрения',
            'addition_name_param'=>[
                'Производитель' => '',
            ],
        ],*/

        /*'693' => [
            'sellers_id' => ['92'],
            //'category_rozetka_name' => 'Cуккуленты',
            'addition_name_param'=>[
                'Производитель' => '',
            ],
        ],*/
    ]

];

$controller = new RozetkaController();

$controller->getFileAction($dataInputArr, false);