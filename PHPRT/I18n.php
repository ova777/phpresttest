<?php

class I18n {
	private static $default = 'ru';

	/**
	 * Возвращает фразу на необходимом языке
	 * @param string $text
	 * @return string
	 */
	public static function gettext($text) {
		$lang = PHPRT::app()->lang;
		if($lang == self::$default) return $text;
		if(false !== $data = self::getlang($lang) AND isset($data[$text])) return $data[$text];
		return $text;
	}

	/**
	 * Возвращает языковой массив
	 * @param string $lang
	 * @return bool|array
	 */
	private static function getlang($lang) {
		static $langs = [];
		if(isset($langs[$lang])) return $langs[$lang];
		
		$path = dirname(__FILE__).'/i18n/'.$lang.'.php';
		$data = false;
		if(file_exists($path)) $data = require($path);
		
		$langs[$lang] = $data;
		return $data;
	}
}