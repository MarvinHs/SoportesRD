<?php
require __DIR__ . '/../includes/functions.php';
$me = require_admin();
$pageTitle = 'Configuración del sistema';
$section = 'configuracion';
$grupos = [
    'Datos del negocio' => [
        'nombre_comercial' => 'Nombre comercial', 'eslogan' => 'Frase que identifica el negocio',
        'direccion' => 'Ubicación', 'zona' => 'Zona de cobertura', 'horario' => 'Horario de atención',
    ],
    'Contacto' => [
        'whatsapp' => 'WhatsApp (solo números con código de país, ej. 18095551234)', 'whatsapp_texto' => 'WhatsApp (como se muestra)',
        'telefono' => 'Teléfono de contacto', 'email' => 'Correo empresarial (recibe las notificaciones)',
    ],
    'Redes sociales' => ['facebook' => 'Facebook', 'instagram' => 'Instagram', 'linkedin' => 'LinkedIn', 'tiktok' => 'TikTok'],
    'Sistema' => ['itbis' => 'Tasa de ITBIS (%)'],
];
if (is_post()) {
    csrf_check();
    foreach ($grupos as $campos) foreach ($campos as $k => $l) setting_save($k, (string)post($k));
    setting_save('notificar_email', post('notificar_email') ? '1' : '0');
    log_activity('Actualizó la configuración del sistema');
    flash('success', 'Configuración guardada.');
    redirect('admin/configuracion.php');
}
$c = config();
require __DIR__ . '/partials/top.php';
?>
<form method="post" class="form">
    <?= csrf_field() ?>
    <div class="grid-2">
        <?php foreach ($grupos as $g => $campos): ?>
            <section class="panel">
                <h2><?= e($g) ?></h2>
                <?php foreach ($campos as $k => $l): ?>
                    <label><?= e($l) ?><input name="<?= $k ?>" value="<?= e(setting($k)) ?>"></label>
                <?php endforeach; ?>
                <?php if ($g === 'Sistema'): ?>
                    <label class="radio"><input type="checkbox" name="notificar_email" value="1" <?= setting('notificar_email', '1') === '1' ? 'checked' : '' ?>> Enviar notificaciones por correo a los clientes al cambiar el estado de un ticket</label>
                    <div class="alert alert-info">
                        <b>Información técnica</b><br>
                        Base de datos: <b><?= e(strtoupper($c['db']['driver'])) ?></b> · PHP <?= PHP_VERSION ?><br>
                        Envío de correo: <b><?= $c['mail']['enabled'] ? 'Activo (función mail)' : 'Modo registro (storage/mail.log)' ?></b><br>
                        Adjuntos: hasta <?= $c['upload_max_files'] ?> archivos de <?= $c['upload_max_mb'] ?> MB
                    </div>
                <?php endif; ?>
            </section>
        <?php endforeach; ?>
    </div>
    <button class="btn btn-primary btn-lg">Guardar configuración</button>
</form>
<?php require __DIR__ . '/partials/bottom.php'; ?>
