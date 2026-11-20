<?php
require __DIR__ . '/../includes/functions.php';
require __DIR__ . '/partials/charts.php';
$me = require_staff();
$pageTitle = 'Dashboard';
$section = 'dashboard';

// Los técnicos ven solo sus tickets
$w = $me['rol'] === 'admin' ? '1=1' : 't.tecnico_id = ' . (int)$me['id'];

$total     = (int)q_val("SELECT COUNT(*) FROM tickets t WHERE $w");
$recibidos = (int)q_val("SELECT COUNT(*) FROM tickets t WHERE $w AND estado = 'Recibido'");
$pend      = (int)q_val("SELECT COUNT(*) FROM tickets t WHERE $w AND estado IN ('Pendiente','En espera del cliente')");
$proceso   = (int)q_val("SELECT COUNT(*) FROM tickets t WHERE $w AND estado = 'En proceso'");
$resueltos = (int)q_val("SELECT COUNT(*) FROM tickets t WHERE $w AND estado IN ('Resuelto','Cerrado')");
$clientes  = (int)q_val('SELECT COUNT(*) FROM clientes');
$nuevosCli = (int)q_val('SELECT COUNT(*) FROM clientes WHERE creado >= ?', [date('Y-m-d', strtotime('-30 days'))]);

// Solicitudes por mes (últimos 6 meses)
$meses = []; $vals = [];
for ($i = 5; $i >= 0; $i--) {
    $ym = date('Y-m', strtotime("first day of -$i month"));
    $meses[] = meses_es()[(int)substr($ym, 5, 2) - 1];
    $vals[] = (int)q_val("SELECT COUNT(*) FROM tickets t WHERE $w AND substr(creado,1,7) = ?", [$ym]);
}

$porPrioridad = [];
foreach (URGENCIAS as $u) $porPrioridad[$u] = (int)q_val("SELECT COUNT(*) FROM tickets t WHERE $w AND urgencia = ? AND estado NOT IN ('Resuelto','Cerrado')", [$u]);

$porEstado = [];
foreach (ESTADOS as $s) $porEstado[$s] = (int)q_val("SELECT COUNT(*) FROM tickets t WHERE $w AND estado = ?", [$s]);

$recientes = q_all("SELECT t.*, c.nombre AS cliente, u.nombre AS tecnico FROM tickets t JOIN clientes c ON c.id = t.cliente_id LEFT JOIN usuarios u ON u.id = t.tecnico_id WHERE $w ORDER BY t.creado DESC LIMIT 8");

