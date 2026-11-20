<?php
require __DIR__ . '/includes/functions.php';
$pageTitle = 'Sobre nosotros';
$active = 'nosotros';
require __DIR__ . '/includes/header.php';
?>
<section class="page-hero">
    <div class="container">
        <h1>Sobre nosotros</h1>
        <p>Conoce quiénes somos y por qué nuestros clientes confían en nosotros.</p>
    </div>
</section>

<section class="section">
    <div class="container about-grid">
        <div>
            <span class="eyebrow">Quiénes somos</span>
            <h2>Tecnología que funciona, personas que responden</h2>
            <p class="lead">En SoportesRD nos dedicamos a ofrecer soluciones tecnológicas y servicios de soporte informático, comprometidos con brindar asistencia eficiente, atención personalizada y soluciones confiables para nuestros clientes.</p>
            <p>Atendemos hogares, profesionales independientes, pequeñas y medianas empresas, instituciones educativas y comercios. Cada solicitud se registra con un número de ticket, se asigna a un técnico especializado y se le da seguimiento hasta su solución.</p>
        </div>
        <div class="about-art"><img src="<?= url('assets/img/hero.svg') ?>" alt="Ilustración de equipos tecnológicos" loading="lazy"></div>
    </div>
</section>

<section class="section section-alt">
    <div class="container mvv">
        <div class="mvv-card">
            <div class="service-icon"><?= icon('activity', 28) ?></div>
            <h3>Misión</h3>
            <p>Brindar soluciones tecnológicas y de soporte informático eficientes y confiables, con atención personalizada, para que nuestros clientes trabajen sin interrupciones.</p>
        </div>
        <div class="mvv-card">
            <div class="service-icon"><?= icon('search', 28) ?></div>
            <h3>Visión</h3>
            <p>Ser la empresa de soporte técnico de referencia en República Dominicana, reconocida por su rapidez, su honestidad y la calidad de su servicio.</p>
        </div>
        <div class="mvv-card">
            <div class="service-icon"><?= icon('shield', 28) ?></div>
            <h3>Valores</h3>
            <ul class="check-list">
                <li><?= icon('check', 16) ?> Responsabilidad</li>
                <li><?= icon('check', 16) ?> Honestidad y transparencia</li>
                <li><?= icon('check', 16) ?> Confidencialidad</li>
                <li><?= icon('check', 16) ?> Mejora continua</li>
                <li><?= icon('check', 16) ?> Trato humano y cercano</li>
            </ul>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-head">
            <span class="eyebrow">Compromiso</span>
            <h2>Nuestro compromiso con los clientes</h2>
        </div>
        <div class="cards-grid cards-4">
            <div class="feature"><?= icon('clock', 26) ?><h3>Respuesta rápida</h3><p>Atendemos tu solicitud en el menor tiempo posible según su urgencia.</p></div>
            <div class="feature"><?= icon('ticket', 26) ?><h3>Seguimiento claro</h3><p>Consulta en línea el estado de tu ticket en cada etapa.</p></div>
            <div class="feature"><?= icon('shield', 26) ?><h3>Tu información, segura</h3><p>Tratamos tus datos y equipos con total confidencialidad.</p></div>
            <div class="feature"><?= icon('quote', 26) ?><h3>Precios justos</h3><p>Cotizaciones claras antes de cada trabajo, sin sorpresas.</p></div>
        </div>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
