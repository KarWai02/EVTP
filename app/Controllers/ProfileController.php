<?php
class ProfileController {
  public function edit(){
    Auth::requireRole(['learner']);
    $pdo = DB::conn();
    $id = Auth::user()['id'];
    $stmt = $pdo->prepare("SELECT learnerID, learnerName, learnerEmail, learnerPhone FROM Learners WHERE learnerID=?");
    $stmt->execute([$id]);
    $learner = $stmt->fetch();
    if(!$learner){ $_SESSION['flash']='Profile not found'; return redirect('dashboard'); }

    // pull validation context from session
    $errors = $_SESSION['errors'] ?? [];
    $old    = $_SESSION['old']    ?? [];
    unset($_SESSION['errors'], $_SESSION['old']);

    // basic stats and recent enrollments
    $stats = [
      'enrolled'   => 0,
      'in_progress'=> 0,
      'completed'  => 0,
    ];
    $st = $pdo->prepare("SELECT 
        COUNT(*) AS enrolled,
        SUM(CASE WHEN completionStatus='In Progress' THEN 1 ELSE 0 END) AS in_progress,
        SUM(CASE WHEN completionStatus='Completed'   THEN 1 ELSE 0 END) AS completed
      FROM Enroll WHERE learnerID=?");
    $st->execute([$id]);
    if($row = $st->fetch()){ $stats = array_map('intval', $row); }

    $recent = $pdo->prepare("SELECT e.enrollDate, e.completionStatus, c.courseID, c.courseTitle
                              FROM Enroll e
                              JOIN Course c ON c.courseID=e.courseID
                              WHERE e.learnerID=?
                              ORDER BY e.enrollDate DESC
                              LIMIT 5");
    $recent->execute([$id]);
    $recentEnrolls = $recent->fetchAll();

    return render('profile/edit', [
      'learner'=>$learner,
      'errors'=>$errors,
      'old'=>$old,
      'stats'=>$stats,
      'recent'=>$recentEnrolls,
    ]);
  }

  public function update(){
    Auth::requireRole(['learner']);
    csrf_verify();
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $errors = [];
    if($name===''){ $errors['name']='Name is required'; }
    if($phone===''){ $errors['phone']='Phone is required'; }
    elseif(!preg_match('/^[0-9+\-\s]{7,15}$/', $phone)){ $errors['phone']='Enter a valid phone number'; }
    if($errors){ $_SESSION['errors']=$errors; $_SESSION['old']=['name'=>$name,'phone'=>$phone]; return redirect('profile'); }

    $pdo = DB::conn();
    $id = Auth::user()['id'];
    $stm = $pdo->prepare("UPDATE Learners SET learnerName=?, learnerPhone=? WHERE learnerID=?");
    $stm->execute([$name, $phone, $id]);

    // update session name so header shows new name
    $_SESSION['user']['name'] = $name;
    $_SESSION['flash'] = 'Profile updated';
    return redirect('profile');
  }

  public function updatePassword(){
    Auth::requireRole(['learner']);
    csrf_verify();
    $current = $_POST['current_password'] ?? '';
    $pass = $_POST['password'] ?? '';
    $confirm = $_POST['password_confirmation'] ?? '';
    $errors = [];
    if($current===''){ $errors['current_password']='Current password is required'; }
    if($pass===''){ $errors['password']='New password is required'; }
    elseif(!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\\d)(?=.*[^A-Za-z0-9]).{8,}$/', $pass)){
      $errors['password']='Password must be 8+ chars and include upper, lower, number, and special';
    }
    if($confirm==='' || $confirm!==$pass){ $errors['password_confirmation']='Passwords do not match'; }
    if($errors){ $_SESSION['errors']=$errors; return redirect('profile'); }

    $pdo = DB::conn();
    $id = Auth::user()['id'];
    $stm = $pdo->prepare("SELECT learnerPswd FROM Learners WHERE learnerID=?");
    $stm->execute([$id]); $row = $stm->fetch();
    if(!$row || !password_verify($current, $row['learnerPswd'])){
      $_SESSION['errors']=['current_password'=>'Current password is incorrect'];
      return redirect('profile');
    }

    $hash = password_hash($pass, PASSWORD_DEFAULT);
    $upd = $pdo->prepare("UPDATE Learners SET learnerPswd=? WHERE learnerID=?");
    $upd->execute([$hash, $id]);
    $_SESSION['flash'] = 'Password updated successfully';
    return redirect('profile');
  }

  public function enrollments(){
    Auth::requireRole(['learner']);
    $pdo = DB::conn();
    $id = Auth::user()['id'];
    $stmt = $pdo->prepare("SELECT e.enrollDate, e.progress, e.completionStatus,
                                  c.courseID, c.courseTitle, c.category
                           FROM Enroll e
                           JOIN Course c ON c.courseID=e.courseID
                           WHERE e.learnerID=?
                           ORDER BY e.enrollDate DESC");
    $stmt->execute([$id]);
    $items = $stmt->fetchAll();
    return render('profile/enrollments', ['items'=>$items]);
  }
}
