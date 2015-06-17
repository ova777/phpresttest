<?php

class PHPRT {
	const VERSION = '1.0.0.1';
	
	private $config = [];
	private $scenarios = [];
	private $scenariosPath = './scenarios';
	private $scenariosList = [];
	public $typesPath = './types';
	private $configPath = './config.php';
	public $lang = 'ru';
	private static $args = [
		'-c' => 'configPath',
		'-t' => 'typesPath',
		'-l' => 'lang'
	];
	
	private $resultsData = [];
	private $assignes = [];
	
	const TYPE_BOOL = 	'_PHPRT_TYPE_BOOL';
	const TYPE_TRUE = 	'_PHPRT_TYPE_TRUE';
	const TYPE_FALSE = 	'_PHPRT_TYPE_FALSE';
	const TYPE_NULL = 	'_PHPRT_TYPE_NULL';
	const TYPE_INT = 	'_PHPRT_TYPE_INT';
	const TYPE_STRING = '_PHPRT_TYPE_STRING';
	const TYPE_ARRAY = 	'_PHPRT_TYPE_ARRAY';
	const _ASSIGN = 	'_PHPRT_ASSIGN_';
	const _SELFTYPE = 	'_PHPRT_SELFTYPE_';
	const _RESULTS = 	'_PHPRT_RESULTS_';

	/**
	 * Синглтон экземпляра класса
	 * @return PHPRT
	 */
	public static function app() {
		static $app = false;
		if(false === $app) $app = new self;
		return $app;
	}

	/**
	 * Запустить выполнение приложения
	 * @param array $args аргументы командной строки
	 */
	public function start($args) {
		Console::version();
		
		$this->parseArgs($args);
		$this->readConfig();
		$this->readScenariosList();
		$this->readScenarios();
		$this->runScenarios();
		Console::log('OK');
	}

	/**
	 * Запускает сценарии на выполнение
	 */
	private function runScenarios() {
		foreach($this->scenarios as $file=>$scenario) {
			Console::log('- '.I18n::gettext('Сценарий').' '.basename($file).' -');
			foreach($scenario as $data) $this->runScenario($data);
		}
	}

	/**
	 * Выполнить сценарий
	 * @param array $data данные сценария
	 */
	private function runScenario($data) {
		Console::log($data['method'].' '.$data['url']);

		/**
		 * Подготавливаем данные для отправки запроса
		 */
		//Full path to API
		$path = System::smartSlashEnd($this->config['url']).System::smartSlashBegin($data['url'], false);
		//Headers
		$headers = isset($this->config['headers']) ? $this->config['headers'] : [];
		if(isset($data['headers'])) $headers = array_merge($headers, $data['headers']);
		//Basic auth
		$basic = isset($this->config['basicAuth']) ? $this->config['basicAuth'] : [];
		if(isset($data['basicAuth'])) $basic = $data['basicAuth'];
		//request
		$request = isset($data['data']) ? $data['data'] : [];
		//Ищем и заменяем переменные в request 
		$request = System::arrRecMap($request, function($v){
			if(false !== $str = System::detectConst(self::_RESULTS, $v)) return $this->getResult($str);
			return $v;
		});
		
		//Отправка запроса
		$req = Request::send([
			'path' => $path,
			'method' => $data['method'],
			'headers' => $headers,
			'basic' => $basic,
			'data' => $request
		]);
		
		//данные -> json|text
		$body = $req->body;
		if(!isset($data['dataType']) OR $data['dataType'] == 'json') $body = json_decode($req->body, true);
		//Если debug==true - отображаем полученные данные
		if(isset($data['debug'])) {
			Console::log('-- Debug --');
			Console::log($req->headers);
			Console::debug($body);
			exit;
		}
		//Сохраняем результат в resultTo
		if(isset($data['resultTo'])) $this->saveResult($data['resultTo'], $body);
		//Проверка HTTP статуса
		if(isset($data['httpStatus'])) Response::checkHttpStatus($req->headers, $data['httpStatus']);
		//Проверяем полученные данные
		if(isset($data['responseHeaders'])) Response::checkHeaders($req->headers, $data['responseHeaders']);
		if(isset($data['response'])) Response::checkResponse($body, $data['response']);
		//Время выполнения запроса
		Console::log($req->info['total_time'].' '.I18n::gettext('сек').'.');
	}

