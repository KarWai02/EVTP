<?php
class DB {
  private static ?PDO $pdo = null;
  public static function conn(): PDO {
    if (!self::$pdo) {
      $cfg = $GLOBALS['APP_CONFIG']['db'] ?? [];
      $host = $cfg['host'] ?? '127.0.0.1';
      $port = $cfg['port'] ?? 3306;
      $db = $cfg['name'] ?? '';
      $user = $cfg['user'] ?? '';
      $pass = $cfg['pass'] ?? '';
      $charset = $cfg['charset'] ?? 'utf8mb4';
      $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
      $opt = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
      ];
      self::$pdo = new PDO($dsn, $user, $pass, $opt);
    }
    return self::$pdo;
  }
}
