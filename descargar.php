<?php
/** Descarga segura de adjuntos: solo personal, el cliente dueño o quien consultó el ticket. */
require __DIR__ . '/includes/functions.php';
$a = q_one('SELECT a.*, t.cliente_id FROM adjuntos a JOIN tickets t ON t.id = a.ticket_id WHERE a.id = ?', [(int)get('id')]);
if (!$a) { http_response_code(404); exit('Archivo no encontrado.'); }

$cli = cliente();
$ok = staff() || ($cli && (int)$cli['id'] === (int)$a['cliente_id']) || !empty($_SESSION['tickets_ok'][$a['ticket_id']]);
if (!$ok) { http_response_code(403); exit('No tienes permiso para ver este archivo.'); }

$path = realpath(config('upload_dir') . '/' . $a['archivo']);
$base = realpath(config('upload_dir'));
if (!$path || !str_starts_with($path, $base) || !is_file($path)) { http_response_code(404); exit('Archivo no disponible.'); }

$mime = function_exists('mime_content_type') ? (mime_content_type($path) ?: 'application/octet-stream') : 'application/octet-stream';
$inline = preg_match('#^(image/(png|jpeg|gif|webp)|application/pdf)$#', $mime);
header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($path));
header('X-Content-Type-Options: nosniff');
header('Content-Disposition: ' . ($inline ? 'inline' : 'attachment') . '; filename="' . str_replace('"', '', $a['nombre_original']) . '"');
readfile($path);
