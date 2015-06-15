<?php

class System {
	
	/**
	 * Относительный путь приводит к абсолютному относительно рабочей директории
	 * @param string $path
	 * @return string
	 */
	public static function localPath($path) {
		if('/' === substr($path, 0, 1)) return $path;
		return getcwd().'/'.$path;
	}
	
	/**
	 * Проверяет ничинается ли $v с $const. Возвращает false или сожержимое $v после подстроки $const
	 * detectConst('AAA', 'AAABBB') => 'BBB'
	 * detectConst('AAA', '!!AAABBB') => false
	 * @param string $const
	 * @param string $v
	 * @return bool|string
	 */
	public static function detectConst($const, $v) {
		if(!is_string($v)) return false;
		if(0 === strpos($v, $const)) return substr($v, strlen($const));
		return false;
	}

	/**
	 * Рекурсивно пробегает ассоциативный массив и применяет функцию $fn к каждому ключу и значению
	 * @param mixed $item
	 * @param callable $fn
	 * @return mixed
	 */
	public static function arrRecMap($item, $fn) {
		if(is_array($item)) {
			$res = [];
			foreach($item as $k=>$v) $res[$fn($k)] = self::arrRecMap($v, $fn);
		} else $res = $fn($item);
		return $res;
	}

	/**
	 * Добавляет/убирает слеш в конце строки (с проверкой есть он уже или нет)
	 * @param string $path
	 * @param bool $add добавить/удалить
	 * @return string
	 */
	public static function smartSlashEnd($path, $add = true) {
		if(!strlen($path)) return $path;
		if(substr($path, strlen($path)-1) == '/') $path = substr($path, 0, strlen($path)-1);
		if($add) $path.='/';
		return $path;
	}

	/**
	 * Добавляет/убирает слеш в начале строки (с проверкой есть он уже или нет)
	 * @param string $path
	 * @param bool $add добавить/удалить
	 * @return string
	 */
	public static function smartSlashBegin($path, $add = true) {
		if(!strlen($path)) return $path;
		if(substr($path, 0, 1) == '/') $path = substr($path, 1);
		if($add) $path = '/'.$path;
		return $path;
	}

}