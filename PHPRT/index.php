<?php

//Автозагрузка классов
spl_autoload_register(function($class){
	require dirname(__FILE__).'/'.$class.'.php';
});
//Регистрируем функцию по окончанию работы приложения
register_shutdown_function('Catcher::shutdown');

PHPRT::app()->start($argv);