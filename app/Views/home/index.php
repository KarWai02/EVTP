<div class="home">
  <section class="home-hero">
    <div class="container hero-grid">
      <div class="hero-left card">
        <h1>Start, switch, or advance your career</h1>
        <p>Grow with industry-aligned courses from leading trainers and organizations.</p>
        <div class="actions">
          <a class="btn" href="<?=app_url('signup')?>">Join for Free</a>
          <a class="btn btn-outline" href="<?=app_url('login')?>">Log in</a>
        </div>
      </div>
      <div class="hero-right card">
        <h2>Gain in-demand skills</h2>
        <p>Learn AI, software, business, and more. Earn certificates to showcase your growth.</p>
        <a class="btn" href="<?=app_url('courses')?>">Explore programs</a>
      </div>
    </div>
  </section>

  <section class="home-features">
    <div class="container features-grid">
      <div class="card feature">
        <h3>Launch a new career</h3>
        <p>Follow curated paths designed to build job-ready skills.</p>
      </div>
      <div class="card feature">
        <h3>Gain in‑demand skills</h3>
        <p>Hands-on content developed with practitioners and experts.</p>
      </div>
      <div class="card feature">
        <h3>Earn a certificate</h3>
        <p>Showcase your achievements with shareable certificates.</p>
      </div>
    </div>
  </section>

  <section class="home-latest">
    <div class="container">
      <h2>Trending courses</h2>
      <?php if(!empty($courses)): ?>
        <div class="grid cards">
          <?php foreach($courses as $c): ?>
            <div class="card course">
              <h3><?=e($c['courseTitle'])?></h3>
              <p>Category: <?=e($c['category'] ?? 'General')?></p>
              <p><small>Created: <?=e($c['createdDate'])?></small></p>
              <a class="btn btn-outline" href="<?=app_url('courses/view')?>?id=<?=e($c['courseID'])?>">View</a>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <p>No courses yet. Check back soon.</p>
      <?php endif; ?>
    </div>
  </section>

<section class="home-partners">
  <div class="container logos">
    <span class="muted">Trusted by learners from</span>

    <div class="logo-row">
      <div class="logo">
        <img src="<?=app_url('assets/img/google.png')?>" alt="Google" />
      </div>
      <div class="logo">
        <img src="<?=app_url('assets/img/microsoft.png')?>" alt="Microsoft" />
      </div>
      <div class="logo">
        <img src="<?=app_url('assets/img/ibm.png')?>" alt="IBM" />
      </div>
      <div class="logo">
        <img src="<?=app_url('assets/img/meta.png')?>" alt="Meta" />
      </div>
      <div class="logo">
        <img src="<?=app_url('assets/img/stanford.png')?>" alt="Stanford" />
      </div>
      <div class="logo">
        <img src="<?=app_url('assets/img/mit.png')?>" alt="MIT" />
      </div>
    </div>
  </div>
</section>

</div>

