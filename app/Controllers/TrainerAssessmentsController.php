<?php
class TrainerAssessmentsController {
  private function ensureTrainer(){ Auth::requireRole(['trainer']); }
  private function trainerId(){ return Auth::user()['id']; }

  // List assessments for a module
  public function index(){
    $this->ensureTrainer();
    $pdo = DB::conn(); $tid = $this->trainerId();
    $module = $_GET['module'] ?? '';
    if($module===''){ http_response_code(400); echo 'module required'; return; }
    // optional check: module belongs to a course of this trainer
    try{
      $chk = $pdo->prepare("SELECT c.courseID FROM Modules m JOIN Course c ON c.courseID=m.courseID WHERE m.moduleID=? AND c.trainerID=?");
      $chk->execute([$module,$tid]); if(!$chk->fetch()){ http_response_code(403); echo 'Forbidden'; return; }
    }catch(Throwable $e){ /* skip strict check if schema differs */ }

    $rows=[]; try{
      $s = $pdo->prepare("SELECT assessmentID, assessType, passScore, maxScore, durationLimit FROM Assessment WHERE moduleID=? ORDER BY assessmentID DESC");
      $s->execute([$module]); $rows = $s->fetchAll();
    }catch(Throwable $e){ $rows = []; }

    return render('trainer/assessments/index', ['moduleID'=>$module,'rows'=>$rows]);
  }

  public function create(){
    $this->ensureTrainer();
    $module = $_GET['module'] ?? '';
    $errors = $_SESSION['errors'] ?? []; $old = $_SESSION['old'] ?? [];
    unset($_SESSION['errors'], $_SESSION['old']);
    return render('trainer/assessments/form', ['mode'=>'create','assessment'=>[],'moduleID'=>$module,'errors'=>$errors,'old'=>$old]);
  }

  public function store(){
    $this->ensureTrainer(); csrf_verify();
    $pdo = DB::conn();
    $module = $_POST['moduleID'] ?? '';
    $type = trim($_POST['assessType'] ?? 'quiz');
    $pass = (int)($_POST['passScore'] ?? 0);
    $max  = (int)($_POST['maxScore'] ?? 100);
    $dur  = (int)($_POST['durationLimit'] ?? 0);

    $errors=[]; if($module===''){ $errors['moduleID']='Module required'; }
    if($max<=0){ $errors['maxScore']='Max score must be > 0'; }
    if($pass<0 || $pass>$max){ $errors['passScore']='Pass score must be between 0 and Max'; }
    if($errors){ $_SESSION['errors']=$errors; $_SESSION['old']=$_POST; return redirect('trainer/assessments/create?module='.$module); }

    $id = gen_id($pdo,'Assessment','assessmentID','ASM');
    try{
      $ins = $pdo->prepare("INSERT INTO Assessment (assessmentID, moduleID, assessType, passScore, maxScore, durationLimit) VALUES (?,?,?,?,?,?)");
      $ins->execute([$id,$module,$type,$pass,$max,$dur]);
      $_SESSION['flash']='Assessment created';
    }catch(Throwable $e){ $_SESSION['flash']='Failed to create assessment'; }
    return redirect('trainer/assessments?module='.$module);
  }

  public function edit(){
    $this->ensureTrainer();
    $pdo = DB::conn(); $id = $_GET['id'] ?? '';
    $s = $pdo->prepare("SELECT * FROM Assessment WHERE assessmentID=?");
    $s->execute([$id]); $a = $s->fetch(); if(!$a){ http_response_code(404); echo 'Not found'; return; }
    $errors = $_SESSION['errors'] ?? []; $old = $_SESSION['old'] ?? [];
    unset($_SESSION['errors'], $_SESSION['old']);
    return render('trainer/assessments/form', ['mode'=>'edit','assessment'=>$a,'moduleID'=>$a['moduleID'] ?? '','errors'=>$errors,'old'=>$old]);
  }

  public function update(){
    $this->ensureTrainer(); csrf_verify();
    $pdo = DB::conn();
    $id   = $_POST['id'] ?? '';
    $type = trim($_POST['assessType'] ?? 'quiz');
    $pass = (int)($_POST['passScore'] ?? 0);
    $max  = (int)($_POST['maxScore'] ?? 100);
    $dur  = (int)($_POST['durationLimit'] ?? 0);

    $errors=[]; if($max<=0){ $errors['maxScore']='Max score must be > 0'; }
    if($pass<0 || $pass>$max){ $errors['passScore']='Pass score must be between 0 and Max'; }
    if($errors){ $_SESSION['errors']=$errors; $_SESSION['old']=$_POST; return redirect('trainer/assessments/edit?id='.$id); }

    try{
      $upd = $pdo->prepare("UPDATE Assessment SET assessType=?, passScore=?, maxScore=?, durationLimit=? WHERE assessmentID=?");
      $upd->execute([$type,$pass,$max,$dur,$id]);
      $_SESSION['flash']='Assessment updated';
    }catch(Throwable $e){ $_SESSION['flash']='Failed to update'; }
    // find module to redirect back
    $m = $pdo->prepare("SELECT moduleID FROM Assessment WHERE assessmentID=?");
    $m->execute([$id]); $module = ($m->fetch()['moduleID'] ?? '');
    return redirect('trainer/assessments?module='.$module);
  }

  public function delete(){
    $this->ensureTrainer(); csrf_verify();
    $pdo = DB::conn(); $id = $_POST['id'] ?? '';
    $m = $pdo->prepare("SELECT moduleID FROM Assessment WHERE assessmentID=?");
    $m->execute([$id]); $module = ($m->fetch()['moduleID'] ?? '');
    try{
      $del = $pdo->prepare("DELETE FROM Assessment WHERE assessmentID=?");
      $del->execute([$id]); $_SESSION['flash']='Assessment deleted';
    }catch(Throwable $e){ $_SESSION['flash']='Failed to delete'; }
    return redirect('trainer/assessments?module='.$module);
  }
}
