<?php
/** @var array $me  @var string $pageTitle  @var string $section */
$me = $me ?? require_staff();
$menu = [
    ['index.php', 'dashboard', 'Dashboard', 'grid', false],
    ['tickets.php', 'tickets', 'Tickets', 'ticket', false],
    ['clientes.php', 'clientes', 'Clientes', 'users', false],
    ['servicios.php', 'servicios', 'Servicios', 'tool', true],
    ['tecnicos.php', 'tecnicos', 'Técnicos', 'headset', true],
    ['asignaciones.php', 'asignaciones', 'Asignaciones', 'link', true],
    ['cotizaciones.php', 'cotizaciones', 'Cotizaciones', 'quote', true],
    ['reportes.php', 'reportes', 'Reportes', 'chart', true],
    ['faq.php', 'faq', 'Base de conocimientos', 'help', true],
    ['mensajes.php', 'mensajes', 'Mensajes', 'inbox', true],
    ['configuracion.php', 'configuracion', 'Configuración', 'settings', true],
];
$unread = (int)q_val('SELECT COUNT(*) FROM mensajes WHERE leido = 0');
$nuevos = (int)q_val("SELECT COUNT(*) FROM tickets WHERE estado = 'Recibido'");
?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle ?? 'Panel') ?> | Panel SoportesRD</title>
    <link rel="icon" href="<?= url('assets/img/favicon.svg') ?>" type="image/svg+xml">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
    <link rel="stylesheet" href="<?= url('assets/css/admin.css') ?>">
</head>
<body class="admin">
<aside class="sidebar">
    <a href="<?= url('admin/index.php') ?>" class="brand brand-light"><img src="<?= url('assets/img/logo.svg') ?>" alt="" width="36" height="36"><span>Soportes<b>RD</b></span></a>
    <nav class="side-nav">
        <?php foreach ($menu as [$href, $key, $label, $ic, $adminOnly]): if ($adminOnly && $me['rol'] !== 'admin') continue; ?>
            <a href="<?= url('admin/' . $href) ?>" class="<?= ($section ?? '') === $key ? 'active' : '' ?>">
                <?= icon($ic, 20) ?> <span><?= e($label) ?></span>
                <?php if ($key === 'tickets' && $nuevos): ?><em class="pill"><?= $nuevos ?></em><?php endif; ?>
                <?php if ($key === 'mensajes' && $unread): ?><em class="pill"><?= $unread ?></em><?php endif; ?>
            </a>
        <?php endforeach; ?>
    </nav>
    <div class="side-foot">
        <a href="<?= url('index.php') ?>" target="_blank"><?= icon('link2', 18) ?> Ver sitio web</a>
    </div>
</aside>
<div class="admin-main">
    <header class="topbar">
        <button class="nav-toggle side-toggle" data-side-toggle aria-label="Menú"><?= icon('menu') ?></button>
        <h1><?= e($pageTitle ?? '') ?></h1>
        <div class="topbar-user">
            <a href="<?= url('admin/perfil.php') ?>" class="avatar" title="Mi perfil"><?= e(mb_strtoupper(mb_substr($me['nombre'], 0, 1))) ?></a>
            <div class="tu-name"><b><?= e($me['nombre']) ?></b><small><?= $me['rol'] === 'admin' ? 'Administrador' : 'Técnico' ?></small></div>
            <a href="<?= url('admin/logout.php') ?>" class="btn btn-sm btn-ghost" title="Cerrar sesión"><?= icon('logout', 16) ?></a>
        </div>
    </header>
    <div class="admin-content">
        <?= flashes() ?>
