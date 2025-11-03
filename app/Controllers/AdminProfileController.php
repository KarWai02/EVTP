<?php
class AdminProfileController {
  private function ensureAdmin(){ Auth::requireRole(['admin']); }

  public function edit(){
    $this->ensureAdmin();
    $pdo = DB::conn();
    $id = Auth::user()['id'];
    $stmt = $pdo->prepare("SELECT adminID, adminName, adminEmail, adminPhone FROM Admin WHERE adminID=?");
    $stmt->execute([$id]);
    $admin = $stmt->fetch();
    if(!$admin){ $_SESSION['flash']='Profile not found'; return redirect('dashboard'); }

    $errors = $_SESSION['errors'] ?? [];
    $old    = $_SESSION['old']    ?? [];
    unset($_SESSION['errors'], $_SESSION['old']);

    // Avatar URL (DB column optional). Fallback to file in public/uploads/avatars
    $avatarUrl = '';
    try{
      $q = $pdo->prepare("SELECT adminAvatar FROM Admin WHERE adminID=?");
      $q->execute([$id]);
      $col = $q->fetchColumn();
      if($col){ $avatarUrl = $col; }
    }catch(\Throwable $e){ /* no column */ }
    if($avatarUrl===''){
      $file = '/uploads/avatars/admin_'.preg_replace('/[^A-Za-z0-9_\-]/','',$id).'.png';
      if(file_exists(dirname(__DIR__,2).'/public'.$file)) $avatarUrl = app_url(ltrim($file,'/'));
    }

    // Recent admin actions (audit): prefer AdminAudit table
    $audit = [];
    try{
      $pdo->query("SELECT adminID, action, created_at FROM AdminAudit LIMIT 1");
      $q = $pdo->prepare("SELECT action, created_at FROM AdminAudit WHERE adminID=? ORDER BY created_at DESC LIMIT 20");
      $q->execute([$id]); $audit = $q->fetchAll();
    }catch(\Throwable $e){
      // Fallback: storage log file storage/admin_audit_{id}.log lines formatted as "YYYY-mm-dd HH:MM:SS | Action text"
      $path = __DIR__.'/../../storage/admin_audit_'.preg_replace('/[^A-Za-z0-9_\-]/','',$id).'.log';
      if(file_exists($path)){
        $lines = @file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        $lines = array_slice(array_reverse($lines), 0, 20);
        foreach($lines as $ln){
          $parts = explode('|', $ln, 2);
          $audit[] = ['created_at'=>trim($parts[0] ?? ''), 'action'=>trim($parts[1] ?? $ln)];
        }
      }
    }

    return render('admin/profile/edit', [
      'admin'=>$admin,
      'errors'=>$errors,
      'old'=>$old,
      'avatarUrl'=>$avatarUrl,
      'audit'=>$audit,
    ]);
  }

