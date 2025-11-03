<?php
class EmployerJobsController {
  private function ensureEmployer(){ Auth::requireRole(['employer']); }
  private function employerId(){ return Auth::user()['id']; }

  public function index(){
    $this->ensureEmployer();
    $pdo = DB::conn();
    $eid = $this->employerId();
    $q = trim($_GET['q'] ?? '');
    $page = max(1, (int)($_GET['page'] ?? 1));
    $pp = (int)($_GET['pp'] ?? 10); $perPage = in_array($pp,[10,25,50],true)?$pp:10; $offset = ($page-1)*$perPage;

    $where = "WHERE employerID=?"; $params = [$eid];
    if($q !== ''){ $where .= " AND (jobTitle LIKE ? OR location LIKE ?)"; $params[]='%'.$q.'%'; $params[]='%'.$q.'%'; }

    $c = $pdo->prepare("SELECT COUNT(*) FROM JobPosting $where");
    $c->execute($params); $total = (int)$c->fetchColumn();
    $pages = max(1,(int)ceil($total/$perPage)); if($page>$pages){ $page=$pages; $offset=($page-1)*$perPage; }

    // Try to include extended fields if available; fall back gracefully
    $rows = [];
    try{
      $sql = "SELECT jobID, jobTitle, location, salary, postDate, closedDate, jobType, salaryMin, salaryMax, deadline, skills FROM JobPosting $where ORDER BY COALESCE(closedDate, postDate) DESC LIMIT $perPage OFFSET $offset";
      $stm = $pdo->prepare($sql); $stm->execute($params); $rows = $stm->fetchAll();
    }catch(\Throwable $e){
      $sql = "SELECT jobID, jobTitle, location, salary, postDate, closedDate FROM JobPosting $where ORDER BY COALESCE(closedDate, postDate) DESC LIMIT $perPage OFFSET $offset";
      $stm = $pdo->prepare($sql); $stm->execute($params); $rows = $stm->fetchAll();
    }

    return render('employer/jobs/index', compact('rows','q','page','pages','perPage','total'));
  }

  public function create(){
    $this->ensureEmployer();
    $errors = $_SESSION['errors'] ?? []; $old = $_SESSION['old'] ?? [];
    unset($_SESSION['errors'], $_SESSION['old']);
    return render('employer/jobs/form', ['mode'=>'create','job'=>[],'errors'=>$errors,'old'=>$old]);
  }

