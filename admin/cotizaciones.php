<?php
require __DIR__ . '/../includes/functions.php';
$me = require_admin();
$pageTitle = 'Cotizaciones';
$section = 'cotizaciones';
$estadosCot = ['Borrador', 'Enviada', 'Aprobada', 'Rechazada'];

if (is_post()) {
    csrf_check();
    $id = (int)post('id');
    if (post('accion') === 'guardar') {
        $items = [];
        foreach ((array)($_POST['desc'] ?? []) as $i => $d) {
            $d = trim((string)$d);
            if ($d === '') continue;
            $items[] = ['desc' => $d, 'cant' => max(0, (float)($_POST['cant'][$i] ?? 1)), 'precio' => max(0, (float)($_POST['precio'][$i] ?? 0))];
        }
        $cid = (int)post('cliente_id');
        if (!$cid || !$items) { flash('error', 'Selecciona un cliente y agrega al menos una línea.'); redirect('admin/cotizaciones.php?' . ($id ? 'editar=' . $id : 'nueva=1')); }
        $sub = array_sum(array_map(fn($x) => $x['cant'] * $x['precio'], $items));
        $itbis = post('aplica_itbis') ? round($sub * (float)setting('itbis', '18') / 100, 2) : 0;
        $estado = in_array(post('estado'), $estadosCot, true) ? post('estado') : 'Borrador';
        $vals = [$cid, (int)post('ticket_id') ?: null, post('detalle'), json_encode($items, JSON_UNESCAPED_UNICODE), $sub, $itbis, $sub + $itbis, $estado, (int)post('validez_dias') ?: 15];
        if ($id) {
            q('UPDATE cotizaciones SET cliente_id=?, ticket_id=?, detalle=?, items=?, subtotal=?, itbis=?, total=?, estado=?, validez_dias=? WHERE id=?', [...$vals, $id]);
        } else {
            $num = 'COT-' . date('Y') . '-' . str_pad((string)((int)q_val('SELECT COUNT(*) FROM cotizaciones') + 1), 4, '0', STR_PAD_LEFT);
            q('INSERT INTO cotizaciones (cliente_id, ticket_id, detalle, items, subtotal, itbis, total, estado, validez_dias, numero, creado) VALUES (?,?,?,?,?,?,?,?,?,?,?)', [...$vals, $num, now()]);
            $id = (int)db()->lastInsertId();
        }
        $c = q_one('SELECT * FROM cotizaciones WHERE id = ?', [$id]);
        if ($estado === 'Enviada' && post('enviar')) {
            $cl = q_one('SELECT * FROM clientes WHERE id = ?', [$cid]);
            send_mail($cl['email'], "Cotización {$c['numero']} de SoportesRD", '<p>Hola ' . e($cl['nombre']) . ',</p><p>Te enviamos la cotización <b>' . e($c['numero']) . '</b> por un total de <b>' . money($c['total']) . '</b>.</p><p>Puedes verla en tu <a href="' . e(full_url('cliente/login.php')) . '">portal de clientes</a>.</p>');
        }
        if ($c['ticket_id']) add_history((int)$c['ticket_id'], 'interno', null, "Cotización {$c['numero']} guardada ({$estado}) por " . money($c['total']) . '.', false, $me);
        log_activity("Guardó la cotización {$c['numero']}");
        flash('success', "Cotización {$c['numero']} guardada.");
        redirect('admin/cotizaciones.php?editar=' . $id);
    } elseif (post('accion') === 'eliminar') {
        q('DELETE FROM cotizaciones WHERE id = ?', [$id]);
        flash('success', 'Cotización eliminada.');
        redirect('admin/cotizaciones.php');
    }
}

if (get('imprimir')) {
    $c = q_one('SELECT * FROM cotizaciones WHERE id = ?', [(int)get('imprimir')]);
    if ($c) { require __DIR__ . '/../includes/cotizacion_print.php'; exit; }
}

