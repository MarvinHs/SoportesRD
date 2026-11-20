<?php
require __DIR__ . '/../includes/functions.php';
if (staff()) redirect('admin/index.php');
$email = '';
if (is_post()) {
    csrf_check();
    $email = strtolower(post('email'));
    $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;
    if ($_SESSION['login_attempts'] > 8) {
        flash('error', 'Demasiados intentos. Espera unos minutos y vuelve a intentarlo.');
    } else {
        $u = q_one('SELECT * FROM usuarios WHERE LOWER(email) = ? AND activo = 1', [$email]);
        if ($u && password_verify(post('password'), $u['password'])) {
            session_regenerate_id(true);
            $_SESSION['staff_id'] = $u['id'];
            unset($_SESSION['login_attempts']);
            log_activity('Inició sesión', $u['nombre']);
            redirect('admin/index.php');
        }
        flash('error', 'Correo o contraseña incorrectos.');
    }
}
?><!DOCTYPE html>
<html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Acceso al panel | SoportesRD</title>
<link rel="icon" href="<?= url('assets/img/favicon.svg') ?>" type="image/svg+xml">
<link href="https://fonts.googleapis.com/css2?family=Inter:ital,wght@0,400;0,500;0,600;0,700;0,800;1,800;1,900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= url('assets/css/style.css') ?>"><link rel="stylesheet" href="<?= url('assets/css/admin.css') ?>">
</head><body class="login-page">
<div class="login-split">
    <div class="login-art">
        <a href="<?= url('index.php') ?>" class="brand-wordmark"><img src="<?= url('assets/img/logo-wordmark.svg') ?>" alt="SoportesRD" width="240" height="63"></a>
        <div><h2>Panel administrativo</h2><p>Gestiona tickets, clientes, técnicos, cotizaciones y reportes desde un solo lugar.</p></div>
        <img src="<?= url('assets/img/logo-card.svg') ?>" alt="Logo SoportesRD" class="login-logo">
    </div>
    <div class="login-form">
        <form method="post" class="form">
            <h1>Iniciar sesión</h1>
            <p class="muted">Acceso exclusivo para administradores y técnicos.</p>
            <?= flashes() ?>
            <?= csrf_field() ?>
            <label>Correo electrónico<input type="email" name="email" value="<?= e($email) ?>" required autofocus autocomplete="username"></label>
            <label>Contraseña<input type="password" name="password" required autocomplete="current-password"></label>
            <button class="btn btn-primary btn-block btn-lg">Entrar</button>
            <p class="form-note"><a href="<?= url('index.php') ?>">← Volver al sitio</a></p>
        </form>
    </div>
</div>
</body></html>
