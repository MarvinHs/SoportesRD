<?php
require __DIR__ . '/includes/functions.php';
$pageTitle = 'Preguntas frecuentes';
$active = 'faq';
$buscar = get('q');
$faq = $buscar !== ''
    ? q_all('SELECT * FROM faq WHERE activo = 1 AND (pregunta LIKE ? OR respuesta LIKE ?) ORDER BY orden', ["%$buscar%", "%$buscar%"])
    : q_all('SELECT * FROM faq WHERE activo = 1 ORDER BY orden');
$grupos = [];
foreach ($faq as $f) $grupos[$f['categoria'] ?: 'General'][] = $f;
require __DIR__ . '/includes/header.php';
?>
<section class="page-hero">
    <div class="container">
        <h1>Base de conocimientos</h1>
        <p>Respuestas rápidas a las preguntas más comunes.</p>
        <form class="faq-search" method="get"><?= icon('search', 20) ?><input type="search" name="q" value="<?= e($buscar) ?>" placeholder="Buscar una pregunta…"></form>
    </div>
</section>
<section class="section">
    <div class="container narrow">
        <?php if (!$faq): ?><p class="center muted">No encontramos resultados. <a href="<?= url('contacto.php') ?>">Escríbenos</a> y te ayudamos.</p><?php endif; ?>
        <?php foreach ($grupos as $cat => $items): ?>
            <h2 class="faq-cat"><?= e($cat) ?></h2>
            <?php foreach ($items as $f): ?>
                <details class="faq-item">
                    <summary><?= e($f['pregunta']) ?></summary>
                    <div><?= nl2br(e($f['respuesta'])) ?></div>
                </details>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
