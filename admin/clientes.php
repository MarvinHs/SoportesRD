<?php
require __DIR__ . '/../includes/functions.php';
$me = require_staff();
$admin = $me['rol'] === 'admin';
$pageTitle = 'Clientes';
$section = 'clientes';

if (is_post() && $admin) {
    csrf_check();
    $acc = post('accion');
    if ($acc === 'guardar') {
        $id = (int)post('id');
        $email = strtolower(post('email'));
        if (mb_strlen(post('nombre')) < 3 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash('error', 'Nombre y correo válido son obligatorios.');
        } elseif (q_val('SELECT COUNT(*) FROM clientes WHERE LOWER(email) = ? AND id <> ?', [$email, $id])) {
            flash('error', 'Ya existe otro cliente con ese correo.');
        } else {
            $vals = [post('nombre'), $email, post('telefono'), post('empresa'), post('direccion'), post('notas')];
            if ($id) {
                q('UPDATE clientes SET nombre=?, email=?, telefono=?, empresa=?, direccion=?, notas=? WHERE id=?', [...$vals, $id]);
                log_activity('Editó el cliente ' . post('nombre'));
            } else {
                q('INSERT INTO clientes (nombre, email, telefono, empresa, direccion, notas, creado) VALUES (?,?,?,?,?,?,?)', [...$vals, now()]);
                $id = (int)db()->lastInsertId();
                log_activity('Registró el cliente ' . post('nombre'));
            }
            flash('success', 'Cliente guardado.');
            redirect('admin/clientes.php?ver=' . $id);
        }
    } elseif ($acc === 'eliminar') {
        $id = (int)post('id');
        if (q_val('SELECT COUNT(*) FROM tickets WHERE cliente_id = ?', [$id])) {
            flash('error', 'No se puede eliminar un cliente con tickets registrados.');
        } else {
            q('DELETE FROM cotizaciones WHERE cliente_id = ?', [$id]);
            q('DELETE FROM clientes WHERE id = ?', [$id]);
            log_activity('Eliminó un cliente');
            flash('success', 'Cliente eliminado.');
        }
    }
    redirect('admin/clientes.php');
}

$ver = (int)get('ver');
$editar = get('editar');
$cli = $ver || ($editar !== '' && $editar !== 'nuevo') ? q_one('SELECT * FROM clientes WHERE id = ?', [$ver ?: (int)$editar]) : null;

require __DIR__ . '/partials/top.php';

if ($editar !== '' && $admin):
    $c = $cli ?: ['id' => 0, 'nombre' => '', 'email' => '', 'telefono' => '', 'empresa' => '', 'direccion' => '', 'notas' => ''];
?>
    <a href="<?= url('admin/clientes.php') ?>" class="back-link">← Volver</a>
    <section class="panel narrow-panel">
        <h2><?= $c['id'] ? 'Editar cliente' : 'Registrar cliente' ?></h2>
        <form method="post" class="form">
            <?= csrf_field() ?><input type="hidden" name="accion" value="guardar"><input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
            <div class="form-grid">
                <label>Nombre *<input name="nombre" value="<?= e($c['nombre']) ?>" required></label>
                <label>Correo *<input type="email" name="email" value="<?= e($c['email']) ?>" required></label>
                <label>Teléfono<input name="telefono" value="<?= e($c['telefono']) ?>"></label>
                <label>Empresa<input name="empresa" value="<?= e($c['empresa']) ?>"></label>
                <label class="full">Dirección<input name="direccion" value="<?= e($c['direccion']) ?>"></label>
                <label class="full">Notas internas<textarea name="notas" rows="3"><?= e($c['notas']) ?></textarea></label>
            </div>
            <button class="btn btn-primary">Guardar cliente</button>
        </form>
    </section>
<?php elseif ($cli):
    $tk = q_all('SELECT t.*, s.nombre AS servicio, u.nombre AS tecnico FROM tickets t LEFT JOIN servicios s ON s.id = t.servicio_id LEFT JOIN usuarios u ON u.id = t.tecnico_id WHERE t.cliente_id = ? ORDER BY t.creado DESC', [$cli['id']]);
    $cots = q_all('SELECT * FROM cotizaciones WHERE cliente_id = ? ORDER BY creado DESC', [$cli['id']]);