  public function store(){
    $this->ensureEmployer(); csrf_verify();
    $pdo = DB::conn(); $eid = $this->employerId();
    $title = trim($_POST['jobTitle'] ?? '');
    $desc = trim($_POST['jobDesc'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $salary = isset($_POST['salary']) && $_POST['salary'] !== '' ? (float)$_POST['salary'] : null; // legacy single salary
    // Extended fields
    $jobType = trim($_POST['jobType'] ?? '');
    $salaryMin = $_POST['salaryMin'] !== '' ? (float)$_POST['salaryMin'] : null;
    $salaryMax = $_POST['salaryMax'] !== '' ? (float)$_POST['salaryMax'] : null;
    $deadline  = trim($_POST['deadline'] ?? '');
    $skills    = trim($_POST['skills'] ?? '');
    $education = trim($_POST['educationReq'] ?? '');

    $errors=[]; if($title===''){ $errors['jobTitle']='Title is required'; }
    if($errors){ $_SESSION['errors']=$errors; $_SESSION['old']=$_POST; return redirect('employer/jobs/create'); }

    $id = gen_id($pdo, 'JobPosting', 'jobID', 'JOB');
    $ins = $pdo->prepare("INSERT INTO JobPosting (jobID, employerID, jobTitle, jobDesc, location, salary, postDate) VALUES (?,?,?,?,?,?,?)");
    $ins->execute([$id,$eid,$title,$desc,$location,$salary,date('Y-m-d')]);
    // Try to set extended fields if columns exist
    try{
      $u = $pdo->prepare("UPDATE JobPosting SET jobType=?, salaryMin=?, salaryMax=?, deadline=?, skills=?, educationReq=? WHERE jobID=? AND employerID=?");
      $u->execute([$jobType,$salaryMin,$salaryMax,($deadline?:null),$skills,$education,$id,$eid]);
    }catch(\Throwable $e){ /* ignore if columns not present */ }
    $_SESSION['flash'] = 'Job posted';
    return redirect('employer/jobs');
  }

  public function edit(){
    $this->ensureEmployer();
    $pdo = DB::conn(); $eid = $this->employerId(); $id = $_GET['id'] ?? '';
    $s = $pdo->prepare("SELECT * FROM JobPosting WHERE jobID=? AND employerID=?");
    $s->execute([$id,$eid]); $job = $s->fetch(); if(!$job){ http_response_code(404); echo 'Job not found'; return; }
    $errors = $_SESSION['errors'] ?? []; $old = $_SESSION['old'] ?? [];
    unset($_SESSION['errors'], $_SESSION['old']);
    return render('employer/jobs/form', ['mode'=>'edit','job'=>$job,'errors'=>$errors,'old'=>$old]);
  }

  public function update(){
    $this->ensureEmployer(); csrf_verify();
    $pdo = DB::conn(); $eid = $this->employerId();
    $id = $_POST['id'] ?? '';
    $title = trim($_POST['jobTitle'] ?? '');
    $desc = trim($_POST['jobDesc'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $salary = isset($_POST['salary']) && $_POST['salary'] !== '' ? (float)$_POST['salary'] : null; // legacy single salary
    // Extended fields
    $jobType = trim($_POST['jobType'] ?? '');
    $salaryMin = $_POST['salaryMin'] !== '' ? (float)$_POST['salaryMin'] : null;
    $salaryMax = $_POST['salaryMax'] !== '' ? (float)$_POST['salaryMax'] : null;
    $deadline  = trim($_POST['deadline'] ?? '');
    $skills    = trim($_POST['skills'] ?? '');
    $education = trim($_POST['educationReq'] ?? '');
    $close  = isset($_POST['close']) ? date('Y-m-d') : null;

    $errors=[]; if($title===''){ $errors['jobTitle']='Title is required'; }
    if($errors){ $_SESSION['errors']=$errors; $_SESSION['old']=$_POST; return redirect('employer/jobs/edit?id='.$id); }

    $u = $pdo->prepare("UPDATE JobPosting SET jobTitle=?, jobDesc=?, location=?, salary=?, closedDate=? WHERE jobID=? AND employerID=?");
    $u->execute([$title,$desc,$location,$salary,$close,$id,$eid]);
    // Try to update extended fields
    try{
      $ux = $pdo->prepare("UPDATE JobPosting SET jobType=?, salaryMin=?, salaryMax=?, deadline=?, skills=?, educationReq=? WHERE jobID=? AND employerID=?");
      $ux->execute([$jobType,$salaryMin,$salaryMax,($deadline?:null),$skills,$education,$id,$eid]);
    }catch(\Throwable $e){ /* ignore */ }
    $_SESSION['flash']='Job updated';
    return redirect('employer/jobs');
  }

  public function delete(){
    $this->ensureEmployer(); csrf_verify();
    $pdo = DB::conn(); $eid = $this->employerId(); $id = $_POST['id'] ?? '';
    $d = $pdo->prepare("DELETE FROM JobPosting WHERE jobID=? AND employerID=?");
    $d->execute([$id,$eid]);
    $_SESSION['flash']='Job deleted';
    return redirect('employer/jobs');
  }

  public function applicants(){
    $this->ensureEmployer();
    $pdo = DB::conn(); $eid = $this->employerId(); $job = $_GET['job'] ?? '';
    // authorize job belongs to employer
    $chk = $pdo->prepare("SELECT jobTitle FROM JobPosting WHERE jobID=? AND employerID=?");
    $chk->execute([$job,$eid]); $jobRow = $chk->fetch(); if(!$jobRow){ http_response_code(404); echo 'Job not found'; return; }

    $status = $_GET['status'] ?? '';
    $where = "WHERE a.jobID=?"; $params = [$job];
    if($status!==''){ $where.=" AND a.appStatus=?"; $params[]=$status; }

    $sql = "SELECT a.appID, a.applicationDate, a.appStatus, a.coverText,
                   l.learnerID, l.learnerName, l.learnerEmail
            FROM Application a JOIN Learners l ON l.learnerID=a.learnerID
            $where
            ORDER BY a.applicationDate DESC";
    $stm = $pdo->prepare($sql); $stm->execute($params); $apps = $stm->fetchAll();

    return render('employer/jobs/applicants', ['jobID'=>$job,'jobTitle'=>$jobRow['jobTitle'],'apps'=>$apps,'status'=>$status]);
  }

  public function updateStatus(){
    $this->ensureEmployer(); csrf_verify();
    $pdo = DB::conn(); $eid = $this->employerId();
    $app = $_POST['appID'] ?? ''; $status = $_POST['status'] ?? '';
    // ensure app belongs to employer via job link
    $ok = $pdo->prepare("SELECT 1 FROM Application a JOIN JobPosting j ON j.jobID=a.jobID WHERE a.appID=? AND j.employerID=?");
    $ok->execute([$app,$eid]); if(!$ok->fetch()){ http_response_code(403); echo 'Not allowed'; return; }
    $u = $pdo->prepare("UPDATE Application SET appStatus=? WHERE appID=?");
    $u->execute([$status,$app]);
    $_SESSION['flash']='Status updated';
    $job = $_POST['job'] ?? '';
    return redirect('employer/jobs/applicants?job='.$job);
  }

  public function downloadResume(){
    $this->ensureEmployer();
    $pdo = DB::conn(); $eid = $this->employerId();
    $app = $_GET['app'] ?? '';
    if($app===''){ http_response_code(400); echo 'Missing app ID'; return; }
    // authorize app belongs to this employer
    $ok = $pdo->prepare("SELECT a.appID, a.jobID FROM Application a JOIN JobPosting j ON j.jobID=a.jobID WHERE a.appID=? AND j.employerID=?");
    $ok->execute([$app,$eid]); $row = $ok->fetch(); if(!$row){ http_response_code(403); echo 'Not allowed'; return; }

    $path = null; $filename = 'resume';
    // Try DB resumePath if column exists
    try{
      $q = $pdo->prepare("SELECT resumePath FROM Application WHERE appID=?");
      $q->execute([$app]); $rp = $q->fetchColumn();
      if($rp && file_exists($rp)){ $path = $rp; }
    }catch(\Throwable $e){ /* column may not exist */ }

    if(!$path){
      $base = dirname(__DIR__,2).'/storage/applications/'.preg_replace('/[^A-Za-z0-9_\-]/','',$app);
      foreach(['pdf','doc','docx'] as $ext){
        $p = $base.'/resume.'.$ext; if(file_exists($p)){ $path=$p; $filename='resume.'.$ext; break; }
      }
    }

    if(!$path || !file_exists($path)){ http_response_code(404); echo 'Resume not found'; return; }

    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    $mime = $ext==='pdf' ? 'application/pdf' : ($ext==='doc' ? 'application/msword' : 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    header('Content-Type: '.$mime);
    header('Content-Length: '.filesize($path));
    header('Content-Disposition: attachment; filename="'.$filename.'"');
    readfile($path);
    exit;
  }
}
