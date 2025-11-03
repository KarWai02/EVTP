<?php
class AdminCoursesController {
  private function ensureAdmin(){ Auth::requireRole(['admin']); }
  private function ensureTrainerOrAdmin(){ Auth::requireRole(['admin','trainer']); }

  public function index(){
    $this->ensureAdmin();
    $pdo = DB::conn();
    $q = trim($_GET['q'] ?? '');
    $page = max(1, (int)($_GET['page'] ?? 1));
    $pp = (int)($_GET['pp'] ?? 10); $perPage = in_array($pp,[10,25,50],true)?$pp:10; $offset = ($page-1)*$perPage;

    $base = "FROM Course";
    $where=''; $params=[];
    if($q!==''){ $where = " WHERE courseTitle LIKE ? OR category LIKE ?"; $params=['%'.$q.'%','%'.$q.'%']; }

    $c = $pdo->prepare("SELECT COUNT(*) cnt $base$where"); $c->execute($params); $total=(int)($c->fetch()['cnt']??0);
    $pages = max(1,(int)ceil($total/$perPage)); if($page>$pages){ $page=$pages; $offset=($page-1)*$perPage; }

    $list = $pdo->prepare("SELECT courseID, courseTitle, category, createdDate $base$where ORDER BY createdDate DESC LIMIT $perPage OFFSET $offset");
    $list->execute($params); $rows=$list->fetchAll();

    return render('admin/courses/index', compact('rows','q','page','pages','total','perPage'));
  }

  public function create(){
    $this->ensureAdmin();
    $pdo = DB::conn();
    // Optional: load trainers for selection
    $trainers = [];
    try{ $t=$pdo->query("SELECT trainerID, COALESCE(fullName, trainerID) AS name FROM trainers"); $trainers = $t ? $t->fetchAll() : []; }catch(\Throwable $e){}
    $errors = $_SESSION['errors'] ?? []; $old = $_SESSION['old'] ?? [];
    unset($_SESSION['errors'], $_SESSION['old']);
    return render('admin/courses/form', ['mode'=>'create','course'=>[],'errors'=>$errors,'old'=>$old,'trainers'=>$trainers]);
  }

