<?php

class Response {
	/**
	 * Проверить полученные данные
	 * @param mixed $data
	 * @param mixed $response
	 */
	public static function checkResponse($data, $response) {
		//Ищем и заменяем переменные (PHPRT::results)
		$response = System::arrRecMap($response, function($v){
			if(false !== $str = System::detectConst(PHPRT::_RESULTS, $v)) return PHPRT::app()->getResult($str);
			return $v;
		});
		
		if(true !== $err = self::compare($data, $response))
			//Response => path1 => path2: Error_message
			Console::error(implode(' => ', array_merge(['Response'], $err[0])).': '.$err[1]);
	}

	/**
	 * Выполняет проверку полученных данных по ожидаемым
	 * @param mixed $data
	 * @param mixed $ideal
	 * @param array $path
	 * @return array|bool
	 */
	private static function compare($data, $ideal, $path = []) {
		//Проверяем на соответствие PHPRT::assign
		if(false !== $assignId = System::detectConst(PHPRT::_ASSIGN, $ideal)) {
			$assign = PHPRT::app()->getAssign($assignId);
			if(isset($assign['type'])) {
				if(true !== $res = self::compare($data, $assign['type'], array_merge($path, ['PHPRT::assign[type]']))) {
					return $res;
				}
			}
			if(isset($assign['expression'])) {
				if(!PHPRT::app()->checkExpression($assign['expression'], $data)) 
					return [array_merge($path, ['PHPRT::assign[expression]']), '('.$assign['expression'].') === false'];
			}
			if(isset($assign['values'])) {
				if(!is_array($data)) return [array_merge($path, ['PHPRT::assign[values]']), I18n::gettext('Получен тип').' '.self::getType($data).', '.I18n::gettext('ожидается').' array'];
				foreach($data as $dk => $dvalue) {
					if(true !== $res = self::compare($dvalue, $assign['values'], array_merge($path, ['PHPRT::assign[values]', $dk]))) return $res;
				}
			}
			if(isset($assign['match'])) {
				if(1 !== @preg_match($assign['match'], $data)) 
					return [array_merge($path, ['PHPRT::assign[match]']), I18n::gettext('Не соответствует шаблону')];
			}
			return true;
		}
		
		//Проверяем на соответствие PHPRT::type('type_name')
		if(false !== $stype = System::detectConst(PHPRT::_SELFTYPE, $ideal)) {
			$path[] = "PHPRT::type('".$stype."')";
			$ideal = Types::readType($stype);
		}
		
		//Проверяем на соответствие простым типам (PHPRT::TYPE_...)
		if(false !== $ctypes = self::compareTypes($data, $ideal)) {
			if(true === $ctypes) return true;
			return [$path, $ctypes];
		}
		
		if(is_array($ideal)) {
			if(!is_array($data)) return [$path, I18n::gettext('Получен тип').' '.self::getType($data).', '.I18n::gettext('ожидается').' array'];
			foreach($ideal as $k=>$v) {
				if(!isset($data[$k])) return [$path, I18n::gettext('В массиве не найден ключ').' '.$k];
				if(true !== $err = self::compare($data[$k], $v, array_merge($path, [$k]))) return $err;
			}
			return true;
		}
		if($data !== $ideal) {
			$typea = self::getType($data);
			$typeb = self::getType($ideal);
			if($typea != $typeb) return [$path, I18n::gettext('Получен тип').' '.$typea.', '.I18n::gettext('ожидается').' '.$typeb];
			else return [$path, I18n::gettext('Получен').' '.$data.', '.I18n::gettext('ожидается').' '.$ideal];
		}
		return true;
	}

	/**
	 * Проверяет $data на соответствие PHPRT::TYPE_...
	 * @param mixed $data
	 * @param mixed $ideal
	 * @return bool|string
	 */
	private static function compareTypes($data, $ideal) {
		if($ideal === PHPRT::TYPE_BOOL) {
			if(!is_bool($data)) return I18n::gettext('Получен тип').' '.self::getType($data).', '.I18n::gettext('ожидается').' bool';
			return true;
		}
		if($ideal === PHPRT::TYPE_TRUE) {
			if(true !== $data) return I18n::gettext('Получен тип').' '.self::getType($data).', '.I18n::gettext('ожидается').' true';
			return true;
		}
		if($ideal === PHPRT::TYPE_FALSE) {
			if(false !== $data) return I18n::gettext('Получен тип').' '.self::getType($data).', '.I18n::gettext('ожидается').' false';
			return true;
		}
		if($ideal === PHPRT::TYPE_INT) {
			if(!is_int($data)) return I18n::gettext('Получен тип').' '.self::getType($data).', '.I18n::gettext('ожидается').' int';
			return true;
		}
		if($ideal === PHPRT::TYPE_STRING) {
			if(!is_string($data)) return I18n::gettext('Получен тип').' '.self::getType($data).', '.I18n::gettext('ожидается').' string';
			return true;
		}
		if($ideal === PHPRT::TYPE_ARRAY) {
			if(!is_array($data)) return I18n::gettext('Получен тип').' '.self::getType($data).', '.I18n::gettext('ожидается').' array';
			return true;
		}
		return false;
	}

	/**
	 * Определяет тип переменной
	 * @param mixed $var
	 * @return string
	 */
	private static function getType($var) {
		if(is_array($var)) return 'array';
		if(is_string($var)) return 'string';
		if(is_bool($var)) return 'bool';
		if(is_null($var)) return 'null';
		if(is_int($var)) return 'int';
		return 'undefined';
	}

	/**
	 * Проверка headers
	 * @param string $headers
	 * @param array $check
	 */
	public static function checkHeaders($headers, $check) {
		if(!is_array($check)) Console::error('responseHeaders '.I18n::gettext('не является массивом'));
		foreach($check as $k=>$v) {
			if(is_array($v)) {
				if(sizeof($v) != 2) Console::error(I18n::gettext('Некорректный параметр').' responseHeaders['.$k.']');
				$check[$k] = implode(': ', $v);
			}
			if(!is_string($check[$k])) Console::error(I18n::gettext('Некорректный параметр').' responseHeaders['.$k.']');
		}
		
		$headers = array_filter(
			array_map(function($head){
				return trim($head);
			}, explode("\r\n", $headers))
		);
		
		foreach($check as $item) {
			if(false === array_search(trim($item), $headers)) Console::error(I18n::gettext('Не получен').' header '.$item);
		}
	}

	/**
	 * Проверяет HTTP статус по headers
	 * @param string $headers
	 * @param int|string|array $status
	 * @return bool
	 */
	public static function checkHttpStatus($headers, $status) {
		$head = trim(array_shift(explode("\r\n", $headers)));
		$parts = explode(' ', $head);
		if(sizeof($parts) < 2) Console::error(I18n::gettext('Некорректный HTTP статус').': '.$head);
		$h = [ array_shift($parts), array_shift($parts), implode(' ', $parts) ];
		
		if(is_array($status)) $status = implode(' ', $status);
		
		if(is_string($status)) {
			if($status != $h[1].' '.$h[2]) Console::error(I18n::gettext('Получен HTTP статус').' '.$h[1].' '.$h[2].', '.I18n::gettext('ожидается').' '.$status);
		} elseif((int)$status !== (int)$h[1]) Console::error(I18n::gettext('Получен HTTP статус').' '.$h[1].', '.I18n::gettext('ожидается').' '.$status);
		
		return true;
	}
}