	/**
	 * Обрабатывает переменные из результатов выполнения запроса ('$varName["param"]')
	 * @param string $str
	 * @return mixed
	 */
	public function getResult($str) {
		foreach($this->resultsData as $k=>$v) ${$k} = $v;
		@eval('$result = '.$str.';');
		if(@$result === null) Console::error(I18n::gettext('Не найдена переменная').' '.$str);
		return $result;
	}

	/**
	 * Проверяет expression
	 * @param string $expr
	 * @param null|mixed $self значение переменной $self
	 * @return bool
	 */
	public function checkExpression($expr, $self = null) {
		foreach($this->resultsData as $k=>$v) ${$k} = $v;
		@eval('$result = ('.$expr.');');
		return $result === true;
	}

	/**
	 * Созраняет результат выполнения запроса ('resultTo' => 'varName')
	 * @param string $varname
	 * @param mixed $data
	 */
	private function saveResult($varname, $data) {
		$this->resultsData[$varname] = $data;
	}

	/**
	 * Параметр PHPRP::results в конфигурационных файлах
	 * @param string $str
	 * @return string
	 */
	public static function results($str) {
		return self::_RESULTS.$str;
	}
	
	/**
	 * Параметр PHPRT::assign в конфигурационных файлах
	 * @param array $data
	 * @return string
	 */
	public static function assign($data){
		self::app()->assignes[] = $data;
		return self::_ASSIGN.( end(array_keys(self::app()->assignes)) );
	}

	/**
	 * Возвращает значение установленной PHPRT::assign
	 * @param int|string $id
	 * @return array
	 */
	public function getAssign($id) {
		return $this->assignes[$id];
	}

	/**
	 * Параметр PHPRT::type в конфигурационных файлах
	 * @param string $name
	 * @return string
	 */
	public static function type($name) {
		return self::_SELFTYPE.$name;
	}

	/**
	 * Считывает файлы сценариев
	 */
	private function readScenarios() {
		foreach($this->scenariosList as $file) {
			Catcher::$handle = Catcher::HANDLE_SCENARIO;
			$this->scenarios[$file] = @require($file);
			Catcher::$handle = Catcher::HANDLE_DEFAULT;
		}
	}

	/**
	 * Разбирает агрументы командной строки
	 * @param array $args
	 */
	private function parseArgs($args) {
		array_shift($args);
		
		while(null !== $arg = array_shift($args)) {
			if($arg == '-v') { exit; }
			
			if(isset(self::$args[$arg])) {
				$this->{self::$args[$arg]} = array_shift($args);
			} else {
				$this->scenariosPath = $arg;
			}
		}
	}

	/**
	 * Получает список сценариев
	 */
	private function readScenariosList() {
		$path = System::localPath($this->scenariosPath);
		
		if(!file_exists($path)) Console::error(I18n::gettext('Сценарии не найдены'));
		if(is_file($path)) {
			$this->scenariosList[] = $path;
			return;
		} else {
			$base = System::smartSlashEnd($path);
			$dir = scandir($path);
			foreach($dir as $file) {
				if($file == '..' OR $file == '.') continue;
				$this->scenariosList[] = $base.$file;
			}
		}
		foreach($this->scenariosList as $file) if(!file_exists($file)) Console::error(I18n::gettext('Не найден файл сценария').' '.$file);
		$this->sortScenarios();
	}

	/**
	 * Сортирует сценарии по config['scerios']
	 */
	private function sortScenarios() {
		if(!isset($this->config['scenarios'])) return;
		$list = [];
		foreach($this->config['scenarios'] as $scen) {
			foreach($this->scenariosList as $item) if(false !== strpos($item, $scen)) $list[] = $item;
		}
		$this->scenariosList = $list;
	}

	/**
	 * Считывает файл конфишурации
	 */
	private function readConfig() {
		$path = System::localPath($this->configPath);
		
		if(!file_exists($path)) Console::error(I18n::gettext('Не найден файл конфигурации'));
		if(!is_readable($path)) Console::error(I18n::gettext('Невозможно прочитать файл конфигурации'));

		Catcher::$handle = Catcher::HANDLE_CONFIG;
		$this->config = @require($path);
		Catcher::$handle = Catcher::HANDLE_DEFAULT;
		
		if(!is_array($this->config)) Console::error(I18n::gettext('Ошибка структуры файла конфигурации'));
		if(!isset($this->config['url']) || !is_string($this->config['url'])) Console::error(I18n::gettext('Ошибка структуры файла конфигурации'));
	}
}