<?php
require __DIR__ . '/../includes/functions.php';
require __DIR__ . '/partials/charts.php';
$me = require_admin();
$pageTitle = 'Reportes y estadísticas';
$section = 'reportes';

$desde = get('desde') ?: date('Y-m-01', strtotime('-5 months'));
$hasta = get('hasta') ?: date('Y-m-d');
$rango = [$desde . ' 00:00:00', $hasta . ' 23:59:59'];

$tickets = q_all('SELECT t.*, s.nombre AS servicio, u.nombre AS tecnico, c.nombre AS cliente FROM tickets t
    JOIN clientes c ON c.id = t.cliente_id LEFT JOIN servicios s ON s.id = t.servicio_id LEFT JOIN usuarios u ON u.id = t.tecnico_id
    WHERE t.creado BETWEEN ? AND ? ORDER BY t.creado', $rango);

$total = count($tickets);
$porEstado = array_fill_keys(ESTADOS, 0);
$porUrg = array_fill_keys(URGENCIAS, 0);
$porServ = []; $porTec = []; $porMes = []; $horas = [];
foreach ($tickets as $t) {
    $porEstado[$t['estado']] = ($porEstado[$t['estado']] ?? 0) + 1;
    $porUrg[$t['urgencia']] = ($porUrg[$t['urgencia']] ?? 0) + 1;
    $porServ[$t['servicio'] ?? 'Sin servicio'] = ($porServ[$t['servicio'] ?? 'Sin servicio'] ?? 0) + 1;
    $k = $t['tecnico'] ?? 'Sin asignar';
    $porTec[$k] ??= ['total' => 0, 'resueltos' => 0, 'horas' => []];
    $porTec[$k]['total']++;
    $ym = substr($t['creado'], 0, 7);
    $porMes[$ym] = ($porMes[$ym] ?? 0) + 1;
    if ($t['resuelto']) {
        $h = (strtotime($t['resuelto']) - strtotime($t['creado'])) / 3600;
        $horas[] = $h;
        $porTec[$k]['resueltos']++;
        $porTec[$k]['horas'][] = $h;
    }
}
arsort($porServ);
ksort($porMes);
$resueltos = $porEstado['Resuelto'] + $porEstado['Cerrado'];
$promedio = $horas ? array_sum($horas) / count($horas) : 0;
$fmtH = fn($h) => $h >= 48 ? round($h / 24, 1) . ' días' : round($h, 1) . ' h';
$mesLabels = array_map(fn($ym) => meses_es()[(int)substr($ym, 5, 2) - 1] . ' ' . substr($ym, 2, 2), array_keys($porMes));
$nuevosClientes = (int)q_val('SELECT COUNT(*) FROM clientes WHERE creado BETWEEN ? AND ?', $rango);
$cotAprob = (float)q_val("SELECT COALESCE(SUM(total),0) FROM cotizaciones WHERE estado = 'Aprobada' AND creado BETWEEN ? AND ?", $rango);

// Exportar CSV
if (get('csv')) {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="tickets_' . $desde . '_' . $hasta . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, ['Ticket', 'Fecha', 'Cliente', 'Servicio', 'Prioridad', 'Estado', 'Técnico', 'Resuelto']);
    foreach ($tickets as $t) fputcsv($out, [$t['codigo'], $t['creado'], $t['cliente'], $t['servicio'], $t['urgencia'], $t['estado'], $t['tecnico'], $t['resuelto']]);
    exit;
}

// Vista PDF / impresión
if (get('pdf')): ?><!DOCTYPE html>
<html lang="es"><head><meta charset="UTF-8"><title>Reporte <?= e($desde) ?> a <?= e($hasta) ?> - SoportesRD</title><link rel="stylesheet" href="<?= url('assets/css/print.css') ?>"><link rel="stylesheet" href="<?= url('assets/css/admin.css') ?>"></head>
<body class="print-report"><div class="toolbar no-print"><button onclick="window.print()">Imprimir / Guardar PDF</button> <a href="?desde=<?= e($desde) ?>&hasta=<?= e($hasta) ?>">Volver</a></div>
<div class="doc">
    <header class="doc-head"><div class="doc-brand"><img src="<?= url('assets/img/logo.svg') ?>" width="54" alt=""><div><b class="doc-name">SoportesRD</b><small>Reporte de operaciones</small></div></div>
        <div class="doc-meta"><h1>REPORTE</h1><p>Del <?= fecha($desde, false) ?> al <?= fecha($hasta, false) ?></p><p>Generado: <?= fecha(now()) ?></p></div></header>
    <div class="kpis"><div><b><?= $total ?></b>Tickets</div><div><b><?= $resueltos ?></b>Resueltos</div><div><b><?= $total ? round($resueltos * 100 / $total) : 0 ?>%</b>Tasa de solución</div><div><b><?= $fmtH($promedio) ?></b>Tiempo promedio</div></div>
    <h2 class="sec">Solicitudes por mes</h2><?= chart_bars($mesLabels, array_values($porMes)) ?>
    <h2 class="sec">Por estado</h2><table class="doc-table"><tbody><?php foreach ($porEstado as $k => $v): ?><tr><td><?= e($k) ?></td><td class="r"><?= $v ?></td></tr><?php endforeach; ?></tbody></table>
    <h2 class="sec">Por servicio</h2><table class="doc-table"><tbody><?php foreach ($porServ as $k => $v): ?><tr><td><?= e($k) ?></td><td class="r"><?= $v ?></td></tr><?php endforeach; ?></tbody></table>
    <h2 class="sec">Rendimiento por técnico</h2><table class="doc-table"><thead><tr><th>Técnico</th><th class="r">Tickets</th><th class="r">Resueltos</th><th class="r">Tiempo prom.</th></tr></thead><tbody>
        <?php foreach ($porTec as $k => $v): ?><tr><td><?= e($k) ?></td><td class="r"><?= $v['total'] ?></td><td class="r"><?= $v['resueltos'] ?></td><td class="r"><?= $v['horas'] ? $fmtH(array_sum($v['horas']) / count($v['horas'])) : '—' ?></td></tr><?php endforeach; ?></tbody></table>
    <footer class="doc-foot">SoportesRD · <?= e(setting('email')) ?> · <?= e(setting('telefono')) ?></footer>
</div></body></html>
<?php exit; endif;

require __DIR__ . '/partials/top.php';
?>
<div class="toolbar-row">
    <form class="filters" method="get">
        <label class="inline-label">Desde <input type="date" name="desde" value="<?= e($desde) ?>"></label>
        <label class="inline-label">Hasta <input type="date" name="hasta" value="<?= e($hasta) ?>"></label>
        <button class="btn btn-ghost btn-sm">Aplicar</button>
    </form>
    <div class="btn-row">
        <a class="btn btn-primary" target="_blank" href="?desde=<?= e($desde) ?>&hasta=<?= e($hasta) ?>&pdf=1"><?= icon('file', 18) ?> Reporte PDF</a>
        <a class="btn btn-ghost" href="?desde=<?= e($desde) ?>&hasta=<?= e($hasta) ?>&csv=1">Exportar Excel (CSV)</a>
    </div>
</div>

<div class="kpi-grid">
    <div class="kpi"><span class="kpi-ic blue"><?= icon('ticket') ?></span><div><b><?= $total ?></b><small>Tickets en el período</small></div></div>
    <div class="kpi"><span class="kpi-ic green"><?= icon('check') ?></span><div><b><?= $total ? round($resueltos * 100 / $total) : 0 ?>%</b><small>Tasa de solución</small></div></div>
    <div class="kpi"><span class="kpi-ic amber"><?= icon('clock') ?></span><div><b><?= $fmtH($promedio) ?></b><small>Tiempo promedio de solución</small></div></div>
    <div class="kpi"><span class="kpi-ic navy"><?= icon('users') ?></span><div><b><?= $nuevosClientes ?></b><small>Clientes nuevos · <?= money($cotAprob) ?> cotizado aprobado</small></div></div>
</div>

<div class="grid-2-1">
    <section class="panel"><div class="panel-head"><h2>Solicitudes por mes</h2></div><?= chart_bars($mesLabels ?: ['—'], array_values($porMes) ?: [0]) ?></section>
    <section class="panel"><div class="panel-head"><h2>Por prioridad</h2></div><?= chart_donut($porUrg, ['#94a3b8', '#1677FF', '#f59e0b', '#dc2626']) ?></section>
</div>
<div class="grid-2">
    <section class="panel"><div class="panel-head"><h2>Por servicio</h2></div><?= chart_hbars($porServ, '#1677FF') ?></section>
    <section class="panel"><div class="panel-head"><h2>Por estado</h2></div><?= chart_hbars($porEstado, '#16A34A') ?></section>
</div>
<section class="panel">
    <div class="panel-head"><h2>Rendimiento por técnico</h2></div>
    <div class="table-wrap"><table class="table">
        <thead><tr><th>Técnico</th><th>Tickets</th><th>Resueltos</th><th>Efectividad</th><th>Tiempo promedio</th></tr></thead>
        <tbody><?php foreach ($porTec as $k => $v): $ef = $v['total'] ? round($v['resueltos'] * 100 / $v['total']) : 0; ?>
            <tr><td><b><?= e($k) ?></b></td><td><?= $v['total'] ?></td><td><?= $v['resueltos'] ?></td><td><div class="progress"><i style="width:<?= $ef ?>%"></i></div><small><?= $ef ?>%</small></td><td><?= $v['horas'] ? $fmtH(array_sum($v['horas']) / count($v['horas'])) : '—' ?></td></tr>
        <?php endforeach; ?></tbody>
    </table></div>
</section>
<?php require __DIR__ . '/partials/bottom.php'; ?>
