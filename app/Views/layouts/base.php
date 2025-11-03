<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>EVTP</title>
  <link rel="stylesheet" href="<?=app_url('assets/css/app.css')?>">
</head>
<body>
  <!-- Utility bar -->
  <div class="utilitybar">
    <div class="container flex-between">
      <div class="utility-left">
        <?php $role = Auth::check() ? (Auth::user()['role'] ?? '') : ''; if($role !== 'employer'): ?>
          <a href="<?=app_url('')?>">Home</a>
          <a href="<?=app_url('courses')?>">Courses</a>
          <a href="<?=app_url('jobs')?>">Jobs</a>
          <a href="<?=app_url('about')?>">About Us</a>
        <?php endif; ?>
      </div>
      <div class="utility-right">
        <?php if(Auth::check()): $u=Auth::user(); $urole = $u['role'] ?? ''; $uid = $u['id'] ?? null; $unread=0; try{ $pdo=DB::conn(); $q=$pdo->prepare("SELECT COUNT(*) c FROM notifications WHERE role=? AND user_id=? AND read_at IS NULL"); $q->execute([$urole,$uid]); $unread=(int)($q->fetch()['c'] ?? 0); }catch(Throwable $e){ $unread=0; } if(($urole ?? '')==='admin'){ try{ $path = __DIR__.'/../../storage/notifications.json'; $list = file_exists($path) ? json_decode(file_get_contents($path), true) : []; if(is_array($list)){ foreach($list as $n){ if(($n['role'] ?? '')!=='admin') continue; $target = $n['user_id'] ?? null; if($target!==null && $target!=$uid) continue; if(empty($n['read_at'])) $unread++; } } }catch(Throwable $e){} try{ $modPath = __DIR__.'/../../storage/forum_moderation.json'; $mod = file_exists($modPath) ? json_decode(file_get_contents($modPath), true) : []; if(is_array($mod)){ foreach(($mod['reports'] ?? []) as $r){ if(($r['status'] ?? 'open')==='open') $unread++; } } }catch(Throwable $e){} } ?>
          <span class="pill sm"><?=e(ucfirst($urole))?>: <?=e($u['name'])?></span>
          <?php if($urole==='employer'): ?>
            <a href="<?=app_url('employer/jobs/create')?>">Post Job</a>
            <a href="<?=app_url('employer/jobs')?>">My Job Listings</a>
            <a href="<?=app_url('employer/talent')?>">Talent Search</a>
            <a href="<?=app_url('employer/candidates')?>">Manage Candidates</a>
          <?php elseif($urole==='learner'): ?>
            <a href="<?=app_url('learner/certificates')?>">My Certificates</a>
            <a href="<?=app_url('activity')?>">Job applications</a>
          <?php elseif($urole==='trainer'): ?>
            <a href="<?=app_url('trainer/profile')?>">My profile</a>
            <a href="<?=app_url('trainer/courses')?>">Manage Courses</a>
            <a href="<?=app_url('trainer/workshops')?>">Workshops</a>
          <?php elseif($urole==='admin'): ?>
            <a href="<?=app_url('admin/profile')?>">My profile</a>
            <a href="<?=app_url('admin/users')?>">Manage Users</a>
            <a href="<?=app_url('admin/courses')?>">Manage Courses</a>
            <a href="<?=app_url('admin/reports')?>">View Reports</a>
            <a href="<?=app_url('admin/certificates')?>">Manage Certificates</a>
          <?php endif; ?>
          <?php $openReports=0; if(($urole ?? '')==='admin'){ try{ $mod = json_decode(@file_get_contents(__DIR__.'/../../storage/forum_moderation.json'), true) ?: []; foreach(($mod['reports'] ?? []) as $r){ if(($r['status'] ?? 'open')==='open') $openReports++; } }catch(\Throwable $e){} } ?>
          <a href="<?=app_url('community') ?>" style="position:relative;">
            Community
            <?php if(($urole ?? '')==='admin' && $openReports>0): ?>
              <span class="pill sm" style="position:absolute;top:-6px;right:-12px;"><?= $openReports ?></span>
            <?php endif; ?>
          </a>
          <a href="<?=app_url('notifications')?>" aria-label="Notifications" style="position:relative;">
            <span>🔔</span>
            <?php if($unread>0): ?><span class="pill sm" style="position:absolute;top:-6px;right:-12px;"><?= $unread ?></span><?php endif; ?>
          </a>
          <a href="<?=app_url('dashboard')?>">Dashboard</a>
          <a href="<?=app_url('logout')?>">Logout</a>
        <?php else: ?>
          <a href="<?=app_url('login')?>">Log in</a>
          <a class="btn btn-sm" href="<?=app_url('signup')?>">Join for free</a>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Main navbar -->
  <header class="topbar">
  <div class="container header-grid">
    <!-- Brand -->
    <?php $role = Auth::check() ? (Auth::user()['role'] ?? '') : ''; if($role==='employer'): ?>
      <div class="brand">EVTP — Employer</div>
    <?php elseif($role==='admin'): ?>
      <div class="brand"></div>
    <?php else: ?>
      <a class="brand" href="<?=app_url('')?>">EVTP</a>
    <?php endif; ?>

    <!-- Search (center) -->
    <?php if($role==='learner'): ?>
      <form class="nav-search" method="get" action="<?=app_url('courses')?>">
        <input type="text" name="q" placeholder="What do you want to learn?" value="<?=e($_GET['q'] ?? '')?>" aria-label="Search courses">
        <button type="submit" aria-label="Search">
          <!-- simple magnifier icon -->
          <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor" aria-hidden="true">
            <path d="M15.5 14h-.79l-.28-.27a6.5 6.5 0 10-.71.71l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0A4.5 4.5 0 1114 9.5 4.5 4.5 0 019.5 14z"/>
          </svg>
        </button>
      </form>
    <?php endif; ?>

    <!-- Right side intentionally empty: links live in the utility bar above -->
  </div>
</header>


  <main class="container py-6">
    <?php if(!empty($_SESSION['flash'])): ?>
      <div class="alert"><?=e($_SESSION['flash']); unset($_SESSION['flash']);?></div>
    <?php endif; ?>
    <?=$content?>
  </main>

  <footer class="footer">
    <div class="container">
      <div class="footer-top">
        <div>
          <h4 class="foot-title">Categories</h4>
          <div class="foot-grid">
            <?php $cats = course_categories(); ?>
            <?php if ($cats): ?>
              <?php foreach($cats as $c): ?>
                <a class="foot-link" href="<?=app_url('courses')?>?cat=<?=urlencode($c)?>"><?=e($c)?></a>
              <?php endforeach; ?>
            <?php else: ?>
              <span class="muted">No categories yet.</span>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <div class="footer-bottom flex-between">
        <p>&copy; <?=date('Y')?> EVTP. All rights reserved.</p>
      </div>
    </div>
  </footer>

  <script src="<?=app_url('assets/js/app.js')?>"></script>
</body>
</html>
