<?php
require __DIR__ . '/../includes/functions.php';
$me = require_staff();
$pageTitle = 'Gestión de tickets';
$section = 'tickets';
$tecnicos = q_all("SELECT id, nombre FROM usuarios WHERE activo = 1 ORDER BY nombre");
$servicios = q_all('SELECT id, nombre FROM servicios ORDER BY orden');

// Crear ticket manual (llamada, WhatsApp, visita)
if (is_post() && post('accion') === 'crear') {
    csrf_check();
    $clienteId = (int)post('cliente_id');
    if (!$clienteId && post('nuevo_nombre') && filter_var(post('nuevo_email'), FILTER_VALIDATE_EMAIL)) {
        $ex = q_one('SELECT id FROM clientes WHERE LOWER(email) = ?', [strtolower(post('nuevo_email'))]);
        if ($ex) $clienteId = (int)$ex['id'];
        else {
            q('INSERT INTO clientes (nombre, email, telefono, creado) VALUES (?,?,?,?)', [post('nuevo_nombre'), strtolower(post('nuevo_email')), post('nuevo_telefono'), now()]);
            $clienteId = (int)db()->lastInsertId();
        }
    }
    if (!$clienteId || mb_strlen(post('descripcion')) < 5) {
        flash('error', 'Selecciona o registra un cliente y escribe la descripción.');
    } else {
        $codigo = new_ticket_code();
        $tec = $me['rol'] === 'admin' ? ((int)post('tecnico_id') ?: null) : (int)$me['id'];
        $estado = $tec ? 'Pendiente' : 'Recibido';
        $urg = in_array(post('urgencia'), URGENCIAS, true) ? post('urgencia') : 'Media';
        q('INSERT INTO tickets (codigo, cliente_id, servicio_id, tecnico_id, titulo, descripcion, urgencia, estado, creado, actualizado) VALUES (?,?,?,?,?,?,?,?,?,?)',
            [$codigo, $clienteId, (int)post('servicio_id') ?: null, $tec, post('titulo') ?: mb_strimwidth(post('descripcion'), 0, 80, '…'), post('descripcion'), $urg, $estado, now(), now()]);
        $tid = (int)db()->lastInsertId();
        add_history($tid, 'estado', $estado, 'Ticket registrado por ' . $me['nombre'] . ' (canal: ' . (post('canal') ?: 'Interno') . ').', true, $me);
        save_attachments($tid);
        log_activity("Creó el ticket $codigo");
        flash('success', "Ticket $codigo creado.");
        redirect('admin/ticket.php?id=' . $tid);
    }
}

// Filtros
$f = ['q' => get('q'), 'estado' => get('estado'), 'urgencia' => get('urgencia'), 'tecnico' => get('tecnico'), 'servicio' => get('servicio')];
$where = ['1=1']; $p = [];
if ($me['rol'] !== 'admin') { $where[] = 't.tecnico_id = ?'; $p[] = $me['id']; }
if ($f['q'] !== '') { $where[] = '(t.codigo LIKE ? OR t.titulo LIKE ? OR c.nombre LIKE ? OR c.email LIKE ?)'; array_push($p, "%{$f['q']}%", "%{$f['q']}%", "%{$f['q']}%", "%{$f['q']}%"); }
if ($f['estado'] === 'abiertos') { $where[] = "t.estado NOT IN ('Resuelto','Cerrado')"; }
elseif ($f['estado'] !== '') { $where[] = 't.estado = ?'; $p[] = $f['estado']; }
if ($f['urgencia'] !== '') { $where[] = 't.urgencia = ?'; $p[] = $f['urgencia']; }
if ($f['tecnico'] === 'sin') { $where[] = 't.tecnico_id IS NULL'; }
elseif ($f['tecnico'] !== '') { $where[] = 't.tecnico_id = ?'; $p[] = (int)$f['tecnico']; }
if ($f['servicio'] !== '') { $where[] = 't.servicio_id = ?'; $p[] = (int)$f['servicio']; }
$ws = implode(' AND ', $where);

