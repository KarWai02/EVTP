<?php
// Image paths (put real images in public/assets/img/)
$heroImg   = app_url('assets/img/about-hero.jpg');   // 1600×600 suggested
$visionImg = app_url('assets/img/about-vision.jpg'); // 900×700 suggested
?>
<section class="about">

  <!-- HERO -->
  <div class="about-hero-wrap">
    <div class="about-hero container">
      <div class="hero-kicker">ABOUT EVTP</div>
      <h1 class="hero-title">
        Powering the world’s <span class="accent">vocational talent</span>
      </h1>
    </div>
    <div class="hero-art" style="background-image:url('<?= $heroImg ?>')"></div>
  </div>

  <!-- INTRO BLURB -->
  <div class="about-intro">
    <div class="container intro-inner">
      <p>
        <strong>EVTP (Education and Vocational Training Platform)</strong> helps learners gain
        job-ready skills through expert-led courses — and connects graduates with employers.
        Founded to bridge the gap between learning and employability, EVTP partners with trainers and
        industry to deliver practical, validated learning pathways.
      </p>
    </div>
  </div>

  <!-- LOGOS (optional — reuse your home logos) -->
  <div class="container about-logos">
    <img src="<?=app_url('assets/img/google.png')?>" alt="Google">
    <img src="<?=app_url('assets/img/microsoft.png')?>" alt="Microsoft">
    <img src="<?=app_url('assets/img/ibm.png')?>" alt="IBM">
    <img src="<?=app_url('assets/img/meta.png')?>" alt="Meta">
    <img src="<?=app_url('assets/img/stanford.png')?>" alt="Stanford">
    <img src="<?=app_url('assets/img/mit.png')?>" alt="MIT">
  </div>

  <!-- VISION / STORY (two columns) -->
  <section class="container vision">
    <div class="vision-media">
      <div class="img-frame" style="background-image:url('<?= $visionImg ?>')"></div>
    </div>
    <div class="vision-copy card">
      <h2>Our Vision</h2>
      <p>
        We believe high-quality vocational education should be accessible to everyone. Through EVTP,
        we’re building flexible pathways to employment — designed with practitioners, validated by
        industry, and focused on measurable outcomes.
      </p>
      <ul class="bullet">
        <li>Expert-led, practice-first learning experiences</li>
        <li>Clear pathways to recognised certificates and jobs</li>
        <li>Opportunities at every investment level</li>
      </ul>
    </div>
  </section>

  <!-- VALUES -->
  <section class="container values">
    <div class="grid">
      <div class="card">
        <h3>🎯 Our Mission</h3>
        <p class="muted">Provide accessible, high-quality training and a bridge to employment.</p>
      </div>
      <div class="card">
        <h3>📘 What We Offer</h3>
        <p class="muted">Curated courses, assessments, and certificates validated by experts.</p>
      </div>
      <div class="card">
        <h3>🤝 Who We Serve</h3>
        <p class="muted">Learners, trainers, and employers seeking skills, delivery, and talent.</p>
      </div>
    </div>
  </section>

  <!-- STATS -->
  <section class="stats">
    <div class="container stats-row">
      <div class="stat-item">
        <div class="stat-num"><?= number_format($stats['learners'] ?? 0) ?>+</div>
        <div class="stat-label">Learners</div>
      </div>
      <div class="divider"></div>
      <div class="stat-item">
        <div class="stat-num"><?= number_format($stats['courses'] ?? 0) ?>+</div>
        <div class="stat-label">Courses</div>
      </div>
      <div class="divider"></div>
      <div class="stat-item">
        <div class="stat-num"><?= number_format($stats['trainers'] ?? 0) ?>+</div>
        <div class="stat-label">Trainers</div>
      </div>
    </div>
  </section>

  <!-- CTA -->
  <section class="container about-cta card">
    <h2>Ready to build job-ready skills?</h2>
    <p class="muted">Explore programs designed with practitioners and aligned with employer needs.</p>
    <div class="actions">
      <a class="btn" href="<?=app_url('courses')?>">Browse programs</a>
      <a class="btn btn-outline" href="<?=app_url('signup')?>">Join for free</a>
    </div>
  </section>

</section>
