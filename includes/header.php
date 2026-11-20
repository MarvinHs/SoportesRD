<?php
/** @var string $pageTitle  @var string $active */
$pageTitle = $pageTitle ?? 'Inicio';
$active    = $active ?? '';
$cli       = cliente();
$nav = [
    'inicio'    => ['index.php', 'Inicio'],
    'servicios' => ['servicios.php', 'Servicios'],
    'nosotros'  => ['nosotros.php', 'Sobre nosotros'],
    'solicitar' => ['solicitar.php', 'Solicitar soporte'],
    'consultar' => ['consultar.php', 'Consultar ticket'],
    'faq'       => ['faq.php', 'Preguntas frecuentes'],
    'contacto'  => ['contacto.php', 'Contacto'],
];
?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> | <?= e(setting('nombre_comercial', 'SoportesRD')) ?></title>
    <meta name="description" content="<?= e(setting('eslogan')) ?> Soporte técnico, redes, seguridad informática y asistencia remota en República Dominicana.">
    <meta name="theme-color" content="#0B1F3A">
    <link rel="icon" href="<?= url('assets/img/favicon.svg') ?>" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
</head>
<body>
<header class="site-header">
    <div class="container nav-wrap">
        <a href="<?= url('index.php') ?>" class="brand" aria-label="SoportesRD inicio">
            <img src="<?= url('assets/img/logo.svg') ?>" alt="Logo de SoportesRD" width="42" height="42">
            <span>Soportes<b>RD</b></span>
        </a>
        <button class="nav-toggle" aria-label="Abrir menú" aria-expanded="false" data-nav-toggle><?= icon('menu') ?></button>
        <nav class="main-nav" data-nav>
            <?php foreach ($nav as $k => [$href, $label]): ?>
                <a href="<?= url($href) ?>" class="<?= $active === $k ? 'active' : '' ?>"><?= e($label) ?></a>
            <?php endforeach; ?>
            <?php if ($cli): ?>
                <a href="<?= url('cliente/panel.php') ?>" class="nav-account <?= $active === 'cuenta' ? 'active' : '' ?>"><?= icon('user', 18) ?> Mi cuenta</a>
            <?php else: ?>
                <a href="<?= url('cliente/login.php') ?>" class="nav-account <?= $active === 'cuenta' ? 'active' : '' ?>"><?= icon('user', 18) ?> Acceder</a>
            <?php endif; ?>
        </nav>
    </div>
</header>
<main>