  public function update(){
    $this->ensureAdmin(); csrf_verify();
    $name = trim($_POST['name'] ?? '');
    $email= trim($_POST['email'] ?? '');
    $phone= trim($_POST['phone'] ?? '');

    $errors=[];
    if($name===''){ $errors['name']='Name is required'; }
    if($email===''){ $errors['email']='Email is required'; }
    elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)){ $errors['email']='Invalid email'; }
    if($phone===''){ $errors['phone']='Phone is required'; }
    elseif(!preg_match('/^\d{3}-\d{7,8}$/', $phone)){ $errors['phone']='Phone must be xxx-xxxxxxx or xxx-xxxxxxxx'; }

    if($errors){ $_SESSION['errors']=$errors; $_SESSION['old']=['name'=>$name,'email'=>$email,'phone'=>$phone]; return redirect('admin/profile'); }

    $pdo = DB::conn();
    $id = Auth::user()['id'];
    // unique email excluding self
    $chk = $pdo->prepare("SELECT 1 FROM Admin WHERE adminEmail=? AND adminID<>?");
    $chk->execute([$email,$id]); if($chk->fetch()){ $_SESSION['errors']=['email'=>'Email already exists']; $_SESSION['old']=['name'=>$name,'email'=>$email,'phone'=>$phone]; return redirect('admin/profile'); }

    $upd = $pdo->prepare("UPDATE Admin SET adminName=?, adminEmail=?, adminPhone=? WHERE adminID=?");
    $upd->execute([$name,$email,$phone,$id]);

    // update session
    $_SESSION['user']['name'] = $name;
    $_SESSION['flash'] = 'Profile updated';
    return redirect('admin/profile');
  }

  public function updatePassword(){
    $this->ensureAdmin(); csrf_verify();
    $current = $_POST['current_password'] ?? '';
    $pass = $_POST['password'] ?? '';
    $confirm = $_POST['password_confirmation'] ?? '';
    $errors = [];
    if($current===''){ $errors['current_password']='Current password is required'; }
    if($pass===''){ $errors['password']='New password is required'; }
    elseif(!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}$/', $pass)){
      $errors['password']='Password must be 8+ chars and include upper, lower, number, and special';
    }
    if($confirm==='' || $confirm!==$pass){ $errors['password_confirmation']='Passwords do not match'; }
    if($errors){ $_SESSION['errors']=$errors; return redirect('admin/profile'); }

    $pdo = DB::conn();
    $id = Auth::user()['id'];
    $stm = $pdo->prepare("SELECT adminPswd FROM Admin WHERE adminID=?");
    $stm->execute([$id]); $row = $stm->fetch();
    if(!$row || !password_verify($current, $row['adminPswd'])){
      $_SESSION['errors']=['current_password'=>'Current password is incorrect'];
      return redirect('admin/profile');
    }

    $hash = password_hash($pass, PASSWORD_DEFAULT);
    $upd = $pdo->prepare("UPDATE Admin SET adminPswd=? WHERE adminID=?");
    $upd->execute([$hash, $id]);
    $_SESSION['flash'] = 'Password updated successfully';
    return redirect('admin/profile');
  }

  public function uploadAvatar(){
    $this->ensureAdmin(); csrf_verify();
    if(empty($_FILES['avatar']) || !is_uploaded_file($_FILES['avatar']['tmp_name'])){ $_SESSION['flash']='Please choose an image.'; return redirect('admin/profile'); }
    $f = $_FILES['avatar'];
    $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
    if(!in_array($ext, ['png','jpg','jpeg'])){ $_SESSION['flash']='Avatar must be PNG or JPG.'; return redirect('admin/profile'); }
    if(($f['size'] ?? 0) > 2*1024*1024){ $_SESSION['flash']='Avatar too large (max 2MB).'; return redirect('admin/profile'); }

    $id = Auth::user()['id'];
    $dir = dirname(__DIR__,2).'/public/uploads/avatars';
    if(!is_dir($dir)) @mkdir($dir,0777,true);
    $dest = $dir.'/admin_'.preg_replace('/[^A-Za-z0-9_\-]/','',$id).'.png';
    // Convert to PNG if JPG uploaded
    $tmp = $f['tmp_name'];
    try{
      if($ext==='png'){
        @move_uploaded_file($tmp, $dest);
      } else {
        $img = @imagecreatefromjpeg($tmp);
        if($img){ @imagepng($img, $dest); @imagedestroy($img); }
        else { @move_uploaded_file($tmp, $dest); }
      }
    }catch(\Throwable $e){ @move_uploaded_file($tmp, $dest); }

    // Update DB column if present
    try{
      $pdo = DB::conn();
      $url = app_url('uploads/avatars/'.basename($dest));
      $pdo->query("SELECT adminAvatar FROM Admin LIMIT 1");
      $u = $pdo->prepare("UPDATE Admin SET adminAvatar=? WHERE adminID=?");
      $u->execute([$url, $id]);
    }catch(\Throwable $e){ /* optional */ }

    $_SESSION['flash']='Avatar updated';
    return redirect('admin/profile');
  }
}
