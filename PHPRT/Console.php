<?php

class Console {
	
	/**
	 * Отобразить информацию в консоль
	 * @param string $msg
	 */
	public static function log($msg) {
		echo $msg.PHP_EOL;
	}
	
	/**
	 * Отобразить описание ошибки и завершить приложение
	 * @param string $err
	 */
	public static function error($err) {
		echo $err.PHP_EOL;
		echo 'FAILED'.PHP_EOL;
		exit;
	}

	/**
	 * Вывести данные в консоль
	 * @param mixed $data
	 */
	public static function debug($data) {
		if(is_array($data)) print_r($data);
		else echo $data.PHP_EOL;
	}
}