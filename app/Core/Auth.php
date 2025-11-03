<?php
class Auth {
  public static function check(){ return isset($_SESSION['user']); }
  public static function user(){ return $_SESSION['user'] ?? null; }
  public static function logout(){
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
      $params = session_get_cookie_params();
      setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'] ?? false, $params['httponly'] ?? true);
    }
    session_destroy();
    session_start();
    session_regenerate_id(true);
  }

  public static function attempt($email,$password){
    $pdo = DB::conn();

    // Admin
    $q = $pdo->prepare("SELECT adminID id, adminName name, adminEmail email, adminPswd pswd FROM Admin WHERE adminEmail=?");
    $q->execute([$email]); $u = $q->fetch();
    if($u && password_verify($password, $u['pswd'])){
      self::loginAs('admin',$u);
      if (self::checkMustChange($u['email'])){
        // auto-generate reset token and store for redirect
        try{
          $token = bin2hex(random_bytes(16));
          $exp = time() + 3600;
          $path = __DIR__.'/../../storage/resets.json';
          $data = file_exists($path) ? json_decode(file_get_contents($path), true) : [];
          // prune expired
          $now = time(); foreach ($data as $tk=>$row){ if(($row['exp'] ?? 0) < $now) unset($data[$tk]); }
          $data[$token] = ['email'=>$u['email'],'exp'=>$exp];
          file_put_contents($path, json_encode($data));
          $_SESSION['force_reset_token'] = $token;
          $_SESSION['flash'] = 'Please set a new password to continue.';
        }catch(Throwable $e){ /* ignore */ }
      }
      return true;
    }

    // Trainer
    $q = $pdo->prepare("SELECT trainerID id, trainerName name, trainerEmail email, trainerPswd pswd FROM Trainers WHERE trainerEmail=?");
    $q->execute([$email]); $u = $q->fetch();
    if($u && password_verify($password, $u['pswd'])){
      self::loginAs('trainer',$u);
      if (self::checkMustChange($u['email'])){
        try{
          $token = bin2hex(random_bytes(16));
          $exp = time() + 3600;
          $path = __DIR__.'/../../storage/resets.json';
          $data = file_exists($path) ? json_decode(file_get_contents($path), true) : [];
          $now = time(); foreach ($data as $tk=>$row){ if(($row['exp'] ?? 0) < $now) unset($data[$tk]); }
          $data[$token] = ['email'=>$u['email'],'exp'=>$exp];
          file_put_contents($path, json_encode($data));
          $_SESSION['force_reset_token'] = $token;
          $_SESSION['flash'] = 'Please set a new password to continue.';
        }catch(Throwable $e){ /* ignore */ }
      }
      return true;
    }

    // Employer (needs employerPswd in Employers table)
    $q = $pdo->prepare("SELECT employerID id, contactPerson name, employerEmail email, employerPswd pswd FROM Employers WHERE employerEmail=?");
    $q->execute([$email]); $u = $q->fetch();
    if($u && password_verify($password, $u['pswd'])){
      self::loginAs('employer',$u);
      if (self::checkMustChange($u['email'])){
        try{
          $token = bin2hex(random_bytes(16));
          $exp = time() + 3600;
          $path = __DIR__.'/../../storage/resets.json';
          $data = file_exists($path) ? json_decode(file_get_contents($path), true) : [];
          $now = time(); foreach ($data as $tk=>$row){ if(($row['exp'] ?? 0) < $now) unset($data[$tk]); }
          $data[$token] = ['email'=>$u['email'],'exp'=>$exp];
          file_put_contents($path, json_encode($data));
          $_SESSION['force_reset_token'] = $token;
          $_SESSION['flash'] = 'Please set a new password to continue.';
        }catch(Throwable $e){ /* ignore */ }
      }
      return true;
    }

    // Learner
    $q = $pdo->prepare("SELECT learnerID id, learnerName name, learnerEmail email, learnerPswd pswd FROM Learners WHERE learnerEmail=?");
    $q->execute([$email]); $u = $q->fetch();
    if($u && password_verify($password, $u['pswd'])){
      self::loginAs('learner',$u);
      if (self::checkMustChange($u['email'])){
        try{
          $token = bin2hex(random_bytes(16));
          $exp = time() + 3600;
          $path = __DIR__.'/../../storage/resets.json';
          $data = file_exists($path) ? json_decode(file_get_contents($path), true) : [];
          $now = time(); foreach ($data as $tk=>$row){ if(($row['exp'] ?? 0) < $now) unset($data[$tk]); }
          $data[$token] = ['email'=>$u['email'],'exp'=>$exp];
          file_put_contents($path, json_encode($data));
          $_SESSION['force_reset_token'] = $token;
          $_SESSION['flash'] = 'Please set a new password to continue.';
        }catch(Throwable $e){ /* ignore */ }
      }
      return true;
    }

    return false;
  }

  private static function loginAs($role,$u){
    session_regenerate_id(true);
    $_SESSION['user'] = ['role'=>$role,'id'=>$u['id'],'name'=>$u['name'],'email'=>$u['email']];
    return true;
  }

  private static function checkMustChange($email){
    try{
      $flagPath = __DIR__.'/../../storage/mustchange.json';
      if(!file_exists($flagPath)) return false;
      $flags = json_decode(file_get_contents($flagPath), true) ?: [];
      if(!empty($flags[$email])){ $_SESSION['must_change_password']=true; return true; }
    }catch(Throwable $e){ /* ignore */ }
    return false;
  }

  public static function requireRole($roles=[]){
    if(!self::check()) { header('Location: '.app_url('login')); exit; }
    $userRole = strtolower((string)(self::user()['role'] ?? ''));
    $need = array_map(function($r){ return strtolower((string)$r); }, (array)$roles);
    if(!in_array($userRole, $need, true)) {
      header('Location: '.app_url('login')); exit;
    }
  }
}
