<?php
require __DIR__ . '/../includes/functions.php';
$me = require_staff();
$pageTitle = 'Mi perfil';
$section = '';
if (is_post()) {
    csrf_check();
    if (!password_verify(post('actual'), $me['password'])) flash('error', 'La contraseña actual no es correcta.');
    elseif (strlen((string)post('nueva')) < 8) flash('error', 'La nueva contraseña debe tener al menos 8 caracteres.');
    elseif (post('nueva') !== post('nueva2')) flash('error', 'Las contraseñas no coinciden.');
    else {
        q('UPDATE usuarios SET password = ?, telefono = ? WHERE id = ?', [password_hash(post('nueva'), PASSWORD_DEFAULT), post('telefono'), $me['id']]);
        log_activity('Cambió su contraseña');
        flash('success', 'Contraseña actualizada.');
    }
    redirect('admin/perfil.php');
}
require __DIR__ . '/partials/top.php';
?>
<section class="panel narrow-panel">
    <div class="profile-card"><span class="avatar lg"><?= e(mb_strtoupper(mb_substr($me['nombre'], 0, 1))) ?></span><h2><?= e($me['nombre']) ?></h2><p class="muted"><?= e($me['email']) ?> · <?= $me['rol'] === 'admin' ? 'Administrador' : 'Técnico' ?></p></div>
    <form method="post" class="form">
        <?= csrf_field() ?>
        <label>Teléfono<input name="telefono" value="<?= e($me['telefono']) ?>"></label>
        <label>Contraseña actual *<input type="password" name="actual" required autocomplete="current-password"></label>
        <div class="form-grid">
            <label>Nueva contraseña *<input type="password" name="nueva" minlength="8" required autocomplete="new-password"></label>
            <label>Repetir nueva contraseña *<input type="password" name="nueva2" minlength="8" required autocomplete="new-password"></label>
        </div>
        <button class="btn btn-primary">Guardar</button>
    </form>
</section>
<?php require __DIR__ . '/partials/bottom.php'; ?>
