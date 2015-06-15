<?php

class Request {
	public $headers;
	public $body;
	public $info;
	
	/**
	 * Отправить запрос
	 * @param array $params {path, method, headers, basic, data}
	 * @return Request
	 */
	public static function send($params) {
		if($params['method'] == 'GET') {
			$params['path'] = self::createGetPath($params['path'], $params['data']);
		}
		
		$ch = curl_init($params['path']);
		if($params['basic']) {
			curl_setopt($ch, CURLOPT_USERPWD, $params['basic'][0].":".$params['basic'][1]);
		}
		if($params['method'] == 'POST') {
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params['data']));
		}
		if($params['method'] == 'PUT' OR $params['method'] == 'DELETE') {
			$fields = http_build_query($params['data']);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $params['method']);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Length: '.strlen($fields)));
			curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
		}
		curl_setopt($ch, CURLOPT_HEADER, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $params['headers']);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		$res = curl_exec($ch);
		
		$req = new self;
		$req->info = curl_getinfo($ch);
		$header_size = $req->info['header_size'];
		$req->headers = substr($res, 0, $header_size);
		$req->body = substr($res, $header_size);

		curl_close($ch);
		return $req;
	}

	/**
	 * Добавляет данные $data в строку запроса $url
	 * @param string $url
	 * @param array $data
	 * @return string
	 */
	private static function createGetPath($url, $data) {
		$url = parse_url($url);
		$query = $data;
		if(isset($url['query'])) {
			parse_str($url['query'], $query);
			$query = array_merge($query, $data);
		}
		$querystr = http_build_query($query);

		return  $url['scheme'].'://'.$url['host'].$url['path'].($querystr?'?'.$querystr:'');
	}
}