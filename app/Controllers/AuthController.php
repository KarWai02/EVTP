<?php
class AuthController {
  public function showLogin(){ return render('auth/login'); }

  public function showSignup(){
    $errors = $_SESSION['errors'] ?? [];
    $old    = $_SESSION['old']    ?? [];
    unset($_SESSION['errors'], $_SESSION['old']);
    return render('auth/signup', compact('errors','old'));
  }

  public function login(){
    csrf_verify();
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $now = time();
    $max = 5; $window = 900;
    $_SESSION['login_attempts'] = $_SESSION['login_attempts'] ?? [];
    $_SESSION['login_attempts'][$ip] = array_values(array_filter($_SESSION['login_attempts'][$ip] ?? [], fn($t)=> $t > $now - $window));
    if(count($_SESSION['login_attempts'][$ip]) >= $max){ $_SESSION['flash'] = 'Too many login attempts. Please try again later.'; return redirect('login'); }
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';
    $errors = [];
    if($email === ''){ $errors['email'] = 'Email is required'; }
    elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)){ $errors['email'] = 'Invalid email format'; }
    if($pass === ''){ $errors['password'] = 'Password is required'; }
    if($errors){ $_SESSION['errors']=$errors; $_SESSION['old']=['email'=>$email]; return redirect('login'); }
    if(Auth::attempt($email, $pass)){
      // If admin is flagged to change password, redirect to reset with pre-issued token
      if(!empty($_SESSION['must_change_password']) && !empty($_SESSION['force_reset_token'])){
        $tk = $_SESSION['force_reset_token'];
        unset($_SESSION['force_reset_token']);
        // Pre-generate CSRF so the reset form matches immediately after redirect
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
        return redirect('reset?token='.urlencode($tk));
      }
      return redirect('dashboard');
    }
    $_SESSION['login_attempts'][$ip][] = $now;
    $_SESSION['flash'] = 'Invalid credentials';
    $_SESSION['old']=['email'=>$email];
    return redirect('login');
  }

  public function signup(){
    csrf_verify();
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $pass = $_POST['password'] ?? '';
    $confirm = $_POST['password_confirmation'] ?? '';

    $errors = [];
    if($name === ''){ $errors['name'] = 'Name is required'; }
    if($email === ''){ $errors['email'] = 'Email is required'; }
    elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)){ $errors['email'] = 'Invalid email address'; }
    if($phone === ''){ $errors['phone'] = 'Phone is required'; }
    elseif(!preg_match('/^[0-9+\-\s]{7,15}$/', $phone)){ $errors['phone'] = 'Enter a valid phone number'; }
    if($pass === ''){ $errors['password'] = 'Password is required'; }
    elseif(!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\\d)(?=.*[^A-Za-z0-9]).{8,}$/', $pass)){
      $errors['password'] = 'Password must be 8+ chars and include upper, lower, number, and special';
    }
    if($confirm === '' || $pass !== $confirm){ $errors['password_confirmation'] = 'Passwords do not match'; }
    if($errors){ $_SESSION['errors']=$errors; $_SESSION['old']=['name'=>$name,'email'=>$email,'phone'=>$phone]; return redirect('signup'); }

    $pdo = DB::conn();

    $exists = false;
    foreach ([
      ["SELECT 1 FROM Admin WHERE adminEmail=?", [$email]],
      ["SELECT 1 FROM Trainers WHERE trainerEmail=?", [$email]],
      ["SELECT 1 FROM Employers WHERE employerEmail=?", [$email]],
      ["SELECT 1 FROM Learners WHERE learnerEmail=?", [$email]],
    ] as $q){
      $stm = $pdo->prepare($q[0]); $stm->execute($q[1]); if($stm->fetchColumn()){ $exists=true; break; }
    }
    if($exists){ $_SESSION['errors']=['email'=>'Email already registered']; $_SESSION['old']=['name'=>$name,'email'=>$email,'phone'=>$phone]; return redirect('signup'); }

    $hash = password_hash($pass, PASSWORD_DEFAULT);
    $id = gen_id($pdo, 'Learners', 'learnerID', 'LRN');
    $stm = $pdo->prepare("INSERT INTO Learners (learnerID, learnerName, learnerEmail, learnerPhone, learnerPswd) VALUES (?,?,?,?,?)");
    $stm->execute([$id, $name, $email, $phone, $hash]);

    if(Auth::attempt($email, $pass)) return redirect('dashboard');
    $_SESSION['flash'] = 'Signup succeeded. Please login.';
    return redirect('login');
  }

  public function logout(){ Auth::logout(); return redirect('login'); }

  public function showForgot(){ return render('auth/forgot'); }

  public function forgot(){
    csrf_verify();
    $email = trim($_POST['email'] ?? '');
    $errors = [];
    if($email === ''){ $errors['email']='Email is required'; }
    elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)){ $errors['email']='Invalid email format'; }
    if($errors){ $_SESSION['errors']=$errors; $_SESSION['old']=['email'=>$email]; return redirect('forgot'); }

    $pdo = DB::conn();
    $exists = false;
    foreach ([
      ["SELECT 1 FROM Admin WHERE adminEmail=?", [$email]],
      ["SELECT 1 FROM Trainers WHERE trainerEmail=?", [$email]],
      ["SELECT 1 FROM Employers WHERE employerEmail=?", [$email]],
      ["SELECT 1 FROM Learners WHERE learnerEmail=?", [$email]],
    ] as $q){
      $stm = $pdo->prepare($q[0]); $stm->execute($q[1]); if($stm->fetchColumn()){ $exists=true; break; }
    }
    // Always show success to avoid user enumeration
    $token = bin2hex(random_bytes(16));
    $exp = time() + 3600;
    $path = __DIR__.'/../../storage/resets.json';
    $data = file_exists($path) ? json_decode(file_get_contents($path), true) : [];
    // prune expired tokens
    $now = time();
    foreach ($data as $tk=>$row){ if(($row['exp'] ?? 0) < $now) unset($data[$tk]); }
    $data[$token] = ['email'=>$email,'exp'=>$exp];
    file_put_contents($path, json_encode($data));
    // send email with link
    $link = app_url('reset').'?token='.$token;
    send_mail($email, 'Reset your EVTP password', "Use this link within 1 hour to reset your password: $link");
    $_SESSION['flash'] = 'If that email exists, a reset link has been sent.';
    return redirect('forgot');
  }

  public function showReset(){ return render('auth/reset'); }

  public function reset(){
    csrf_verify();
    $token = $_POST['token'] ?? '';
    $pass = $_POST['password'] ?? '';
    $confirm = $_POST['password_confirmation'] ?? '';
    $errors = [];
    if($token===''){ $errors['token']='Invalid token'; }
    if($pass===''){ $errors['password']='Password is required'; }
    elseif(!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\\d)(?=.*[^A-Za-z0-9]).{8,}$/', $pass)){
      $errors['password']='Password must be 8+ chars and include upper, lower, number, and special';
    }
    if($confirm==='' || $confirm!==$pass){ $errors['password_confirmation']='Passwords do not match'; }
    if($errors){ $_SESSION['errors']=$errors; return redirect('reset?token='.urlencode($token)); }

    $path = __DIR__.'/../../storage/resets.json';
    $data = file_exists($path) ? json_decode(file_get_contents($path), true) : [];
    // prune expired tokens before use
    $now = time();
    foreach ($data as $tk=>$row){ if(($row['exp'] ?? 0) < $now) unset($data[$tk]); }
    $entry = $data[$token] ?? null;
    if(!$entry || ($entry['exp'] ?? 0) < time()){ $_SESSION['flash']='Reset link is invalid or expired'; return redirect('forgot'); }
    $email = $entry['email'];

    $pdo = DB::conn();
    $hash = password_hash($pass, PASSWORD_DEFAULT);
    foreach ([
      ["UPDATE Admin SET adminPswd=? WHERE adminEmail=?", [$hash,$email]],
      ["UPDATE Trainers SET trainerPswd=? WHERE trainerEmail=?", [$hash,$email]],
      ["UPDATE Employers SET employerPswd=? WHERE employerEmail=?", [$hash,$email]],
      ["UPDATE Learners SET learnerPswd=? WHERE learnerEmail=?", [$hash,$email]],
    ] as $q){
      $stm = $pdo->prepare($q[0]); $stm->execute($q[1]);
      if($stm->rowCount()>0) break;
    }
    unset($data[$token]); file_put_contents($path, json_encode($data));
    // Clear must-change flag if present
    try{
      $flagPath = __DIR__.'/../../storage/mustchange.json';
      $flags = file_exists($flagPath) ? json_decode(file_get_contents($flagPath), true) : [];
      if(isset($flags[$email])){ unset($flags[$email]); file_put_contents($flagPath, json_encode($flags)); }
      unset($_SESSION['must_change_password']);
    }catch(\Throwable $e){ /* ignore */ }
    $_SESSION['flash']='Password has been reset. Please login.';
    return redirect('login');
  }
}