$per = 20;
$page = max(1, (int)get('p', '1'));
$totalRows = (int)q_val("SELECT COUNT(*) FROM tickets t JOIN clientes c ON c.id = t.cliente_id WHERE $ws", $p);
$pages = max(1, (int)ceil($totalRows / $per));
$off = ($page - 1) * $per;
$rows = q_all("SELECT t.*, c.nombre AS cliente, s.nombre AS servicio, u.nombre AS tecnico FROM tickets t
    JOIN clientes c ON c.id = t.cliente_id LEFT JOIN servicios s ON s.id = t.servicio_id LEFT JOIN usuarios u ON u.id = t.tecnico_id
    WHERE $ws ORDER BY CASE WHEN t.estado IN ('Resuelto','Cerrado') THEN 1 ELSE 0 END, t.creado DESC LIMIT $per OFFSET $off", $p);
$qs = fn($extra) => '?' . http_build_query(array_merge($f, $extra));

require __DIR__ . '/partials/top.php';
?>
<div class="toolbar-row">
    <form class="filters" method="get">
        <input type="search" name="q" value="<?= e($f['q']) ?>" placeholder="Buscar ticket, cliente o correo…">
        <select name="estado"><option value="">Todos los estados</option><option value="abiertos" <?= $f['estado'] === 'abiertos' ? 'selected' : '' ?>>Solo abiertos</option>
            <?php foreach (ESTADOS as $s): ?><option <?= $f['estado'] === $s ? 'selected' : '' ?>><?= e($s) ?></option><?php endforeach; ?></select>
        <select name="urgencia"><option value="">Toda prioridad</option><?php foreach (URGENCIAS as $u): ?><option <?= $f['urgencia'] === $u ? 'selected' : '' ?>><?= e($u) ?></option><?php endforeach; ?></select>
        <?php if ($me['rol'] === 'admin'): ?>
        <select name="tecnico"><option value="">Todos los técnicos</option><option value="sin" <?= $f['tecnico'] === 'sin' ? 'selected' : '' ?>>Sin asignar</option>
            <?php foreach ($tecnicos as $t): ?><option value="<?= $t['id'] ?>" <?= $f['tecnico'] == $t['id'] ? 'selected' : '' ?>><?= e($t['nombre']) ?></option><?php endforeach; ?></select>
        <?php endif; ?>
        <select name="servicio"><option value="">Todos los servicios</option><?php foreach ($servicios as $s): ?><option value="<?= $s['id'] ?>" <?= $f['servicio'] == $s['id'] ? 'selected' : '' ?>><?= e($s['nombre']) ?></option><?php endforeach; ?></select>
        <button class="btn btn-ghost btn-sm"><?= icon('search', 16) ?> Filtrar</button>
        <a href="<?= url('admin/tickets.php') ?>" class="btn btn-sm btn-ghost">Limpiar</a>
    </form>
    <button class="btn btn-primary" onclick="document.getElementById('nuevo').showModal()">+ Nuevo ticket</button>
</div>

<section class="panel">
    <div class="panel-head"><h2><?= $totalRows ?> tickets</h2></div>
    <div class="table-wrap"><table class="table">
        <thead><tr><th>Ticket</th><th>Cliente</th><th>Servicio</th><th>Prioridad</th><th>Estado</th><th>Técnico</th><th>Creado</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($rows as $t): ?>
            <tr>
                <td><a href="<?= url('admin/ticket.php?id=' . $t['id']) ?>"><b><?= e($t['codigo']) ?></b></a><br><small class="muted"><?= e(mb_strimwidth($t['titulo'], 0, 45, '…')) ?></small></td>
                <td><?= e($t['cliente']) ?></td><td><?= e($t['servicio'] ?? '—') ?></td>
                <td><?= urgencia_badge($t['urgencia']) ?></td><td><?= estado_badge($t['estado']) ?></td>
                <td><?= $t['tecnico'] ? e($t['tecnico']) : '<span class="muted">Sin asignar</span>' ?></td>
                <td><?= fecha($t['creado']) ?></td>
                <td><a href="<?= url('admin/ticket.php?id=' . $t['id']) ?>" class="btn btn-sm btn-ghost">Abrir</a></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?><tr><td colspan="8" class="center muted">No se encontraron tickets.</td></tr><?php endif; ?>
        </tbody>
    </table></div>
    <?php if ($pages > 1): ?>
        <nav class="pager"><?php for ($i = 1; $i <= $pages; $i++): ?><a href="<?= $qs(['p' => $i]) ?>" class="<?= $i === $page ? 'active' : '' ?>"><?= $i ?></a><?php endfor; ?></nav>
    <?php endif; ?>
</section>

<dialog id="nuevo" class="modal">
    <form method="post" enctype="multipart/form-data" class="form">
        <div class="modal-head"><h2>Nuevo ticket</h2><button type="button" class="btn btn-sm btn-ghost" onclick="this.closest('dialog').close()">✕</button></div>
        <?= csrf_field() ?><input type="hidden" name="accion" value="crear">
        <div class="form-grid">
            <label class="full">Cliente existente
                <select name="cliente_id"><option value="">— Cliente nuevo (completar abajo) —</option>
                    <?php foreach (q_all('SELECT id, nombre, email FROM clientes ORDER BY nombre') as $c): ?><option value="<?= $c['id'] ?>"><?= e($c['nombre'] . ' · ' . $c['email']) ?></option><?php endforeach; ?>
                </select>
            </label>
            <label>Nombre (cliente nuevo)<input name="nuevo_nombre"></label>
            <label>Correo (cliente nuevo)<input type="email" name="nuevo_email"></label>
            <label>Teléfono (cliente nuevo)<input name="nuevo_telefono"></label>
            <label>Canal<select name="canal"><option>Teléfono</option><option>WhatsApp</option><option>Visita</option><option>Correo</option><option>Interno</option></select></label>
            <label>Servicio<select name="servicio_id"><?php foreach ($servicios as $s): ?><option value="<?= $s['id'] ?>"><?= e($s['nombre']) ?></option><?php endforeach; ?></select></label>
            <label>Prioridad<select name="urgencia"><?php foreach (URGENCIAS as $u): ?><option <?= $u === 'Media' ? 'selected' : '' ?>><?= e($u) ?></option><?php endforeach; ?></select></label>
            <?php if ($me['rol'] === 'admin'): ?>
            <label class="full">Asignar a<select name="tecnico_id"><option value="">Sin asignar</option><?php foreach ($tecnicos as $t): ?><option value="<?= $t['id'] ?>"><?= e($t['nombre']) ?></option><?php endforeach; ?></select></label>
            <?php endif; ?>
            <label class="full">Asunto<input name="titulo"></label>
            <label class="full">Descripción *<textarea name="descripcion" rows="4" required></textarea></label>
            <label class="full">Adjuntos<input type="file" name="adjuntos[]" multiple></label>
        </div>
        <button class="btn btn-primary btn-block">Crear ticket</button>
    </form>
</dialog>
<?php require __DIR__ . '/partials/bottom.php'; ?>
