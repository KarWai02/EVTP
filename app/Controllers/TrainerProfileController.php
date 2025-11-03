<?php
class TrainerProfileController {
  private function ensureTrainer(){ if(!Auth::check()){ return redirect('login'); } }

  public function edit(){
    $this->ensureTrainer();
    $pdo = DB::conn();
    $uid = Auth::user()['id'] ?? null;
    $trainer = [ 'trainerID'=>'', 'name'=>'', 'email'=>'', 'phone'=>'', 'expertise'=>'' ];
    // Try canonical schema (Trainers table)
    try{
      $s = $pdo->prepare("SELECT trainerID, COALESCE(trainerName,'') AS name, COALESCE(trainerEmail,'') AS email, COALESCE(trainerPhone,'') AS phone, COALESCE(expertise,'') AS expertise FROM Trainers WHERE userID=? LIMIT 1");
      $s->execute([$uid]); $row = $s->fetch(); if($row){ $trainer = $row; }
      else {
        // fallback: match by trainerID==userID
        $s = $pdo->prepare("SELECT trainerID, COALESCE(trainerName,'') AS name, COALESCE(trainerEmail,'') AS email, COALESCE(trainerPhone,'') AS phone, COALESCE(expertise,'') AS expertise FROM Trainers WHERE trainerID=? LIMIT 1");
        $s->execute([$uid]); $row = $s->fetch(); if($row){ $trainer = $row; }
      }
    }catch(\Throwable $e){
      // Alternate schema (lowercase table/columns)
      try{
        $s = $pdo->prepare("SELECT trainerID, COALESCE(fullName,'') AS name, COALESCE(email,'') AS email, COALESCE(phone,'') AS phone, COALESCE(expertise,'') AS expertise FROM trainers WHERE userID=? LIMIT 1");
        $s->execute([$uid]); $row = $s->fetch(); if($row){ $trainer = $row; }
      }catch(\Throwable $e2){ }
    }

    // If expertise empty, try fetch from Trainers by email/name
    if(trim((string)($trainer['expertise'] ?? ''))===''){
      try{ $q=$pdo->prepare("SELECT expertise FROM Trainers WHERE trainerEmail=? LIMIT 1"); $q->execute([ (string)($u['email'] ?? '') ]); $ex=$q->fetchColumn(); if($ex){ $trainer['expertise']=$ex; } }catch(\Throwable $e){}
      if(trim((string)($trainer['expertise'] ?? ''))===''){
        $nm = (string)($trainer['name'] ?? ($u['name'] ?? ''));
        if($nm!==''){
          try{ $q=$pdo->prepare("SELECT expertise FROM Trainers WHERE trainerName=? LIMIT 1"); $q->execute([$nm]); $ex=$q->fetchColumn(); if($ex){ $trainer['expertise']=$ex; } }catch(\Throwable $e){}
        }
      }
    }

    // If expertise still empty, hydrate from TrainerExpertise junction table when available
    if(trim((string)($trainer['expertise'] ?? ''))===''){
      try{
        $pdo->query("SELECT trainerID, skill FROM TrainerExpertise LIMIT 1");
        // find a trainerID
        $tid = $trainer['trainerID'] ?? null;
        if(!$tid){ try{ $q=$pdo->prepare("SELECT trainerID FROM Trainers WHERE userID=? LIMIT 1"); $q->execute([$uid]); $tid=$q->fetchColumn(); }catch(\Throwable $e){} }
        if(!$tid){ $tid = $uid; }
        // try by session email
        if(!$tid){ try{ $q=$pdo->prepare("SELECT trainerID FROM Trainers WHERE trainerEmail=? LIMIT 1"); $q->execute([ (string)($u['email'] ?? '') ]); $tid=$q->fetchColumn(); }catch(\Throwable $e){} }
        // try by name
        if(!$tid){
          $nm = (string)($trainer['name'] ?? ($u['name'] ?? ''));
          if($nm!==''){
            try{ $q=$pdo->prepare("SELECT trainerID FROM Trainers WHERE trainerName=? LIMIT 1"); $q->execute([$nm]); $tid=$q->fetchColumn(); }catch(\Throwable $e){}
          }
        }
        $q = $pdo->prepare("SELECT GROUP_CONCAT(skill ORDER BY skill SEPARATOR ', ') FROM TrainerExpertise WHERE trainerID=?");
        $q->execute([$tid]); $ex = $q->fetchColumn(); if($ex){ $trainer['expertise'] = $ex; }
      }catch(\Throwable $e){ }
    }

    // Fallback to session values when DB fields are empty
    $u = Auth::user() ?? [];
    if(trim((string)($trainer['name'] ?? ''))===''){ $trainer['name'] = (string)($u['name'] ?? ''); }
    if(trim((string)($trainer['email'] ?? ''))===''){ $trainer['email'] = (string)($u['email'] ?? ''); }
    if(trim((string)($trainer['phone'] ?? ''))===''){
      $trainer['phone'] = (string)($u['phone'] ?? '');
      // cross-table fallback if still empty
      if(trim((string)$trainer['phone'])===''){
        // Try Trainers by session email or name
        try{ if(!empty($u['email'] ?? '')){ $q=$pdo->prepare("SELECT trainerPhone FROM Trainers WHERE trainerEmail=? LIMIT 1"); $q->execute([$u['email']]); $p=$q->fetchColumn(); if($p){ $trainer['phone']=$p; } } }catch(\Throwable $e){}
        if(trim((string)$trainer['phone'])===''){
          try{ if(!empty($trainer['name'] ?? '')){ $q=$pdo->prepare("SELECT trainerPhone FROM Trainers WHERE trainerName=? LIMIT 1"); $q->execute([$trainer['name']]); $p=$q->fetchColumn(); if($p){ $trainer['phone']=$p; } } }catch(\Throwable $e){}
        }
        try{ $q=$pdo->prepare("SELECT learnerPhone FROM Learners WHERE learnerID=?"); $q->execute([$uid]); $p=$q->fetchColumn(); if($p){ $trainer['phone'] = $p; } }catch(\Throwable $e){}
        if(trim((string)$trainer['phone'])===''){
          try{ $q=$pdo->prepare("SELECT adminPhone FROM Admin WHERE adminID=?"); $q->execute([$uid]); $p=$q->fetchColumn(); if($p){ $trainer['phone'] = $p; } }catch(\Throwable $e){}
        }
        if(trim((string)$trainer['phone'])===''){
          try{ $q=$pdo->prepare("SELECT companyPhone FROM Employers WHERE employerID=?"); $q->execute([$uid]); $p=$q->fetchColumn(); if($p){ $trainer['phone'] = $p; } }catch(\Throwable $e){}
        }
      }
    }
    $errors = $_SESSION['errors'] ?? []; $old = $_SESSION['old'] ?? [];
    unset($_SESSION['errors'], $_SESSION['old']);
    return render('trainer/profile/edit', ['trainer'=>$trainer,'errors'=>$errors,'old'=>$old]);
  }

