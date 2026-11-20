<?php
/** Vista imprimible / PDF de una cotización. Requiere $c (fila de cotizaciones). */
$cl = q_one('SELECT * FROM clientes WHERE id = ?', [$c['cliente_id']]);
$items = json_decode($c['items'] ?: '[]', true) ?: [];
$tk = $c['ticket_id'] ? q_one('SELECT codigo FROM tickets WHERE id = ?', [$c['ticket_id']]) : null;
?><!DOCTYPE html>
<html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Cotización <?= e($c['numero']) ?> - SoportesRD</title>
<link rel="stylesheet" href="<?= url('assets/css/print.css') ?>">
</head><body>
<div class="toolbar no-print"><button onclick="window.print()">Imprimir / Guardar PDF</button></div>
<div class="doc">
    <header class="doc-head">
        <div class="doc-brand"><img src="<?= url('assets/img/logo.svg') ?>" alt="" width="54"><div><b class="doc-name">SoportesRD</b><small><?= e(setting('eslogan')) ?></small></div></div>
        <div class="doc-meta"><h1>COTIZACIÓN</h1><p><b><?= e($c['numero']) ?></b></p><p>Fecha: <?= fecha($c['creado'], false) ?></p><p>Válida por <?= (int)$c['validez_dias'] ?> días</p><?php if ($tk): ?><p>Ticket: <?= e($tk['codigo']) ?></p><?php endif; ?></div>
    </header>
    <section class="doc-parties">
        <div><h3>Cliente</h3><p><b><?= e($cl['nombre'] ?? '') ?></b><br><?= e($cl['empresa'] ?? '') ?><br><?= e($cl['email'] ?? '') ?><br><?= e($cl['telefono'] ?? '') ?></p></div>
        <div><h3>Emitida por</h3><p><b>SoportesRD</b><br><?= e(setting('direccion')) ?><br><?= e(setting('email')) ?><br><?= e(setting('telefono')) ?></p></div>
    </section>
    <?php if ($c['detalle']): ?><p class="doc-detail"><?= nl2br(e($c['detalle'])) ?></p><?php endif; ?>
    <table class="doc-table">
        <thead><tr><th>#</th><th>Descripción</th><th class="r">Cant.</th><th class="r">Precio</th><th class="r">Importe</th></tr></thead>
        <tbody><?php foreach ($items as $i => $it): ?>
            <tr><td><?= $i + 1 ?></td><td><?= e($it['desc']) ?></td><td class="r"><?= e($it['cant']) ?></td><td class="r"><?= money($it['precio']) ?></td><td class="r"><?= money($it['cant'] * $it['precio']) ?></td></tr>
        <?php endforeach; ?></tbody>
        <tfoot>
            <tr><td colspan="4" class="r">Subtotal</td><td class="r"><?= money($c['subtotal']) ?></td></tr>
            <tr><td colspan="4" class="r">ITBIS</td><td class="r"><?= money($c['itbis']) ?></td></tr>
            <tr class="total"><td colspan="4" class="r">TOTAL</td><td class="r"><?= money($c['total']) ?></td></tr>
        </tfoot>
    </table>
    <footer class="doc-foot">Gracias por confiar en SoportesRD · <?= e(setting('whatsapp_texto')) ?> · <?= e(setting('email')) ?></footer>
</div>
</body></html>
