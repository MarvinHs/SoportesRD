<?php
require __DIR__ . '/../includes/functions.php';
$me = require_admin();
$pageTitle = 'Asignaciones';
$section = 'asignaciones';

if (is_post()) {
    csrf_check();
    $tec = (int)post('tecnico_id');
    $ids = array_map('intval', (array)($_POST['tickets'] ?? []));
    $nom = q_val('SELECT nombre FROM usuarios WHERE id = ? AND activo = 1', [$tec]);
    if (!$nom || !$ids) {
        flash('error', 'Selecciona al menos un ticket y un técnico.');
    } else {
        foreach ($ids as $id) {
            $t = q_one('SELECT * FROM tickets WHERE id = ?', [$id]);
            if (!$t) continue;
            $estado = $t['estado'] === 'Recibido' ? 'Pendiente' : $t['estado'];
            q('UPDATE tickets SET tecnico_id = ?, estado = ?, actualizado = ? WHERE id = ?', [$tec, $estado, now(), $id]);
            add_history($id, 'estado', $estado !== $t['estado'] ? $estado : null, "Ticket asignado a $nom.", true, $me);
        }
        send_mail(q_val('SELECT email FROM usuarios WHERE id = ?', [$tec]), 'Nuevos tickets asignados', '<p>Se te asignaron ' . count($ids) . ' ticket(s) en el panel de SoportesRD.</p>');
        log_activity('Asignó ' . count($ids) . " ticket(s) a $nom");
        flash('success', count($ids) . " ticket(s) asignados a $nom.");
    }
    redirect('admin/asignaciones.php');
}

$sin = q_all("SELECT t.*, c.nombre AS cliente, s.nombre AS servicio FROM tickets t JOIN clientes c ON c.id = t.cliente_id LEFT JOIN servicios s ON s.id = t.servicio_id
    WHERE t.tecnico_id IS NULL AND t.estado NOT IN ('Resuelto','Cerrado')
    ORDER BY CASE t.urgencia WHEN 'Crítica' THEN 0 WHEN 'Alta' THEN 1 WHEN 'Media' THEN 2 ELSE 3 END, t.creado");
$tecs = q_all("SELECT u.*, (SELECT COUNT(*) FROM tickets t WHERE t.tecnico_id = u.id AND t.estado NOT IN ('Resuelto','Cerrado')) AS carga,
    (SELECT COUNT(*) FROM tickets t WHERE t.tecnico_id = u.id AND t.estado NOT IN ('Resuelto','Cerrado') AND t.urgencia IN ('Alta','Crítica')) AS urgentes
    FROM usuarios u WHERE u.activo = 1 ORDER BY carga, u.nombre");
$maxCarga = max(array_column($tecs, 'carga') ?: [1]) ?: 1;
require __DIR__ . '/partials/top.php';
?>
<div class="grid-2-1">
    <section class="panel">
        <form method="post">
            <?= csrf_field() ?>
            <div class="panel-head"><h2>Tickets sin asignar (<?= count($sin) ?>)</h2>
                <div class="form-inline"><select name="tecnico_id" class="input-sm"><option value="">Asignar a…</option><?php foreach ($tecs as $t): ?><option value="<?= $t['id'] ?>"><?= e($t['nombre']) ?> (<?= $t['carga'] ?>)</option><?php endforeach; ?></select><button class="btn btn-primary btn-sm">Asignar seleccionados</button></div>
            </div>
            <div class="table-wrap"><table class="table">
                <thead><tr><th><input type="checkbox" onclick="document.querySelectorAll('[name=\'tickets[]\']').forEach(c => c.checked = this.checked)"></th><th>Ticket</th><th>Cliente</th><th>Servicio</th><th>Prioridad</th><th>Espera</th></tr></thead>
                <tbody><?php foreach ($sin as $t): $h = floor((time() - strtotime($t['creado'])) / 3600); ?>
                    <tr><td><input type="checkbox" name="tickets[]" value="<?= $t['id'] ?>"></td>
                        <td><a href="<?= url('admin/ticket.php?id=' . $t['id']) ?>"><b><?= e($t['codigo']) ?></b></a><br><small class="muted"><?= e(mb_strimwidth($t['titulo'], 0, 40, '…')) ?></small></td>
                        <td><?= e($t['cliente']) ?></td><td><?= e($t['servicio']) ?></td><td><?= urgencia_badge($t['urgencia']) ?></td>
                        <td><?= $h < 48 ? $h . ' h' : floor($h / 24) . ' días' ?></td></tr>
                <?php endforeach; ?>
                <?php if (!$sin): ?><tr><td colspan="6" class="center muted">🎉 Todos los tickets abiertos están asignados.</td></tr><?php endif; ?></tbody>
            </table></div>
        </form>
    </section>
    <section class="panel">
        <div class="panel-head"><h2>Carga de trabajo</h2></div>
        <?php foreach ($tecs as $t): ?>
            <div class="workload">
                <div class="flex-between"><b><?= e($t['nombre']) ?></b><span><?= $t['carga'] ?> abiertos<?= $t['urgentes'] ? ' · <span class="badge badge-danger">' . $t['urgentes'] . ' urgentes</span>' : '' ?></span></div>
                <small class="muted"><?= e($t['especialidad']) ?></small>
                <div class="progress"><i style="width:<?= round($t['carga'] * 100 / $maxCarga) ?>%"></i></div>
                <a href="<?= url('admin/tickets.php?tecnico=' . $t['id'] . '&estado=abiertos') ?>" class="small">Ver sus tickets →</a>
            </div>
        <?php endforeach; ?>
    </section>
</div>
<?php require __DIR__ . '/partials/bottom.php'; ?>
