<?php
class Router {

	private $routes = array();

	public function route($pattern, $callback) {
		$this->routes[$pattern] = $callback;
	}

	public function execute($uri) {
		if(empty($uri) || $uri == '/'){
			if(array_key_exists('',$this->routes)){
				$res = call_user_func($this->routes['']);
				if(!empty($res)){
					return $res;
				}
			}
		}
		foreach ($this->routes as $pattern => $callback) {
			if(empty($pattern)){
				continue;
			}
			if (preg_match($pattern, $uri, $params) === 1) {
				array_shift($params);
				$res = call_user_func_array($callback, array_values($params));
				if(!empty($res)){
					return $res;
				}
			}
		}
		return false;
	}

}