  public function store(){
    $this->ensureAdmin(); csrf_verify();
    $title = trim($_POST['courseTitle'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $sector = trim($_POST['sector'] ?? '');
    $level  = trim($_POST['level'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $createdDate = trim($_POST['createdDate'] ?? date('Y-m-d'));

    $errors=[];
    if($title===''){ $errors['courseTitle']='Title is required'; }
    if($errors){ $_SESSION['errors']=$errors; $_SESSION['old']=$_POST; return redirect('admin/courses/create'); }

    $pdo = DB::conn();
    $id = gen_id($pdo, 'Course', 'courseID', 'CRS');
    // Resolve trainerID to satisfy FK if present/not-null
    // Accept admin-specified trainerID when provided
    $trainerID = trim($_POST['trainerID'] ?? '') ?: null;
    try{
      // Try map current user -> trainers.userID
      if($trainerID===null && class_exists('Auth') && Auth::check()){
        $uid = Auth::user()['id'] ?? null;
        if($uid){
          try{ $t=$pdo->prepare("SELECT trainerID FROM trainers WHERE userID=? LIMIT 1"); $t->execute([$uid]); $trainerID=$t->fetch()['trainerID'] ?? null; }catch(\Throwable $eT){}
        }
      }
      if($trainerID===null){
        $t2=$pdo->query("SELECT trainerID FROM trainers LIMIT 1"); $trainerID=$t2?($t2->fetch()['trainerID'] ?? null):null;
      }
    }catch(\Throwable $e){ $trainerID=null; }

    // If still null, attempt to auto-create a trainer for the current user when none exist
    if($trainerID===null){
      try{
        $cntQ = $pdo->query("SELECT COUNT(*) AS c FROM trainers");
        $cnum = $cntQ ? (int)($cntQ->fetch()['c'] ?? 0) : 0;
        if($cnum === 0){
          $newTid = gen_id($pdo, 'trainers', 'trainerID', 'TRN');
          $fullName = '';
          $uid = null;
          if(class_exists('Auth') && Auth::check()){
            $u = Auth::user(); $fullName = trim(($u['name'] ?? '') ?: (($u['fullName'] ?? '') ?: 'Admin Trainer'));
            $uid = $u['id'] ?? null;
          }
          // Try rich insert with userID + fullName; fallback to minimal
          try{
            $it = $pdo->prepare("INSERT INTO trainers (trainerID, userID, fullName, created_at) VALUES (?,?,?,?)");
            $it->execute([$newTid, $uid, ($fullName ?: 'Trainer'), date('Y-m-d')]);
          }catch(\Throwable $eT1){
            try{ $it=$pdo->prepare("INSERT INTO trainers (trainerID, fullName) VALUES (?,?)"); $it->execute([$newTid, ($fullName ?: 'Trainer')]); }catch(\Throwable $eT2){ $it=$pdo->prepare("INSERT INTO trainers (trainerID) VALUES (?)"); $it->execute([$newTid]); }
          }
          $trainerID = $newTid;
        }
      }catch(\Throwable $e){ /* ignore */ }
    }
    // If still null, fail gracefully to avoid FK error
    if($trainerID===null){
      $_SESSION['errors']=['trainerID'=>'Please create/select a trainer first.'];
      $_SESSION['old']=$_POST;
      return redirect('admin/courses/create');
    }
    // Verify trainer exists
    try{
      $chkT = $pdo->prepare("SELECT 1 FROM trainers WHERE trainerID=?");
      $chkT->execute([$trainerID]);
      if(!$chkT->fetch()){
        $_SESSION['errors']=['trainerID'=>'Selected trainer does not exist.'];
        $_SESSION['old']=$_POST;
        return redirect('admin/courses/create');
      }
    }catch(\Throwable $e){}

    // Try rich insert; fallback gracefully if optional columns are missing
    try{
      if($trainerID!==null){
        $ins = $pdo->prepare("INSERT INTO Course (courseID, courseTitle, description, category, sector, level, createdDate, trainerID) VALUES (?,?,?,?,?,?,?,?)");
        $ins->execute([$id,$title,$description,$category,$sector,$level,$createdDate,$trainerID]);
      } else {
        $ins = $pdo->prepare("INSERT INTO Course (courseID, courseTitle, description, category, sector, level, createdDate) VALUES (?,?,?,?,?,?,?)");
        $ins->execute([$id,$title,$description,$category,$sector,$level,$createdDate]);
      }
    }catch(\Throwable $e1){
      // Likely trainer FK or schema constraint — return to form with error
      $_SESSION['errors']=['trainerID'=>'Could not assign trainer (FK). Please select a valid trainer.'];
      $_SESSION['old']=$_POST;
      return redirect('admin/courses/create');
    }
    $_SESSION['flash']='Course created';
    return redirect('admin/courses');
  }

  public function edit(){
    $this->ensureTrainerOrAdmin();
    $pdo = DB::conn();
    $id = $_GET['id'] ?? '';
    $s = $pdo->prepare("SELECT * FROM Course WHERE courseID=?");
    $s->execute([$id]); $course=$s->fetch(); if(!$course){ http_response_code(404); echo 'Course not found'; return; }
    // If trainer, ensure they own the course when Course.trainerID exists
    try{
      if((Auth::user()['role'] ?? '')==='trainer'){
        $tid = null; try{ $q=$pdo->prepare("SELECT trainerID FROM trainers WHERE userID=? LIMIT 1"); $q->execute([Auth::user()['id'] ?? null]); $tid=$q->fetchColumn(); }catch(\Throwable $e){}
        if($tid){
          if(array_key_exists('trainerID', $course) && !empty($course['trainerID']) && $course['trainerID'] !== $tid){
            $_SESSION['flash'] = 'You can only edit your own course.';
            return redirect('trainer/courses');
          }
        }
      }
    }catch(\Throwable $e){}
    $trainers = [];
    try{ $t=$pdo->query("SELECT trainerID, COALESCE(fullName, trainerID) AS name FROM trainers"); $trainers = $t ? $t->fetchAll() : []; }catch(\Throwable $e){}
    $errors = $_SESSION['errors'] ?? []; $old = $_SESSION['old'] ?? [];
    unset($_SESSION['errors'], $_SESSION['old']);
    return render('admin/courses/form', ['mode'=>'edit','course'=>$course,'errors'=>$errors,'old'=>$old,'trainers'=>$trainers]);
  }

  public function update(){
    $this->ensureTrainerOrAdmin(); csrf_verify();
    $pdo = DB::conn();
    $id = $_POST['id'] ?? '';
    $title = trim($_POST['courseTitle'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $sector = trim($_POST['sector'] ?? '');
    $level  = trim($_POST['level'] ?? '');
    $createdDate = trim($_POST['createdDate'] ?? '');
    $description = trim($_POST['description'] ?? '');

    $errors=[]; if($title===''){ $errors['courseTitle']='Title is required'; }
    // If trainer, enforce ownership when Course.trainerID exists
    try{
      if((Auth::user()['role'] ?? '')==='trainer'){
        $tid = null; try{ $q=$pdo->prepare("SELECT trainerID FROM trainers WHERE userID=? LIMIT 1"); $q->execute([Auth::user()['id'] ?? null]); $tid=$q->fetchColumn(); }catch(\Throwable $e){}
        if($tid){
          try{ $c=$pdo->prepare("SELECT trainerID FROM Course WHERE courseID=?"); $c->execute([$id]); $cid=$c->fetchColumn(); if($cid && $cid !== $tid){ $errors['course']='You can only update your own course'; } }catch(\Throwable $e){}
        }
      }
    }catch(\Throwable $e){}
    if($errors){ $_SESSION['errors']=$errors; $_SESSION['old']=$_POST; return redirect('admin/courses/edit?id='.$id); }

    // Try rich update first, then fallback to minimal available columns
    try{
      $cols = [];$params=[];
      $cols[]='courseTitle=?'; $params[]=$title;
      $cols[]='description=?'; $params[]=$description;
      $cols[]='category=?';    $params[]=$category;
      // Optional columns may not exist; try including them first
      $cols[]='sector=?';      $params[]=$sector;
      $cols[]='level=?';       $params[]=$level;
      if($createdDate!==''){ $cols[]='createdDate=?'; $params[]=$createdDate; }
      $params[]=$id;
      $sql = "UPDATE Course SET ".implode(',', $cols)." WHERE courseID=?";
      $upd = $pdo->prepare($sql); $upd->execute($params);
    }catch(\Throwable $e1){
      try{
        $upd = $pdo->prepare("UPDATE Course SET courseTitle=?, description=?, category=? WHERE courseID=?");
        $upd->execute([$title,$description,$category,$id]);
      }catch(\Throwable $e2){
        $upd = $pdo->prepare("UPDATE Course SET courseTitle=? WHERE courseID=?");
        $upd->execute([$title,$id]);
      }
    }
    $_SESSION['flash']='Course updated';
    return redirect('admin/courses');
  }

  public function delete(){
    $this->ensureAdmin(); csrf_verify();
    $pdo = DB::conn();
    $id = $_POST['id'] ?? '';
    $del = $pdo->prepare("DELETE FROM Course WHERE courseID=?");
    $del->execute([$id]);
    $_SESSION['flash']='Course deleted';
    return redirect('admin/courses');
  }

  // ---- Modules management ----
  public function modules(){
    $this->ensureTrainerOrAdmin();
    $pdo = DB::conn(); $courseID = $_GET['courseID'] ?? '';
    $c = $pdo->prepare("SELECT * FROM Course WHERE courseID=?"); $c->execute([$courseID]);
    $course = $c->fetch(); if(!$course){ http_response_code(404); echo 'Course not found'; return; }
    // Order by moduleOrder when column exists; otherwise fall back to moduleID
    try {
      $m = $pdo->prepare("SELECT * FROM Modules WHERE courseID=? ORDER BY moduleOrder, moduleID");
      $m->execute([$courseID]); $modules=$m->fetchAll();
    } catch(\Throwable $e) {
      $m = $pdo->prepare("SELECT * FROM Modules WHERE courseID=? ORDER BY moduleID");
      $m->execute([$courseID]); $modules=$m->fetchAll();
    }
    // Normalize minutes for view/edit: prefer estimatedDuration; else use duration/minutes/mins/totalMinutes
    foreach($modules as &$mx0){
      $mins = null;
      foreach(['estimatedDuration','duration','minutes','mins','totalMinutes','EstimatedDuration','Duration','Minutes'] as $col){
        if(array_key_exists($col,$mx0) && $mx0[$col] !== null && $mx0[$col] !== ''){ $mins = (int)$mx0[$col]; break; }
      }
      if($mins !== null){ $mx0['estimatedDuration'] = $mins; }
    }
    unset($mx0);
    // Parse META counts for display
    foreach($modules as &$mx){
      $desc = (string)($mx['description'] ?? '');
      if(preg_match('/\\[META\\](\{.*\})/s',$desc,$mm)){
        $meta = json_decode($mm[1],true);
        if(is_array($meta)){
          $mx['videoCount']=(int)($meta['videoCount']??0);
          $mx['taskCount']=(int)($meta['taskCount']??0);
          $mx['quizCount']=(int)($meta['quizCount']??0);
        }
      }
    }
    unset($mx);
    $errors = $_SESSION['errors'] ?? []; $old = $_SESSION['old'] ?? [];
    unset($_SESSION['errors'], $_SESSION['old']);
    return render('admin/courses/modules', compact('course','modules','errors','old'));
  }

  public function addModule(){
    $this->ensureTrainerOrAdmin(); csrf_verify();
    $pdo = DB::conn();
    $courseID = $_POST['courseID'] ?? '';
    $content = trim($_POST['content'] ?? '');
    $mins = (int)($_POST['estimatedDuration'] ?? 0);
    $desc = trim($_POST['description'] ?? '');
    $video = max(0,(int)($_POST['videoCount'] ?? 0));
    $task  = max(0,(int)($_POST['taskCount'] ?? 0));
    $quiz  = max(0,(int)($_POST['quizCount'] ?? 0));
    $videoTitles = array_values(array_filter(array_map('trim', preg_split('/\r?\n/', (string)($_POST['videoTitles'] ?? '')))));
    $videoUrls   = array_values(array_filter(array_map('trim', preg_split('/\r?\n/', (string)($_POST['videoUrls'] ?? '')))));
    $taskTitles  = array_values(array_filter(array_map('trim', preg_split('/\r?\n/', (string)($_POST['taskTitles'] ?? '')))));
    $quizUrl     = trim((string)($_POST['quizUrl'] ?? ''));
    $order = (int)($_POST['moduleOrder'] ?? 0);
    if($content===''){ $_SESSION['errors']=['content'=>'Title is required']; $_SESSION['old']=$_POST; return redirect('admin/courses/modules?courseID='.$courseID); }
    // Append META json
    $meta = '[META]'.json_encode(['videoCount'=>$video,'taskCount'=>$task,'quizCount'=>$quiz,'videoTitles'=>$videoTitles,'videoUrls'=>$videoUrls,'taskTitles'=>$taskTitles,'quizUrl'=>$quizUrl]);
    $combined = $desc ? ($desc."\n".$meta) : $meta;
    if($order<=0){ $order = (int)date('His'); }
    $mid = gen_id($pdo,'Modules','moduleID','MOD');
    try{
      $ins = $pdo->prepare("INSERT INTO Modules (moduleID, courseID, content, estimatedDuration, description, moduleOrder) VALUES (?,?,?,?,?,?)");
      $ins->execute([$mid,$courseID,$content,$mins,$combined,$order]);
    }catch(\Throwable $e){
      try{ $ins=$pdo->prepare("INSERT INTO Modules (moduleID, courseID, content, estimatedDuration, description) VALUES (?,?,?,?,?)"); $ins->execute([$mid,$courseID,$content,$mins,$combined]); }catch(\Throwable $e2){ $ins=$pdo->prepare("INSERT INTO Modules (moduleID, courseID, content) VALUES (?,?,?)"); $ins->execute([$mid,$courseID,$content]); }
    }
    $_SESSION['flash']='Module added';
    return redirect('admin/courses/modules?courseID='.$courseID);
  }

  public function updateModule(){
    $this->ensureTrainerOrAdmin(); csrf_verify();
    $pdo = DB::conn();
    $moduleID = $_POST['moduleID'] ?? '';
    $courseID = $_POST['courseID'] ?? '';
    $content = trim($_POST['content'] ?? '');
    $mins = (int)($_POST['estimatedDuration'] ?? 0);
    $desc = trim($_POST['description'] ?? '');
    $video = max(0,(int)($_POST['videoCount'] ?? 0));
    $task  = max(0,(int)($_POST['taskCount'] ?? 0));
    $quiz  = max(0,(int)($_POST['quizCount'] ?? 0));
    $order = (int)($_POST['moduleOrder'] ?? 0);
    // New fields for richer content
    $videoTitles = array_values(array_filter(array_map('trim', preg_split('/\r?\n/', (string)($_POST['videoTitles'] ?? '')))));
    $videoUrls   = array_values(array_filter(array_map('trim', preg_split('/\r?\n/', (string)($_POST['videoUrls'] ?? '')))));
    $taskTitles  = array_values(array_filter(array_map('trim', preg_split('/\r?\n/', (string)($_POST['taskTitles'] ?? '')))));
    $quizUrl     = trim((string)($_POST['quizUrl'] ?? ''));
    if($content===''){ $_SESSION['errors']=['content'=>'Title is required']; $_SESSION['old']=$_POST; return redirect('admin/courses/modules?courseID='.$courseID); }
    $meta = '[META]'.json_encode([
      'videoCount'=>$video,
      'taskCount'=>$task,
      'quizCount'=>$quiz,
      'videoTitles'=>$videoTitles,
      'videoUrls'=>$videoUrls,
      'taskTitles'=>$taskTitles,
      'quizUrl'=>$quizUrl
    ]);
    $combined = $desc ? ($desc."\n".$meta) : $meta;
    // Detect which minutes column exists in Modules table
    $minCol = null; $existing = [];
    try{
      $schema = $pdo->query("SHOW COLUMNS FROM Modules");
      if($schema){ foreach($schema->fetchAll() as $r){ $f = $r['Field'] ?? ($r['COLUMN_NAME'] ?? ''); if($f!=='') $existing[$f]=true; } }
    }catch(\Throwable $eS){ try{ $schema=$pdo->query("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_NAME='Modules'"); if($schema){ foreach($schema->fetchAll() as $r){ $f=$r['COLUMN_NAME']??''; if($f!=='') $existing[$f]=true; } } }catch(\Throwable $eS2){} }
    foreach(['estimatedDuration','duration','minutes','mins','totalMinutes','EstimatedDuration','Duration','Minutes'] as $c){ if(!empty($existing[$c])){ $minCol=$c; break; } }

    // Build dynamic update
    $sets = ['content=?','description=?']; $params = [$content,$combined];
    if($minCol!==null){ $sets[] = "$minCol=?"; $params[] = $mins; }
    if(!empty($existing['moduleOrder'])){ $sets[] = 'moduleOrder=?'; $params[] = $order; }
    $params[] = $moduleID;
    try{
      $sql = "UPDATE Modules SET ".implode(',', $sets)." WHERE moduleID=?";
      $upd = $pdo->prepare($sql); $upd->execute($params);
    }catch(\Throwable $e){
      // Fallback per-column updates
      try{ $pdo->prepare("UPDATE Modules SET content=? WHERE moduleID=?")->execute([$content,$moduleID]); }catch(\Throwable $e1){}
      try{ if($minCol!==null){ $pdo->prepare("UPDATE Modules SET $minCol=? WHERE moduleID=?")->execute([$mins,$moduleID]); } }catch(\Throwable $e2){}
      try{ $pdo->prepare("UPDATE Modules SET description=? WHERE moduleID=?")->execute([$combined,$moduleID]); }catch(\Throwable $e3){}
      try{ if(!empty($existing['moduleOrder'])){ $pdo->prepare("UPDATE Modules SET moduleOrder=? WHERE moduleID=?")->execute([$order,$moduleID]); } }catch(\Throwable $e4){}
    }
    $_SESSION['flash']='Module updated';
    return redirect('admin/courses/modules?courseID='.$courseID);
  }

  public function deleteModule(){
    $this->ensureTrainerOrAdmin(); csrf_verify();
    $pdo = DB::conn(); $moduleID = $_POST['moduleID'] ?? ''; $courseID = $_POST['courseID'] ?? '';
    $del = $pdo->prepare("DELETE FROM Modules WHERE moduleID=?"); $del->execute([$moduleID]);
    $_SESSION['flash']='Module deleted';
    return redirect('admin/courses/modules?courseID='.$courseID);
  }

  public function reorderModules(){
    $this->ensureTrainerOrAdmin(); csrf_verify();
    $pdo = DB::conn();
    // Expect order[] = moduleID in desired order and courseID
    $courseID = $_POST['courseID'] ?? '';
    $orderArr = $_POST['order'] ?? [];
    if(!is_array($orderArr)) $orderArr = [];
    $pos = 1;
    foreach($orderArr as $mid){
      if($mid==='') continue;
      try{ $u=$pdo->prepare("UPDATE Modules SET moduleOrder=? WHERE moduleID=? AND courseID=?"); $u->execute([$pos,$mid,$courseID]); }catch(\Throwable $e){}
      $pos++;
    }
    header('Content-Type: application/json'); echo json_encode(['ok'=>true]);
  }
}
