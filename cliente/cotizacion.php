<?php
require __DIR__ . '/../includes/functions.php';
$cli = cliente();
$c = q_one('SELECT * FROM cotizaciones WHERE id = ?', [(int)get('id')]);
if (!$c || (!staff() && (!$cli || (int)$cli['id'] !== (int)$c['cliente_id'] || $c['estado'] === 'Borrador'))) { http_response_code(403); exit('No disponible.'); }
require __DIR__ . '/../includes/cotizacion_print.php';