$clientes = q_all('SELECT id, nombre, email FROM clientes ORDER BY nombre');
$editar = get('editar');
$nueva = get('nueva');
require __DIR__ . '/partials/top.php';

if ($editar !== '' || $nueva):
    $c = $editar !== '' ? q_one('SELECT * FROM cotizaciones WHERE id = ?', [(int)$editar]) : null;
    $tkPre = (int)get('ticket');
    $cliPre = (int)get('cliente') ?: ($tkPre ? (int)q_val('SELECT cliente_id FROM tickets WHERE id = ?', [$tkPre]) : 0);
    $c = $c ?: ['id' => 0, 'numero' => 'Nueva', 'cliente_id' => $cliPre, 'ticket_id' => $tkPre ?: null, 'detalle' => '', 'items' => '[]', 'itbis' => 1, 'estado' => 'Borrador', 'validez_dias' => 15];
    $items = json_decode($c['items'], true) ?: [['desc' => '', 'cant' => 1, 'precio' => 0]];
    $tks = $c['cliente_id'] ? q_all('SELECT id, codigo, titulo FROM tickets WHERE cliente_id = ? ORDER BY creado DESC', [$c['cliente_id']]) : [];
?>
    <a href="<?= url('admin/cotizaciones.php') ?>" class="back-link">← Volver a cotizaciones</a>
    <form method="post" class="form">
        <?= csrf_field() ?><input type="hidden" name="accion" value="guardar"><input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
        <section class="panel">
            <div class="panel-head"><h2>Cotización <?= e($c['numero']) ?></h2>
                <?php if ($c['id']): ?><a href="?imprimir=<?= $c['id'] ?>" target="_blank" class="btn btn-ghost btn-sm"><?= icon('file', 16) ?> Ver / PDF</a><?php endif; ?></div>
            <div class="form-grid">
                <label>Cliente *<select name="cliente_id" required><option value="">Selecciona…</option><?php foreach ($clientes as $cl): ?><option value="<?= $cl['id'] ?>" <?= (int)$c['cliente_id'] === (int)$cl['id'] ? 'selected' : '' ?>><?= e($cl['nombre'] . ' · ' . $cl['email']) ?></option><?php endforeach; ?></select></label>
                <label>Ticket relacionado<select name="ticket_id"><option value="">Ninguno</option><?php foreach ($tks as $t): ?><option value="<?= $t['id'] ?>" <?= (int)$c['ticket_id'] === (int)$t['id'] ? 'selected' : '' ?>><?= e($t['codigo'] . ' · ' . mb_strimwidth($t['titulo'], 0, 40, '…')) ?></option><?php endforeach; ?></select></label>
                <label>Estado<select name="estado"><?php foreach ($estadosCot as $s): ?><option <?= $c['estado'] === $s ? 'selected' : '' ?>><?= $s ?></option><?php endforeach; ?></select></label>
                <label>Validez (días)<input type="number" name="validez_dias" value="<?= (int)$c['validez_dias'] ?>" min="1"></label>
                <label class="full">Detalle / condiciones<textarea name="detalle" rows="2"><?= e($c['detalle']) ?></textarea></label>
            </div>
            <div class="table-wrap"><table class="table items-table">
                <thead><tr><th>Descripción</th><th style="width:110px">Cantidad</th><th style="width:150px">Precio (RD$)</th><th style="width:150px" class="r">Importe</th><th></th></tr></thead>
                <tbody data-items data-itbis="<?= e(setting('itbis', '18')) ?>">
                <?php foreach ($items as $it): ?>
                    <tr><td><input name="desc[]" value="<?= e($it['desc']) ?>"></td><td><input type="number" step="0.01" name="cant[]" value="<?= e($it['cant']) ?>"></td><td><input type="number" step="0.01" name="precio[]" value="<?= e($it['precio']) ?>"></td><td class="r" data-imp></td><td><button type="button" class="btn btn-sm btn-ghost" data-del>✕</button></td></tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr><td colspan="5"><button type="button" class="btn btn-sm btn-ghost" data-add-item>+ Agregar línea</button></td></tr>
                    <tr><td colspan="3" class="r">Subtotal</td><td class="r" data-sub></td><td></td></tr>
                    <tr><td colspan="3" class="r"><label class="radio inline-flex"><input type="checkbox" name="aplica_itbis" value="1" <?= ($c['id'] ? (float)$c['itbis'] > 0 : true) ? 'checked' : '' ?>> ITBIS <?= e(setting('itbis', '18')) ?>%</label></td><td class="r" data-itbis-val></td><td></td></tr>
                    <tr class="total-row"><td colspan="3" class="r"><b>TOTAL</b></td><td class="r"><b data-total></b></td><td></td></tr>
                </tfoot>
            </table></div>
            <template id="item-tpl"><tr><td><input name="desc[]"></td><td><input type="number" step="0.01" name="cant[]" value="1"></td><td><input type="number" step="0.01" name="precio[]" value="0"></td><td class="r" data-imp></td><td><button type="button" class="btn btn-sm btn-ghost" data-del>✕</button></td></tr></template>
            <div class="form-inline mt">
                <label class="radio"><input type="checkbox" name="enviar" value="1"> Notificar al cliente por correo (si el estado es "Enviada")</label>
                <button class="btn btn-primary">Guardar cotización</button>
            </div>
        </section>
    </form>
    <?php if ($c['id']): ?>
        <form method="post" data-confirm="¿Eliminar esta cotización?"><?= csrf_field() ?><input type="hidden" name="accion" value="eliminar"><input type="hidden" name="id" value="<?= $c['id'] ?>"><button class="btn btn-danger btn-sm">Eliminar cotización</button></form>
    <?php endif; ?>
