<?php
class TrainerMaterialsController {
  private function ensureTrainer(){ Auth::requireRole(['trainer']); }
  private function trainerId(){ return Auth::user()['id']; }

  public function courses(){
    $this->ensureTrainer();
    $pdo = DB::conn();
    $tid = $this->trainerId();
    $rows=[];
    try{
      $s = $pdo->prepare("SELECT courseID, courseTitle FROM Course WHERE trainerID=? ORDER BY createdDate DESC");
      $s->execute([$tid]); $rows = $s->fetchAll();
    }catch(Throwable $e){
      $s = $pdo->query("SELECT courseID, courseTitle FROM Course ORDER BY createdDate DESC");
      $rows = $s->fetchAll();
    }
    return render('trainer/materials/courses', compact('rows'));
  }

  public function modules(){
    $this->ensureTrainer();
    $course = $_GET['course'] ?? '';
    // Reuse unified modules manager (admin/trainer)
    return redirect('admin/courses/modules?courseID='.$course);
  }

  public function createModule(){
    $this->ensureTrainer(); csrf_verify();
    $pdo = DB::conn(); $course = $_POST['course'] ?? ''; $title = trim($_POST['title'] ?? ''); $desc = trim($_POST['description'] ?? '');
    $errors=[]; if($title===''){ $errors['title']='Title is required'; }
    if($errors){ $_SESSION['errors']=$errors; return redirect('trainer/materials/modules?course='.$course); }
    try{
      $id = gen_id($pdo,'Modules','moduleID','MOD');
      // If moduleTitle column exists, populate it; otherwise store in content as a header line.
      try{
        $pdo->query("SELECT moduleTitle FROM Modules LIMIT 1");
        $ins = $pdo->prepare("INSERT INTO Modules (moduleID, courseID, moduleTitle, content) VALUES (?,?,?,?)");
        $ins->execute([$id,$course,$title,$desc]);
      }catch(Throwable $e2){
        $ins = $pdo->prepare("INSERT INTO Modules (moduleID, courseID, content) VALUES (?,?,?)");
        $ins->execute([$id,$course, ($title!==''? ($title."\n\n".$desc):$desc) ]);
      }
    }catch(Throwable $e){}
    return redirect('trainer/materials/modules?course='.$course);
  }

  public function materials(){
    $this->ensureTrainer();
    $pdo = DB::conn(); $module = $_GET['module'] ?? '';
    $m = $pdo->prepare("SELECT * FROM Material WHERE moduleID=? ORDER BY position ASC, createdDate ASC");
    try{ $m->execute([$module]); $materials=$m->fetchAll(); }catch(Throwable $e){ $materials=[]; }
    return render('trainer/materials/materials', ['moduleID'=>$module,'materials'=>$materials,'errors'=>$_SESSION['errors']??[]]);
  }

  public function storeMaterial(){
    $this->ensureTrainer(); csrf_verify();
    $pdo = DB::conn(); $module = $_POST['module'] ?? ''; $type = $_POST['type'] ?? 'link';
    $title = trim($_POST['title'] ?? ''); $url = trim($_POST['url'] ?? '');
    $errors=[]; if($title===''){ $errors['title']='Title is required'; }
    if($type==='link' && $url===''){ $errors['url']='URL required'; }
    if($errors){ $_SESSION['errors']=$errors; return redirect('trainer/materials/list?module='.$module); }
    $pos = 1; try{ $pos=(int)$pdo->query("SELECT COALESCE(MAX(position),0)+1 FROM Material WHERE moduleID='".addslashes($module)."'")->fetchColumn(); }catch(Throwable $e){}
    $filePath = null;
    if($type==='file' && isset($_FILES['file']) && is_uploaded_file($_FILES['file']['tmp_name'])){
      $dir = storage_path('uploads/materials/'.$module);
      if(!is_dir($dir)) @mkdir($dir,0777,true);
      $name = time().'_'.preg_replace('/[^A-Za-z0-9_.-]/','_', $_FILES['file']['name']);
      $dest = $dir.'/'.$name;
      if(move_uploaded_file($_FILES['file']['tmp_name'],$dest)) $filePath = 'uploads/materials/'.$module.'/'.$name;
      else $errors['file']='Upload failed';
    }
    if($errors){ $_SESSION['errors']=$errors; return redirect('trainer/materials/list?module='.$module); }
    try{
      $id = gen_id($pdo,'Material','materialID','MAT');
      $ins = $pdo->prepare("INSERT INTO Material (materialID,moduleID,type,title,filePath,url,position,version,createdDate) VALUES (?,?,?,?,?,?,?,?,?)");
      $ins->execute([$id,$module,$type,$title,$filePath,$url,$pos,1,date('Y-m-d')]);
    }catch(Throwable $e){}
    return redirect('trainer/materials/list?module='.$module);
  }

