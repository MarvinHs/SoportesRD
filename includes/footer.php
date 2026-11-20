</main>
<footer class="site-footer">
    <div class="container footer-grid">
        <div>
            <a href="<?= url('index.php') ?>" class="brand-wordmark"><img src="<?= url('assets/img/logo-wordmark.svg') ?>" alt="SoportesRD" width="230" height="60"></a>
            <p class="muted-light"><?= e(setting('eslogan')) ?></p>
            <div class="socials">
                <?php foreach (['facebook', 'instagram', 'linkedin', 'tiktok'] as $s): if (setting($s)): ?>
                    <a href="<?= e(setting($s)) ?>" target="_blank" rel="noopener" aria-label="<?= ucfirst($s) ?>"><?= icon($s, 20) ?></a>
                <?php endif; endforeach; ?>
            </div>
        </div>
        <div>
            <h4>Servicios</h4>
            <ul>
                <?php foreach (q_all('SELECT id, nombre FROM servicios WHERE activo = 1 ORDER BY orden LIMIT 7') as $s): ?>
                    <li><a href="<?= url('solicitar.php?servicio=' . $s['id']) ?>"><?= e($s['nombre']) ?></a></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <div>
            <h4>Enlaces</h4>
            <ul>
                <li><a href="<?= url('nosotros.php') ?>">Sobre nosotros</a></li>
                <li><a href="<?= url('solicitar.php') ?>">Solicitar soporte</a></li>
                <li><a href="<?= url('consultar.php') ?>">Consultar ticket</a></li>
                <li><a href="<?= url('faq.php') ?>">Preguntas frecuentes</a></li>
                <li><a href="<?= url('cliente/login.php') ?>">Portal de clientes</a></li>
                <li><a href="<?= url('admin/login.php') ?>">Acceso del personal</a></li>
            </ul>
        </div>
        <div>
            <h4>Contacto</h4>
            <ul class="contact-list">
                <li><?= icon('whatsapp', 18) ?> <a href="<?= e(whatsapp_link()) ?>" target="_blank" rel="noopener"><?= e(setting('whatsapp_texto')) ?></a></li>
                <li><?= icon('phone', 18) ?> <?= e(setting('telefono')) ?></li>
                <li><?= icon('mail', 18) ?> <a href="mailto:<?= e(setting('email')) ?>"><?= e(setting('email')) ?></a></li>
                <li><?= icon('clock', 18) ?> <?= e(setting('horario')) ?></li>
            </ul>
        </div>
    </div>
    <div class="container footer-bottom">© <?= date('Y') ?> <?= e(setting('nombre_comercial', 'SoportesRD')) ?>. Todos los derechos reservados.</div>
</footer>

<a href="<?= e(whatsapp_link()) ?>" class="wa-float" target="_blank" rel="noopener" aria-label="Escríbenos por WhatsApp"><?= icon('whatsapp', 30) ?></a>
<script src="<?= url('assets/js/app.js') ?>"></script>
</body>
</html>
