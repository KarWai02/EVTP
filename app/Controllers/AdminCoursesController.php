<?php
class AdminCoursesController {
  private function ensureAdmin(){ Auth::requireRole(['admin']); }

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
    $errors = $_SESSION['errors'] ?? []; $old = $_SESSION['old'] ?? [];
    unset($_SESSION['errors'], $_SESSION['old']);
    return render('admin/courses/form', ['mode'=>'create','course'=>[],'errors'=>$errors,'old'=>$old]);
  }

  public function store(){
    $this->ensureAdmin(); csrf_verify();
    $title = trim($_POST['courseTitle'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $sector = trim($_POST['sector'] ?? '');
    $description = trim($_POST['description'] ?? '');

    $errors=[];
    if($title===''){ $errors['courseTitle']='Title is required'; }
    if($errors){ $_SESSION['errors']=$errors; $_SESSION['old']=$_POST; return redirect('admin/courses/create'); }

    $pdo = DB::conn();
    $id = gen_id($pdo, 'Course', 'courseID', 'CRS');
    $ins = $pdo->prepare("INSERT INTO Course (courseID, courseTitle, description, category, sector, createdDate) VALUES (?,?,?,?,?,?)");
    $ins->execute([$id,$title,$description,$category,$sector,date('Y-m-d')]);
    $_SESSION['flash']='Course created';
    return redirect('admin/courses');
  }

  public function edit(){
    $this->ensureAdmin();
    $pdo = DB::conn();
    $id = $_GET['id'] ?? '';
    $s = $pdo->prepare("SELECT * FROM Course WHERE courseID=?");
    $s->execute([$id]); $course=$s->fetch(); if(!$course){ http_response_code(404); echo 'Course not found'; return; }
    $errors = $_SESSION['errors'] ?? []; $old = $_SESSION['old'] ?? [];
    unset($_SESSION['errors'], $_SESSION['old']);
    return render('admin/courses/form', ['mode'=>'edit','course'=>$course,'errors'=>$errors,'old'=>$old]);
  }

  public function update(){
    $this->ensureAdmin(); csrf_verify();
    $pdo = DB::conn();
    $id = $_POST['id'] ?? '';
    $title = trim($_POST['courseTitle'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $sector = trim($_POST['sector'] ?? '');
    $description = trim($_POST['description'] ?? '');

    $errors=[]; if($title===''){ $errors['courseTitle']='Title is required'; }
    if($errors){ $_SESSION['errors']=$errors; $_SESSION['old']=$_POST; return redirect('admin/courses/edit?id='.$id); }

    $upd = $pdo->prepare("UPDATE Course SET courseTitle=?, description=?, category=?, sector=? WHERE courseID=?");
    $upd->execute([$title,$description,$category,$sector,$id]);
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
}
