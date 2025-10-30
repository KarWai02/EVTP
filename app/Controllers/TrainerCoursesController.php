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
}
