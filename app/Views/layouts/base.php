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
        <a href="<?=app_url('')?>">Home</a>
        <a href="<?=app_url('courses')?>">Courses</a>
        <a href="<?=app_url('about')?>">About Us</a>
      </div>
      <div class="utility-right">
        <?php if(Auth::check()): $u=Auth::user(); ?>
          <span class="pill sm"><?=e(ucfirst($u['role']))?>: <?=e($u['name'])?></span>
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
    <a class="brand" href="<?=app_url('')?>">EVTP</a>

    <!-- Search (center) -->
    <form class="nav-search" method="get" action="<?=app_url('courses')?>">
      <input type="text" name="q" placeholder="What do you want to learn?" value="<?=e($_GET['q'] ?? '')?>" aria-label="Search courses">
      <button type="submit" aria-label="Search">
        <!-- simple magnifier icon -->
        <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor" aria-hidden="true">
          <path d="M15.5 14h-.79l-.28-.27a6.5 6.5 0 10-.71.71l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0A4.5 4.5 0 1114 9.5 4.5 4.5 0 019.5 14z"/>
        </svg>
      </button>
    </form>

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
