<?php
require __DIR__ . '/../includes/functions.php';
$me = require_admin();
$pageTitle = 'Base de conocimientos (FAQ)';
$section = 'faq';
if (is_post()) {
    csrf_check();
    $id = (int)post('id');
    if (post('accion') === 'guardar') {
        if (post('pregunta') === '' || post('respuesta') === '') { flash('error', 'Pregunta y respuesta son obligatorias.'); redirect('admin/faq.php?editar=' . $id); }
        $v = [post('pregunta'), post('respuesta'), post('categoria') ?: 'General', post('activo') ? 1 : 0, (int)post('orden')];
        if ($id) q('UPDATE faq SET pregunta=?, respuesta=?, categoria=?, activo=?, orden=? WHERE id=?', [...$v, $id]);
        else q('INSERT INTO faq (pregunta, respuesta, categoria, activo, orden) VALUES (?,?,?,?,?)', $v);
        log_activity(($id ? 'Editó' : 'Agregó') . ' una pregunta frecuente');
        flash('success', 'Pregunta guardada.');
    } elseif (post('accion') === 'eliminar') {
        q('DELETE FROM faq WHERE id = ?', [$id]);
        flash('success', 'Pregunta eliminada.');
    }
    redirect('admin/faq.php');
}
$rows = q_all('SELECT * FROM faq ORDER BY categoria, orden');
$ed = get('editar') !== '' ? (q_one('SELECT * FROM faq WHERE id = ?', [(int)get('editar')]) ?: ['id' => 0, 'pregunta' => '', 'respuesta' => '', 'categoria' => 'General', 'activo' => 1, 'orden' => count($rows) + 1]) : null;
require __DIR__ . '/partials/top.php';
?>
<div class="grid-2-1">
    <section class="panel">
        <div class="panel-head"><h2>Preguntas frecuentes</h2><a href="?editar=0" class="btn btn-primary btn-sm">+ Nueva pregunta</a></div>
        <div class="table-wrap"><table class="table">
            <thead><tr><th>Pregunta</th><th>Categoría</th><th>Estado</th><th></th></tr></thead>
            <tbody><?php foreach ($rows as $f): ?>
                <tr><td><b><?= e($f['pregunta']) ?></b><br><small class="muted"><?= e(mb_strimwidth($f['respuesta'], 0, 90, '…')) ?></small></td><td><?= e($f['categoria']) ?></td>
                <td><?= $f['activo'] ? '<span class="badge badge-success">Publicada</span>' : '<span class="badge badge-muted">Oculta</span>' ?></td>
                <td class="nowrap"><a href="?editar=<?= $f['id'] ?>" class="btn btn-sm btn-ghost">Editar</a>
                <form method="post" class="inline" data-confirm="¿Eliminar esta pregunta?"><?= csrf_field() ?><input type="hidden" name="accion" value="eliminar"><input type="hidden" name="id" value="<?= $f['id'] ?>"><button class="btn btn-sm btn-ghost">✕</button></form></td></tr>
            <?php endforeach; ?></tbody>
        </table></div>
    </section>
    <?php if ($ed): ?>
    <section class="panel">
        <h2><?= $ed['id'] ? 'Editar pregunta' : 'Nueva pregunta' ?></h2>
        <form method="post" class="form">
            <?= csrf_field() ?><input type="hidden" name="accion" value="guardar"><input type="hidden" name="id" value="<?= (int)$ed['id'] ?>">
            <label>Pregunta *<input name="pregunta" value="<?= e($ed['pregunta']) ?>" required></label>
            <label>Respuesta *<textarea name="respuesta" rows="5" required><?= e($ed['respuesta']) ?></textarea></label>
            <div class="form-grid"><label>Categoría<input name="categoria" value="<?= e($ed['categoria']) ?>"></label><label>Orden<input type="number" name="orden" value="<?= (int)$ed['orden'] ?>"></label></div>
            <label class="radio"><input type="checkbox" name="activo" value="1" <?= $ed['activo'] ? 'checked' : '' ?>> Publicada en el sitio</label>
            <button class="btn btn-primary btn-block">Guardar</button>
        </form>
    </section>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/partials/bottom.php'; ?>
