<?php
class CourseController {
  public function index(){
    $pdo = DB::conn();

      $q      = trim($_GET['q'] ?? '');
      $cat    = trim($_GET['cat'] ?? '');
      $dur    = trim($_GET['dur'] ?? '');      // '', lt1h, 1-3h, gt3h
      $sector = trim($_GET['sector'] ?? '');
      $level  = trim($_GET['level'] ?? '');
      $sort   = trim($_GET['sort'] ?? 'new');  // 'new' | 'title'
      $page   = max(1, (int)($_GET['page'] ?? 1));
      $perPage = 9;
      $offset  = ($page - 1) * $perPage;

    // Base FROM/JOIN used for both count and list
    $baseJoin = "FROM Course c
            LEFT JOIN (
              SELECT courseID, SUM(estimatedDuration) AS totalMins
              FROM Modules GROUP BY courseID
            ) m ON m.courseID = c.courseID";

    $where = [];
    $params = [];
    if($cat !== ''){ $where[] = 'c.category = ?'; $params[] = $cat; }
    
    // Only apply sector filter if helper returns sectors (column likely exists)
    $sectorsList = function_exists('course_sectors') ? course_sectors() : [];
    if($sector !== '' && !empty($sectorsList)){
      $where[] = 'c.sector = ?';
      $params[] = $sector;
    }
    $levelsList = function_exists('course_levels') ? course_levels() : [];
    if($level !== '' && !empty($levelsList)){
      $where[] = 'c.level = ?';
      $params[] = $level;
    }
    if($q !== ''){
      $like = '%'.$q.'%';
      $where[] = '(c.courseTitle LIKE ? OR c.description LIKE ? OR c.category LIKE ?)';
      array_push($params, $like,$like,$like);
    }
    if($dur !== ''){
      if($dur==='lt1h'){ $where[] = 'COALESCE(m.totalMins,0) < 60'; }
      elseif($dur==='1-3h'){ $where[] = 'COALESCE(m.totalMins,0) BETWEEN 60 AND 180'; }
      elseif($dur==='gt3h'){ $where[] = 'COALESCE(m.totalMins,0) > 180'; }
    }
    $whereSql = $where ? (' WHERE '.implode(' AND ', $where)) : '';
    $orderBy = ($sort === 'title') ? 'c.courseTitle ASC' : 'c.createdDate DESC';

    // Count total for pagination
    $countSql = "SELECT COUNT(*) AS cnt $baseJoin $whereSql";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $total = (int)($countStmt->fetch()['cnt'] ?? 0);
    $pages = max(1, (int)ceil($total / $perPage));

    // --- data query ---
    $listSql = "SELECT c.courseID, c.courseTitle, c.description, c.category, c.createdDate,
                       COALESCE(m.totalMins,0) AS totalMins
                $baseJoin
                $whereSql
                ORDER BY $orderBy
                LIMIT $perPage OFFSET $offset";

    $stmt = $pdo->prepare($listSql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    return render('courses/list', [
       'courses'      => $rows,
       'query'        => $q,
       'activeCat'    => $cat,
       'activeDur'    => $dur,
       'activeSector' => $sector,
       'activeLevel'  => $level,
       'sort'         => $sort,
       'page'         => $page,
       'pages'        => $pages,
       'total'        => $total,
       'categories'   => function_exists('course_categories') ? course_categories() : [],
       'sectors'      => $sectorsList,
       'levels'       => $levelsList
    ]);
  }

  public function view(){
    $id = $_GET['id'] ?? '';
    $pdo = DB::conn();
    $stmt = $pdo->prepare("SELECT * FROM Course WHERE courseID=?");
    $stmt->execute([$id]);
    $course = $stmt->fetch();
    if(!$course){ http_response_code(404); echo 'Course not found'; return; }
    $mods = $pdo->prepare("SELECT * FROM Modules WHERE courseID=? ORDER BY moduleID");
    $mods->execute([$id]);
    $modules = $mods->fetchAll();
    return render('courses/view', compact('course','modules'));
  }

  public function enroll(){
    csrf_verify();
    if(!Auth::check()) redirect('login');
    if(Auth::user()['role'] !== 'learner'){ $_SESSION['flash']='Only learners can enroll.'; return redirect('courses'); }
  
    $courseID = $_POST['courseID'] ?? '';
    $pdo = DB::conn();
  
    // generate ID like ENR00001
    $enrollID = gen_id($pdo, 'Enroll', 'enrollID', 'ENR');
  
    // prevent duplicates
    $chk = $pdo->prepare("SELECT 1 FROM Enroll WHERE learnerID=? AND courseID=?");
    $chk->execute([Auth::user()['id'], $courseID]);
    if(!$chk->fetch()){
      $ins = $pdo->prepare("INSERT INTO Enroll(enrollID, learnerID, courseID, enrollDate, progress, completionStatus)
                            VALUES(?,?,?,?,0,'In Progress')");
      $ins->execute([$enrollID, Auth::user()['id'], $courseID, date('Y-m-d')]);
      $_SESSION['flash']='Enrolled successfully';
    } else {
      $_SESSION['flash']='You are already enrolled';
    }
    return redirect('courses');
  }
  
}
