<?php
require __DIR__ . '/../includes/functions.php';
$me = require_admin();
$pageTitle = 'Mensajes de contacto';
$section = 'mensajes';
if (is_post()) {
    csrf_check();
    $id = (int)post('id');
    if (post('accion') === 'leido') q('UPDATE mensajes SET leido = 1 WHERE id = ?', [$id]);
    if (post('accion') === 'eliminar') q('DELETE FROM mensajes WHERE id = ?', [$id]);
    if (post('accion') === 'ticket') {
        $m = q_one('SELECT * FROM mensajes WHERE id = ?', [$id]);
        if ($m && filter_var($m['email'], FILTER_VALIDATE_EMAIL)) {
            $c = q_one('SELECT id FROM clientes WHERE LOWER(email) = ?', [strtolower($m['email'])]);
            if ($c) $cid = (int)$c['id'];
            else { q('INSERT INTO clientes (nombre, email, telefono, creado) VALUES (?,?,?,?)', [$m['nombre'], strtolower($m['email']), $m['telefono'], now()]); $cid = (int)db()->lastInsertId(); }
            $codigo = new_ticket_code();
            q('INSERT INTO tickets (codigo, cliente_id, titulo, descripcion, urgencia, estado, creado, actualizado) VALUES (?,?,?,?,?,?,?,?)', [$codigo, $cid, $m['asunto'] ?: 'Mensaje de contacto', $m['mensaje'], 'Media', 'Recibido', now(), now()]);
            $tid = (int)db()->lastInsertId();
            add_history($tid, 'estado', 'Recibido', 'Ticket creado a partir de un mensaje de contacto.', true, $me);
            q('UPDATE mensajes SET leido = 1 WHERE id = ?', [$id]);
            log_activity("Convirtió un mensaje en el ticket $codigo");
            flash('success', "Ticket $codigo creado.");
            redirect('admin/ticket.php?id=' . $tid);
        }
    }
    redirect('admin/mensajes.php');
}
$rows = q_all('SELECT * FROM mensajes ORDER BY leido, creado DESC');
require __DIR__ . '/partials/top.php';
?>
<section class="panel">
    <div class="panel-head"><h2><?= count($rows) ?> mensajes</h2></div>
    <?php foreach ($rows as $m): ?>
        <article class="message <?= $m['leido'] ? '' : 'unread' ?>">
            <div class="flex-between"><div><b><?= e($m['nombre']) ?></b> · <a href="mailto:<?= e($m['email']) ?>"><?= e($m['email']) ?></a> <?= $m['telefono'] ? '· ' . e($m['telefono']) : '' ?></div><small class="muted"><?= fecha($m['creado']) ?></small></div>
            <?php if ($m['asunto']): ?><h3><?= e($m['asunto']) ?></h3><?php endif; ?>
            <p><?= nl2br(e($m['mensaje'])) ?></p>
            <div class="btn-row">
                <?php foreach (['ticket' => 'Convertir en ticket', 'leido' => 'Marcar como leído', 'eliminar' => 'Eliminar'] as $a => $l): if ($a === 'leido' && $m['leido']) continue; ?>
                    <form method="post" <?= $a === 'eliminar' ? 'data-confirm="¿Eliminar mensaje?"' : '' ?>><?= csrf_field() ?><input type="hidden" name="accion" value="<?= $a ?>"><input type="hidden" name="id" value="<?= $m['id'] ?>"><button class="btn btn-sm <?= $a === 'ticket' ? 'btn-primary' : 'btn-ghost' ?>"><?= $l ?></button></form>
                <?php endforeach; ?>
            </div>
        </article>
    <?php endforeach; ?>
    <?php if (!$rows): ?><p class="muted center">No hay mensajes.</p><?php endif; ?>
</section>
<?php require __DIR__ . '/partials/bottom.php'; ?>