<?php else:
    $rows = q_all('SELECT q.*, c.nombre AS cliente, t.codigo AS ticket FROM cotizaciones q JOIN clientes c ON c.id = q.cliente_id LEFT JOIN tickets t ON t.id = q.ticket_id ORDER BY q.creado DESC');
    $totAprob = array_sum(array_map(fn($r) => $r['estado'] === 'Aprobada' ? $r['total'] : 0, $rows));
?>
    <div class="toolbar-row"><div class="muted">Aprobado: <b><?= money($totAprob) ?></b></div><a href="?nueva=1" class="btn btn-primary">+ Nueva cotización</a></div>
    <section class="panel">
        <div class="table-wrap"><table class="table">
            <thead><tr><th>Número</th><th>Cliente</th><th>Ticket</th><th>Fecha</th><th class="r">Total</th><th>Estado</th><th></th></tr></thead>
            <tbody><?php foreach ($rows as $r):
                $b = ['Borrador' => 'muted', 'Enviada' => 'info', 'Aprobada' => 'success', 'Rechazada' => 'danger'][$r['estado']] ?? 'muted'; ?>
                <tr><td><b><?= e($r['numero']) ?></b></td><td><?= e($r['cliente']) ?></td><td><?= e($r['ticket'] ?? '—') ?></td><td><?= fecha($r['creado'], false) ?></td><td class="r"><?= money($r['total']) ?></td><td><span class="badge badge-<?= $b ?>"><?= e($r['estado']) ?></span></td>
                <td class="nowrap"><a href="?editar=<?= $r['id'] ?>" class="btn btn-sm btn-ghost">Editar</a> <a href="?imprimir=<?= $r['id'] ?>" target="_blank" class="btn btn-sm btn-ghost">PDF</a></td></tr>
            <?php endforeach; ?>
            <?php if (!$rows): ?><tr><td colspan="7" class="center muted">Aún no hay cotizaciones.</td></tr><?php endif; ?></tbody>
        </table></div>
    </section>
<?php endif;
require __DIR__ . '/partials/bottom.php';
