<?php
require __DIR__ . '/../includes/functions.php';
$cli = cliente();
if (!$cli) redirect('cliente/login.php');
$pageTitle = 'Mi cuenta';
$active = 'cuenta';

if (is_post() && post('accion') === 'perfil') {
    csrf_check();
    $nombre = post('nombre');
    if (mb_strlen($nombre) >= 3) {
        q('UPDATE clientes SET nombre = ?, telefono = ?, empresa = ?, direccion = ? WHERE id = ?', [$nombre, post('telefono'), post('empresa'), post('direccion'), $cli['id']]);
        if (strlen((string)post('password')) >= 8) q('UPDATE clientes SET password = ? WHERE id = ?', [password_hash(post('password'), PASSWORD_DEFAULT), $cli['id']]);
        flash('success', 'Datos actualizados.');
    } else flash('error', 'El nombre es obligatorio.');
    redirect('cliente/panel.php');
}

$tickets = q_all('SELECT t.*, s.nombre AS servicio FROM tickets t LEFT JOIN servicios s ON s.id = t.servicio_id WHERE t.cliente_id = ? ORDER BY t.creado DESC', [$cli['id']]);
$cotizaciones = q_all("SELECT * FROM cotizaciones WHERE cliente_id = ? AND estado <> 'Borrador' ORDER BY creado DESC", [$cli['id']]);
foreach ($tickets as $t) $_SESSION['tickets_ok'][$t['id']] = true;
$abiertos = count(array_filter($tickets, fn($t) => !in_array($t['estado'], ['Resuelto', 'Cerrado'], true)));
require __DIR__ . '/../includes/header.php';
?>
<section class="page-hero small">
    <div class="container flex-between">
        <div><h1>Hola, <?= e(explode(' ', $cli['nombre'])[0]) ?></h1><p>Tu historial de servicios con SoportesRD.</p></div>
        <div class="hero-actions"><a href="<?= url('solicitar.php') ?>" class="btn btn-light">Nueva solicitud</a><a href="<?= url('cliente/logout.php') ?>" class="btn btn-outline-light">Salir</a></div>
    </div>
</section>
<section class="section">
    <div class="container">
        <?= flashes() ?>
        <div class="mini-stats">
            <div class="card"><b><?= count($tickets) ?></b><span>Solicitudes totales</span></div>
            <div class="card"><b><?= $abiertos ?></b><span>Abiertas</span></div>
            <div class="card"><b><?= count($tickets) - $abiertos ?></b><span>Resueltas / cerradas</span></div>
        </div>
        <div class="card">
            <h2>Historial de servicios</h2>
            <?php if (!$tickets): ?><p class="muted">Aún no tienes solicitudes. <a href="<?= url('solicitar.php') ?>">Crea la primera</a>.</p><?php else: ?>
            <div class="table-wrap"><table class="table">
                <thead><tr><th>Ticket</th><th>Asunto</th><th>Servicio</th><th>Urgencia</th><th>Estado</th><th>Fecha</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($tickets as $t): ?>
                    <tr>
                        <td><b><?= e($t['codigo']) ?></b></td><td><?= e($t['titulo']) ?></td><td><?= e($t['servicio']) ?></td>
                        <td><?= urgencia_badge($t['urgencia']) ?></td><td><?= estado_badge($t['estado']) ?></td><td><?= fecha($t['creado'], false) ?></td>
                        <td><a class="btn btn-sm btn-ghost" href="<?= url('consultar.php?codigo=' . urlencode($t['codigo']) . '&email=' . urlencode($cli['email'])) ?>">Ver</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table></div>
            <?php endif; ?>
        </div>
        <?php if ($cotizaciones): ?>
        <div class="card">
            <h2>Cotizaciones</h2>
            <div class="table-wrap"><table class="table">
                <thead><tr><th>Número</th><th>Fecha</th><th>Total</th><th>Estado</th><th></th></tr></thead>
                <tbody><?php foreach ($cotizaciones as $c): ?>
                    <tr><td><b><?= e($c['numero']) ?></b></td><td><?= fecha($c['creado'], false) ?></td><td><?= money($c['total']) ?></td><td><span class="badge badge-info"><?= e($c['estado']) ?></span></td>
                    <td><a class="btn btn-sm btn-ghost" href="<?= url('cliente/cotizacion.php?id=' . $c['id']) ?>" target="_blank">Ver / PDF</a></td></tr>
                <?php endforeach; ?></tbody>
            </table></div>
        </div>
        <?php endif; ?>
        <details class="card">
            <summary><b>Mis datos</b></summary>
            <form method="post" class="form">
                <?= csrf_field() ?><input type="hidden" name="accion" value="perfil">
                <div class="form-grid">
                    <label>Nombre<input name="nombre" value="<?= e($cli['nombre']) ?>" required></label>
                    <label>Correo<input value="<?= e($cli['email']) ?>" disabled></label>
                    <label>Teléfono<input name="telefono" value="<?= e($cli['telefono']) ?>"></label>
                    <label>Empresa<input name="empresa" value="<?= e($cli['empresa']) ?>"></label>
                    <label class="full">Dirección<input name="direccion" value="<?= e($cli['direccion']) ?>"></label>
                    <label class="full">Nueva contraseña (dejar en blanco para no cambiarla)<input type="password" name="password" minlength="8" autocomplete="new-password"></label>
                </div>
                <button class="btn btn-primary">Guardar</button>
            </form>
        </details>
    </div>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