?>
    <a href="<?= url('admin/clientes.php') ?>" class="back-link">← Volver a clientes</a>
    <div class="ticket-admin">
        <section class="panel">
            <div class="panel-head"><h2>Historial de servicios (<?= count($tk) ?>)</h2></div>
            <div class="table-wrap"><table class="table">
                <thead><tr><th>Ticket</th><th>Servicio</th><th>Estado</th><th>Técnico</th><th>Fecha</th></tr></thead>
                <tbody><?php foreach ($tk as $t): ?>
                    <tr><td><a href="<?= url('admin/ticket.php?id=' . $t['id']) ?>"><b><?= e($t['codigo']) ?></b></a><br><small class="muted"><?= e($t['titulo']) ?></small></td><td><?= e($t['servicio']) ?></td><td><?= estado_badge($t['estado']) ?></td><td><?= e($t['tecnico'] ?? '—') ?></td><td><?= fecha($t['creado'], false) ?></td></tr>
                <?php endforeach; ?>
                <?php if (!$tk): ?><tr><td colspan="5" class="muted center">Sin tickets.</td></tr><?php endif; ?></tbody>
            </table></div>
            <?php if ($cots): ?>
                <div class="panel-head mt"><h2>Cotizaciones</h2></div>
                <div class="table-wrap"><table class="table"><tbody><?php foreach ($cots as $c): ?>
                    <tr><td><b><?= e($c['numero']) ?></b></td><td><?= fecha($c['creado'], false) ?></td><td><?= money($c['total']) ?></td><td><?= e($c['estado']) ?></td><td><a href="<?= url('admin/cotizaciones.php?editar=' . $c['id']) ?>" class="btn btn-sm btn-ghost">Abrir</a></td></tr>
                <?php endforeach; ?></tbody></table></div>
            <?php endif; ?>
        </section>
        <aside>
            <section class="panel">
                <div class="profile-card"><span class="avatar lg"><?= e(mb_strtoupper(mb_substr($cli['nombre'], 0, 1))) ?></span><h2><?= e($cli['nombre']) ?></h2><p class="muted"><?= e($cli['empresa']) ?></p></div>
                <dl class="dl">
                    <dt>Correo</dt><dd><a href="mailto:<?= e($cli['email']) ?>"><?= e($cli['email']) ?></a></dd>
                    <dt>Teléfono</dt><dd><?= e($cli['telefono'] ?: '—') ?></dd>
                    <dt>Dirección</dt><dd><?= e($cli['direccion'] ?: '—') ?></dd>
                    <dt>Cliente desde</dt><dd><?= fecha($cli['creado'], false) ?></dd>
                    <dt>Cuenta en el portal</dt><dd><?= $cli['password'] ? 'Sí' : 'No' ?></dd>
                    <?php if ($cli['notas']): ?><dt>Notas</dt><dd><?= nl2br(e($cli['notas'])) ?></dd><?php endif; ?>
                </dl>
                <?php if ($admin): ?>
                <div class="btn-row">
                    <a href="<?= url('admin/clientes.php?editar=' . $cli['id']) ?>" class="btn btn-ghost btn-sm">Editar</a>
                    <a href="<?= url('admin/cotizaciones.php?nueva=1&cliente=' . $cli['id']) ?>" class="btn btn-ghost btn-sm">+ Cotización</a>
                    <form method="post" data-confirm="¿Eliminar este cliente?"><?= csrf_field() ?><input type="hidden" name="accion" value="eliminar"><input type="hidden" name="id" value="<?= $cli['id'] ?>"><button class="btn btn-danger btn-sm">Eliminar</button></form>
                </div>
                <?php endif; ?>
            </section>
        </aside>
    </div>
<?php else:
    $s = get('q');
    $rows = q_all('SELECT c.*, (SELECT COUNT(*) FROM tickets t WHERE t.cliente_id = c.id) AS tickets,
        (SELECT COUNT(*) FROM tickets t WHERE t.cliente_id = c.id AND t.estado NOT IN (\'Resuelto\',\'Cerrado\')) AS abiertos
        FROM clientes c WHERE c.nombre LIKE ? OR c.email LIKE ? OR c.telefono LIKE ? OR c.empresa LIKE ? ORDER BY c.nombre', ["%$s%", "%$s%", "%$s%", "%$s%"]);
?>
    <div class="toolbar-row">
        <form class="filters" method="get"><input type="search" name="q" value="<?= e($s) ?>" placeholder="Buscar por nombre, correo, teléfono o empresa…"><button class="btn btn-ghost btn-sm">Buscar</button></form>
        <?php if ($admin): ?><a href="<?= url('admin/clientes.php?editar=nuevo') ?>" class="btn btn-primary">+ Registrar cliente</a><?php endif; ?>
    </div>
    <section class="panel">
        <div class="panel-head"><h2><?= count($rows) ?> clientes</h2></div>
        <div class="table-wrap"><table class="table">
            <thead><tr><th>Cliente</th><th>Contacto</th><th>Empresa</th><th>Tickets</th><th>Abiertos</th><th>Desde</th><th></th></tr></thead>
            <tbody><?php foreach ($rows as $c): ?>
                <tr>
                    <td><b><?= e($c['nombre']) ?></b></td><td><?= e($c['email']) ?><br><small class="muted"><?= e($c['telefono']) ?></small></td>
                    <td><?= e($c['empresa'] ?: '—') ?></td><td><?= $c['tickets'] ?></td><td><?= $c['abiertos'] ? '<span class="badge badge-warning">' . $c['abiertos'] . '</span>' : '0' ?></td>
                    <td><?= fecha($c['creado'], false) ?></td>
                    <td><a href="<?= url('admin/clientes.php?ver=' . $c['id']) ?>" class="btn btn-sm btn-ghost">Ver</a></td>
                </tr>
            <?php endforeach; ?></tbody>
        </table></div>
    </section>
<?php endif;
require __DIR__ . '/partials/bottom.php';