  // Allow offline download of uploaded files (secured to trainers for now)
  public function downloadMaterial(){
    $this->ensureTrainer();
    $pdo = DB::conn(); $id = $_GET['id'] ?? '';
    $s = $pdo->prepare("SELECT filePath, title FROM Material WHERE materialID=? AND type='file'");
    $s->execute([$id]); $m = $s->fetch(); if(!$m || empty($m['filePath'])){ http_response_code(404); echo 'Not found'; return; }
    $path = storage_path($m['filePath']); if(!is_file($path)){ http_response_code(404); echo 'Not found'; return; }
    $filename = basename($path);
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="'.str_replace('"','',$filename).'"');
    header('Content-Length: '.filesize($path));
    readfile($path);
    exit;
  }

  public function replaceMaterial(){
    $this->ensureTrainer(); csrf_verify();
    $pdo = DB::conn();
    $id = $_POST['materialID'] ?? '';
    // fetch current material
    $s = $pdo->prepare("SELECT materialID, moduleID, title, position, version FROM Material WHERE materialID=? AND type='file'");
    $s->execute([$id]); $cur = $s->fetch(); if(!$cur){ $_SESSION['flash']='Material not found or not a file'; return redirect('trainer/materials/list?module='.e($_POST['module'] ?? '')); }
    $module = $cur['moduleID'];
    // upload new file
    $errors=[]; $filePath=null;
    if(isset($_FILES['file']) && is_uploaded_file($_FILES['file']['tmp_name'])){
      $dir = storage_path('uploads/materials/'.$module);
      if(!is_dir($dir)) @mkdir($dir,0777,true);
      $name = time().'_'.preg_replace('/[^A-Za-z0-9_.-]/','_', $_FILES['file']['name']);
      $dest = $dir.'/'.$name;
      if(move_uploaded_file($_FILES['file']['tmp_name'],$dest)) $filePath = 'uploads/materials/'.$module.'/'.$name;
      else $errors['file']='Upload failed';
    } else { $errors['file']='File required'; }
    if($errors){ $_SESSION['errors']=$errors; return redirect('trainer/materials/list?module='.$module); }
    // next version
    $ver = 1; try{ $q=$pdo->prepare("SELECT COALESCE(MAX(version),1)+1 FROM Material WHERE moduleID=? AND title=?"); $q->execute([$module,$cur['title']]); $ver=(int)$q->fetchColumn(); }catch(Throwable $e){ $ver = ((int)$cur['version'])+1; }
    // insert new row
    try{
      $newId = gen_id($pdo,'Material','materialID','MAT');
      $ins = $pdo->prepare("INSERT INTO Material (materialID,moduleID,type,title,filePath,url,position,version,createdDate) VALUES (?,?,?,?,?,?,?,?,?)");
      $ins->execute([$newId,$module,'file',$cur['title'],$filePath,null,$cur['position'],$ver,date('Y-m-d')]);
      $_SESSION['flash']='File replaced (v'.$ver.')';
    }catch(Throwable $e){ $_SESSION['flash']='Failed to replace file'; }
    return redirect('trainer/materials/list?module='.$module);
  }
}
