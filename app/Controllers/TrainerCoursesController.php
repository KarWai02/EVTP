<?php
class TrainerCoursesController {
  private function ensureTrainer(){ Auth::requireRole(['trainer']); }
  private function trainerId(){ return Auth::user()['id']; }

  public function create(){
    $this->ensureTrainer();
    $errors = $_SESSION['errors'] ?? []; $old = $_SESSION['old'] ?? [];
    unset($_SESSION['errors'], $_SESSION['old']);
    return render('trainer/courses/form', ['mode'=>'create','errors'=>$errors,'old'=>$old]);
  }

  public function store(){
    $this->ensureTrainer(); csrf_verify();
    $pdo = DB::conn(); $tid = $this->trainerId();
    $title = trim($_POST['courseTitle'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $sector = trim($_POST['sector'] ?? '');
    $description = trim($_POST['description'] ?? '');

    $errors=[]; if($title===''){ $errors['courseTitle']='Title is required'; }
    if($errors){ $_SESSION['errors']=$errors; $_SESSION['old']=$_POST; return redirect('trainer/courses/create'); }

    $id = gen_id($pdo, 'Course', 'courseID', 'CRS');
    // Try preferred schema with sector and adminID optional
    try{
      $stmt = $pdo->prepare("INSERT INTO Course (courseID, trainerID, courseTitle, description, category, sector, createdDate) VALUES (?,?,?,?,?,?,?)");
      $stmt->execute([$id,$tid,$title,$description,$category,$sector,date('Y-m-d')]);
    }catch(Throwable $e1){
      try{
        $stmt = $pdo->prepare("INSERT INTO Course (courseID, trainerID, courseTitle, description, category, createdDate) VALUES (?,?,?,?,?,?)");
        $stmt->execute([$id,$tid,$title,$description,$category,date('Y-m-d')]);
      }catch(Throwable $e2){
        // last fallback minimal
        $stmt = $pdo->prepare("INSERT INTO Course (courseID, trainerID, courseTitle, createdDate) VALUES (?,?,?,?)");
        $stmt->execute([$id,$tid,$title,date('Y-m-d')]);
      }
    }
    $_SESSION['flash']='Course created';
    return redirect('trainer/courses');
  }

  public function edit(){
    $this->ensureTrainer();
    $pdo = DB::conn(); $id = $_GET['id'] ?? '';
    $s = $pdo->prepare("SELECT * FROM Course WHERE courseID=?"); $s->execute([$id]);
    $course = $s->fetch(); if(!$course){ http_response_code(404); echo 'Course not found'; return; }
    // Ownership: resolve current trainerID from session first, then fall back to trainers.userID mapping
    try{
      $tid = Auth::user()['id'] ?? null; // for trainer logins, this is already trainerID
      if(!$tid){ try{ $q=$pdo->prepare("SELECT trainerID FROM trainers WHERE userID=? LIMIT 1"); $q->execute([Auth::user()['id'] ?? null]); $tid=$q->fetchColumn(); }catch(\Throwable $e){} }
      if($tid && array_key_exists('trainerID',$course) && !empty($course['trainerID']) && $course['trainerID']!==$tid){
        $_SESSION['flash']='You can only edit your own course.'; return redirect('trainer/courses');
      }
    }catch(\Throwable $e){}
    // Trainers list (optional)
    $trainers=[]; try{ $t=$pdo->query("SELECT trainerID, COALESCE(fullName, trainerID) AS name FROM trainers"); $trainers = $t? $t->fetchAll():[]; }catch(\Throwable $e){}
    $errors = $_SESSION['errors'] ?? []; $old = $_SESSION['old'] ?? [];
    unset($_SESSION['errors'], $_SESSION['old']);
    // Render trainer-specific edit view
    return render('trainer/courses/edit', ['mode'=>'edit','course'=>$course,'errors'=>$errors,'old'=>$old]);
  }

  public function update(){
    $this->ensureTrainer(); csrf_verify();
    $pdo = DB::conn();
    $id = $_POST['id'] ?? '';
    $title = trim($_POST['courseTitle'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $sector = trim($_POST['sector'] ?? '');
    $level  = trim($_POST['level'] ?? '');
    $createdDate = trim($_POST['createdDate'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $errors=[]; if($title===''){ $errors['courseTitle']='Title is required'; }
    // Ownership
    try{
      $tid = Auth::user()['id'] ?? null; if(!$tid){ try{ $q=$pdo->prepare("SELECT trainerID FROM trainers WHERE userID=? LIMIT 1"); $q->execute([Auth::user()['id'] ?? null]); $tid=$q->fetchColumn(); }catch(\Throwable $e){} }
      if($tid){ try{ $c=$pdo->prepare("SELECT trainerID FROM Course WHERE courseID=?"); $c->execute([$id]); $cid=$c->fetchColumn(); if($cid && $cid!==$tid){ $errors['course']='You can only update your own course'; } }catch(\Throwable $e){} }
    }catch(\Throwable $e){}
    if($errors){ $_SESSION['errors']=$errors; $_SESSION['old']=$_POST; return redirect('trainer/courses/edit?id='.$id); }
    // Update only the columns that exist in the Course table
    $existing = [];
    try{
      $schema = $pdo->query("SHOW COLUMNS FROM Course");
      if($schema){ foreach($schema->fetchAll() as $r){ $f = $r['Field'] ?? ($r['COLUMN_NAME'] ?? ''); if($f!=='') $existing[$f]=true; } }
    }catch(\Throwable $eS){
      try{
        $schema = $pdo->query("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_NAME='Course'");
        if($schema){ foreach($schema->fetchAll() as $r){ $f = $r['COLUMN_NAME'] ?? ''; if($f!=='') $existing[$f]=true; } }
      }catch(\Throwable $eS2){ /* ignore */ }
    }

    $set = [];$params=[];
    $set[]='courseTitle=?'; $params[]=$title;
    if(!empty($existing['description'])){ $set[]='description=?'; $params[]=$description; }
    if(!empty($existing['category']))   { $set[]='category=?';    $params[]=$category; }
    if(!empty($existing['sector']))     { $set[]='sector=?';      $params[]=$sector; }
    if(!empty($existing['level']) || !empty($existing['Level']))      { $set[]=( !empty($existing['level']) ? 'level' : 'Level')."=?";       $params[]=$level; }
    elseif(!empty($existing['courseLevel']) || !empty($existing['CourseLevel'])) { $set[]=( !empty($existing['courseLevel']) ? 'courseLevel' : 'CourseLevel')."=?"; $params[]=$level; }
    elseif(!empty($existing['courseLvl'])   || !empty($existing['CourseLvl']))   { $set[]=( !empty($existing['courseLvl']) ? 'courseLvl' : 'CourseLvl')."=?";   $params[]=$level; }
    elseif(!empty($existing['course_level'])|| !empty($existing['Course_Level'])){ $set[]=( !empty($existing['course_level']) ? 'course_level' : 'Course_Level')."=?"; $params[]=$level; }
    elseif(!empty($existing['lvl'])         || !empty($existing['Lvl']))         { $set[]=( !empty($existing['lvl']) ? 'lvl' : 'Lvl')."=?";         $params[]=$level; }
    elseif(!empty($existing['difficultyLevel']) || !empty($existing['DifficultyLevel'])){ $set[]=( !empty($existing['difficultyLevel']) ? 'difficultyLevel' : 'DifficultyLevel')."=?"; $params[]=$level; }
    elseif(!empty($existing['difficulty'])  || !empty($existing['Difficulty']))  { $set[]=( !empty($existing['difficulty']) ? 'difficulty' : 'Difficulty')."=?";  $params[]=$level; }
    if(!empty($existing['createdDate']) && $createdDate!==''){ $set[]='createdDate=?'; $params[]=$createdDate; }
    $params[]=$id;
    try{
      $upd = $pdo->prepare("UPDATE Course SET ".implode(',', $set)." WHERE courseID=?");
      $upd->execute($params);
    }catch(\Throwable $e1){
      // Fallback: update each available column individually
      try{ $pdo->prepare("UPDATE Course SET courseTitle=? WHERE courseID=?")->execute([$title,$id]); }catch(\Throwable $e){}
      if(!empty($existing['description'])){ try{ $pdo->prepare("UPDATE Course SET description=? WHERE courseID=?")->execute([$description,$id]); }catch(\Throwable $e){} }
      if(!empty($existing['category']))   { try{ $pdo->prepare("UPDATE Course SET category=? WHERE courseID=?")->execute([$category,$id]); }catch(\Throwable $e){} }
      if(!empty($existing['sector']))     { try{ $pdo->prepare("UPDATE Course SET sector=? WHERE courseID=?")->execute([$sector,$id]); }catch(\Throwable $e){} }
      if(!empty($existing['level']) || !empty($existing['Level']))      { try{ $pdo->prepare("UPDATE Course SET ".( !empty($existing['level']) ? 'level' : 'Level')."=? WHERE courseID=?")->execute([$level,$id]); }catch(\Throwable $e){} }
      elseif(!empty($existing['courseLevel']) || !empty($existing['CourseLevel'])) { try{ $pdo->prepare("UPDATE Course SET ".( !empty($existing['courseLevel']) ? 'courseLevel' : 'CourseLevel')."=? WHERE courseID=?")->execute([$level,$id]); }catch(\Throwable $e){} }
      elseif(!empty($existing['courseLvl'])   || !empty($existing['CourseLvl']))   { try{ $pdo->prepare("UPDATE Course SET ".( !empty($existing['courseLvl']) ? 'courseLvl' : 'CourseLvl')."=? WHERE courseID=?")->execute([$level,$id]); }catch(\Throwable $e){} }
      elseif(!empty($existing['course_level'])|| !empty($existing['Course_Level'])){ try{ $pdo->prepare("UPDATE Course SET ".( !empty($existing['course_level']) ? 'course_level' : 'Course_Level')."=? WHERE courseID=?")->execute([$level,$id]); }catch(\Throwable $e){} }
      elseif(!empty($existing['lvl'])         || !empty($existing['Lvl']))         { try{ $pdo->prepare("UPDATE Course SET ".( !empty($existing['lvl']) ? 'lvl' : 'Lvl')."=? WHERE courseID=?")->execute([$level,$id]); }catch(\Throwable $e){} }
      elseif(!empty($existing['difficultyLevel']) || !empty($existing['DifficultyLevel'])){ try{ $pdo->prepare("UPDATE Course SET ".( !empty($existing['difficultyLevel']) ? 'difficultyLevel' : 'DifficultyLevel')."=? WHERE courseID=?")->execute([$level,$id]); }catch(\Throwable $e){} }
      elseif(!empty($existing['difficulty'])  || !empty($existing['Difficulty']))  { try{ $pdo->prepare("UPDATE Course SET ".( !empty($existing['difficulty']) ? 'difficulty' : 'Difficulty')."=? WHERE courseID=?")->execute([$level,$id]); }catch(\Throwable $e){} }
      if(!empty($existing['createdDate']) && $createdDate!==''){ try{ $pdo->prepare("UPDATE Course SET createdDate=? WHERE courseID=?")->execute([$createdDate,$id]); }catch(\Throwable $e){} }
    }
    $_SESSION['flash']='Course updated';
    return redirect('trainer/courses');
  }
}
