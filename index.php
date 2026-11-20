<?php
require __DIR__ . '/includes/functions.php';
$pageTitle = 'Soporte técnico e informático';
$active = 'inicio';
$servicios = q_all('SELECT * FROM servicios WHERE activo = 1 ORDER BY orden');
$resueltos = (int)q_val("SELECT COUNT(*) FROM tickets WHERE estado IN ('Resuelto','Cerrado')");
require __DIR__ . '/includes/header.php';
?>
<section class="hero">
    <div class="container hero-grid">
        <div class="hero-text">
            <span class="eyebrow"><?= icon('check', 16) ?> Soporte técnico profesional en RD</span>
            <h1><?= e(setting('eslogan')) ?></h1>
            <p class="lead">En <b>SoportesRD</b> diagnosticamos, reparamos y protegemos tus equipos y redes. Atención personalizada en sitio o de forma remota, con seguimiento de cada solicitud.</p>
            <div class="hero-actions">
                <a href="<?= url('solicitar.php') ?>" class="btn btn-primary btn-lg"><?= icon('ticket', 20) ?> Solicitar un servicio</a>
                <a href="<?= e(whatsapp_link()) ?>" target="_blank" rel="noopener" class="btn btn-whatsapp btn-lg"><?= icon('whatsapp', 20) ?> WhatsApp</a>
            </div>
            <ul class="hero-points">
                <li><?= icon('check', 18) ?> Respuesta rápida</li>
                <li><?= icon('check', 18) ?> Técnicos certificados</li>
                <li><?= icon('check', 18) ?> Seguimiento en línea</li>
            </ul>
        </div>
        <div class="hero-art">
            <img src="<?= url('assets/img/hero.svg') ?>" alt="Ilustración de soporte técnico: computadora, red Wi-Fi y seguridad" width="560" height="440">
        </div>
    </div>
</section>

<section class="stats-band">
    <div class="container stats-grid">
        <div><b><?= max($resueltos, 1) ?>+</b><span>Casos resueltos</span></div>
        <div><b>24 h</b><span>Tiempo promedio de respuesta</span></div>
        <div><b><?= count($servicios) ?></b><span>Áreas de servicio</span></div>
        <div><b>100%</b><span>Compromiso con el cliente</span></div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-head">
            <span class="eyebrow">Lo que hacemos</span>
            <h2>Nuestros servicios</h2>
            <p>Soluciones tecnológicas completas para hogares, profesionales y empresas.</p>
        </div>
        <div class="cards-grid">
            <?php foreach ($servicios as $s): ?>
                <article class="service-card">
                    <div class="service-icon"><?= icon($s['icono'] ?: 'tool', 28) ?></div>
                    <h3><?= e($s['nombre']) ?></h3>
                    <p><?= e($s['descripcion']) ?></p>
                    <a href="<?= url('solicitar.php?servicio=' . $s['id']) ?>" class="link-arrow">Solicitar <?= icon('link2', 16) ?></a>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section section-alt">
    <div class="container">
        <div class="section-head">
            <span class="eyebrow">Así trabajamos</span>
            <h2>¿Cómo funciona?</h2>
        </div>
        <div class="steps">
            <div class="step"><span>1</span><h3>Solicita</h3><p>Completa el formulario o escríbenos por WhatsApp y describe tu problema.</p></div>
            <div class="step"><span>2</span><h3>Recibe tu ticket</h3><p>Te asignamos un número de ticket y un técnico especializado.</p></div>
            <div class="step"><span>3</span><h3>Da seguimiento</h3><p>Consulta en línea el estado de tu solicitud en todo momento.</p></div>
            <div class="step"><span>4</span><h3>Problema resuelto</h3><p>Te notificamos cuando tu caso esté resuelto.</p></div>
        </div>
    </div>
</section>

<section class="section">
    <div class="container cta-box">
        <div>
            <h2>¿Tienes un problema con tu equipo o tu red?</h2>
            <p>Cuéntanos qué pasa y te ayudamos hoy mismo.</p>
        </div>
        <div class="cta-actions">
            <a href="<?= url('solicitar.php') ?>" class="btn btn-light btn-lg">Solicitar soporte</a>
            <a href="<?= url('consultar.php') ?>" class="btn btn-outline-light btn-lg">Consultar mi ticket</a>
        </div>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
