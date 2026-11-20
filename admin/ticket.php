<?php
require __DIR__ . '/../includes/functions.php';
$me = require_staff();
$id = (int)get('id');
$sqlT = 'SELECT t.*, c.nombre AS cliente, c.email, c.telefono, c.empresa, s.nombre AS servicio, u.nombre AS tecnico
    FROM tickets t JOIN clientes c ON c.id = t.cliente_id LEFT JOIN servicios s ON s.id = t.servicio_id LEFT JOIN usuarios u ON u.id = t.tecnico_id WHERE t.id = ?';
$t = q_one($sqlT, [$id]);
if (!$t) { flash('error', 'Ticket no encontrado.'); redirect('admin/tickets.php'); }
if ($me['rol'] !== 'admin' && (int)$t['tecnico_id'] !== (int)$me['id']) { flash('error', 'Este ticket no está asignado a ti.'); redirect('admin/tickets.php'); }

if (is_post()) {
    csrf_check();
    $acc = post('accion');
    $back = 'admin/ticket.php?id=' . $id;

    if ($acc === 'estado') {
        $nuevo = post('estado');
        $nota = post('nota');
        if (in_array($nuevo, ESTADOS, true) && $nuevo !== $t['estado']) {
            $resuelto = in_array($nuevo, ['Resuelto', 'Cerrado'], true) ? ($t['resuelto'] ?: now()) : null;
            q('UPDATE tickets SET estado = ?, actualizado = ?, resuelto = ? WHERE id = ?', [$nuevo, now(), $resuelto, $id]);
            add_history($id, 'estado', $nuevo, $nota ?: "Estado cambiado a $nuevo.", true, $me);
            log_activity("Cambió {$t['codigo']} a \"$nuevo\"");
            if (post('notificar')) notify_ticket_status($t, $nuevo, $nota);
            flash('success', "Estado actualizado a $nuevo" . (post('notificar') ? ' y cliente notificado.' : '.'));
        }
    } elseif ($acc === 'asignar' && $me['rol'] === 'admin') {
        $tec = (int)post('tecnico_id') ?: null;
        $estado = ($tec && $t['estado'] === 'Recibido') ? 'Pendiente' : $t['estado'];
        q('UPDATE tickets SET tecnico_id = ?, estado = ?, actualizado = ? WHERE id = ?', [$tec, $estado, now(), $id]);
        $nom = $tec ? q_val('SELECT nombre FROM usuarios WHERE id = ?', [$tec]) : null;
        add_history($id, 'estado', $estado !== $t['estado'] ? $estado : null, $nom ? "Ticket asignado a $nom." : 'Asignación removida.', true, $me);
        log_activity("Asignó {$t['codigo']} a " . ($nom ?: 'nadie'));
        if ($tec) {
            $email = q_val('SELECT email FROM usuarios WHERE id = ?', [$tec]);
            send_mail($email, "Se te asignó el ticket {$t['codigo']}", '<p>Se te asignó el ticket <b>' . e($t['codigo']) . '</b>: ' . e($t['titulo']) . ' (prioridad ' . e($t['urgencia']) . ').</p>');
        }
        flash('success', 'Asignación actualizada.');
    } elseif ($acc === 'comentario') {
        $msg = post('mensaje');
        $publico = post('publico') === '1';
        if ($msg !== '' || !empty($_FILES['adjuntos']['name'][0])) {
            add_history($id, $publico ? 'comentario' : 'interno', null, $msg ?: '(Archivo adjunto)', $publico, $me);
            foreach (save_attachments($id) as $er) flash('warning', $er);
            q('UPDATE tickets SET actualizado = ? WHERE id = ?', [now(), $id]);
            if ($publico && post('notificar')) {
                send_mail($t['email'], "Nueva respuesta en tu ticket {$t['codigo']}", '<p>Hola ' . e($t['cliente']) . ',</p><p>' . nl2br(e($msg)) . '</p><p><a href="' . e(full_url('consultar.php?codigo=' . urlencode($t['codigo']) . '&email=' . urlencode($t['email']))) . '">Ver ticket</a></p>');
            }
            flash('success', $publico ? 'Respuesta publicada.' : 'Nota interna guardada.');
        }
    } elseif ($acc === 'editar') {
        $urg = in_array(post('urgencia'), URGENCIAS, true) ? post('urgencia') : $t['urgencia'];
        q('UPDATE tickets SET urgencia = ?, servicio_id = ?, titulo = ?, actualizado = ? WHERE id = ?', [$urg, (int)post('servicio_id') ?: null, post('titulo') ?: $t['titulo'], now(), $id]);
        if ($urg !== $t['urgencia']) add_history($id, 'interno', null, "Prioridad cambiada de {$t['urgencia']} a $urg.", false, $me);
        flash('success', 'Datos del ticket actualizados.');
    } elseif ($acc === 'eliminar' && $me['rol'] === 'admin') {
        foreach (q_all('SELECT archivo FROM adjuntos WHERE ticket_id = ?', [$id]) as $a) @unlink(config('upload_dir') . '/' . $a['archivo']);
        q('DELETE FROM adjuntos WHERE ticket_id = ?', [$id]);
        q('DELETE FROM ticket_historial WHERE ticket_id = ?', [$id]);
        q('UPDATE cotizaciones SET ticket_id = NULL WHERE ticket_id = ?', [$id]);
        q('DELETE FROM tickets WHERE id = ?', [$id]);
        log_activity("Eliminó el ticket {$t['codigo']}");
        flash('success', "Ticket {$t['codigo']} eliminado.");
        redirect('admin/tickets.php');
    }
    redirect($back);
}

