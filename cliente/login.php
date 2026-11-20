<?php
require __DIR__ . '/../includes/functions.php';
if (cliente()) redirect('cliente/panel.php');
$pageTitle = 'Acceso de clientes';
$active = 'cuenta';
$email = '';
if (is_post()) {
    csrf_check();
    $email = strtolower(post('email'));
    $c = q_one('SELECT * FROM clientes WHERE LOWER(email) = ?', [$email]);
    if ($c && $c['password'] && password_verify(post('password'), $c['password'])) {
        session_regenerate_id(true);
        $_SESSION['cliente_id'] = $c['id'];
        redirect('cliente/panel.php');
    }
    flash('error', 'Correo o contraseña incorrectos.');
}
require __DIR__ . '/../includes/header.php';
?>
<section class="section auth-section">
    <div class="container auth-box">
        <form method="post" class="card form">
            <h1>Portal de clientes</h1>
            <p class="muted">Accede para ver el historial de tus servicios y solicitudes.</p>
            <?= flashes() ?>
            <?= csrf_field() ?>
            <label>Correo electrónico<input type="email" name="email" value="<?= e($email) ?>" required autocomplete="email"></label>
            <label>Contraseña<input type="password" name="password" required autocomplete="current-password"></label>
            <button class="btn btn-primary btn-block">Entrar</button>
            <p class="form-note">¿No tienes cuenta? <a href="<?= url('cliente/registro.php') ?>">Regístrate</a></p>
            <p class="form-note">¿Solo quieres ver un ticket? <a href="<?= url('consultar.php') ?>">Consúltalo sin cuenta</a></p>
        </form>
    </div>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
