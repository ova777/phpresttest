<?php

class Types {
	private static $types = [];

	/**
	 * Возвращает данные типа
	 * @param string $name
	 * @return mixed
	 */
	public static function readType($name) {
		if(!isset(self::$types[$name])) {
			$path = System::smartSlashEnd(System::localPath(PHPRT::app()->typesPath), true).$name.'.php';
			if(!file_exists($path)) Console::error(I18n::gettext('Не найден файл').' '.$path);
			if(!is_readable($path)) Console::error(I18n::gettext('Невозможно прочитать файл').' '.$path);
			Catcher::$handle = Catcher::HANDLE_TYPE;
			self::$types[$name] = @require($path);
			Catcher::$handle = Catcher::HANDLE_DEFAULT;
		}
		return self::$types[$name];
	}
}