$hist = q_all('SELECT * FROM ticket_historial WHERE ticket_id = ? ORDER BY creado DESC, id DESC', [$id]);
$adj  = q_all('SELECT * FROM adjuntos WHERE ticket_id = ?', [$id]);
$tecnicos = q_all('SELECT u.id, u.nombre, u.especialidad, (SELECT COUNT(*) FROM tickets x WHERE x.tecnico_id = u.id AND x.estado NOT IN (\'Resuelto\',\'Cerrado\')) AS carga FROM usuarios u WHERE u.activo = 1 ORDER BY u.nombre');
$servicios = q_all('SELECT id, nombre FROM servicios ORDER BY orden');
$otros = q_all('SELECT id, codigo, titulo, estado, creado FROM tickets WHERE cliente_id = ? AND id <> ? ORDER BY creado DESC LIMIT 5', [$t['cliente_id'], $id]);
$cots = q_all('SELECT * FROM cotizaciones WHERE ticket_id = ?', [$id]);
$pageTitle = 'Ticket ' . $t['codigo'];
$section = 'tickets';
require __DIR__ . '/partials/top.php';
?>
<a href="<?= url('admin/tickets.php') ?>" class="back-link">← Volver a tickets</a>
<div class="ticket-admin">
    <div>
        <section class="panel">
            <div class="ticket-head">
                <div><small class="muted"><?= e($t['servicio'] ?? 'Sin servicio') ?></small><h2><?= e($t['titulo']) ?></h2><p class="muted">Creado el <?= fecha($t['creado']) ?> · Actualizado <?= fecha($t['actualizado']) ?></p></div>
                <div class="ticket-head-badges"><?= estado_badge($t['estado']) ?> <?= urgencia_badge($t['urgencia']) ?></div>
            </div>
            <p class="pre"><?= nl2br(e($t['descripcion'])) ?></p>
            <?php if ($adj): ?>
                <h3>Adjuntos</h3>
                <ul class="attachments"><?php foreach ($adj as $a): ?><li><a href="<?= url('descargar.php?id=' . $a['id']) ?>" target="_blank"><?= icon('file', 16) ?> <?= e($a['nombre_original']) ?> <small class="muted">(<?= round($a['tamano'] / 1024) ?> KB)</small></a></li><?php endforeach; ?></ul>
            <?php endif; ?>
        </section>

        <section class="panel">
            <div class="panel-head"><h2>Responder / agregar nota</h2></div>
            <form method="post" enctype="multipart/form-data" class="form">
                <?= csrf_field() ?><input type="hidden" name="accion" value="comentario">
                <label><textarea name="mensaje" rows="3" placeholder="Escribe una respuesta para el cliente o una nota interna…"></textarea></label>
                <div class="form-inline">
                    <label class="radio"><input type="radio" name="publico" value="1" checked> Respuesta al cliente</label>
                    <label class="radio"><input type="radio" name="publico" value="0"> Nota interna</label>
                    <label class="radio"><input type="checkbox" name="notificar" value="1" checked> Notificar por correo</label>
                    <input type="file" name="adjuntos[]" multiple>
                    <button class="btn btn-primary btn-sm">Guardar</button>
                </div>
            </form>
        </section>

        <section class="panel">
            <div class="panel-head"><h2>Historial</h2></div>
            <ul class="timeline">
                <?php foreach ($hist as $h): ?>
                    <li class="tl-<?= e($h['tipo']) ?>">
                        <div class="tl-meta"><b><?= e($h['autor']) ?></b> · <?= fecha($h['creado']) ?> <?= $h['estado'] ? estado_badge($h['estado']) : '' ?> <?= !$h['publico'] ? '<span class="badge badge-warning">Interna</span>' : '' ?></div>
                        <div class="tl-msg"><?= nl2br(e($h['mensaje'])) ?></div>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>
    </div>

    <aside>
        <section class="panel">
            <h3>Cambiar estado</h3>
            <form method="post" class="form">
                <?= csrf_field() ?><input type="hidden" name="accion" value="estado">
                <label><select name="estado"><?php foreach (ESTADOS as $s): ?><option <?= $t['estado'] === $s ? 'selected' : '' ?>><?= e($s) ?></option><?php endforeach; ?></select></label>
                <label><textarea name="nota" rows="2" placeholder="Nota para el cliente (opcional)"></textarea></label>
                <label class="radio"><input type="checkbox" name="notificar" value="1" checked> Notificar al cliente</label>
                <button class="btn btn-primary btn-block btn-sm">Actualizar estado</button>
            </form>
        </section>

        <?php if ($me['rol'] === 'admin'): ?>
        <section class="panel">
            <h3>Técnico asignado</h3>
            <form method="post" class="form">
                <?= csrf_field() ?><input type="hidden" name="accion" value="asignar">
                <label><select name="tecnico_id"><option value="">Sin asignar</option>
                    <?php foreach ($tecnicos as $tc): ?><option value="<?= $tc['id'] ?>" <?= (int)$t['tecnico_id'] === (int)$tc['id'] ? 'selected' : '' ?>><?= e($tc['nombre']) ?> (<?= $tc['carga'] ?> abiertos)</option><?php endforeach; ?>
                </select></label>
                <button class="btn btn-ghost btn-block btn-sm">Asignar</button>
            </form>
        </section>
        <?php endif; ?>

        <section class="panel">
            <h3>Cliente</h3>
            <p><b><?= e($t['cliente']) ?></b><?= $t['empresa'] ? '<br>' . e($t['empresa']) : '' ?><br><a href="mailto:<?= e($t['email']) ?>"><?= e($t['email']) ?></a><br><?= e($t['telefono']) ?></p>
            <div class="btn-row">
                <?php if ($t['telefono']): ?><a class="btn btn-whatsapp btn-sm" target="_blank" rel="noopener" href="https://wa.me/<?= e(preg_replace('/\D/', '', (strlen(preg_replace('/\D/', '', $t['telefono'])) === 10 ? '1' : '') . $t['telefono'])) ?>?text=<?= rawurlencode("Hola {$t['cliente']}, le escribimos de SoportesRD sobre su ticket {$t['codigo']}.") ?>"><?= icon('whatsapp', 16) ?> WhatsApp</a><?php endif; ?>
                <?php if ($me['rol'] === 'admin'): ?><a class="btn btn-ghost btn-sm" href="<?= url('admin/clientes.php?ver=' . $t['cliente_id']) ?>">Ver historial</a><?php endif; ?>
            </div>
            <?php if ($otros): ?><h4 class="mt">Otros tickets</h4><ul class="mini-list"><?php foreach ($otros as $o): ?><li><div><a href="<?= url('admin/ticket.php?id=' . $o['id']) ?>"><b><?= e($o['codigo']) ?></b></a> <?= estado_badge($o['estado']) ?><small><?= e(mb_strimwidth($o['titulo'], 0, 40, '…')) ?></small></div></li><?php endforeach; ?></ul><?php endif; ?>
        </section>

        <section class="panel">
            <h3>Detalles</h3>
            <form method="post" class="form">
                <?= csrf_field() ?><input type="hidden" name="accion" value="editar">
                <label>Asunto<input name="titulo" value="<?= e($t['titulo']) ?>"></label>
                <label>Servicio<select name="servicio_id"><?php foreach ($servicios as $s): ?><option value="<?= $s['id'] ?>" <?= (int)$t['servicio_id'] === (int)$s['id'] ? 'selected' : '' ?>><?= e($s['nombre']) ?></option><?php endforeach; ?></select></label>
                <label>Prioridad<select name="urgencia"><?php foreach (URGENCIAS as $u): ?><option <?= $t['urgencia'] === $u ? 'selected' : '' ?>><?= e($u) ?></option><?php endforeach; ?></select></label>
                <button class="btn btn-ghost btn-block btn-sm">Guardar cambios</button>
            </form>
        </section>

        <?php if ($me['rol'] === 'admin'): ?>
        <section class="panel">
            <h3>Cotizaciones</h3>
            <?php foreach ($cots as $c): ?><p><a href="<?= url('admin/cotizaciones.php?editar=' . $c['id']) ?>"><?= e($c['numero']) ?></a> · <?= money($c['total']) ?> · <?= e($c['estado']) ?></p><?php endforeach; ?>
            <a class="btn btn-ghost btn-block btn-sm" href="<?= url('admin/cotizaciones.php?nueva=1&ticket=' . $id) ?>">+ Crear cotización</a>
            <form method="post" class="mt" data-confirm="¿Eliminar definitivamente este ticket y sus adjuntos?">
                <?= csrf_field() ?><input type="hidden" name="accion" value="eliminar">
                <button class="btn btn-danger btn-block btn-sm">Eliminar ticket</button>
            </form>
        </section>
        <?php endif; ?>
    </aside>
</div>
<?php require __DIR__ . '/partials/bottom.php'; ?>