  public function update(){
    $this->ensureTrainer(); csrf_verify();
    $pdo = DB::conn(); $uid = Auth::user()['id'] ?? null;
    $fullName = trim($_POST['fullName'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $expertise= trim($_POST['expertise'] ?? '');
    $errors=[]; if($fullName===''){ $errors['fullName']='Name is required'; }
    if($email!=='' && !filter_var($email, FILTER_VALIDATE_EMAIL)){ $errors['email']='Invalid email'; }
    if($phone!=='' && !preg_match('/^\d{3}-\d{7,8}$/',$phone)){ $errors['phone']='Phone must be xxx-xxxxxxx or xxx-xxxxxxxx'; }
    if($errors){ $_SESSION['errors']=$errors; $_SESSION['old']=['fullName'=>$fullName,'email'=>$email,'phone'=>$phone,'expertise'=>$expertise]; return redirect('trainer/profile'); }

    try{
      // Preferred schema
      try{
        $pdo->query("SELECT trainerName, trainerEmail, trainerPhone FROM Trainers LIMIT 1");
        // if userID column exists use it; otherwise fallback by trainerID==userID
        try{ $pdo->query("SELECT userID FROM Trainers LIMIT 1"); $u = $pdo->prepare("UPDATE Trainers SET trainerName=?, trainerEmail=?, trainerPhone=?, expertise=? WHERE userID=?"); $u->execute([$fullName,$email,$phone,$expertise,$uid]); }
        catch(\Throwable $e1){ $u = $pdo->prepare("UPDATE Trainers SET trainerName=?, trainerEmail=?, trainerPhone=?, expertise=? WHERE trainerID=?"); $u->execute([$fullName,$email,$phone,$expertise,$uid]); }
      }catch(\Throwable $e){
        // Alternate schema
        $u = $pdo->prepare("UPDATE trainers SET fullName=?, email=?, phone=?, expertise=? WHERE userID=?");
        $u->execute([$fullName,$email,$phone,$expertise,$uid]);
      }
    }catch(\Throwable $e){
      try{ $u=$pdo->prepare("UPDATE trainers SET fullName=? WHERE userID=?"); $u->execute([$fullName,$uid]); }catch(\Throwable $e2){}
    }
    // Sync optional TrainerExpertise table
    try{
      $pdo->query("SELECT trainerID, skill FROM TrainerExpertise LIMIT 1");
      // find trainerID for this user
      $tid = null; try{ $q=$pdo->prepare("SELECT trainerID FROM Trainers WHERE userID=? LIMIT 1"); $q->execute([$uid]); $tid=$q->fetchColumn(); }catch(\Throwable $e){ }
      if(!$tid){ $tid = $uid; }
      $del = $pdo->prepare("DELETE FROM TrainerExpertise WHERE trainerID=?"); $del->execute([$tid]);
      if($expertise!==''){
        $tags = array_values(array_filter(array_map('trim', explode(',', $expertise))));
        $ins = $pdo->prepare("INSERT INTO TrainerExpertise (trainerID, skill) VALUES (?,?)");
        foreach($tags as $t){ if($t!=='') $ins->execute([$tid,$t]); }
      }
    }catch(\Throwable $e){ }
    // Update session so header and fallbacks show latest values
    $_SESSION['user']['name'] = $fullName;
    if($email!=='') $_SESSION['user']['email'] = $email;
    if($phone!=='') $_SESSION['user']['phone'] = $phone;
    $_SESSION['flash']='Profile updated';
    return redirect('trainer/profile');
  }

  public function updatePassword(){
    $this->ensureTrainer(); csrf_verify();
    $pdo = DB::conn(); $uid = Auth::user()['id'] ?? null;
    $current = $_POST['current_password'] ?? '';
    $pass    = $_POST['password'] ?? '';
    $confirm = $_POST['password_confirmation'] ?? '';
    $errors = [];
    if($current===''){ $errors['current_password']='Current password is required'; }
    if($pass===''){ $errors['password']='New password is required'; }
    elseif(!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}$/', $pass)){
      $errors['password']='Password must be 8+ chars and include upper, lower, number, and special';
    }
    if($confirm==='' || $confirm!==$pass){ $errors['password_confirmation']='Passwords do not match'; }
    if($errors){ $_SESSION['errors']=$errors; return redirect('trainer/profile'); }

    // fetch current hash from Trainers table (multiple schema options)
    $hash = null; $idCol='userID';
    try{ $q=$pdo->prepare("SELECT trainerPswd FROM Trainers WHERE userID=?"); $q->execute([$uid]); $hash=$q->fetchColumn(); $idCol='userID'; }
    catch(\Throwable $e){ try{ $q=$pdo->prepare("SELECT trainerPswd FROM Trainers WHERE trainerID=?"); $q->execute([$uid]); $hash=$q->fetchColumn(); $idCol='trainerID'; }catch(\Throwable $e2){ try{ $q=$pdo->prepare("SELECT password FROM trainers WHERE userID=?"); $q->execute([$uid]); $hash=$q->fetchColumn(); $idCol='userID'; }catch(\Throwable $e3){ $hash=null; } } }
    if(!$hash || !password_verify($current, $hash)){
      $_SESSION['errors']=['current_password'=>'Current password is incorrect']; return redirect('trainer/profile');
    }
    $new = password_hash($pass, PASSWORD_DEFAULT);
    try{ $u=$pdo->prepare("UPDATE Trainers SET trainerPswd=? WHERE $idCol=?"); $u->execute([$new,$uid]); }
    catch(\Throwable $e){ try{ $u=$pdo->prepare("UPDATE trainers SET password=? WHERE $idCol=?"); $u->execute([$new,$uid]); }catch(\Throwable $e2){} }
    $_SESSION['flash']='Password updated successfully';
    return redirect('trainer/profile');
  }
}
