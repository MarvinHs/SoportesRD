<?php
require __DIR__ . '/includes/functions.php';
$pageTitle = 'Contacto';
$active = 'contacto';
$d = ['nombre' => '', 'email' => '', 'telefono' => '', 'asunto' => '', 'mensaje' => ''];
$errors = [];
if (is_post()) {
    csrf_check();
    foreach ($d as $k => $v) $d[$k] = post($k);
    if (mb_strlen($d['nombre']) < 3) $errors[] = 'Escribe tu nombre.';
    if (!filter_var($d['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Escribe un correo válido.';
    if (mb_strlen($d['mensaje']) < 5) $errors[] = 'Escribe tu mensaje.';
    if (post('website') !== '') $errors[] = 'Mensaje no válido.';
    if (!$errors) {
        q('INSERT INTO mensajes (nombre, email, telefono, asunto, mensaje, leido, creado) VALUES (?,?,?,?,?,0,?)', [$d['nombre'], $d['email'], $d['telefono'], $d['asunto'], $d['mensaje'], now()]);
        send_mail(setting('email'), 'Nuevo mensaje de contacto: ' . ($d['asunto'] ?: $d['nombre']), '<p><b>' . e($d['nombre']) . '</b> (' . e($d['email']) . ', ' . e($d['telefono']) . ')</p><p>' . nl2br(e($d['mensaje'])) . '</p>');
        log_activity('Nuevo mensaje de contacto de ' . $d['nombre'], $d['nombre']);
        flash('success', '¡Gracias! Recibimos tu mensaje y te responderemos pronto.');
        redirect('contacto.php');
    }
}
require __DIR__ . '/includes/header.php';
?>
<section class="page-hero">
    <div class="container">
        <h1>Contacto</h1>
        <p>Estamos para ayudarte. Escríbenos por el medio que prefieras.</p>
    </div>
</section>
<section class="section">
    <div class="container contact-grid">
        <div class="contact-info">
            <a class="contact-item" href="<?= e(whatsapp_link()) ?>" target="_blank" rel="noopener">
                <span class="ci-icon wa"><?= icon('whatsapp', 24) ?></span>
                <span><small>WhatsApp</small><b><?= e(setting('whatsapp_texto')) ?></b></span>
            </a>
            <a class="contact-item" href="tel:<?= e(preg_replace('/[^0-9+]/', '', setting('telefono'))) ?>">
                <span class="ci-icon"><?= icon('phone', 24) ?></span>
                <span><small>Teléfono</small><b><?= e(setting('telefono')) ?></b></span>
            </a>
            <a class="contact-item" href="mailto:<?= e(setting('email')) ?>">
                <span class="ci-icon"><?= icon('mail', 24) ?></span>
                <span><small>Correo empresarial</small><b><?= e(setting('email')) ?></b></span>
            </a>
            <div class="contact-item">
                <span class="ci-icon"><?= icon('clock', 24) ?></span>
                <span><small>Horario de atención</small><b><?= e(setting('horario')) ?></b></span>
            </div>
            <div class="contact-item">
                <span class="ci-icon"><?= icon('pin', 24) ?></span>
                <span><small>Ubicación y zona de cobertura</small><b><?= e(setting('direccion')) ?></b><em><?= e(setting('zona')) ?></em></span>
            </div>
            <div class="socials socials-dark">
                <?php foreach (['facebook', 'instagram', 'linkedin', 'tiktok'] as $s): if (setting($s)): ?>
                    <a href="<?= e(setting($s)) ?>" target="_blank" rel="noopener" aria-label="<?= ucfirst($s) ?>"><?= icon($s, 22) ?></a>
                <?php endif; endforeach; ?>
            </div>
        </div>
        <div>
            <?= flashes() ?>
            <?php if ($errors): ?><div class="alert alert-error"><ul><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
            <form method="post" class="card form">
                <h2>Envíanos un mensaje</h2>
                <?= csrf_field() ?>
                <input type="text" name="website" class="hp" tabindex="-1" autocomplete="off" aria-hidden="true">
                <div class="form-grid">
                    <label>Nombre *<input type="text" name="nombre" value="<?= e($d['nombre']) ?>" required></label>
                    <label>Correo *<input type="email" name="email" value="<?= e($d['email']) ?>" required></label>
                    <label>Teléfono<input type="tel" name="telefono" value="<?= e($d['telefono']) ?>"></label>
                    <label>Asunto<input type="text" name="asunto" value="<?= e($d['asunto']) ?>"></label>
                    <label class="full">Mensaje *<textarea name="mensaje" rows="5" required><?= e($d['mensaje']) ?></textarea></label>
                </div>
                <button class="btn btn-primary btn-block"><?= icon('mail', 18) ?> Enviar mensaje</button>
            </form>
        </div>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
