<?php

class Catcher {
	public static $handle = 0;
	const HANDLE_DEFAULT = 0;
	const HANDLE_CONFIG = 1;
	const HANDLE_SCENARIO = 2;
	const HANDLE_TYPE = 3;

	/**
	 * Выполняется по завершению выполнения приложения (по ошибке). Отображает информацию об ошибке
	 */
	public static function shutdown() {
		$error = error_get_last();
		if(!$error) return;
		$ignore = E_WARNING | E_NOTICE | E_USER_WARNING | E_USER_NOTICE | E_STRICT | E_DEPRECATED | E_USER_DEPRECATED;
		if (($error['type'] & $ignore) == 0) {
			switch(self::$handle) {
				case self::HANDLE_CONFIG: $msg = I18n::gettext('Ошибка в файле конфигурации на строке').' '.$error['line'].': '.$error['message']; break;
				case self::HANDLE_SCENARIO: $msg = I18n::gettext('Ошибка в файле сценария').' '.basename($error['file']).' '.I18n::gettext('на строке').' '.$error['line'].': '.$error['message']; break;
				case self::HANDLE_TYPE: $msg = I18n::gettext('Ошибка в файле типа').' '.basename($error['file']).' '.I18n::gettext('на строке').' '.$error['line'].': '.$error['message']; break;
				default: $msg = I18n::gettext('Ошибка в файле').' '.$error['file'].' '.I18n::gettext('на строке').' '.$error['line'].': '.$error['message'];
			}
			Console::error($msg);
		}
	}
}