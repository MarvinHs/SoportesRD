<?php
require __DIR__ . '/includes/functions.php';
$pageTitle = 'Servicios';
$active = 'servicios';
$servicios = q_all('SELECT * FROM servicios WHERE activo = 1 ORDER BY orden');
require __DIR__ . '/includes/header.php';
?>
<section class="page-hero">
    <div class="container">
        <h1>Servicios</h1>
        <p>Todo lo que necesitas para que tu tecnología funcione sin interrupciones.</p>
    </div>
</section>
<section class="section">
    <div class="container service-list">
        <?php foreach ($servicios as $s): ?>
            <article class="service-row">
                <div class="service-icon lg"><?= icon($s['icono'] ?: 'tool', 34) ?></div>
                <div class="service-row-body">
                    <h2><?= e($s['nombre']) ?></h2>
                    <p><?= e($s['descripcion']) ?></p>
                    <?php if ((float)$s['precio_desde'] > 0): ?>
                        <p class="price">Desde <b><?= money($s['precio_desde']) ?></b></p>
                    <?php endif; ?>
                </div>
                <div class="service-row-actions">
                    <a href="<?= url('solicitar.php?servicio=' . $s['id']) ?>" class="btn btn-primary">Solicitar</a>
                    <a href="<?= e(whatsapp_link('Hola SoportesRD, quiero información sobre: ' . $s['nombre'])) ?>" class="btn btn-ghost" target="_blank" rel="noopener"><?= icon('whatsapp', 18) ?> Consultar</a>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
