<?php
class TrainerWorkshopsController {
  private function ensureTrainer(){ Auth::requireRole(['trainer']); }
  private function trainerId(){ return Auth::user()['id']; }

  public function index(){
    $this->ensureTrainer();
    $pdo = DB::conn(); $tid = $this->trainerId();
    $q = trim($_GET['q'] ?? '');
    $scope = $_GET['scope'] ?? 'upcoming'; // upcoming|past|all
    $where = "WHERE trainerID=?"; $params = [$tid];
    if($q!==''){ $where .= " AND (workshopTitle LIKE ? OR workshopTopic LIKE ?)"; $params[]='%'.$q.'%'; $params[]='%'.$q.'%'; }
    if($scope==='upcoming'){ $where .= " AND dateTime >= NOW()"; }
    elseif($scope==='past'){ $where .= " AND dateTime < NOW()"; }

    // Detect columns to avoid selecting non-existent ones
    $hasCourse = false;
    try{
      $schema = $pdo->query("SHOW COLUMNS FROM Workshop");
      if($schema){ foreach($schema->fetchAll() as $r){ $f=$r['Field'] ?? ($r['COLUMN_NAME'] ?? ''); if($f==='courseID'){ $hasCourse=true; break; } } }
    }catch(\Throwable $e){ /* ignore */ }

    $cols = "workshopID, workshopTitle, workshopTopic, dateTime, duration, platformLink";
    if($hasCourse){ $cols .= ", courseID"; }
    $sql = "SELECT $cols FROM Workshop $where ORDER BY dateTime DESC";
    try{ $stm=$pdo->prepare($sql); $stm->execute($params); $rows=$stm->fetchAll(); }
    catch(Throwable $e){ $rows=[]; }
    return render('trainer/workshops/index', compact('rows','q','scope'));
  }

  public function create(){
    $this->ensureTrainer();
    $pdo = DB::conn(); $tid = $this->trainerId();
    // courses owned by trainer
    try{
      $s = $pdo->prepare("SELECT courseID, courseTitle FROM Course WHERE trainerID=? ORDER BY courseTitle");
      $s->execute([$tid]); $courses=$s->fetchAll();
    }catch(Throwable $e){ $courses=[]; }
    $errors = $_SESSION['errors'] ?? []; $old = $_SESSION['old'] ?? [];
    unset($_SESSION['errors'], $_SESSION['old']);
    return render('trainer/workshops/form', ['mode'=>'create','workshop'=>[],'courses'=>$courses,'errors'=>$errors,'old'=>$old]);
  }

  public function store(){
    $this->ensureTrainer(); csrf_verify();
    $pdo = DB::conn(); $tid = $this->trainerId();
    $title = trim($_POST['workshopTitle'] ?? '');
    $topic = trim($_POST['workshopTopic'] ?? '');
    $courseID = trim($_POST['courseID'] ?? '');
    $dateTime = trim($_POST['dateTime'] ?? '');
    $duration = (int)($_POST['duration'] ?? 60);
    $link = trim($_POST['platformLink'] ?? '');

    $errors=[]; if($title===''){ $errors['workshopTitle']='Title is required'; }
    if($dateTime===''){ $errors['dateTime']='Date & time is required'; }
    if($link===''){ $errors['platformLink']='Meeting link is required'; }
    if($errors){ $_SESSION['errors']=$errors; $_SESSION['old']=$_POST; return redirect('trainer/workshops/create'); }

    $id = gen_id($pdo,'Workshop','workshopID','WKS');
    try{
      $ins = $pdo->prepare("INSERT INTO Workshop (workshopID, trainerID, workshopTitle, workshopTopic, courseID, dateTime, duration, platformLink) VALUES (?,?,?,?,?,?,?,?)");
      $ins->execute([$id,$tid,$title,$topic,$courseID?:null,$dateTime,$duration,$link]);
    }catch(\Throwable $e){
      // Fallback when courseID column does not exist
      $ins = $pdo->prepare("INSERT INTO Workshop (workshopID, trainerID, workshopTitle, workshopTopic, dateTime, duration, platformLink) VALUES (?,?,?,?,?,?,?)");
      $ins->execute([$id,$tid,$title,$topic,$dateTime,$duration,$link]);
    }
    $_SESSION['flash']='Workshop scheduled';
    return redirect('trainer/workshops');
  }

  public function edit(){
    $this->ensureTrainer();
    $pdo = DB::conn(); $tid = $this->trainerId(); $id = $_GET['id'] ?? '';
    $s = $pdo->prepare("SELECT * FROM Workshop WHERE workshopID=? AND trainerID=?");
    $s->execute([$id,$tid]); $w=$s->fetch(); if(!$w){ http_response_code(404); echo 'Not found'; return; }
    try{
      $c = $pdo->prepare("SELECT courseID, courseTitle FROM Course WHERE trainerID=? ORDER BY courseTitle");
      $c->execute([$tid]); $courses=$c->fetchAll();
    }catch(Throwable $e){ $courses=[]; }
    $errors = $_SESSION['errors'] ?? []; $old = $_SESSION['old'] ?? [];
    unset($_SESSION['errors'], $_SESSION['old']);
    return render('trainer/workshops/form', ['mode'=>'edit','workshop'=>$w,'courses'=>$courses,'errors'=>$errors,'old'=>$old]);
  }

