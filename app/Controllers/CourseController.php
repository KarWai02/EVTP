<?php
class CourseController {
  public function index(){
    try {
      $pdo = DB::conn();

      $q      = trim($_GET['q'] ?? '');
      $normQ  = strtolower(preg_replace('/\s+/', '', $q));
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
      // When searching, ignore other filters and limit to a single best match
      $where = [];
      $params = [];
      if(strlen($normQ) === 1){
        $where[] = 'LOWER(c.courseTitle) LIKE ?';
        $params[] = $normQ.'%';
      } else {
        $where[] = "LOWER(REPLACE(c.courseTitle,' ','')) = ?";
        $params[] = $normQ;
      }
      // Force single result page size
      $perPage = 1;
    }
    if($dur !== ''){
      if($dur==='lt1h'){ $where[] = 'COALESCE(m.totalMins,0) < 60'; }
      elseif($dur==='1-3h'){ $where[] = 'COALESCE(m.totalMins,0) BETWEEN 60 AND 180'; }
      elseif($dur==='gt3h'){ $where[] = 'COALESCE(m.totalMins,0) > 180'; }
    }
    $whereSql = $where ? (' WHERE '.implode(' AND ', $where)) : '';
    if($q !== '' && strlen($normQ) === 1){
      // Prefer "Soft Skills" for 's' queries among other S titles
      $orderBy = "CASE WHEN LOWER(c.courseTitle) LIKE 'soft%' THEN 0 ELSE 1 END, c.courseTitle ASC";
    } else {
      $orderBy = ($q !== '') ? 'c.courseTitle ASC' : (($sort === 'title') ? 'c.courseTitle ASC' : 'c.createdDate DESC');
    }

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

    // Soft Skills/Farming fallback: determine if Featured should show and reflect in count
    $forceFeatured = false;
    if($q !== '' && empty($rows)){
      if((strlen($normQ) === 1 && $normQ === 's') || strpos($normQ, 'soft') !== false || strpos($normQ, 'skill') !== false){
        // Force featured section to show Soft Skills only by setting active category
        $cat = 'Soft Skills';
        $forceFeatured = true;
      } elseif ((strlen($normQ) === 1 && $normQ === 'f') || strpos($normQ, 'farm') !== false){
        // Force featured section to show Farming only by setting active category
        $cat = 'Farming';
        $forceFeatured = true;
      }
    }

    // Compute how many Featured Pathways would be visible (match list.php logic: cat + dur + level)
    $featuredCount = 0;
    $shouldShowFeaturedSection = (($q === '') || $forceFeatured);
    if($shouldShowFeaturedSection){
      $farmingMins = 360;   // 6h
      $softMins    = 300;   // 5h
      $inBucket = function($mins) use ($dur){
        if($dur==='') return true;
        if($dur==='lt1h') return $mins < 60;
        if($dur==='1-3h') return $mins >= 60 && $mins <= 180;
        if($dur==='gt3h') return $mins > 180;
        return true;
      };
      $levelMatch = function($courseLevel) use ($level){
        if($level==='') return true;
        return strcasecmp($level, $courseLevel) === 0;
      };
      $catMatchF = ($cat === '' || $cat === 'Farming');
      $catMatchS = ($cat === '' || $cat === 'Soft Skills');
      $showFarming = $catMatchF && $inBucket($farmingMins) && $levelMatch('Advanced');
      $showSoft    = $catMatchS && $inBucket($softMins)    && $levelMatch('Intermediate');
      $featuredCount = ($showFarming?1:0) + ($showSoft?1:0);
    }

    // If there are no DB rows but Featured items will show, use that count for the results bar
    if(empty($rows) && $featuredCount > 0){
      $total = $featuredCount;
      $pages = 1;
    }

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
         'levels'       => $levelsList,
         'forceFeatured'=> $forceFeatured
      ]);
    } catch (Throwable $e) {
      $q = trim($_GET['q'] ?? '');
      $cat = trim($_GET['cat'] ?? '');
      $dur = trim($_GET['dur'] ?? '');
      $sector = trim($_GET['sector'] ?? '');
      $level = trim($_GET['level'] ?? '');
      $sort = trim($_GET['sort'] ?? 'new');
      $page = max(1, (int)($_GET['page'] ?? 1));
      // When DB is unavailable, derive results count from Featured visibility (cat + dur + level)
      $featuredCount = 0;
      $farmingMins = 360; // 6h
      $softMins    = 300; // 5h
      $inBucket = function($mins) use ($dur){
        if($dur==='') return true;
        if($dur==='lt1h') return $mins < 60;
        if($dur==='1-3h') return $mins >= 60 && $mins <= 180;
        if($dur==='gt3h') return $mins > 180;
        return true;
      };
      $levelMatch = function($courseLevel) use ($level){
        if($level==='') return true;
        return strcasecmp($level, $courseLevel) === 0;
      };
      $catMatchF = ($cat === '' || $cat === 'Farming');
      $catMatchS = ($cat === '' || $cat === 'Soft Skills');
      $showFarming = $catMatchF && $inBucket($farmingMins) && $levelMatch('Advanced');
      $showSoft    = $catMatchS && $inBucket($softMins)    && $levelMatch('Intermediate');
      $featuredCount = ($showFarming?1:0) + ($showSoft?1:0);

      return render('courses/list', [
        'courses'      => [],
        'query'        => $q,
        'activeCat'    => $cat,
        'activeDur'    => $dur,
        'activeSector' => $sector,
        'activeLevel'  => $level,
        'sort'         => $sort,
        'page'         => $page,
        'pages'        => 1,
        'total'        => $featuredCount,
        'categories'   => function_exists('course_categories') ? course_categories() : [],
        'sectors'      => [],
        'levels'       => [],
        'forceFeatured'=> false,
      ]);
    }
  }

  public function view(){
    $id = $_GET['id'] ?? '';
    try{
      $pdo = DB::conn();
      $stmt = $pdo->prepare("SELECT * FROM Course WHERE courseID= ?");
      $stmt->execute([$id]);
      $course = $stmt->fetch();
      if(!$course){ http_response_code(404); echo 'Course not found'; return; }
      // Ensure featured Farming courses have a default description if missing
      if(empty(trim($course['description'] ?? ''))){
        $featured = [
          'Sustainable Agriculture' => 'Principles and practices for long-term farm productivity.',
          'Soil Science' => 'Soil properties, fertility, and management techniques.',
          'Farm Management' => 'Planning, finance, and operations for efficient farming.',
          'Pest and Disease Management' => 'Identify, prevent, and manage common threats to crops.',
          'Organic Farming' => 'Standards and methods for chemical-free agriculture.',
        ];
        $title = (string)($course['courseTitle'] ?? '');
        if(isset($featured[$title])){
          $course['description'] = $featured[$title];
        }
      }
      $mods = $pdo->prepare("SELECT * FROM Modules WHERE courseID=? ORDER BY moduleID");
      $mods->execute([$id]);
      $modules = $mods->fetchAll();
      // Extract counts and links from description meta if present: [META]{"videoCount":5,"taskCount":1,"quizCount":0,"videoTitles":[],"videoUrls":[],"taskTitles":[],"quizUrl":"..."}
      foreach($modules as &$mx){
        $desc = (string)($mx['description'] ?? '');
        if(preg_match_all('/\[META\](\{.*?\})/s', $desc, $mm) && !empty($mm[1])){
          $metaJson = end($mm[1]); // use latest META block
          $meta = json_decode($metaJson, true);
          if(is_array($meta)){
            if(isset($meta['videoCount'])) $mx['videoCount'] = (int)$meta['videoCount'];
            if(isset($meta['taskCount']))  $mx['taskCount']  = (int)$meta['taskCount'];
            if(isset($meta['quizCount']))  $mx['quizCount']  = (int)$meta['quizCount'];
            if(!empty($meta['videoTitles']) && is_array($meta['videoTitles'])) $mx['videoTopics'] = $meta['videoTitles'];
            if(!empty($meta['taskTitles']) && is_array($meta['taskTitles'])) $mx['taskTitles'] = $meta['taskTitles'];
            if(isset($meta['quizUrl'])) $mx['quizUrl'] = (string)$meta['quizUrl'];
            // Build list from titles+urls
            if(empty($mx['videoList']) && !empty($meta['videoTitles'])){
              $mx['videoList'] = [];
              $urls = is_array($meta['videoUrls'] ?? null) ? $meta['videoUrls'] : [];
              foreach($meta['videoTitles'] as $i=>$vt){
                $mx['videoList'][] = ['title'=>(string)$vt,'mins'=>0,'url'=>(string)($urls[$i] ?? '')];
              }
            }
            // Or build from URLs alone
            if(empty($mx['videoList']) && empty($meta['videoTitles']) && !empty($meta['videoUrls']) && is_array($meta['videoUrls'])){
              $mx['videoList'] = [];
              foreach($meta['videoUrls'] as $i=>$vu){
                $mx['videoList'][] = ['title'=>'Video '.($i+1),'mins'=>0,'url'=>(string)$vu];
              }
            }
            // Sync videoCount with list length when available
            if(!empty($mx['videoList'])){ $mx['videoCount'] = (int)count($mx['videoList']); }
          }
        }
      }
      unset($mx);
      // If requested to start immediately, redirect to first available video URL
      if(isset($_GET['start'])){
        $firstUrl = '';
        foreach($modules as $m){ if(!empty($m['videoList'][0]['url'])){ $firstUrl = (string)$m['videoList'][0]['url']; break; } }
        if($firstUrl){ header('Location: '.$firstUrl); exit; }
      }
      // Auto-fill descriptions and simple details (video/task counts and topics)
      $moduleDefaults = [
        'Sustainable Agriculture' => 'Understand sustainability principles and how they guide modern farming practices.',
        'Soil Science' => 'Explore soil composition, fertility, and management for healthy crops.',
        'Farm Management' => 'Plan and manage farm operations, finance, and resources efficiently.',
        'Pest and Disease Management' => 'Identify and control pests and diseases to protect yield.',
        'Organic Farming' => 'Learn standards and methods for chemical-free agriculture.',
        'Introduction to IT' => "Overview of IT history, binary counting, and the role of IT support.",
        'Hardware' => 'Core computer hardware components, functions, and troubleshooting basics.',
        'Software' => 'Understand OS vs applications, installs, updates, and licensing.',
        'Networking' => 'Basics of networks, IP addressing, DNS, and diagnostics.',
        'Troubleshooting' => 'A structured methodology and common fix patterns.',
        'Customer Service' => 'Professional communication, ticketing, SLAs, and empathy.'
      ];
      $moduleTopicMap = [
        'Sustainable Agriculture' => ['Principles of sustainability','Environmental impact','Yield vs. ecology'],
        'Soil Science' => ['Soil composition','Soil fertility','Amendments & testing'],
        'Farm Management' => ['Budgeting','Scheduling','Resource planning'],
        'Pest and Disease Management' => ['Identification','Prevention','Integrated control'],
        'Organic Farming' => ['Standards & certification','Soil health','Inputs & composting'],
        'Introduction to IT' => ['History of computing','Binary and data representation','Role of IT Support'],
        'Hardware' => ['CPU, RAM, storage','Peripherals','Basic diagnostics'],
        'Software' => ['OS concepts','Applications','Updates & licensing'],
        'Networking' => ['IP & subnets','DNS & routing','Troubleshooting tools'],
        'Troubleshooting' => ['Methodology','Root cause','Documentation'],
        'Customer Service' => ['Communication','Ticketing workflows','Escalation']
      ];
      // Optional structured video list (title + minutes) for known modules
      $moduleVideoListMap = [
        'Introduction to IT' => [
          ['title'=>'Program Introduction','mins'=>5,'topics'=>['Course overview','Expectations','How to succeed']],
          ['title'=>'What is IT?','mins'=>3,'topics'=>['Definition','Domains of IT','Real-world examples']],
          ['title'=>'What does an IT Support Specialist do?','mins'=>2,'topics'=>['Responsibilities','Day-to-day tasks','Career paths']],
          ['title'=>'Course Introduction','mins'=>1,'topics'=>['Module structure','Assessment']],
          ['title'=>'Get started with your Certificate','mins'=>2,'topics'=>['Timeline','Resources','Community']],
          ['title'=>'From Abacus to Analytical Engine','mins'=>5,'topics'=>['Early computing','Key figures','Mechanical computers']],
          ['title'=>'The Path to Modern Computers','mins'=>10,'topics'=>['Transistors','Microprocessors','Personal computers']],
        ],
        'Hardware' => [
          ['title'=>'Inside a Computer','mins'=>6,'topics'=>['Motherboard','Power supply','Airflow']],
          ['title'=>'CPU, RAM, and Storage','mins'=>7,'topics'=>['CPU cores','RAM types','SSD vs HDD']],
          ['title'=>'Peripherals & Ports','mins'=>5,'topics'=>['USB, HDMI, DP','Keyboards & mice','Displays']],
          ['title'=>'Hardware Troubleshooting Basics','mins'=>6,'topics'=>['POST beeps','Diagnostics','Swap testing']],
        ],
        'Software' => [
          ['title'=>'Operating Systems 101','mins'=>6,'topics'=>['Processes & services','Filesystems','Users & permissions']],
          ['title'=>'Applications & Packages','mins'=>5,'topics'=>['Installers','Updates','Licensing']],
          ['title'=>'Common Software Issues','mins'=>6,'topics'=>['Crashes','Conflicts','Logs']],
        ],
        'Networking' => [
          ['title'=>'Networking Fundamentals','mins'=>7,'topics'=>['LAN/WAN','OSI vs TCP/IP','Topologies']],
          ['title'=>'IP Addressing & DNS','mins'=>6,'topics'=>['IPv4/IPv6','Subnets','Name resolution']],
          ['title'=>'Diagnostics & Tools','mins'=>6,'topics'=>['ping','tracert/traceroute','nslookup']],
        ],
        'Sustainable Agriculture' => [
          ['title'=>'Introduction to Sustainable Agriculture','mins'=>20,'topics'=>['What it is','Importance to global food systems','Role in preserving environment and natural resources']],
          ['title'=>'Soil Conservation and Management','mins'=>15,'topics'=>['Crop rotation','Cover cropping','Reduced tillage','Organic practices','Preventing erosion']],
          ['title'=>'Water Management and Conservation','mins'=>18,'topics'=>['Irrigation techniques','Rainwater harvesting','Reducing water wastage']],
          ['title'=>'Agroforestry and Biodiversity','mins'=>17,'topics'=>['Integrating trees into farming systems','Enhancing biodiversity','Improving soil health','Boosting climate resilience']],
          ['title'=>'Sustainable Pest and Weed Management','mins'=>20,'topics'=>['Integrated pest management (IPM)','Biological control methods','Reducing chemical pesticide use']],
        ],
        'Soil Science' => [
          ['title'=>'Introduction to Soil Composition and Types','mins'=>8,'topics'=>['Physical, chemical, and biological properties','Field visuals','Soil texture and structure']],
          ['title'=>'Soil Formation and Classification','mins'=>12,'topics'=>['Weathering processes','Soil horizons','Soil taxonomy']],
          ['title'=>'Soil Fertility and Nutrient Cycling','mins'=>14,'topics'=>['Macronutrients','Micronutrients','Organic matter decomposition','Fertilizers']],
          ['title'=>'Soil Sampling and Testing Techniques','mins'=>10,'topics'=>['Sampling methods','Lab testing','Interpreting results']],
          ['title'=>'Soil Conservation and Sustainable Practices','mins'=>16,'topics'=>['Erosion control','Cover crops','Contour farming','Soil rehabilitation']],
        ],
        'Farm Management' => [
          ['title'=>'Principles of Farm Planning and Decision Making','mins'=>15,'topics'=>['Farm objectives','Enterprise selection','Strategic decision frameworks']],
          ['title'=>'Farm Budgeting and Financial Management','mins'=>20,'topics'=>['Cost structures','Record-keeping','Profit/Loss analysis','Budgeting examples']],
          ['title'=>'Human Resource and Labor Management in Farms','mins'=>12,'topics'=>['Scheduling','Supervision','Motivation','Case studies']],
          ['title'=>'Risk and Uncertainty in Farming','mins'=>18,'topics'=>['Climatic risks','Biological risks','Market risks','Diversification','Insurance']],
          ['title'=>'Technology and Innovation in Farm Management','mins'=>25,'topics'=>['Smart farming tools','Precision agriculture','Drones','Data analytics']],
        ],
        'Pest and Disease Management' => [
          ['title'=>'Introduction to Crop Pests and Diseases','mins'=>8,'topics'=>['Pest types: insects, weeds, pathogens','Impact on crops','Basic life cycles']],
          ['title'=>'Integrated Pest Management (IPM)','mins'=>15,'topics'=>['Biological controls','Cultural practices','Mechanical methods','Judicious chemical use']],
          ['title'=>'Biological Control Methods','mins'=>10,'topics'=>['Beneficial insects','Microbes','Natural predators']],
          ['title'=>'Pesticide Use and Safety Practices','mins'=>12,'topics'=>['Proper handling & dosage','Environmental safety','Protective equipment']],
          ['title'=>'Disease Diagnosis and Field Monitoring','mins'=>15,'topics'=>['Symptom identification','Scouting techniques','Record-keeping for prevention']],
        ],
        'Organic Farming' => [
          ['title'=>'Principles and Philosophy of Organic Agriculture','mins'=>10,'topics'=>['Core values','History','Certification overview']],
          ['title'=>'Soil Health and Organic Fertilizers','mins'=>14,'topics'=>['Composting','Green manure','Crop rotation','Nutrient recycling']],
          ['title'=>'Pest and Weed Management in Organic Systems','mins'=>12,'topics'=>['Natural repellents','Biological control','Cultural techniques']],
          ['title'=>'Organic Crop Production Techniques','mins'=>14,'topics'=>['Field preparation','Seed selection','Irrigation','Post-harvest handling']],
          ['title'=>'Marketing and Certification of Organic Produce','mins'=>10,'topics'=>['Labeling','Certification process','Marketing strategies']],
        ],
        'Communication Skills' => [
          ['title'=>'The Basics of Effective Communication','mins'=>10,'topics'=>['Communication process','Barriers','Verbal and non-verbal cues']],
          ['title'=>'Active Listening and Empathy','mins'=>14,'topics'=>['Attentive listening','Emotional intelligence','Empathy at work']],
          ['title'=>'Persuasive and Confident Speaking','mins'=>18,'topics'=>['Public speaking','Presentation skills','Persuasive messaging']],
          ['title'=>'Workplace Communication Etiquette','mins'=>12,'topics'=>['Professional tone','Emails & meetings','Cross-cultural contexts']],
          ['title'=>'Handling Difficult Conversations','mins'=>21,'topics'=>['Managing conflicts','Giving feedback','Composure under pressure']],
        ],
        'Teamwork & Collaboration' => [
          ['title'=>'The Power of Team Dynamics','mins'=>15,'topics'=>['Team roles','Diversity','Leveraging individual strengths']],
          ['title'=>'Building Trust and Psychological Safety','mins'=>14,'topics'=>['Open, respectful environments','Sharing ideas and feedback','Safety practices']],
          ['title'=>'Effective Collaboration Tools and Strategies','mins'=>13,'topics'=>['Trello','Slack','Miro','Coordination methods']],
          ['title'=>'Conflict Resolution and High-Performing Team Habits','mins'=>18,'topics'=>['Constructive disagreements','Habits of successful teams','Retrospectives']],
        ],
        'Problem Solving' => [
          ['title'=>'Defining and Analyzing Problems','mins'=>14,'topics'=>['Root cause identification','Problem scope','Avoiding analysis errors']],
          ['title'=>'Creative Thinking and Innovation Techniques','mins'=>16,'topics'=>['Brainstorming','Mind mapping','Lateral thinking']],
          ['title'=>'Decision-Making Frameworks','mins'=>13,'topics'=>['SWOT','Pareto','Cost-benefit analysis']],
          ['title'=>'Applying Solutions and Evaluating Outcomes','mins'=>17,'topics'=>['Implementation','Monitoring','Continuous improvement']],
        ],
        'Time Management' => [
          ['title'=>'Understanding Time Traps and Productivity Killers','mins'=>8,'topics'=>['Distractions','Multitasking pitfalls','Poor planning habits']],
          ['title'=>'Goal Setting and Prioritization','mins'=>10,'topics'=>['SMART goals','Eisenhower Matrix','Focus on what matters']],
          ['title'=>'Planning and Scheduling Techniques','mins'=>9,'topics'=>['Calendars','To-do lists','Time-blocking']],
          ['title'=>'Overcoming Procrastination','mins'=>8,'topics'=>['Psychology of procrastination','Actionable focus techniques']],
          ['title'=>'Work–Life Balance and Stress Management','mins'=>10,'topics'=>['Avoiding burnout','Healthy boundaries','Stress management']],
        ],
        'Leadership Basics' => [
          ['title'=>'Understanding Leadership Styles','mins'=>10,'topics'=>['Autocratic','Democratic','Transformational','Situational','Case examples']],
          ['title'=>'Building Trust and Credibility','mins'=>12,'topics'=>['Ethical leadership','Transparency','Integrity']],
          ['title'=>'Motivating and Influencing Others','mins'=>14,'topics'=>['Inspiration techniques','Engagement','Recognition']],
          ['title'=>'Decision-Making and Accountability','mins'=>10,'topics'=>['Decision frameworks','Ownership','Learning from outcomes']],
          ['title'=>'Leading Through Change','mins'=>14,'topics'=>['Guiding transitions','Managing resistance','Communication during change']],
        ],
      ];
      foreach($modules as &$m){
        $title = $m['content'] ?? ($m['title'] ?? '');
        $desc  = trim($m['description'] ?? '');
        if($desc === '' && $title !== '' && isset($moduleDefaults[$title])){
          $m['description'] = $moduleDefaults[$title];
        }
        // Defaults: 1 video and 1 task unless provided
        if(!isset($m['videoCount'])){ $m['videoCount'] = 1; }
        if(!isset($m['taskCount'])){ $m['taskCount'] = 1; }
        if(!isset($m['quizCount'])){ $m['quizCount'] = 0; }
        if(empty($m['videoTopics']) && $title !== '' && isset($moduleTopicMap[$title])){
          $m['videoTopics'] = $moduleTopicMap[$title];
        }
        if(empty($m['videoList']) && $title !== '' && isset($moduleVideoListMap[$title])){
          $m['videoList'] = $moduleVideoListMap[$title];
          // keep counts consistent with list
          $m['videoCount'] = count($m['videoList']);
        }
      }
      unset($m);
    } catch (Throwable $e) {
      // Friendly fallback when DB is unavailable
      $_SESSION['flash'] = 'Database is currently unavailable. Showing limited course details.';
      $course = [
        'courseID' => $id,
        'courseTitle' => 'Course',
        'description' => '',
        'category' => 'General',
        'createdDate' => date('Y-m-d')
      ];
      $modules = [];
    }

    // Stats: module count and total minutes
    $moduleCount = count($modules);
    $totalMins = 0; foreach($modules as $m){ $totalMins += (int)($m['estimatedDuration'] ?? 0); }
    $hours = (int)floor($totalMins/60); $mins = (int)($totalMins%60);

    // Enrollment count (if Enroll table exists)
    $enrollCount = 0;
    try{
      $e = $pdo->prepare("SELECT COUNT(*) AS cnt FROM Enroll WHERE courseID=?");
      $e->execute([$id]);
      $enrollCount = (int)($e->fetch()['cnt'] ?? 0);
    }catch(Throwable $ex){ $enrollCount = 0; }

    return render('courses/view', compact('course','modules','moduleCount','totalMins','hours','mins','enrollCount'));
  }

  public function enroll(){
    csrf_verify();
    if(!Auth::check()) redirect('login');
    if(Auth::user()['role'] !== 'learner'){ $_SESSION['flash']='Only learners can enroll.'; return redirect('courses'); }
  
    $courseID = $_POST['courseID'] ?? '';
    $pdo = DB::conn();

    // Ensure target course exists; if not, auto-create for known pathway IDs
    $exists = false; $title = ''; $category = '';
    try{
      $c = $pdo->prepare("SELECT courseTitle, category FROM Course WHERE courseID=?");
      $c->execute([$courseID]); $row = $c->fetch();
      if($row){ $exists=true; $title=(string)$row['courseTitle']; $category=(string)($row['category'] ?? ''); }
    }catch(\Throwable $e){ $exists=false; }

    if(!$exists){
      $preset = null;
      if($courseID==='FARMING'){ $preset = ['title'=>'Farming','category'=>'Farming','description'=>'Learn sustainable agriculture, soil science, farm management, pest and disease management, and organic farming.']; }
      elseif($courseID==='SOFTSKILLS'){ $preset = ['title'=>'Soft Skills','category'=>'Soft Skills','description'=>'Communication, teamwork, problem-solving, time management, and leadership.']; }
      if($preset){
        try{
          $newId = gen_id($pdo, 'Course', 'courseID', 'CRS');
          // Try rich insert, fallback to minimal
          try{
            $insC = $pdo->prepare("INSERT INTO Course (courseID, courseTitle, description, category, createdDate) VALUES (?,?,?,?,?)");
            $insC->execute([$newId, $preset['title'], $preset['description'], $preset['category'], date('Y-m-d')]);
          }catch(\Throwable $e1){
            $insC = $pdo->prepare("INSERT INTO Course (courseID, courseTitle, category, createdDate) VALUES (?,?,?,?)");
            $insC->execute([$newId, $preset['title'], $preset['category'], date('Y-m-d')]);
          }
          $courseID = $newId; $exists=true; $title=$preset['title']; $category=$preset['category'];
        }catch(\Throwable $e2){ /* fall through */ }
      }
    }

    if(!$exists){ $_SESSION['flash']='Course not found.'; return redirect('courses'); }

    // generate ID like ENR00001
    $enrollID = gen_id($pdo, 'Enroll', 'enrollID', 'ENR');

    // prevent duplicates
    $chk = $pdo->prepare("SELECT 1 FROM Enroll WHERE learnerID=? AND courseID=?");
    $chk->execute([Auth::user()['id'], $courseID]);
    if(!$chk->fetch()){
      try{
        $ins = $pdo->prepare("INSERT INTO Enroll(enrollID, learnerID, courseID, enrollDate, progress, completionStatus)
                              VALUES(?,?,?,?,0,'In Progress')");
        $ins->execute([$enrollID, Auth::user()['id'], $courseID, date('Y-m-d')]);
        $_SESSION['flash']='Enrolled successfully';
      }catch(\Throwable $ex){
        $_SESSION['flash']='Could not enroll right now.';
        return redirect('courses/view?id='.$courseID);
      }
    } else {
      $_SESSION['flash']='You are already enrolled';
    }
    return redirect('courses/view?id='.$courseID);
  }

  public function detailsByTitle(){
    $title = trim($_GET['title'] ?? '');
    $cat   = trim($_GET['cat'] ?? '');
    if($title===''){ return redirect('courses'); }
    // Prefer exact match within category when provided
    try {
      $pdo = DB::conn();
      if($cat!==''){
        $s = $pdo->prepare("SELECT courseID FROM Course WHERE courseTitle = ? AND category = ? LIMIT 1");
        $s->execute([$title,$cat]);
        $row = $s->fetch();
        if($row){ return redirect('courses/view?id='.$row['courseID']); }
      }
      // Fallback: exact by title only
      $s2 = $pdo->prepare("SELECT courseID FROM Course WHERE courseTitle = ? LIMIT 1");
      $s2->execute([$title]);
      $row2 = $s2->fetch();
      if($row2){ return redirect('courses/view?id='.$row2['courseID']); }
      // Last fallback: LIKE search
      $s3 = $pdo->prepare("SELECT courseID FROM Course WHERE courseTitle LIKE ? ORDER BY createdDate DESC LIMIT 1");
      $s3->execute(['%'.$title.'%']);
      $row3 = $s3->fetch();
      if($row3){ return redirect('courses/view?id='.$row3['courseID']); }

      // Limited auto-create for the 5 known Farming titles
      $featured = [
        'Sustainable Agriculture' => 'Principles and practices for long-term farm productivity.',
        'Soil Science' => 'Soil properties, fertility, and management techniques.',
        'Farm Management' => 'Planning, finance, and operations for efficient farming.',
        'Pest and Disease Management' => 'Identify, prevent, and manage common threats to crops.',
        'Organic Farming' => 'Standards and methods for chemical-free agriculture.',
      ];
      if(isset($featured[$title]) && (strtolower($cat)==='farming' || $cat==='')){
        $id = gen_id($pdo, 'Course', 'courseID', 'CRS');
        $desc = $featured[$title];
        try{
          $ins = $pdo->prepare("INSERT INTO Course (courseID, courseTitle, description, category, createdDate) VALUES (?,?,?,?,?)");
          $ins->execute([$id,$title,$desc,'Farming',date('Y-m-d')]);
        }catch(Throwable $e1){
          try{
            $ins = $pdo->prepare("INSERT INTO Course (courseID, courseTitle, category, createdDate) VALUES (?,?,?,?)");
            $ins->execute([$id,$title,'Farming',date('Y-m-d')]);
          }catch(Throwable $e2){
            $ins = $pdo->prepare("INSERT INTO Course (courseID, courseTitle) VALUES (?,?)");
            $ins->execute([$id,$title]);
          }
        }
        return redirect('courses/view?id='.$id);
      }
    } catch (Throwable $e) {
      // No-DB fallback for 5 featured titles: render details directly
      $featured = [
        'Sustainable Agriculture' => 'Principles and practices for long-term farm productivity.',
        'Soil Science' => 'Soil properties, fertility, and management techniques.',
        'Farm Management' => 'Planning, finance, and operations for efficient farming.',
        'Pest and Disease Management' => 'Identify, prevent, and manage common threats to crops.',
        'Organic Farming' => 'Standards and methods for chemical-free agriculture.',
      ];
      if(isset($featured[$title])){
        $course = [
          'courseID' => 'TMP'.substr(md5($title),0,6),
          'courseTitle' => $title,
          'description' => $featured[$title],
          'category' => $cat ?: 'Farming',
          'createdDate' => date('Y-m-d')
        ];
        $modules = [];
        $moduleCount = 0; $totalMins = 0; $hours = 0; $mins = 0; $enrollCount = 0;
        return render('courses/view', compact('course','modules','moduleCount','totalMins','hours','mins','enrollCount'));
      }
      // Otherwise, go back to listing pre-filtered
      $_SESSION['flash'] = 'Course details not available right now. Showing results for "'.$title.'"';
      $qs = 'courses?'.http_build_query(['q'=>$title,'cat'=>$cat]);
      return redirect($qs);
    }
  }

  public function farming(){
    $course = [
      'courseID' => 'FARMING',
      'courseTitle' => 'Farming',
      'description' => 'Learn sustainable agriculture, soil science, farm management, pest and disease management, and organic farming — a cohesive pathway into modern farming best practices.',
      'category' => 'Farming',
      'level' => 'Advanced',
      'createdDate' => date('Y-m-d')
    ];
    $modules = [
      ['content' => 'Sustainable Agriculture',        'estimatedDuration' => 90, 'description' => 'Understand sustainability principles and how they guide modern farming practices.'],
      ['content' => 'Soil Science',                    'estimatedDuration' => 60, 'description' => 'Explore soil composition, fertility, and management for healthy crops.'],
      ['content' => 'Farm Management',                 'estimatedDuration' => 90, 'description' => 'Plan and manage farm operations, finance, and resources efficiently.'],
      ['content' => 'Pest and Disease Management',     'estimatedDuration' => 60, 'description' => 'Identify and control pests and diseases to protect yield.'],
      ['content' => 'Organic Farming',                 'estimatedDuration' => 60, 'description' => 'Learn standards and methods for chemical-free agriculture.'],
    ];
    // Attach detailed video list for Sustainable Agriculture so the video toggle shows content
    foreach($modules as &$mm){
      if(($mm['content'] ?? '') === 'Sustainable Agriculture'){
        $mm['videoList'] = [
          ['title'=>'Introduction to Sustainable Agriculture','mins'=>20,'topics'=>['What it is','Importance to global food systems','Role in preserving environment and natural resources']],
          ['title'=>'Soil Conservation and Management','mins'=>15,'topics'=>['Crop rotation','Cover cropping','Reduced tillage','Organic practices','Preventing erosion']],
          ['title'=>'Water Management and Conservation','mins'=>18,'topics'=>['Irrigation techniques','Rainwater harvesting','Reducing water wastage']],
          ['title'=>'Agroforestry and Biodiversity','mins'=>17,'topics'=>['Integrating trees into farming systems','Enhancing biodiversity','Improving soil health','Boosting climate resilience']],
          ['title'=>'Sustainable Pest and Weed Management','mins'=>20,'topics'=>['Integrated pest management (IPM)','Biological control methods','Reducing chemical pesticide use']],
        ];
        $mm['videoCount'] = count($mm['videoList']);
      } elseif(($mm['content'] ?? '') === 'Soil Science') {
        $mm['videoList'] = [
          ['title'=>'Introduction to Soil Composition and Types','mins'=>8,'topics'=>['Physical, chemical, and biological properties','Field visuals','Soil texture and structure']],
          ['title'=>'Soil Formation and Classification','mins'=>12,'topics'=>['Weathering processes','Soil horizons','Soil taxonomy']],
          ['title'=>'Soil Fertility and Nutrient Cycling','mins'=>14,'topics'=>['Macronutrients','Micronutrients','Organic matter decomposition','Fertilizers']],
          ['title'=>'Soil Sampling and Testing Techniques','mins'=>10,'topics'=>['Sampling methods','Lab testing','Interpreting results']],
          ['title'=>'Soil Conservation and Sustainable Practices','mins'=>16,'topics'=>['Erosion control','Cover crops','Contour farming','Soil rehabilitation']],
        ];
        $mm['videoCount'] = count($mm['videoList']);
      } elseif(($mm['content'] ?? '') === 'Farm Management') {
        $mm['videoList'] = [
          ['title'=>'Principles of Farm Planning and Decision Making','mins'=>15,'topics'=>['Farm objectives','Enterprise selection','Strategic decision frameworks']],
          ['title'=>'Farm Budgeting and Financial Management','mins'=>20,'topics'=>['Cost structures','Record-keeping','Profit/Loss analysis','Budgeting examples']],
          ['title'=>'Human Resource and Labor Management in Farms','mins'=>12,'topics'=>['Scheduling','Supervision','Motivation','Case studies']],
          ['title'=>'Risk and Uncertainty in Farming','mins'=>18,'topics'=>['Climatic risks','Biological risks','Market risks','Diversification','Insurance']],
          ['title'=>'Technology and Innovation in Farm Management','mins'=>25,'topics'=>['Smart farming tools','Precision agriculture','Drones','Data analytics']],
        ];
        $mm['videoCount'] = count($mm['videoList']);
      } elseif(($mm['content'] ?? '') === 'Pest and Disease Management') {
        $mm['videoList'] = [
          ['title'=>'Introduction to Crop Pests and Diseases','mins'=>8,'topics'=>['Pest types: insects, weeds, pathogens','Impact on crops','Basic life cycles']],
          ['title'=>'Integrated Pest Management (IPM)','mins'=>15,'topics'=>['Biological controls','Cultural practices','Mechanical methods','Judicious chemical use']],
          ['title'=>'Biological Control Methods','mins'=>10,'topics'=>['Beneficial insects','Microbes','Natural predators']],
          ['title'=>'Pesticide Use and Safety Practices','mins'=>12,'topics'=>['Proper handling & dosage','Environmental safety','Protective equipment']],
          ['title'=>'Disease Diagnosis and Field Monitoring','mins'=>15,'topics'=>['Symptom identification','Scouting techniques','Record-keeping for prevention']],
        ];
        $mm['videoCount'] = count($mm['videoList']);
      } elseif(($mm['content'] ?? '') === 'Organic Farming') {
        $mm['videoList'] = [
          ['title'=>'Principles and Philosophy of Organic Agriculture','mins'=>10,'topics'=>['Core values','History','Certification overview']],
          ['title'=>'Soil Health and Organic Fertilizers','mins'=>14,'topics'=>['Composting','Green manure','Crop rotation','Nutrient recycling']],
          ['title'=>'Pest and Weed Management in Organic Systems','mins'=>12,'topics'=>['Natural repellents','Biological control','Cultural techniques']],
          ['title'=>'Organic Crop Production Techniques','mins'=>14,'topics'=>['Field preparation','Seed selection','Irrigation','Post-harvest handling']],
          ['title'=>'Marketing and Certification of Organic Produce','mins'=>10,'topics'=>['Labeling','Certification process','Marketing strategies']],
        ];
        $mm['videoCount'] = count($mm['videoList']);
      }
    }
    unset($mm);
    $moduleCount = count($modules);
    $totalMins = 0; foreach($modules as $m){ $totalMins += (int)($m['estimatedDuration'] ?? 0); }
    $hours = (int)floor($totalMins/60); $mins = (int)($totalMins%60);
    $enrollCount = 0;
    return render('courses/view', compact('course','modules','moduleCount','totalMins','hours','mins','enrollCount'));
  }
  
  public function softskills(){
    $course = [
      'courseID' => 'SOFTSKILLS',
      'courseTitle' => 'Soft Skills',
      'description' => 'Build core professional soft skills: communication, teamwork, problem-solving, time management, and leadership.',
      'category' => 'Soft Skills',
      'level' => 'Intermediate',
      'createdDate' => date('Y-m-d')
    ];
    $modules = [
      ['content' => 'Communication Skills',     'estimatedDuration' => 75, 'description' => 'Verbal, nonverbal, and written communication for the workplace.'],
      ['content' => 'Teamwork & Collaboration', 'estimatedDuration' => 60, 'description' => 'Working effectively in teams, roles, conflict resolution, and feedback.'],
      ['content' => 'Problem Solving',          'estimatedDuration' => 60, 'description' => 'Structured problem-solving, critical thinking, and creativity.'],
      ['content' => 'Time Management',          'estimatedDuration' => 45, 'description' => 'Prioritization, planning, and focus tactics to manage workload.'],
      ['content' => 'Leadership Basics',        'estimatedDuration' => 60, 'description' => 'Influence, decision-making, and situational leadership fundamentals.'],
    ];
    // Attach detailed videos for Leadership Basics
    foreach($modules as &$mm){
      if(($mm['content'] ?? '') === 'Leadership Basics'){
        $mm['videoList'] = [
          ['title'=>'Understanding Leadership Styles','mins'=>10,'topics'=>['Autocratic','Democratic','Transformational','Situational','Case examples']],
          ['title'=>'Building Trust and Credibility','mins'=>12,'topics'=>['Ethical leadership','Transparency','Integrity']],
          ['title'=>'Motivating and Influencing Others','mins'=>14,'topics'=>['Inspiration techniques','Engagement','Recognition']],
          ['title'=>'Decision-Making and Accountability','mins'=>10,'topics'=>['Decision frameworks','Ownership','Learning from outcomes']],
          ['title'=>'Leading Through Change','mins'=>14,'topics'=>['Guiding transitions','Managing resistance','Communication during change']],
        ];
        $mm['videoCount'] = count($mm['videoList']);
      } elseif(($mm['content'] ?? '') === 'Communication Skills') {
        $mm['videoList'] = [
          ['title'=>'The Basics of Effective Communication','mins'=>10,'topics'=>['Communication process','Barriers','Verbal and non-verbal cues']],
          ['title'=>'Active Listening and Empathy','mins'=>14,'topics'=>['Attentive listening','Emotional intelligence','Empathy at work']],
          ['title'=>'Persuasive and Confident Speaking','mins'=>18,'topics'=>['Public speaking','Presentation skills','Persuasive messaging']],
          ['title'=>'Workplace Communication Etiquette','mins'=>12,'topics'=>['Professional tone','Emails & meetings','Cross-cultural contexts']],
          ['title'=>'Handling Difficult Conversations','mins'=>21,'topics'=>['Managing conflicts','Giving feedback','Composure under pressure']],
        ];
        $mm['videoCount'] = count($mm['videoList']);
      } elseif(($mm['content'] ?? '') === 'Teamwork & Collaboration') {
        $mm['videoList'] = [
          ['title'=>'The Power of Team Dynamics','mins'=>15,'topics'=>['Team roles','Diversity','Leveraging individual strengths']],
          ['title'=>'Building Trust and Psychological Safety','mins'=>14,'topics'=>['Open, respectful environments','Sharing ideas and feedback','Safety practices']],
          ['title'=>'Effective Collaboration Tools and Strategies','mins'=>13,'topics'=>['Trello','Slack','Miro','Coordination methods']],
          ['title'=>'Conflict Resolution and High-Performing Team Habits','mins'=>18,'topics'=>['Constructive disagreements','Habits of successful teams','Retrospectives']],
        ];
        $mm['videoCount'] = count($mm['videoList']);
      } elseif(($mm['content'] ?? '') === 'Problem Solving') {
        $mm['videoList'] = [
          ['title'=>'Defining and Analyzing Problems','mins'=>14,'topics'=>['Root cause identification','Problem scope','Avoiding analysis errors']],
          ['title'=>'Creative Thinking and Innovation Techniques','mins'=>16,'topics'=>['Brainstorming','Mind mapping','Lateral thinking']],
          ['title'=>'Decision-Making Frameworks','mins'=>13,'topics'=>['SWOT','Pareto','Cost-benefit analysis']],
          ['title'=>'Applying Solutions and Evaluating Outcomes','mins'=>17,'topics'=>['Implementation','Monitoring','Continuous improvement']],
        ];
        $mm['videoCount'] = count($mm['videoList']);
      } elseif(($mm['content'] ?? '') === 'Time Management') {
        $mm['videoList'] = [
          ['title'=>'Understanding Time Traps and Productivity Killers','mins'=>8,'topics'=>['Distractions','Multitasking pitfalls','Poor planning habits']],
          ['title'=>'Goal Setting and Prioritization','mins'=>10,'topics'=>['SMART goals','Eisenhower Matrix','Focus on what matters']],
          ['title'=>'Planning and Scheduling Techniques','mins'=>9,'topics'=>['Calendars','To-do lists','Time-blocking']],
          ['title'=>'Overcoming Procrastination','mins'=>8,'topics'=>['Psychology of procrastination','Actionable focus techniques']],
          ['title'=>'Work–Life Balance and Stress Management','mins'=>10,'topics'=>['Avoiding burnout','Healthy boundaries','Stress management']],
        ];
        $mm['videoCount'] = count($mm['videoList']);
      }
    }
    unset($mm);
    $moduleCount = count($modules);
    $totalMins = 0; foreach($modules as $m){ $totalMins += (int)($m['estimatedDuration'] ?? 0); }
    $hours = (int)floor($totalMins/60); $mins = (int)($totalMins%60);
    $enrollCount = 0;
    return render('courses/view', compact('course','modules','moduleCount','totalMins','hours','mins','enrollCount'));
  }
  
  
  // --- Admin utility: seed curated pathways into DB ---
  public function seedPathways(){
    Auth::requireRole(['admin']); csrf_verify();
    $pdo = DB::conn(); $created = [];
    $now = date('Y-m-d');
    $pathways = [
      ['title'=>'Farming','category'=>'Farming','level'=>'Advanced','description'=>'Learn sustainable agriculture, soil science, farm management, pest and disease management, and organic farming — a cohesive pathway into modern farming best practices.',
        'modules' => [
          ['content'=>'Sustainable Agriculture','mins'=>90,'description'=>'Understand sustainability principles and how they guide modern farming practices.'],
          ['content'=>'Soil Science','mins'=>60,'description'=>'Explore soil composition, fertility, and management for healthy crops.'],
          ['content'=>'Farm Management','mins'=>90,'description'=>'Plan and manage farm operations, finance, and resources efficiently.'],
          ['content'=>'Pest and Disease Management','mins'=>60,'description'=>'Identify and control pests and diseases to protect yield.'],
          ['content'=>'Organic Farming','mins'=>60,'description'=>'Learn standards and methods for chemical-free agriculture.'],
        ]
      ],
      ['title'=>'Soft Skills','category'=>'Soft Skills','level'=>'Intermediate','description'=>'Build core professional soft skills: communication, teamwork, problem-solving, time management, and leadership.',
        'modules' => [
          ['content'=>'Communication Skills','mins'=>75,'description'=>'Verbal, nonverbal, and written communication for the workplace.'],
          ['content'=>'Teamwork & Collaboration','mins'=>60,'description'=>'Working effectively in teams, roles, conflict resolution, and feedback.'],
          ['content'=>'Problem Solving','mins'=>60,'description'=>'Structured problem-solving, critical thinking, and creativity.'],
          ['content'=>'Time Management','mins'=>45,'description'=>'Prioritization, planning, and focus tactics to manage workload.'],
          ['content'=>'Leadership Basics','mins'=>60,'description'=>'Influence, decision-making, and situational leadership fundamentals.'],
        ]
      ],
    ];
    foreach($pathways as $p){
      // Check existing by exact title + category
      $courseID = null;
      try{
        $q=$pdo->prepare("SELECT courseID FROM Course WHERE courseTitle=? AND category=? LIMIT 1");
        $q->execute([$p['title'],$p['category']]); $row=$q->fetch();
        if($row){ $courseID = $row['courseID']; }
      }catch(\Throwable $e){}
      if(!$courseID){
        $courseID = gen_id($pdo,'Course','courseID','CRS');
        try{
          $ins = $pdo->prepare("INSERT INTO Course (courseID, courseTitle, description, category, level, createdDate) VALUES (?,?,?,?,?,?)");
          $ins->execute([$courseID,$p['title'],$p['description'],$p['category'],$p['level'],$now]);
        }catch(\Throwable $e1){
          try{ $ins=$pdo->prepare("INSERT INTO Course (courseID, courseTitle, category, createdDate) VALUES (?,?,?,?)"); $ins->execute([$courseID,$p['title'],$p['category'],$now]); }catch(\Throwable $e2){ $ins=$pdo->prepare("INSERT INTO Course (courseID, courseTitle) VALUES (?,?)"); $ins->execute([$courseID,$p['title']]); }
        }
        $created[] = $p['title'];
      }
      // Insert modules if none exist yet for this course
      $hasMods=false; try{ $cm=$pdo->prepare("SELECT 1 FROM Modules WHERE courseID=? LIMIT 1"); $cm->execute([$courseID]); $hasMods=(bool)$cm->fetch(); }catch(\Throwable $e){}
      if(!$hasMods){
        foreach($p['modules'] as $idx=>$m){
          $mid = gen_id($pdo,'Modules','moduleID','MOD');
          // Try rich insert, fallback minimal
          try{
            $im=$pdo->prepare("INSERT INTO Modules (moduleID, courseID, content, estimatedDuration, description, moduleOrder) VALUES (?,?,?,?,?,?)");
            $im->execute([$mid,$courseID,$m['content'],(int)$m['mins'],$m['description'],$idx+1]);
          }catch(\Throwable $e1){
            try{ $im=$pdo->prepare("INSERT INTO Modules (moduleID, courseID, content, estimatedDuration) VALUES (?,?,?,?)"); $im->execute([$mid,$courseID,$m['content'],(int)$m['mins']]); }catch(\Throwable $e2){ $im=$pdo->prepare("INSERT INTO Modules (moduleID, courseID, content) VALUES (?,?,?)"); $im->execute([$mid,$courseID,$m['content']]); }
          }
        }
      }
    }
    $_SESSION['flash'] = 'Seeded: '.(empty($created)?'no new courses':implode(', ',$created));
    return redirect('courses');
  }

}
