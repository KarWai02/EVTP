<section class="card w-400 mx-auto mt-6">
  <h2>Forgot password</h2>
  <form method="post" action="<?=app_url('forgot')?>">
    <input type="hidden" name="csrf" value="<?=csrf_token()?>" />
    <label>Email
      <input type="email" name="email" value="<?=e($old['email'] ?? '')?>" required />
      <?php if(!empty($errors['email'])): ?><small class="error"><?=e($errors['email'])?></small><?php endif; ?>
    </label>
    <button type="submit" class="btn">Send reset link</button>
  </form>
</section>
