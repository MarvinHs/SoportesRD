<?php
require __DIR__ . '/includes/functions.php';
$pageTitle = 'Solicitar soporte';
$active = 'solicitar';
$servicios = q_all('SELECT id, nombre FROM servicios WHERE activo = 1 ORDER BY orden');
$cli = cliente();
$errors = [];
$done = null;
$data = [
    'nombre' => $cli['nombre'] ?? '', 'email' => $cli['email'] ?? '', 'telefono' => $cli['telefono'] ?? '',
    'servicio_id' => get('servicio'), 'titulo' => '', 'descripcion' => '', 'urgencia' => 'Media',
];

if (is_post()) {
    csrf_check();
    foreach ($data as $k => $v) $data[$k] = post($k);
    if ($cli) $data['email'] = $cli['email'];

    if (mb_strlen($data['nombre']) < 3)                         $errors[] = 'Escribe tu nombre completo.';
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL))     $errors[] = 'Escribe un correo electrónico válido.';
    if (!preg_match('/^[0-9+\-\s()]{7,20}$/', $data['telefono'])) $errors[] = 'Escribe un número de teléfono válido.';
    if (!q_val('SELECT COUNT(*) FROM servicios WHERE id = ? AND activo = 1', [(int)$data['servicio_id']])) $errors[] = 'Selecciona el tipo de servicio.';
    if (mb_strlen($data['descripcion']) < 10)                    $errors[] = 'Describe el problema con al menos 10 caracteres.';
    if (!in_array($data['urgencia'], URGENCIAS, true))           $errors[] = 'Selecciona el nivel de urgencia.';
    if (post('website') !== '')                                  $errors[] = 'Solicitud no válida.'; // trampa anti-spam

    if (!$errors) {
        $c = q_one('SELECT * FROM clientes WHERE email = ?', [strtolower($data['email'])]);
        if ($c) {
            q('UPDATE clientes SET nombre = ?, telefono = ? WHERE id = ?', [$data['nombre'], $data['telefono'], $c['id']]);
            $clienteId = (int)$c['id'];
        } else {
            q('INSERT INTO clientes (nombre, email, telefono, creado) VALUES (?,?,?,?)', [$data['nombre'], strtolower($data['email']), $data['telefono'], now()]);
            $clienteId = (int)db()->lastInsertId();
        }
        $codigo = new_ticket_code();
        $titulo = $data['titulo'] ?: mb_strimwidth($data['descripcion'], 0, 80, '…');
        q('INSERT INTO tickets (codigo, cliente_id, servicio_id, titulo, descripcion, urgencia, estado, creado, actualizado) VALUES (?,?,?,?,?,?,?,?,?)',
            [$codigo, $clienteId, (int)$data['servicio_id'], $titulo, $data['descripcion'], $data['urgencia'], 'Recibido', now(), now()]);
        $tid = (int)db()->lastInsertId();
        add_history($tid, 'estado', 'Recibido', 'Solicitud recibida desde el sitio web.', true, null, $data['nombre']);
        $uploadErrors = save_attachments($tid);
        log_activity("Nuevo ticket $codigo de {$data['nombre']}", $data['nombre']);

        $link = full_url('consultar.php?codigo=' . urlencode($codigo) . '&email=' . urlencode($data['email']));
        send_mail($data['email'], "Recibimos tu solicitud $codigo",
            '<p>Hola ' . e($data['nombre']) . ',</p><p>Recibimos tu solicitud de soporte. Tu número de ticket es <b>' . e($codigo) . '</b>.</p><p>Puedes consultar su estado en cualquier momento: <a href="' . e($link) . '">ver mi ticket</a>.</p>');
        send_mail(setting('email'), "Nuevo ticket $codigo ({$data['urgencia']})",
            '<p>Nuevo ticket de <b>' . e($data['nombre']) . '</b> (' . e($data['email']) . ', ' . e($data['telefono']) . ').</p><p>' . nl2br(e($data['descripcion'])) . '</p>');

        $done = ['codigo' => $codigo, 'email' => $data['email'], 'upload_errors' => $uploadErrors];
    }
}
require __DIR__ . '/includes/header.php';
?>
<section class="page-hero">
    <div class="container">
        <h1>Solicitar soporte</h1>
        <p>Cuéntanos qué sucede. Te responderemos lo antes posible.</p>
    </div>
</section>