$rendimiento = q_all("SELECT u.id, u.nombre, u.especialidad,
    SUM(CASE WHEN t.estado IN ('Resuelto','Cerrado') THEN 1 ELSE 0 END) AS resueltos,
    SUM(CASE WHEN t.estado NOT IN ('Resuelto','Cerrado') THEN 1 ELSE 0 END) AS abiertos,
    COUNT(t.id) AS total
    FROM usuarios u LEFT JOIN tickets t ON t.tecnico_id = u.id
    WHERE u.activo = 1 AND u.rol = 'tecnico' GROUP BY u.id, u.nombre, u.especialidad ORDER BY resueltos DESC");

$ultimosClientes = q_all('SELECT c.*, (SELECT COUNT(*) FROM tickets x WHERE x.cliente_id = c.id) AS n FROM clientes c ORDER BY c.creado DESC LIMIT 5');
$actividad = q_all('SELECT * FROM actividad ORDER BY id DESC LIMIT 8');

require __DIR__ . '/partials/top.php';
?>
<div class="kpi-grid">
    <div class="kpi"><span class="kpi-ic blue"><?= icon('ticket') ?></span><div><b><?= $total ?></b><small>Tickets registrados</small></div></div>
    <div class="kpi"><span class="kpi-ic amber"><?= icon('clock') ?></span><div><b><?= $pend + $recibidos ?></b><small>Pendientes <?= $recibidos ? "($recibidos nuevos)" : '' ?></small></div></div>
    <div class="kpi"><span class="kpi-ic navy"><?= icon('settings') ?></span><div><b><?= $proceso ?></b><small>En proceso</small></div></div>
    <div class="kpi"><span class="kpi-ic green"><?= icon('check') ?></span><div><b><?= $resueltos ?></b><small>Resueltos</small></div></div>
</div>

<div class="grid-2-1">
    <section class="panel">
        <div class="panel-head"><h2>Solicitudes por mes</h2><span class="muted">Últimos 6 meses</span></div>
        <?= chart_bars($meses, $vals) ?>
    </section>
    <section class="panel">
        <div class="panel-head"><h2>Tickets por estado</h2></div>
        <?= chart_donut($porEstado, ['#38bdf8', '#f59e0b', '#1677FF', '#7c3aed', '#16A34A', '#94a3b8']) ?>
    </section>
</div>

<div class="grid-2-1">
    <section class="panel">
        <div class="panel-head"><h2>Tickets recientes</h2><a href="<?= url('admin/tickets.php') ?>" class="btn btn-sm btn-ghost">Ver todos</a></div>
        <div class="table-wrap"><table class="table">
            <thead><tr><th>Ticket</th><th>Cliente</th><th>Prioridad</th><th>Estado</th><th>Técnico</th><th>Fecha</th></tr></thead>
            <tbody>
            <?php foreach ($recientes as $t): ?>
                <tr class="row-link" onclick="location='<?= url('admin/ticket.php?id=' . $t['id']) ?>'">
                    <td><b><?= e($t['codigo']) ?></b><br><small class="muted"><?= e(mb_strimwidth($t['titulo'], 0, 40, '…')) ?></small></td>
                    <td><?= e($t['cliente']) ?></td><td><?= urgencia_badge($t['urgencia']) ?></td><td><?= estado_badge($t['estado']) ?></td>
                    <td><?= e($t['tecnico'] ?? '—') ?></td><td><?= fecha($t['creado'], false) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$recientes): ?><tr><td colspan="6" class="muted center">No hay tickets todavía.</td></tr><?php endif; ?>
            </tbody>
        </table></div>
    </section>
    <section class="panel">
        <div class="panel-head"><h2>Abiertos por prioridad</h2></div>
        <?= chart_hbars($porPrioridad, '#1677FF') ?>
        <div class="panel-head mt"><h2>Clientes registrados</h2><b class="big"><?= $clientes ?></b></div>
        <p class="muted small">+<?= $nuevosCli ?> en los últimos 30 días</p>
        <ul class="mini-list">
            <?php foreach ($ultimosClientes as $c): ?>
                <li><span class="avatar sm"><?= e(mb_strtoupper(mb_substr($c['nombre'], 0, 1))) ?></span><div><b><?= e($c['nombre']) ?></b><small><?= e($c['email']) ?> · <?= $c['n'] ?> tickets</small></div></li>
            <?php endforeach; ?>
        </ul>
    </section>
</div>

<div class="grid-2-1">
    <section class="panel">
        <div class="panel-head"><h2>Rendimiento de los técnicos</h2></div>
        <div class="table-wrap"><table class="table">
            <thead><tr><th>Técnico</th><th>Asignados</th><th>Abiertos</th><th>Resueltos</th><th>Efectividad</th></tr></thead>
            <tbody>
            <?php foreach ($rendimiento as $r): $ef = $r['total'] ? round($r['resueltos'] * 100 / $r['total']) : 0; ?>
                <tr>
                    <td><b><?= e($r['nombre']) ?></b><br><small class="muted"><?= e($r['especialidad']) ?></small></td>
                    <td><?= (int)$r['total'] ?></td><td><?= (int)$r['abiertos'] ?></td><td><?= (int)$r['resueltos'] ?></td>
                    <td><div class="progress"><i style="width:<?= $ef ?>%"></i></div><small><?= $ef ?>%</small></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$rendimiento): ?><tr><td colspan="5" class="muted center">Registra técnicos para ver su rendimiento.</td></tr><?php endif; ?>
            </tbody>
        </table></div>
    </section>
    <section class="panel">
        <div class="panel-head"><h2>Actividad reciente</h2></div>
        <ul class="activity">
            <?php foreach ($actividad as $a): ?>
                <li><i></i><div><b><?= e($a['usuario']) ?></b> <?= e($a['accion']) ?><small><?= fecha($a['creado']) ?></small></div></li>
            <?php endforeach; ?>
        </ul>
    </section>
</div>
<?php require __DIR__ . '/partials/bottom.php'; ?>
