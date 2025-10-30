<section class="mt-6">
  <h2>Welcome, <?=e($user['name'] ?? 'Learner')?> 👋</h2>
  <div class="grid">
    <div class="card">
      <h3>Your Actions</h3>
      <ul>
        <li><a href="<?=app_url('courses')?>">Browse Courses</a></li>
        <li><a href="<?=app_url('courses')?>#enrollments">My Enrollments</a></li>
        <li><a href="<?=app_url('profile')?>">Profile</a></li>
      </ul>
    </div>
    <div class="card">
      <h3>Quick Stats</h3>
      <p>Courses in progress: 0</p>
      <p>Completed: 0</p>
    </div>
  </div>
</section>