<section class="section">
    <div class="container narrow">
        <?php if ($done): ?>
            <div class="card success-card">
                <div class="success-icon"><?= icon('check', 40) ?></div>
                <h2>¡Solicitud recibida!</h2>
                <p>Tu número de ticket es:</p>
                <div class="ticket-code"><?= e($done['codigo']) ?></div>
                <p>Guárdalo para consultar el estado de tu solicitud. También te lo enviamos a <b><?= e($done['email']) ?></b>.</p>
                <?php foreach ($done['upload_errors'] as $ue): ?><div class="alert alert-warning"><?= e($ue) ?></div><?php endforeach; ?>
                <div class="hero-actions center">
                    <a href="<?= url('consultar.php?codigo=' . urlencode($done['codigo']) . '&email=' . urlencode($done['email'])) ?>" class="btn btn-primary">Ver estado del ticket</a>
                    <a href="<?= e(whatsapp_link('Hola SoportesRD, acabo de crear el ticket ' . $done['codigo'])) ?>" target="_blank" rel="noopener" class="btn btn-whatsapp"><?= icon('whatsapp', 18) ?> Avisar por WhatsApp</a>
                </div>
            </div>
        <?php else: ?>
            <?php if ($errors): ?>
                <div class="alert alert-error"><b>Revisa lo siguiente:</b><ul><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div>
            <?php endif; ?>
            <form method="post" enctype="multipart/form-data" class="card form" novalidate>
                <?= csrf_field() ?>
                <input type="text" name="website" class="hp" tabindex="-1" autocomplete="off" aria-hidden="true">
                <div class="form-grid">
                    <label>Nombre del cliente *
                        <input type="text" name="nombre" value="<?= e($data['nombre']) ?>" required maxlength="120" autocomplete="name">
                    </label>
                    <label>Correo electrónico *
                        <input type="email" name="email" value="<?= e($data['email']) ?>" required maxlength="150" autocomplete="email" <?= $cli ? 'readonly' : '' ?>>
                    </label>
                    <label>Número de teléfono *
                        <input type="tel" name="telefono" value="<?= e($data['telefono']) ?>" required maxlength="20" placeholder="809-000-0000" autocomplete="tel">
                    </label>
                    <label>Tipo de servicio solicitado *
                        <select name="servicio_id" required>
                            <option value="">Selecciona…</option>
                            <?php foreach ($servicios as $s): ?>
                                <option value="<?= $s['id'] ?>" <?= (string)$data['servicio_id'] === (string)$s['id'] ? 'selected' : '' ?>><?= e($s['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="full">Asunto (opcional)
                        <input type="text" name="titulo" value="<?= e($data['titulo']) ?>" maxlength="200" placeholder="Ej.: Mi laptop no enciende">
                    </label>
                    <label class="full">Descripción del problema *
                        <textarea name="descripcion" rows="6" required placeholder="Describe qué sucede, desde cuándo y qué equipo es."><?= e($data['descripcion']) ?></textarea>
                    </label>
                    <fieldset class="full urgency">
                        <legend>Nivel de urgencia *</legend>
                        <?php foreach (URGENCIAS as $u): ?>
                            <label class="urgency-opt u-<?= strtolower(str_replace('í', 'i', $u)) ?>">
                                <input type="radio" name="urgencia" value="<?= e($u) ?>" <?= $data['urgencia'] === $u ? 'checked' : '' ?>>
                                <span><?= e($u) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </fieldset>
                    <label class="full">Adjuntar imágenes o documentos
                        <div class="dropzone" data-dropzone>
                            <?= icon('file', 28) ?>
                            <span>Arrastra tus archivos aquí o <u>haz clic para seleccionar</u></span>
                            <small>Hasta <?= config('upload_max_files') ?> archivos · máx. <?= config('upload_max_mb') ?> MB c/u · <?= e(implode(', ', config('upload_ext'))) ?></small>
                            <input type="file" name="adjuntos[]" multiple accept=".<?= e(implode(',.', config('upload_ext'))) ?>">
                            <ul class="file-list" data-file-list></ul>
                        </div>
                    </label>
                </div>
                <button type="submit" class="btn btn-primary btn-lg btn-block"><?= icon('ticket', 20) ?> Enviar solicitud</button>
                <p class="form-note">¿Prefieres hablar con alguien? <a href="<?= e(whatsapp_link()) ?>" target="_blank" rel="noopener">Escríbenos por WhatsApp</a>.</p>
            </form>
        <?php endif; ?>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
