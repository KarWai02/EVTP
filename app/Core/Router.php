<?php
class Router {
  private array $routes = [];
  public function get($path, $handler){ $this->routes['GET'][$path] = $handler; }
  public function post($path, $handler){ $this->routes['POST'][$path] = $handler; }
  public function dispatch(){
    $method = $_SERVER['REQUEST_METHOD'];
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
    $path = '/'.ltrim(substr($path, strlen($base)), '/');
    $handler = $this->routes[$method][$path] ?? null;
    if(!$handler){ http_response_code(404); return render('errors/404', ['path'=>$path]); }
    echo call_user_func($handler);
  }
}
