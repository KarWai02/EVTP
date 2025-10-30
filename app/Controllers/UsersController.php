<?php
class UsersController {
  private function mapRole($role){
    $role = strtolower($role ?: 'learner');
    return [
      'admin'    => ['table'=>'Admin',     'id'=>'adminID',    'name'=>'adminName',    'email'=>'adminEmail',    'phone'=>'adminPhone',    'pswd'=>'adminPswd'],
      'trainer'  => ['table'=>'Trainers',  'id'=>'trainerID',  'name'=>'trainerName',  'email'=>'trainerEmail',  'phone'=>'trainerPhone',  'pswd'=>'trainerPswd'],
      'employer' => ['table'=>'Employers', 'id'=>'employerID', 'name'=>'contactPerson','email'=>'employerEmail', 'phone'=>'companyPhone',  'pswd'=>'employerPswd'],
      'learner'  => ['table'=>'Learners',  'id'=>'learnerID',  'name'=>'learnerName',  'email'=>'learnerEmail',  'phone'=>'learnerPhone',  'pswd'=>'learnerPswd'],
    ][$role] ?? null;
  }

  private function ensureAdmin(){ Auth::requireRole(['admin']); }

  public function index(){
    $this->ensureAdmin();
    $pdo = DB::conn();
    $role = $_GET['role'] ?? 'learner';
    $map = $this->mapRole($role); if(!$map){ http_response_code(404); echo 'Unknown role'; return; }
    $q = trim($_GET["q"] ?? '');
    $sort = $_GET['sort'] ?? 'name'; // name|company|industry (employer only)
    $view = $_GET['view'] ?? ($role==='employer' ? 'cards' : 'table'); // table|cards (employer only)
    if(!in_array($view, ['table','cards'], true)) $view = 'table';
    $page = max(1, (int)($_GET['page'] ?? 1));
    $pp = (int)($_GET['pp'] ?? 10);
    $perPage = in_array($pp, [10,25,50], true) ? $pp : 10;
    $offset = ($page - 1) * $perPage;

    $base = "FROM {$map['table']}";
    $where = '';
    $params = [];
    if($q !== ''){ $where = " WHERE {$map['name']} LIKE ? OR {$map['email']} LIKE ?"; $params = ['%'.$q.'%','%'.$q.'%']; }

    // count total
    $countSql = "SELECT COUNT(*) AS cnt $base$where";
    $c = $pdo->prepare($countSql); $c->execute($params);
    $total = (int)($c->fetch()['cnt'] ?? 0);
    $pages = max(1, (int)ceil($total / $perPage));
    if($page > $pages) { $page = $pages; $offset = ($page - 1) * $perPage; }

    // fetch slice
    $extraCols = '';
    if(($role ?? '') === 'employer'){
      try{ $pdo->query("SELECT companyName, companyIndustry FROM {$map['table']} LIMIT 1"); $extraCols = ", companyName, companyIndustry"; }catch(\Throwable $e){ $extraCols=''; }
    }
    // determine order by
    $order = "{$map['name']} ASC";
    if($role==='employer' && $extraCols!=='' ){
      if($sort==='company') $order = "companyName ASC, {$map['name']} ASC";
      elseif($sort==='industry') $order = "companyIndustry ASC, {$map['name']} ASC";
    } else {
      $sort = 'name';
    }
    $listSql = "SELECT {$map['id']} id, {$map['name']} name, {$map['email']} email, {$map['phone']} phone$extraCols $base$where ORDER BY $order LIMIT $perPage OFFSET $offset";
    $stmt = $pdo->prepare($listSql); $stmt->execute($params); $rows = $stmt->fetchAll();

    return render('admin/users/index', compact('rows','role','page','pages','total','q','perPage','sort','view'));
  }

  public function create(){
    $this->ensureAdmin();
    $role = $_GET['role'] ?? 'learner';
    return render('admin/users/form', ['role'=>$role,'mode'=>'create','user'=>[],'errors'=>$_SESSION['errors']??[],'old'=>$_SESSION['old']??[]]);
  }

