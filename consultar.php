<?php
require __DIR__ . '/includes/functions.php';
$pageTitle = 'Consultar ticket';
$active = 'consultar';

$codigo = strtoupper(get('codigo', post('codigo')));
$email  = strtolower(get('email', post('email')));
$ticket = null;
$error  = '';

if ($codigo !== '' || $email !== '') {
    $ticket = q_one('SELECT t.*, c.nombre AS cliente, c.email, s.nombre AS servicio, u.nombre AS tecnico
        FROM tickets t JOIN clientes c ON c.id = t.cliente_id
        LEFT JOIN servicios s ON s.id = t.servicio_id LEFT JOIN usuarios u ON u.id = t.tecnico_id
        WHERE t.codigo = ? AND LOWER(c.email) = ?', [$codigo, $email]);
    if (!$ticket) $error = 'No encontramos un ticket con ese número y correo. Verifica los datos.';
    else $_SESSION['tickets_ok'][$ticket['id']] = true;
}

// Respuesta del cliente
if ($ticket && is_post() && post('accion') === 'responder') {
    csrf_check();
    $msg = post('mensaje');
    if ($ticket['estado'] === 'Cerrado') {
        flash('error', 'Este ticket está cerrado. Si necesitas más ayuda, crea una nueva solicitud.');
    } elseif (mb_strlen($msg) < 2 && empty($_FILES['adjuntos']['name'][0])) {
        flash('error', 'Escribe un mensaje o adjunta un archivo.');
    } else {
        add_history((int)$ticket['id'], 'comentario', null, $msg ?: '(Archivo adjunto)', true, null, $ticket['cliente']);
        $errs = save_attachments((int)$ticket['id']);
        $nuevo = in_array($ticket['estado'], ['En espera del cliente', 'Resuelto'], true) ? 'En proceso' : $ticket['estado'];
        q('UPDATE tickets SET estado = ?, actualizado = ? WHERE id = ?', [$nuevo, now(), $ticket['id']]);
        if ($nuevo !== $ticket['estado']) add_history((int)$ticket['id'], 'estado', $nuevo, 'El cliente respondió; el ticket vuelve a En proceso.', true, null, 'Sistema');
        log_activity("Cliente respondió en {$ticket['codigo']}", $ticket['cliente']);
        foreach ($errs as $er) flash('warning', $er);
        flash('success', 'Tu mensaje fue enviado.');
    }
    redirect('consultar.php?codigo=' . urlencode($codigo) . '&email=' . urlencode($email));
}

if ($ticket) {
    $historial = q_all('SELECT * FROM ticket_historial WHERE ticket_id = ? AND publico = 1 ORDER BY creado ASC, id ASC', [$ticket['id']]);
    $adjuntos  = q_all('SELECT * FROM adjuntos WHERE ticket_id = ? ORDER BY id', [$ticket['id']]);
    $idx = array_search($ticket['estado'], ESTADOS, true);
}
require __DIR__ . '/includes/header.php';
?>
<section class="page-hero">
    <div class="container">
        <h1>Consultar ticket</h1>
        <p>Revisa en qué etapa se encuentra tu solicitud.</p>
    </div>
</section>

<section class="section">
    <div class="container narrow">
        <?= flashes() ?>
        <form method="get" class="card form search-ticket">
            <div class="form-grid">
                <label>Número de ticket
                    <input type="text" name="codigo" value="<?= e($codigo) ?>" placeholder="SRD-<?= date('Y') ?>-0001" required>
                </label>
                <label>Correo electrónico
                    <input type="email" name="email" value="<?= e($email) ?>" placeholder="tu@correo.com" required>
                </label>
            </div>
            <button class="btn btn-primary btn-block"><?= icon('search', 18) ?> Consultar</button>
        </form>
        <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

        <?php if ($ticket): ?>
            <div class="card ticket-view">
                <div class="ticket-head">
                    <div>
                        <small class="muted">Ticket</small>
                        <h2><?= e($ticket['codigo']) ?></h2>
                        <p class="muted"><?= e($ticket['titulo']) ?></p>
                    </div>
                    <div class="ticket-head-badges"><?= estado_badge($ticket['estado']) ?> <?= urgencia_badge($ticket['urgencia']) ?></div>
                </div>

                <ol class="progress-steps">
                    <?php foreach (ESTADOS as $i => $st): ?>
                        <li class="<?= $i < $idx ? 'done' : ($i === $idx ? 'current' : '') ?>"><span><?= $i < $idx ? icon('check', 14) : $i + 1 ?></span><em><?= e($st) ?></em></li>
                    <?php endforeach; ?>
                </ol>

                <dl class="details">
                    <div><dt>Cliente</dt><dd><?= e($ticket['cliente']) ?></dd></div>
                    <div><dt>Servicio</dt><dd><?= e($ticket['servicio'] ?? '—') ?></dd></div>
                    <div><dt>Técnico asignado</dt><dd><?= e($ticket['tecnico'] ?? 'Por asignar') ?></dd></div>
                    <div><dt>Creado</dt><dd><?= fecha($ticket['creado']) ?></dd></div>
                    <div><dt>Última actualización</dt><dd><?= fecha($ticket['actualizado']) ?></dd></div>
                </dl>

                <h3>Descripción</h3>
                <p class="pre"><?= nl2br(e($ticket['descripcion'])) ?></p>

                <?php if ($adjuntos): ?>
                    <h3>Archivos adjuntos</h3>
                    <ul class="attachments">
                        <?php foreach ($adjuntos as $a): ?>
                            <li><a href="<?= url('descargar.php?id=' . $a['id']) ?>" target="_blank"><?= icon('file', 18) ?> <?= e($a['nombre_original']) ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>

                <h3>Historial</h3>
                <ul class="timeline">
                    <?php foreach ($historial as $h): ?>
                        <li class="tl-<?= e($h['tipo']) ?>">
                            <div class="tl-meta"><b><?= e($h['autor']) ?></b> · <?= fecha($h['creado']) ?> <?= $h['estado'] ? estado_badge($h['estado']) : '' ?></div>
                            <div class="tl-msg"><?= nl2br(e($h['mensaje'])) ?></div>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <?php if ($ticket['estado'] !== 'Cerrado'): ?>
                    <form method="post" enctype="multipart/form-data" class="form reply-form">
                        <?= csrf_field() ?>
                        <input type="hidden" name="accion" value="responder">
                        <input type="hidden" name="codigo" value="<?= e($codigo) ?>">
                        <input type="hidden" name="email" value="<?= e($email) ?>">
                        <label>Agregar un comentario o información
                            <textarea name="mensaje" rows="3" placeholder="Escribe aquí…"></textarea>
                        </label>
                        <label class="file-inline">Adjuntar archivos <input type="file" name="adjuntos[]" multiple></label>
                        <button class="btn btn-primary">Enviar</button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
