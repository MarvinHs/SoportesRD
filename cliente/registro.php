<?php
require __DIR__ . '/../includes/functions.php';
if (cliente()) redirect('cliente/panel.php');
$pageTitle = 'Registro de clientes';
$active = 'cuenta';
$d = ['nombre' => '', 'email' => '', 'telefono' => '', 'empresa' => '', 'codigo' => ''];
$errors = [];
$needCode = false;
if (is_post()) {
    csrf_check();
    foreach ($d as $k => $v) $d[$k] = post($k);
    $d['email'] = strtolower($d['email']);
    $pass = (string)post('password');
    if (mb_strlen($d['nombre']) < 3) $errors[] = 'Escribe tu nombre completo.';
    if (!filter_var($d['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Escribe un correo válido.';
    if (strlen($pass) < 8) $errors[] = 'La contraseña debe tener al menos 8 caracteres.';
    if ($pass !== post('password2')) $errors[] = 'Las contraseñas no coinciden.';

    $existe = q_one('SELECT * FROM clientes WHERE LOWER(email) = ?', [$d['email']]);
    if (!$errors && $existe) {
        if ($existe['password']) {
            $errors[] = 'Ya existe una cuenta con ese correo. Inicia sesión.';
        } else {
            // Cliente creado desde una solicitud: debe confirmar con un número de ticket suyo
            $needCode = true;
            $okCode = $d['codigo'] !== '' && q_val('SELECT COUNT(*) FROM tickets WHERE cliente_id = ? AND codigo = ?', [$existe['id'], strtoupper($d['codigo'])]);
            if (!$okCode) $errors[] = 'Ya tenemos solicitudes con este correo. Para activar tu cuenta escribe uno de tus números de ticket.';
        }
    }
    if (!$errors) {
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        if ($existe) {
            q('UPDATE clientes SET nombre = ?, telefono = ?, empresa = ?, password = ? WHERE id = ?', [$d['nombre'], $d['telefono'], $d['empresa'], $hash, $existe['id']]);
            $id = (int)$existe['id'];
        } else {
            q('INSERT INTO clientes (nombre, email, telefono, empresa, password, creado) VALUES (?,?,?,?,?,?)', [$d['nombre'], $d['email'], $d['telefono'], $d['empresa'], $hash, now()]);
            $id = (int)db()->lastInsertId();
        }
        log_activity('Nuevo registro de cliente: ' . $d['nombre'], $d['nombre']);
        send_mail($d['email'], 'Bienvenido a SoportesRD', '<p>Hola ' . e($d['nombre']) . ', tu cuenta en el portal de clientes de SoportesRD fue creada.</p>');
        session_regenerate_id(true);
        $_SESSION['cliente_id'] = $id;
        flash('success', '¡Tu cuenta fue creada!');
        redirect('cliente/panel.php');
    }
}
require __DIR__ . '/../includes/header.php';
?>
<section class="section auth-section">
    <div class="container auth-box wide">
        <form method="post" class="card form">
            <h1>Crear cuenta</h1>
            <p class="muted">Lleva el control de tus solicitudes e historial de servicios.</p>
            <?php if ($errors): ?><div class="alert alert-error"><ul><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
            <?= csrf_field() ?>
            <div class="form-grid">
                <label>Nombre completo *<input type="text" name="nombre" value="<?= e($d['nombre']) ?>" required></label>
                <label>Correo electrónico *<input type="email" name="email" value="<?= e($d['email']) ?>" required></label>
                <label>Teléfono<input type="tel" name="telefono" value="<?= e($d['telefono']) ?>"></label>
                <label>Empresa (opcional)<input type="text" name="empresa" value="<?= e($d['empresa']) ?>"></label>
                <label>Contraseña *<input type="password" name="password" minlength="8" required autocomplete="new-password"></label>
                <label>Repetir contraseña *<input type="password" name="password2" minlength="8" required autocomplete="new-password"></label>
                <?php if ($needCode): ?>
                    <label class="full">Número de uno de tus tickets *<input type="text" name="codigo" value="<?= e($d['codigo']) ?>" placeholder="SRD-<?= date('Y') ?>-0001"></label>
                <?php endif; ?>
            </div>
            <button class="btn btn-primary btn-block">Crear cuenta</button>
            <p class="form-note">¿Ya tienes cuenta? <a href="<?= url('cliente/login.php') ?>">Inicia sesión</a></p>
        </form>
    </div>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
