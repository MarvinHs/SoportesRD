<?php
require __DIR__ . '/../includes/functions.php';
$me = require_admin();
$pageTitle = 'Técnicos y usuarios';
$section = 'tecnicos';

if (is_post()) {
    csrf_check();
    $id = (int)post('id');
    if (post('accion') === 'guardar') {
        $email = strtolower(post('email'));
        $rol = post('rol') === 'admin' ? 'admin' : 'tecnico';
        if (mb_strlen(post('nombre')) < 3 || !filter_var($email, FILTER_VALIDATE_EMAIL)) { flash('error', 'Nombre y correo válido son obligatorios.'); redirect('admin/tecnicos.php?editar=' . $id); }
        if (q_val('SELECT COUNT(*) FROM usuarios WHERE LOWER(email) = ? AND id <> ?', [$email, $id])) { flash('error', 'Ese correo ya está en uso.'); redirect('admin/tecnicos.php?editar=' . $id); }
        if (!$id && strlen((string)post('password')) < 8) { flash('error', 'La contraseña debe tener al menos 8 caracteres.'); redirect('admin/tecnicos.php?editar=0'); }
        $activo = post('activo') ? 1 : 0;
        if ($id === (int)$me['id']) { $activo = 1; $rol = 'admin'; } // no puedes quitarte el acceso a ti mismo
        if ($id) {
            q('UPDATE usuarios SET nombre=?, email=?, telefono=?, rol=?, especialidad=?, activo=? WHERE id=?', [post('nombre'), $email, post('telefono'), $rol, post('especialidad'), $activo, $id]);
            if (strlen((string)post('password')) >= 8) q('UPDATE usuarios SET password=? WHERE id=?', [password_hash(post('password'), PASSWORD_DEFAULT), $id]);
        } else {
            q('INSERT INTO usuarios (nombre, email, telefono, password, rol, especialidad, activo, creado) VALUES (?,?,?,?,?,?,?,?)', [post('nombre'), $email, post('telefono'), password_hash(post('password'), PASSWORD_DEFAULT), $rol, post('especialidad'), $activo, now()]);
        }
        log_activity(($id ? 'Editó' : 'Registró') . ' al usuario ' . post('nombre'));
        flash('success', 'Usuario guardado.');
    } elseif (post('accion') === 'eliminar' && $id !== (int)$me['id']) {
        if (q_val('SELECT COUNT(*) FROM tickets WHERE tecnico_id = ?', [$id])) {
            q('UPDATE usuarios SET activo = 0 WHERE id = ?', [$id]);
            flash('warning', 'El técnico tiene tickets en su historial; se desactivó en lugar de eliminarse.');
        } else {
            q('DELETE FROM usuarios WHERE id = ?', [$id]);
            flash('success', 'Usuario eliminado.');
        }
        log_activity('Eliminó/desactivó un usuario');
    }
    redirect('admin/tecnicos.php');
}
$rows = q_all("SELECT u.*, (SELECT COUNT(*) FROM tickets t WHERE t.tecnico_id = u.id AND t.estado NOT IN ('Resuelto','Cerrado')) AS abiertos,
    (SELECT COUNT(*) FROM tickets t WHERE t.tecnico_id = u.id AND t.estado IN ('Resuelto','Cerrado')) AS resueltos FROM usuarios u ORDER BY u.rol, u.nombre");
$ed = get('editar') !== '' ? (q_one('SELECT * FROM usuarios WHERE id = ?', [(int)get('editar')]) ?: ['id' => 0, 'nombre' => '', 'email' => '', 'telefono' => '', 'rol' => 'tecnico', 'especialidad' => '', 'activo' => 1]) : null;
require __DIR__ . '/partials/top.php';
?>
<div class="grid-2-1">
    <section class="panel">
        <div class="panel-head"><h2>Personal técnico</h2><a href="?editar=0" class="btn btn-primary btn-sm">+ Nuevo usuario</a></div>
        <div class="table-wrap"><table class="table">
            <thead><tr><th>Nombre</th><th>Rol</th><th>Especialidad</th><th>Abiertos</th><th>Resueltos</th><th>Estado</th><th></th></tr></thead>
            <tbody><?php foreach ($rows as $u): ?>
                <tr>
                    <td><b><?= e($u['nombre']) ?></b><br><small class="muted"><?= e($u['email']) ?></small></td>
                    <td><?= $u['rol'] === 'admin' ? '<span class="badge badge-primary">Administrador</span>' : '<span class="badge badge-info">Técnico</span>' ?></td>
                    <td><?= e($u['especialidad'] ?: '—') ?></td><td><?= $u['abiertos'] ?></td><td><?= $u['resueltos'] ?></td>
                    <td><?= $u['activo'] ? '<span class="badge badge-success">Activo</span>' : '<span class="badge badge-muted">Inactivo</span>' ?></td>
                    <td class="nowrap"><a href="?editar=<?= $u['id'] ?>" class="btn btn-sm btn-ghost">Editar</a>
                    <?php if ((int)$u['id'] !== (int)$me['id']): ?><form method="post" class="inline" data-confirm="¿Eliminar este usuario?"><?= csrf_field() ?><input type="hidden" name="accion" value="eliminar"><input type="hidden" name="id" value="<?= $u['id'] ?>"><button class="btn btn-sm btn-ghost">✕</button></form><?php endif; ?></td>
                </tr>
            <?php endforeach; ?></tbody>
        </table></div>
        <div class="alert alert-info mt"><b>Permisos por tipo de usuario:</b> el <b>Administrador</b> accede a todos los módulos. El <b>Técnico</b> solo ve el dashboard, sus tickets asignados y la lista de clientes (solo lectura).</div>
    </section>
    <?php if ($ed): ?>
    <section class="panel">
        <h2><?= $ed['id'] ? 'Editar usuario' : 'Nuevo usuario' ?></h2>
        <form method="post" class="form">
            <?= csrf_field() ?><input type="hidden" name="accion" value="guardar"><input type="hidden" name="id" value="<?= (int)$ed['id'] ?>">
            <label>Nombre completo *<input name="nombre" value="<?= e($ed['nombre']) ?>" required></label>
            <label>Correo (usuario de acceso) *<input type="email" name="email" value="<?= e($ed['email']) ?>" required></label>
            <label>Teléfono<input name="telefono" value="<?= e($ed['telefono']) ?>"></label>
            <label>Especialidad<input name="especialidad" value="<?= e($ed['especialidad']) ?>" placeholder="Ej.: Redes y conectividad"></label>
            <label>Rol<select name="rol"><option value="tecnico" <?= $ed['rol'] === 'tecnico' ? 'selected' : '' ?>>Técnico</option><option value="admin" <?= $ed['rol'] === 'admin' ? 'selected' : '' ?>>Administrador</option></select></label>
            <label><?= $ed['id'] ? 'Nueva contraseña (dejar en blanco para no cambiar)' : 'Contraseña *' ?><input type="password" name="password" minlength="8" autocomplete="new-password"></label>
            <label class="radio"><input type="checkbox" name="activo" value="1" <?= $ed['activo'] ? 'checked' : '' ?>> Usuario activo</label>
            <button class="btn btn-primary btn-block">Guardar</button>
        </form>
    </section>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/partials/bottom.php'; ?>
