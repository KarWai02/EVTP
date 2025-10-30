<?php
function app_url($path=''){
  // builds /evtp/public/... correctly
  $base = $GLOBALS['APP_CONFIG']['app']['base_url'] ?? '';
  return rtrim($base,'/').'/'.ltrim($path,'/');
}

function render($view, $data=[]){
  // Preserve the target view path and avoid collision with a 'view' key from $data (e.g., list view mode)
  $__view = $view;
  if (is_array($data) && array_key_exists('view', $data)) unset($data['view']);
  extract($data);
  // expose validation helpers to views
  $errors = $_SESSION['errors'] ?? [];
  $old = $_SESSION['old'] ?? [];
  extract(compact('errors', 'old')); // expose to view
  ob_start();
  include __DIR__."/../Views/$__view.php";
  $content = ob_get_clean();
  include __DIR__."/../Views/layouts/base.php";
  // clear per-request flash validation data
  unset($_SESSION['errors'], $_SESSION['old']);
}

function redirect($path){ header('Location: '.app_url($path)); exit; }

function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function csrf_token(){ if(empty($_SESSION['csrf'])) $_SESSION['csrf']=bin2hex(random_bytes(16)); return $_SESSION['csrf']; }
function csrf_verify(){ if(($_POST['csrf'] ?? '') !== ($_SESSION['csrf'] ?? null)) die('Invalid CSRF'); }

function storage_path($rel=''){
  return __DIR__."/../../storage/".ltrim($rel,'/');
}

function send_mail(string $to, string $subject, string $message): bool {
  // If PHPMailer is available and config exists, use it; otherwise fallback to logging/mail()
  $logDir = storage_path();
  if(!is_dir($logDir)) @mkdir($logDir, 0777, true);
  $logFile = storage_path('emails.log');
  $entry = date('c')."\nTO: $to\nSUBJECT: $subject\n$message\n---\n";
  @file_put_contents($logFile, $entry, FILE_APPEND);

  $mailCfg = $GLOBALS['APP_CONFIG']['mail'] ?? null;
  if(class_exists('PHPMailer\\PHPMailer\\PHPMailer') && $mailCfg){
    try {
      $mailer = new PHPMailer\PHPMailer\PHPMailer(true);
      if(($mailCfg['transport'] ?? 'smtp') === 'smtp'){
        $mailer->isSMTP();
        $mailer->Host = $mailCfg['host'] ?? '';
        $mailer->SMTPAuth = true;
        $mailer->Username = $mailCfg['username'] ?? '';
        $mailer->Password = $mailCfg['password'] ?? '';
        $mailer->SMTPSecure = $mailCfg['encryption'] ?? PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mailer->Port = $mailCfg['port'] ?? 587;
      }
      $from = $mailCfg['from_address'] ?? 'no-reply@example.com';
      $fromName = $mailCfg['from_name'] ?? 'EVTP';
      $mailer->setFrom($from, $fromName);
      $mailer->addAddress($to);
      $mailer->Subject = $subject;
      $mailer->Body = $message;
      $mailer->AltBody = $message;
      $mailer->send();
      return true;
    } catch (Throwable $e) {
      @file_put_contents($logFile, 'MAIL ERROR: '.$e->getMessage()."\n", FILE_APPEND);
    }
  }
  if(function_exists('mail')){
    $headers = 'Content-Type: text/plain; charset=utf-8';
    @mail($to, $subject, $message, $headers);
  }
  return true;
}

function gen_id(PDO $pdo, string $table, string $pk, string $prefix): string {
    $stmt = $pdo->query("SELECT $pk FROM $table WHERE $pk LIKE '$prefix%' ORDER BY $pk DESC LIMIT 1");
    $last = $stmt->fetchColumn();
    $num = $last ? intval(substr($last, strlen($prefix))) + 1 : 1;
    return $prefix . str_pad((string)$num, 5, '0', STR_PAD_LEFT);
  }

  function course_categories(): array {
  static $cache = null;                 // simple per-request cache
  if ($cache !== null) return $cache;
  $pdo = DB::conn();
  $stmt = $pdo->query("SELECT DISTINCT category 
                       FROM Course 
                       WHERE category IS NOT NULL AND category <> '' 
                       ORDER BY category");
  $rows = $stmt->fetchAll();
  $cache = array_map(fn($r)=>$r['category'], $rows);
  return $cache;
}

function course_sectors(): array {
  static $cache = null;
  if ($cache !== null) return $cache;
  try {
    $pdo = DB::conn();
    $stmt = $pdo->query("SELECT DISTINCT sector FROM Course WHERE sector IS NOT NULL AND sector <> '' ORDER BY sector");
    $rows = $stmt->fetchAll();
    $cache = array_map(fn($r)=>$r['sector'], $rows);
  } catch (Throwable $e) {
    $cache = [];
  }
  return $cache;
 }

function course_levels(): array {
  static $cache = null;
  if ($cache !== null) return $cache;
  try {
    $pdo = DB::conn();
    $stmt = $pdo->query("SELECT DISTINCT level FROM Course WHERE level IS NOT NULL AND level <> '' ORDER BY level");
    $rows = $stmt->fetchAll();
    $cache = array_map(fn($r)=>$r['level'], $rows);
  } catch (Throwable $e) {
    // Fallback common levels if column doesn't exist
    $cache = ['Beginner','Intermediate','Advanced'];
  }
  return $cache;
}