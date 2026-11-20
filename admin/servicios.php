<?php
require __DIR__ . '/../includes/functions.php';
$me = require_admin();
$pageTitle = 'Servicios';
$section = 'servicios';
$iconos = ['tool' => 'Herramienta', 'headset' => 'Auriculares', 'wifi' => 'Wi-Fi', 'download' => 'Descarga', 'sparkle' => 'Limpieza', 'shield' => 'Escudo', 'monitor' => 'Monitor', 'phone' => 'Teléfono', 'settings' => 'Engranaje', 'file' => 'Documento'];

if (is_post()) {
    csrf_check();
    $id = (int)post('id');
    if (post('accion') === 'guardar') {
        if (mb_strlen(post('nombre')) < 3) { flash('error', 'El nombre es obligatorio.'); redirect('admin/servicios.php'); }
        $vals = [post('nombre'), post('descripcion'), array_key_exists(post('icono'), $iconos) ? post('icono') : 'tool', (float)post('precio_desde'), post('activo') ? 1 : 0, (int)post('orden')];
        if ($id) q('UPDATE servicios SET nombre=?, descripcion=?, icono=?, precio_desde=?, activo=?, orden=? WHERE id=?', [...$vals, $id]);
        else q('INSERT INTO servicios (nombre, descripcion, icono, precio_desde, activo, orden) VALUES (?,?,?,?,?,?)', $vals);
        log_activity(($id ? 'Editó' : 'Creó') . ' el servicio ' . post('nombre'));
        flash('success', 'Servicio guardado.');
    } elseif (post('accion') === 'eliminar') {
        if (q_val('SELECT COUNT(*) FROM tickets WHERE servicio_id = ?', [$id])) {
            q('UPDATE servicios SET activo = 0 WHERE id = ?', [$id]);
            flash('warning', 'El servicio tiene tickets asociados; se desactivó en lugar de eliminarse.');
        } else {
            q('DELETE FROM servicios WHERE id = ?', [$id]);
            flash('success', 'Servicio eliminado.');
        }
        log_activity('Eliminó/desactivó un servicio');
    }
    redirect('admin/servicios.php');
}
$rows = q_all('SELECT s.*, (SELECT COUNT(*) FROM tickets t WHERE t.servicio_id = s.id) AS usos FROM servicios s ORDER BY orden, nombre');
$ed = get('editar') !== '' ? (q_one('SELECT * FROM servicios WHERE id = ?', [(int)get('editar')]) ?: ['id' => 0, 'nombre' => '', 'descripcion' => '', 'icono' => 'tool', 'precio_desde' => 0, 'activo' => 1, 'orden' => count($rows) + 1]) : null;
require __DIR__ . '/partials/top.php';
?>
<div class="grid-2-1">
    <section class="panel">
        <div class="panel-head"><h2>Catálogo de servicios</h2><a href="?editar=0" class="btn btn-primary btn-sm">+ Nuevo servicio</a></div>
        <div class="table-wrap"><table class="table">
            <thead><tr><th></th><th>Servicio</th><th>Precio desde</th><th>Tickets</th><th>Estado</th><th></th></tr></thead>
            <tbody><?php foreach ($rows as $s): ?>
                <tr>
                    <td><span class="service-icon sm"><?= icon($s['icono'], 20) ?></span></td>
                    <td><b><?= e($s['nombre']) ?></b><br><small class="muted"><?= e(mb_strimwidth($s['descripcion'], 0, 70, '…')) ?></small></td>
                    <td><?= money($s['precio_desde']) ?></td><td><?= $s['usos'] ?></td>
                    <td><?= $s['activo'] ? '<span class="badge badge-success">Activo</span>' : '<span class="badge badge-muted">Inactivo</span>' ?></td>
                    <td class="nowrap"><a href="?editar=<?= $s['id'] ?>" class="btn btn-sm btn-ghost">Editar</a>
                        <form method="post" class="inline" data-confirm="¿Eliminar este servicio?"><?= csrf_field() ?><input type="hidden" name="accion" value="eliminar"><input type="hidden" name="id" value="<?= $s['id'] ?>"><button class="btn btn-sm btn-ghost">✕</button></form></td>
                </tr>
            <?php endforeach; ?></tbody>
        </table></div>
    </section>
    <?php if ($ed): ?>
    <section class="panel">
        <h2><?= $ed['id'] ? 'Editar servicio' : 'Nuevo servicio' ?></h2>
        <form method="post" class="form">
            <?= csrf_field() ?><input type="hidden" name="accion" value="guardar"><input type="hidden" name="id" value="<?= (int)$ed['id'] ?>">
            <label>Nombre *<input name="nombre" value="<?= e($ed['nombre']) ?>" required></label>
            <label>Descripción<textarea name="descripcion" rows="3"><?= e($ed['descripcion']) ?></textarea></label>
            <label>Ícono<select name="icono"><?php foreach ($iconos as $k => $v): ?><option value="<?= $k ?>" <?= $ed['icono'] === $k ? 'selected' : '' ?>><?= $v ?></option><?php endforeach; ?></select></label>
            <div class="form-grid">
                <label>Precio desde (RD$)<input type="number" step="0.01" min="0" name="precio_desde" value="<?= e($ed['precio_desde']) ?>"></label>
                <label>Orden<input type="number" name="orden" value="<?= (int)$ed['orden'] ?>"></label>
            </div>
            <label class="radio"><input type="checkbox" name="activo" value="1" <?= $ed['activo'] ? 'checked' : '' ?>> Visible en el sitio web</label>
            <button class="btn btn-primary btn-block">Guardar</button>
        </form>
    </section>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/partials/bottom.php'; ?>