  public function store(){
    $this->ensureAdmin();
    csrf_verify();
    $role = $_POST['role'] ?? 'learner';
    $map = $this->mapRole($role); if(!$map){ $_SESSION['flash']='Unknown role'; return redirect('admin/users'); }

    $name = trim($_POST['name'] ?? '');
    $email= trim($_POST['email'] ?? '');
    $phone= trim($_POST['phone'] ?? '');

    $errors=[];
    if($name===''){ $errors['name']='Name is required'; }
    if($email===''){ $errors['email']='Email is required'; }
    elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)){ $errors['email']='Invalid email'; }
    if($phone===''){ $errors['phone']='Phone is required'; }
    elseif(!preg_match('/^[0-9+\-\s]{7,15}$/', $phone)){ $errors['phone']='Invalid phone format'; }
    // extra employer fields
    $companyName = trim($_POST['companyName'] ?? '');
    $companyIndustry = trim($_POST['companyIndustry'] ?? '');
    if($role==='employer'){
      if($companyName===''){ $errors['companyName']='Company name is required'; }
      if($companyIndustry===''){ $errors['companyIndustry']='Industry is required'; }
    }

    if($errors){ $_SESSION['errors']=$errors; $_SESSION['old']=['name'=>$name,'email'=>$email,'phone'=>$phone,'companyName'=>$companyName,'companyIndustry'=>$companyIndustry]; return redirect('admin/users/create?role='.$role); }

    $pdo = DB::conn();
    // ensure unique email in target table
    $chk = $pdo->prepare("SELECT 1 FROM {$map['table']} WHERE {$map['email']}=?");
    $chk->execute([$email]); if($chk->fetch()){ $_SESSION['errors']=['email'=>'Email already exists']; $_SESSION['old']=['name'=>$name,'email'=>$email,'phone'=>$phone]; return redirect('admin/users/create?role='.$role); }

    $prefix = strtoupper(substr($map['id'],0,3));
    if($role==='trainer'){ $prefix = 'TRN'; }
    $id = gen_id($pdo, $map['table'], $map['id'], $prefix);

    if ($role === 'admin'){
      // generate a strong temporary password and hash it
      $temp = self::genTempPass();
      $hash = password_hash($temp, PASSWORD_DEFAULT);
      $sql = "INSERT INTO {$map['table']} ({$map['id']}, {$map['name']}, {$map['email']}, {$map['phone']}, {$map['pswd']}) VALUES (?,?,?,?,?)";
      $ins = $pdo->prepare($sql);
      $ins->execute([$id,$name,$email,$phone,$hash]);
      $_SESSION['flash'] = 'Created admin successfully';
      $_SESSION['temp_pass'] = ['email'=>$email,'password'=>$temp]; // show once

      // Also email the temporary password and a reset link
      try {
        $token = bin2hex(random_bytes(16));
        $exp = time() + 3600; // 1 hour
        $path = __DIR__.'/../../storage/resets.json';
        $data = file_exists($path) ? json_decode(file_get_contents($path), true) : [];
        // prune expired
        $now = time(); foreach ($data as $tk=>$row){ if(($row['exp'] ?? 0) < $now) unset($data[$tk]); }
        $data[$token] = ['email'=>$email,'exp'=>$exp];
        file_put_contents($path, json_encode($data));
        $link = app_url('reset').'?token='.$token;
        $msg = "Hello $name,\n\nYour temporary admin password is: $temp\n\nFor security, please set a new password using this link (valid 1 hour): $link\n\nRegards, EVTP";
        if(function_exists('send_mail')){ send_mail($email, 'Your EVTP admin account', $msg); }
      } catch (Throwable $e) {
        // ignore mail errors; UI still shows the temp password once
      }

      // Flag this admin to force password change on first login
      try {
        $flagPath = __DIR__.'/../../storage/mustchange.json';
        $flags = file_exists($flagPath) ? json_decode(file_get_contents($flagPath), true) : [];
        if(!is_array($flags)) $flags = [];
        $flags[$email] = true;
        file_put_contents($flagPath, json_encode($flags));
      } catch (Throwable $e) { /* ignore */ }
    } elseif ($role === 'employer'){
      // Employers: include phone and generate a temporary password
      $temp = self::genTempPass();
      $hash = password_hash($temp, PASSWORD_DEFAULT);
      // try to insert with companyName and companyIndustry if present in schema
      try{
        $pdo->query("SELECT companyName, companyIndustry FROM {$map['table']} LIMIT 1");
        $sql = "INSERT INTO {$map['table']} ({$map['id']}, companyName, companyIndustry, {$map['name']}, {$map['email']}, {$map['phone']}, {$map['pswd']}) VALUES (?,?,?,?,?,?,?)";
        $ins = $pdo->prepare($sql);
        $ins->execute([$id,$companyName,$companyIndustry,$name,$email,$phone,$hash]);
      }catch(\Throwable $e){
        $sql = "INSERT INTO {$map['table']} ({$map['id']}, {$map['name']}, {$map['email']}, {$map['phone']}, {$map['pswd']}) VALUES (?,?,?,?,?)";
        $ins = $pdo->prepare($sql);
        $ins->execute([$id,$name,$email,$phone,$hash]);
      }
      $_SESSION['flash']='Created employer successfully';
      $_SESSION['temp_pass'] = ['email'=>$email,'password'=>$temp];
      try{
        $msg = "Hello $name,\n\nYour temporary employer password is: $temp\n\nFor security, you will be required to reset your password on your first login. If you prefer, you can also use the Reset Password page to set it now.";
        if(function_exists('send_mail')) send_mail($email, 'Your EVTP employer account', $msg);
      }catch(\Throwable $e){}
      // force password change on first login
      try {
        $flagPath = __DIR__.'/../../storage/mustchange.json';
        $flags = file_exists($flagPath) ? json_decode(file_get_contents($flagPath), true) : [];
        if(!is_array($flags)) $flags = [];
        $flags[$email] = true;
        file_put_contents($flagPath, json_encode($flags));
      } catch (Throwable $e) { /* ignore */ }
    } elseif ($role === 'trainer'){
      // Trainers: include phone and generate a temporary password
      $temp = self::genTempPass();
      $hash = password_hash($temp, PASSWORD_DEFAULT);
      $sql = "INSERT INTO {$map['table']} ({$map['id']}, {$map['name']}, {$map['email']}, {$map['phone']}, {$map['pswd']}) VALUES (?,?,?,?,?)";
      $ins = $pdo->prepare($sql);
      $ins->execute([$id,$name,$email,$phone,$hash]);
      $_SESSION['flash']='Created trainer successfully';
      $_SESSION['temp_pass'] = ['email'=>$email,'password'=>$temp];
      try{
        $msg = "Hello $name,\n\nYour temporary trainer password is: $temp\n\nPlease login and change it via the reset page.";
        if(function_exists('send_mail')) send_mail($email, 'Your EVTP trainer account', $msg);
      }catch(\Throwable $e){}
      // force password change on first login
      try {
        $flagPath = __DIR__.'/../../storage/mustchange.json';
        $flags = file_exists($flagPath) ? json_decode(file_get_contents($flagPath), true) : [];
        if(!is_array($flags)) $flags = [];
        $flags[$email] = true;
        file_put_contents($flagPath, json_encode($flags));
      } catch (Throwable $e) { /* ignore */ }
    } else {
      $ins = $pdo->prepare("INSERT INTO {$map['table']} ({$map['id']}, {$map['name']}, {$map['email']}, {$map['phone']}) VALUES (?,?,?,?)");
      $ins->execute([$id,$name,$email,$phone]);
      $_SESSION['flash']='Created successfully';
    }

    return redirect('admin/users?role='.$role);
  }

  // Admin utility: seed a few sample employers for testing UI
  public function seedEmployers(){
    $this->ensureAdmin();
    $pdo = DB::conn();
    $samples = [
      ['companyName'=>'ACME Sdn Bhd','industry'=>'Manufacturing','person'=>'Ali HR','email'=>'hr@acme.com','phone'=>'012-8888888'],
      ['companyName'=>'TARUMT','industry'=>'Education','person'=>'tarumt','email'=>'tarumt@gmail.com','phone'=>'012-5974630'],
      ['companyName'=>'UCSI','industry'=>'Education','person'=>'UCSI','email'=>'ucsi@gmail.com','phone'=>'03-459736056'],
      ['companyName'=>'TechWave','industry'=>'IT Services','person'=>'Jane Doe','email'=>'hr@techwave.io','phone'=>'03-77889900'],
    ];
    $temp = 'Temp123!';
    $hash = password_hash($temp, PASSWORD_DEFAULT);
    $created = 0;
    foreach($samples as $s){
      // skip if exists
      $chk = $pdo->prepare("SELECT 1 FROM Employers WHERE employerEmail=?");
      $chk->execute([$s['email']]); if($chk->fetch()) continue;
      $id = gen_id($pdo, 'Employers', 'employerID', 'EMP');
      try{
        // attempt insert with company columns
        $pdo->query("SELECT companyName, companyIndustry FROM Employers LIMIT 1");
        $ins = $pdo->prepare("INSERT INTO Employers (employerID, companyName, companyIndustry, contactPerson, employerEmail, companyPhone, employerPswd) VALUES (?,?,?,?,?,?,?)");
        $ins->execute([$id,$s['companyName'],$s['industry'],$s['person'],$s['email'],$s['phone'],$hash]);
      }catch(\Throwable $e){
        $ins = $pdo->prepare("INSERT INTO Employers (employerID, contactPerson, employerEmail, companyPhone, employerPswd) VALUES (?,?,?,?,?)");
        $ins->execute([$id,$s['person'],$s['email'],$s['phone'],$hash]);
      }
      // mark must-change
      try{
        $flagPath = __DIR__.'/../../storage/mustchange.json';
        $flags = file_exists($flagPath) ? json_decode(file_get_contents($flagPath), true) : [];
        if(!is_array($flags)) $flags = [];
        $flags[$s['email']] = true; file_put_contents($flagPath, json_encode($flags));
      }catch(\Throwable $e){ }
      $created++;
    }
    $_SESSION['flash'] = "Seeded $created employer(s). Temp password for all: $temp";
    return redirect('admin/users?role=employer&view=cards&sort=company');
  }

  private static function genTempPass(){
    $charsLower = 'abcdefghjkmnpqrstuvwxyz';
    $charsUpper = 'ABCDEFGHJKMNPQRSTUVWXYZ';
    $digits = '23456789';
    $special = '!@#$%^&*';
    $pool = $charsLower.$charsUpper.$digits.$special;
    $len = 10;
    $pw = '';
    for($i=0;$i<$len;$i++){
      $pw .= $pool[random_int(0, strlen($pool)-1)];
    }
    // ensure policy: include at least one of each
    $pw[ random_int(0,$len-1) ] = $charsLower[random_int(0, strlen($charsLower)-1)];
    $pw[ random_int(0,$len-1) ] = $charsUpper[random_int(0, strlen($charsUpper)-1)];
    $pw[ random_int(0,$len-1) ] = $digits[random_int(0, strlen($digits)-1)];
    $pw[ random_int(0,$len-1) ] = $special[random_int(0, strlen($special)-1)];
    return $pw;
  }

  public function edit(){
    $this->ensureAdmin();
    $pdo = DB::conn();
    $role = $_GET['role'] ?? 'learner';
    $map = $this->mapRole($role); if(!$map){ http_response_code(404); echo 'Unknown role'; return; }
    $id = $_GET['id'] ?? '';
    if(($role ?? '')==='employer'){
      try{
        $stmt = $pdo->prepare("SELECT {$map['id']} id, {$map['name']} name, {$map['email']} email, {$map['phone']} phone, companyName, companyIndustry FROM {$map['table']} WHERE {$map['id']}=?");
        $stmt->execute([$id]);
      }catch(\Throwable $e){
        $stmt = $pdo->prepare("SELECT {$map['id']} id, {$map['name']} name, {$map['email']} email, {$map['phone']} phone FROM {$map['table']} WHERE {$map['id']}=?");
        $stmt->execute([$id]);
      }
    } else {
      $stmt = $pdo->prepare("SELECT {$map['id']} id, {$map['name']} name, {$map['email']} email, {$map['phone']} phone FROM {$map['table']} WHERE {$map['id']}=?");
      $stmt->execute([$id]);
    }
    $user = $stmt->fetch(); if(!$user){ http_response_code(404); echo 'Not found'; return; }
    $errors = $_SESSION['errors'] ?? []; $old = $_SESSION['old'] ?? [];
    unset($_SESSION['errors'], $_SESSION['old']);
    return render('admin/users/form', ['role'=>$role,'mode'=>'edit','user'=>$user,'errors'=>$errors,'old'=>$old]);
  }

  public function update(){
    $this->ensureAdmin();
    csrf_verify();
    $role = $_POST['role'] ?? 'learner';
    $map = $this->mapRole($role); if(!$map){ $_SESSION['flash']='Unknown role'; return redirect('admin/users'); }
    $id = $_POST['id'] ?? '';

    $name = trim($_POST['name'] ?? '');
    $email= trim($_POST['email'] ?? '');
    $phone= trim($_POST['phone'] ?? '');

    $errors=[];
    if($name===''){ $errors['name']='Name is required'; }
    if($email===''){ $errors['email']='Email is required'; }
    elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)){ $errors['email']='Invalid email'; }
    if($phone===''){ $errors['phone']='Phone is required'; }
    elseif(!preg_match('/^[0-9+\-\s]{7,15}$/', $phone)){ $errors['phone']='Invalid phone format'; }

    if($errors){ $_SESSION['errors']=$errors; $_SESSION['old']=['name'=>$name,'email'=>$email,'phone'=>$phone,'companyName'=>$companyName,'companyIndustry'=>$companyIndustry]; return redirect('admin/users/edit?id='.$id.'&role='.$role); }

    $pdo = DB::conn();
    // ensure email unique (exclude current)
    $chk = $pdo->prepare("SELECT 1 FROM {$map['table']} WHERE {$map['email']}=? AND {$map['id']}<>?");
    $chk->execute([$email,$id]); if($chk->fetch()){ $_SESSION['errors']=['email'=>'Email already exists']; $_SESSION['old']=['name'=>$name,'email'=>$email,'phone'=>$phone]; return redirect('admin/users/edit?id='.$id.'&role='.$role); }

    if($role==='employer'){
      try{
        $pdo->query("SELECT companyName, companyIndustry FROM {$map['table']} LIMIT 1");
        $upd = $pdo->prepare("UPDATE {$map['table']} SET companyName=?, companyIndustry=?, {$map['name']}=?, {$map['email']}=?, {$map['phone']}=? WHERE {$map['id']}=?");
        $upd->execute([$companyName,$companyIndustry,$name,$email,$phone,$id]);
      }catch(\Throwable $e){
        $upd = $pdo->prepare("UPDATE {$map['table']} SET {$map['name']}=?, {$map['email']}=?, {$map['phone']}=? WHERE {$map['id']}=?");
        $upd->execute([$name,$email,$phone,$id]);
      }
    } else {
      $upd = $pdo->prepare("UPDATE {$map['table']} SET {$map['name']}=?, {$map['email']}=?, {$map['phone']}=? WHERE {$map['id']}=?");
      $upd->execute([$name,$email,$phone,$id]);
    }
    $_SESSION['flash']='Updated successfully';
    return redirect('admin/users?role='.$role);
  }

  public function delete(){
    $this->ensureAdmin();
    csrf_verify();
    $role = $_POST['role'] ?? 'learner';
    $map = $this->mapRole($role); if(!$map){ $_SESSION['flash']='Unknown role'; return redirect('admin/users'); }
    $id = $_POST['id'] ?? '';
    $pdo = DB::conn();
    $del = $pdo->prepare("DELETE FROM {$map['table']} WHERE {$map['id']}=?");
    $del->execute([$id]);
    $_SESSION['flash']='Deleted successfully';
    return redirect('admin/users?role='.$role);
  }
}
