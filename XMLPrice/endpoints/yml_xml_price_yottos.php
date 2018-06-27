<?php
/**
 * Created by PhpStorm.
 * User: pkovalchuk
 * Date: 02.04.2018
 * Time: 12:17
 */


require_once('includes/application_top.php');
require_once(DIR_FS_INC . 'XMLPrice/Yottos/controllers/YottosController.php');

$controller = new YottosController();

$controller->getFileAction(false);