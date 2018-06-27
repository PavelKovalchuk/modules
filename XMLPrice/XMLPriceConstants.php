<?php
/**
 * Created by PhpStorm.
 * User: pkovalchuk
 * Date: 26.01.2018
 * Time: 16:43
 */

define('XMLPrice_ROOT_DIR', DIR_FS_INC . 'XMLPrice/');
define('XMLPrice_MODELS_DIR', XMLPrice_ROOT_DIR . 'models/');
define('XMLPrice_OFFER_MODEL_DIR', XMLPrice_MODELS_DIR . 'Offer/');
define('XMLPrice_COMPONENTS_DIR', XMLPrice_ROOT_DIR . 'components/');
define('XMLPrice_CONTROLLERS_DIR', XMLPrice_ROOT_DIR . 'controllers/');
define('XMLPrice_REPOSITORY_DIR', XMLPrice_ROOT_DIR . 'dbRepository/');

define('XMLPrice_BASE_URL', HTTP_SERVER );

//ROZETKA CONSTANTS
if(!defined('ROZETKA_ROOT_DIR')){
    define('ROZETKA_ROOT_DIR', XMLPrice_ROOT_DIR . 'Rozetka/');
}

if(!defined('ROZETKA_MODELS_DIR')){
    define('ROZETKA_MODELS_DIR', ROZETKA_ROOT_DIR . 'models/');
}

if(!defined('ROZETKA_COMPONENTS_DIR')){
    define('ROZETKA_COMPONENTS_DIR', ROZETKA_ROOT_DIR . 'components/');
}

if(!defined('ROZETKA_REPOSITORY_DIR')){
    define('ROZETKA_REPOSITORY_DIR', ROZETKA_ROOT_DIR . 'dbRepository/');
}

//RONTAR CONSTANTS
if(!defined('RONTAR_ROOT_DIR')){
    define('RONTAR_ROOT_DIR', XMLPrice_ROOT_DIR . 'Rontar/');
}

if(!defined('RONTAR_MODELS_DIR')){
    define('RONTAR_MODELS_DIR', RONTAR_ROOT_DIR . 'models/');
}

if(!defined('RONTAR_COMPONENTS_DIR')){
    define('RONTAR_COMPONENTS_DIR', RONTAR_ROOT_DIR . 'components/');
}

if(!defined('RONTAR_REPOSITORY_DIR')){
    define('RONTAR_REPOSITORY_DIR', RONTAR_ROOT_DIR . 'dbRepository/');
}

if(!defined('RONTAR_CONTROLLERS_DIR')){
    define('RONTAR_CONTROLLERS_DIR', RONTAR_ROOT_DIR . 'controllers/');
}

//YOTTOS CONSTANTS
if(!defined('YOTTOS_ROOT_DIR')){
    define('YOTTOS_ROOT_DIR', XMLPrice_ROOT_DIR . 'Yottos/');
}

if(!defined('YOTTOS_MODELS_DIR')){
    define('YOTTOS_MODELS_DIR', YOTTOS_ROOT_DIR . 'models/');
}

if(!defined('YOTTOS_COMPONENTS_DIR')){
    define('YOTTOS_COMPONENTS_DIR', YOTTOS_ROOT_DIR . 'components/');
}

if(!defined('YOTTOS_REPOSITORY_DIR')){
    define('YOTTOS_REPOSITORY_DIR', YOTTOS_ROOT_DIR . 'dbRepository/');
}

if(!defined('YOTTOS_CONTROLLERS_DIR')){
    define('YOTTOS_CONTROLLERS_DIR', YOTTOS_ROOT_DIR . 'controllers/');
}

//Googgle CONSTANTS
if(!defined('GOOGLE_ROOT_DIR')){
    define('GOOGLE_ROOT_DIR', XMLPrice_ROOT_DIR . 'Google/');
}

if(!defined('GOOGLE_HELPER_DIR')){
    define('GOOGLE_HELPER_DIR', GOOGLE_ROOT_DIR . 'GoogleShoppingFeedHelper/');
}

if(!defined('GOOGLE_MODELS_DIR')){
    define('GOOGLE_MODELS_DIR', GOOGLE_ROOT_DIR . 'models/');
}

if(!defined('GOOGLE_COMPONENTS_DIR')){
    define('GOOGLE_COMPONENTS_DIR', GOOGLE_ROOT_DIR . 'components/');
}

if(!defined('GOOGLE_REPOSITORY_DIR')){
    define('GOOGLE_REPOSITORY_DIR', GOOGLE_ROOT_DIR . 'dbRepository/');
}

if(!defined('GOOGLE_CONTROLLERS_DIR')){
    define('GOOGLE_CONTROLLERS_DIR', GOOGLE_ROOT_DIR . 'controllers/');
}