  public function update(){
    $this->ensureTrainer(); csrf_verify();
    $pdo = DB::conn(); $tid = $this->trainerId();
    $id = $_POST['id'] ?? '';
    $title = trim($_POST['workshopTitle'] ?? '');
    $topic = trim($_POST['workshopTopic'] ?? '');
    $courseID = trim($_POST['courseID'] ?? '');
    $dateTime = trim($_POST['dateTime'] ?? '');
    $duration = (int)($_POST['duration'] ?? 60);
    $link = trim($_POST['platformLink'] ?? '');

    $errors=[]; if($title===''){ $errors['workshopTitle']='Title is required'; }
    if($dateTime===''){ $errors['dateTime']='Date & time is required'; }
    if($link===''){ $errors['platformLink']='Meeting link is required'; }
    if($errors){ $_SESSION['errors']=$errors; $_SESSION['old']=$_POST; return redirect('trainer/workshops/edit?id='.$id); }

    try{
      $upd = $pdo->prepare("UPDATE Workshop SET workshopTitle=?, workshopTopic=?, courseID=?, dateTime=?, duration=?, platformLink=? WHERE workshopID=? AND trainerID=?");
      $upd->execute([$title,$topic,$courseID?:null,$dateTime,$duration,$link,$id,$tid]);
    }catch(\Throwable $e){
      // Fallback when courseID column does not exist
      $upd = $pdo->prepare("UPDATE Workshop SET workshopTitle=?, workshopTopic=?, dateTime=?, duration=?, platformLink=? WHERE workshopID=? AND trainerID=?");
      $upd->execute([$title,$topic,$dateTime,$duration,$link,$id,$tid]);
    }
    $_SESSION['flash']='Workshop updated';
    return redirect('trainer/workshops');
  }

  public function delete(){
    $this->ensureTrainer(); csrf_verify();
    $pdo = DB::conn(); $tid = $this->trainerId(); $id = $_POST['id'] ?? '';
    $del = $pdo->prepare("DELETE FROM Workshop WHERE workshopID=? AND trainerID=?");
    $del->execute([$id,$tid]);
    $_SESSION['flash']='Workshop deleted';
    return redirect('trainer/workshops');
  }

  // CRON endpoint: send reminders X hours before start
  // GET /cron/workshops/reminders?key=...&hours=2
  public function remindersCron(){
    // no role check: allow headless; optional key check
    $cfgKey = $GLOBALS['APP_CONFIG']['app']['cron_key'] ?? '';
    $reqKey = $_GET['key'] ?? '';
    if($cfgKey !== '' && hash_equals($cfgKey, (string)$reqKey) === false){ http_response_code(403); echo 'Forbidden'; return; }
    $hours = max(1, (int)($_GET['hours'] ?? 2));
    $pdo = DB::conn();
    // find workshops starting within next X hours (with courseID present)
    $q = $pdo->prepare("SELECT w.workshopID, w.workshopTitle, w.workshopTopic, w.dateTime, w.duration, w.platformLink, w.courseID,
                               t.trainerName, t.trainerEmail
                        FROM Workshop w JOIN Trainers t ON t.trainerID=w.trainerID
                        WHERE w.dateTime BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL ? HOUR)
                          AND w.courseID IS NOT NULL");
    $q->execute([$hours]); $workshops = $q->fetchAll();
    // load sent flags
    $flagPath = storage_path('workshop_reminders.json');
    $sent = file_exists($flagPath) ? json_decode(file_get_contents($flagPath), true) : [];
    if(!is_array($sent)) $sent = [];

    $totalEmails = 0; $totalSessions = 0; $skipped=0;
    foreach($workshops as $w){
      $key = $w['workshopID'].'_'.$hours;
      if(!empty($sent[$key])){ $skipped++; continue; }
      // fetch enrolled learners for course
      $s = $pdo->prepare("SELECT l.learnerName, l.learnerEmail
                          FROM Enroll e JOIN Learners l ON l.learnerID=e.learnerID
                          WHERE e.courseID=?");
      $s->execute([$w['courseID']]); $learners = $s->fetchAll();
      if(!$learners){ $sent[$key] = time(); continue; }
      $when = $w['dateTime'];
      $msgTpl = "Hello {{name}},\n\nReminder: A workshop is starting soon.\n\nTitle: {{title}}\nTopic: {{topic}}\nWhen: {{when}}\nDuration: {{dur}} minutes\nJoin: {{link}}\n\nRegards, EVTP";
      foreach($learners as $L){
        $body = str_replace([
          '{{name}}','{{title}}','{{topic}}','{{when}}','{{dur}}','{{link}}'
        ],[
          $L['learnerName'] ?? 'Learner', $w['workshopTitle'], ($w['workshopTopic'] ?? ''), $when, (string)($w['duration'] ?? 0), $w['platformLink']
        ], $msgTpl);
        if(function_exists('send_mail')) send_mail($L['learnerEmail'], 'Workshop Reminder: '.$w['workshopTitle'], $body);
        $totalEmails++;
      }
      $sent[$key] = time(); $totalSessions++;
    }
    // persist sent flags
    @file_put_contents($flagPath, json_encode($sent));
    header('Content-Type: application/json');
    echo json_encode(['ok'=>true,'hours'=>$hours,'sessions'=>$totalSessions,'emails'=>$totalEmails,'skipped'=>$skipped]);
  }
}
