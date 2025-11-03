<?php
class EmployerTalentController {
  private function ensureEmployer(){ Auth::requireRole(['employer']); }
  private function employerId(){ return Auth::user()['id']; }

  public function index(){
    $this->ensureEmployer();
    $pdo = DB::conn();
    $q = trim($_GET['q'] ?? ''); // name/email contains
    $course = trim($_GET['course'] ?? ''); // course title contains
    $minCompleted = (int)($_GET['min_completed'] ?? 0);
    $onlyShortlisted = (int)($_GET['shortlisted'] ?? 0) === 1;

    // Base query: learners with counts of completed courses
    $sql = "SELECT l.learnerID, l.learnerName, l.learnerEmail, l.learnerPhone,
                   COALESCE(SUM(CASE WHEN e.completionStatus='Completed' THEN 1 ELSE 0 END),0) AS completed
            FROM Learners l
            LEFT JOIN Enroll e ON e.learnerID = l.learnerID";
    $where = [];
    $params = [];
    if($q !== ''){ $where[] = "(l.learnerName LIKE ? OR l.learnerEmail LIKE ?)"; $params[]='%'.$q.'%'; $params[]='%'.$q.'%'; }
    if($course !== ''){
      $sql .= " LEFT JOIN Course c ON c.courseID = e.courseID";
      $where[] = "(c.courseTitle LIKE ?)"; $params[]='%'.$course.'%';
    }
    $sql .= ($where? " WHERE ".implode(' AND ',$where) : '')." GROUP BY l.learnerID ORDER BY completed DESC, l.learnerName ASC LIMIT 50";

    $stm = $pdo->prepare($sql); $stm->execute($params); $rows = $stm->fetchAll();
    if($minCompleted>0){ $rows = array_values(array_filter($rows, function($r) use($minCompleted){ return (int)($r['completed'] ?? 0) >= $minCompleted; })); }

    // Load shortlist
    $eid = $this->employerId();
    $list = $this->readShortlist($eid);

    if($onlyShortlisted){
      $rows = array_values(array_filter($rows, function($r) use($list){ return !empty($list[$r['learnerID']]); }));
    }

    return render('employer/talent/index', [
      'rows'=>$rows,
      'q'=>$q,
      'course'=>$course,
      'minCompleted'=>$minCompleted,
      'onlyShortlisted'=>$onlyShortlisted,
      'shortlist'=>$list,
    ]);
  }

  public function shortlist(){
    $this->ensureEmployer(); csrf_verify();
    $eid = $this->employerId();
    $lid = trim($_POST['learnerID'] ?? '');
    $action = $_POST['action'] ?? 'add';
    if($lid===''){ return redirect('employer/talent'); }
    $list = $this->readShortlist($eid);
    if($action==='remove'){
      unset($list[$lid]);
    } else {
      $list[$lid] = true;
    }
    $this->writeShortlist($eid, $list);
    $_SESSION['flash'] = $action==='remove' ? 'Removed from shortlist' : 'Added to shortlist';
    return redirect('employer/talent');
  }

  private function readShortlist($eid){
    $path = __DIR__.'/../../storage/shortlist_'.$eid.'.json';
    if(file_exists($path)){
      $data = json_decode(file_get_contents($path), true);
      if(is_array($data)) return $data;
    }
    return [];
  }
  private function writeShortlist($eid, $list){
    $path = __DIR__.'/../../storage/shortlist_'.$eid.'.json';
    @file_put_contents($path, json_encode($list));
  }

  public function profile(){
    $this->ensureEmployer();
    $pdo = DB::conn();
    $lid = trim($_GET['learner'] ?? '');
    if($lid===''){ http_response_code(404); echo 'Learner not found'; return; }
    $s = $pdo->prepare("SELECT learnerID, learnerName, learnerEmail, learnerPhone FROM Learners WHERE learnerID=?");
    $s->execute([$lid]); $learner = $s->fetch();
    if(!$learner){ http_response_code(404); echo 'Learner not found'; return; }
    $c = $pdo->prepare("SELECT c.courseID, c.courseTitle, e.completionStatus, e.enrollDate FROM Enroll e JOIN Course c ON c.courseID=e.courseID WHERE e.learnerID=? ORDER BY e.enrollDate DESC LIMIT 10");
    $c->execute([$lid]); $courses = $c->fetchAll();
    // shortlist state
    $eid = $this->employerId();
    $inShort = !empty($this->readShortlist($eid)[$lid]);
    return render('employer/talent/profile', ['learner'=>$learner,'courses'=>$courses,'inShort'=>$inShort]);
  }
}
