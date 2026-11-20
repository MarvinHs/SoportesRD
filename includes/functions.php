<?php
/**
 * SoportesRD - Funciones comunes
 */
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
    session_start();
}

function config(?string $key = null)
{
    static $cfg = null;
    if ($cfg === null) $cfg = require __DIR__ . '/../config/config.php';
    return $key === null ? $cfg : ($cfg[$key] ?? null);
}

date_default_timezone_set(config('timezone'));
require_once __DIR__ . '/db.php';

const ESTADOS   = ['Recibido', 'Pendiente', 'En proceso', 'En espera del cliente', 'Resuelto', 'Cerrado'];
const URGENCIAS = ['Baja', 'Media', 'Alta', 'Crítica'];

/* ---------- Utilidades ---------- */
function e($v): string { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }
function url(string $path = ''): string { return rtrim(config('base_url'), '/') . '/' . ltrim($path, '/'); }
function redirect(string $path): void { header('Location: ' . (str_starts_with($path, 'http') ? $path : url($path))); exit; }
function now(): string { return date('Y-m-d H:i:s'); }
function post(string $k, $def = '') { return isset($_POST[$k]) ? (is_string($_POST[$k]) ? trim($_POST[$k]) : $_POST[$k]) : $def; }
function get(string $k, $def = '') { return isset($_GET[$k]) && is_string($_GET[$k]) ? trim($_GET[$k]) : $def; }
function is_post(): bool { return ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST'; }
function money($n): string { return 'RD$ ' . number_format((float)$n, 2); }
function fecha($d, bool $hora = true): string { return $d ? date($hora ? 'd/m/Y h:i a' : 'd/m/Y', strtotime($d)) : '—'; }

function setting(string $k, string $def = ''): string
{
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        foreach (q_all('SELECT clave, valor FROM configuracion') as $r) $cache[$r['clave']] = $r['valor'];
    }
    return $cache[$k] ?? $def;
}
function setting_save(string $k, string $v): void
{
    if (q_val('SELECT COUNT(*) FROM configuracion WHERE clave = ?', [$k])) {
        q('UPDATE configuracion SET valor = ? WHERE clave = ?', [$v, $k]);
    } else {
        q('INSERT INTO configuracion (clave, valor) VALUES (?, ?)', [$k, $v]);
    }
}

function whatsapp_link(string $msg = 'Hola SoportesRD, necesito asistencia técnica.'): string
{
    return 'https://wa.me/' . preg_replace('/\D/', '', setting('whatsapp')) . '?text=' . rawurlencode($msg);
}

/* ---------- Seguridad ---------- */
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . csrf_token() . '">'; }
function csrf_check(): void
{
    if (!is_post() || !hash_equals($_SESSION['csrf'] ?? '', (string)($_POST['csrf'] ?? ''))) {
        http_response_code(419);
        exit('La sesión expiró. Vuelve atrás y recarga la página.');
    }
}

/* ---------- Mensajes flash ---------- */
function flash(string $type, string $msg): void { $_SESSION['flash'][] = [$type, $msg]; }
function flashes(): string
{
    $html = '';
    foreach ($_SESSION['flash'] ?? [] as [$t, $m]) $html .= '<div class="alert alert-' . e($t) . '">' . e($m) . '</div>';
    unset($_SESSION['flash']);
    return $html;
}

/* ---------- Autenticación de personal (admin / técnico) ---------- */
function staff(): ?array
{
    if (empty($_SESSION['staff_id'])) return null;
    static $u = false;
    if ($u === false) $u = q_one('SELECT * FROM usuarios WHERE id = ? AND activo = 1', [$_SESSION['staff_id']]);
    return $u;
}
function require_staff(): array
{
    $u = staff();
    if (!$u) redirect('admin/login.php');
    return $u;
}
function require_admin(): array
{
    $u = require_staff();
    if ($u['rol'] !== 'admin') {
        flash('error', 'No tienes permiso para acceder a esa sección.');
        redirect('admin/index.php');
    }
    return $u;
}
function is_admin(): bool { return (staff()['rol'] ?? '') === 'admin'; }

/* ---------- Autenticación de clientes ---------- */
function cliente(): ?array
{
    if (empty($_SESSION['cliente_id'])) return null;
    return q_one('SELECT * FROM clientes WHERE id = ?', [$_SESSION['cliente_id']]);
}

/* ---------- Bitácora ---------- */
function log_activity(string $accion, ?string $usuario = null): void
{
    q('INSERT INTO actividad (usuario, accion, creado) VALUES (?,?,?)', [$usuario ?? (staff()['nombre'] ?? 'Sistema'), $accion, now()]);
}

/* ---------- Correo ---------- */
function send_mail(string $to, string $subject, string $body): bool
{
    $from = config('mail')['from'];
    $html = '<div style="font-family:Arial,sans-serif;max-width:600px;margin:auto;border:1px solid #e5e7eb;border-radius:8px;overflow:hidden">'
        . '<div style="background:#0B1F3A;color:#fff;padding:16px 20px;font-size:20px;font-weight:bold">Soportes<span style="color:#1677FF">RD</span></div>'
        . '<div style="padding:20px;color:#1f2937;line-height:1.6">' . $body . '</div>'
        . '<div style="background:#F4F7FB;padding:12px 20px;font-size:12px;color:#6b7280">' . e(setting('telefono')) . ' · ' . e(setting('email')) . '</div></div>';

    if (config('mail')['enabled']) {
        $headers = "MIME-Version: 1.0\r\nContent-type: text/html; charset=UTF-8\r\nFrom: SoportesRD <$from>\r\n";
        return @mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $html, $headers);
    }
    @file_put_contents(__DIR__ . '/../storage/mail.log', '[' . now() . "] Para: $to | Asunto: $subject\n" . strip_tags(str_replace(['<br>', '</p>'], "\n", $body)) . "\n-----\n", FILE_APPEND);
    return true;
}

function notify_ticket_status(array $ticket, string $estado, string $nota = ''): void
{
    if (setting('notificar_email', '1') !== '1') return;
    $cli = q_one('SELECT * FROM clientes WHERE id = ?', [$ticket['cliente_id']]);
    if (!$cli) return;
    $link = full_url('consultar.php?codigo=' . urlencode($ticket['codigo']) . '&email=' . urlencode($cli['email']));
    $body = '<p>Hola ' . e($cli['nombre']) . ',</p><p>Tu solicitud <b>' . e($ticket['codigo']) . '</b> cambió de estado a: <b>' . e($estado) . '</b>.</p>'
        . ($nota ? '<p><i>' . nl2br(e($nota)) . '</i></p>' : '')
        . '<p><a href="' . e($link) . '">Ver el estado de tu solicitud</a></p>';
    send_mail($cli['email'], "Ticket {$ticket['codigo']}: $estado", $body);
}

function full_url(string $path): string
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https') ? 'https' : 'http';
    return $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . url($path);
}

/* ---------- Tickets ---------- */
function new_ticket_code(): string
{
    $year = date('Y');
    $next = (int)q_val('SELECT COUNT(*) FROM tickets') + 1;
    do {
        $code = 'SRD-' . $year . '-' . str_pad((string)$next, 4, '0', STR_PAD_LEFT);
        $next++;
    } while (q_val('SELECT COUNT(*) FROM tickets WHERE codigo = ?', [$code]));
    return $code;
}

function add_history(int $ticketId, string $tipo, ?string $estado, string $mensaje, bool $publico = true, ?array $user = null, ?string $autor = null): void
{
    q('INSERT INTO ticket_historial (ticket_id, usuario_id, autor, tipo, estado, mensaje, publico, creado) VALUES (?,?,?,?,?,?,?,?)',
        [$ticketId, $user['id'] ?? null, $autor ?? ($user['nombre'] ?? 'Sistema'), $tipo, $estado, $mensaje, $publico ? 1 : 0, now()]);
}

/** Procesa los archivos de $_FILES[$field] y los asocia al ticket. Devuelve lista de errores. */
function save_attachments(int $ticketId, string $field = 'adjuntos'): array
{
    $errors = [];
    if (empty($_FILES[$field]['name'][0])) return $errors;
    $dir = config('upload_dir') . '/' . date('Y/m');
    if (!is_dir($dir)) mkdir($dir, 0775, true);
    $max = config('upload_max_mb') * 1024 * 1024;
    $allowed = config('upload_ext');
    $files = $_FILES[$field];
    $count = min(count($files['name']), config('upload_max_files'));

    for ($i = 0; $i < $count; $i++) {
        if ($files['error'][$i] === UPLOAD_ERR_NO_FILE) continue;
        $orig = basename($files['name'][$i]);
        if ($files['error'][$i] !== UPLOAD_ERR_OK) { $errors[] = "No se pudo subir \"$orig\"."; continue; }
        $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed, true)) { $errors[] = "\"$orig\": tipo de archivo no permitido."; continue; }
        if ($files['size'][$i] > $max) { $errors[] = "\"$orig\" supera " . config('upload_max_mb') . ' MB.'; continue; }
        $name = bin2hex(random_bytes(12)) . '.' . $ext;
        if (move_uploaded_file($files['tmp_name'][$i], "$dir/$name")) {
            q('INSERT INTO adjuntos (ticket_id, nombre_original, archivo, tamano, creado) VALUES (?,?,?,?,?)',
                [$ticketId, $orig, date('Y/m') . "/$name", (int)$files['size'][$i], now()]);
        } else {
            $errors[] = "No se pudo guardar \"$orig\".";
        }
    }
    return $errors;
}

/* ---------- Presentación ---------- */
function estado_badge(string $estado): string
{
    $map = [
        'Recibido' => 'info', 'Pendiente' => 'warning', 'En proceso' => 'primary',
        'En espera del cliente' => 'purple', 'Resuelto' => 'success', 'Cerrado' => 'muted',
    ];
    return '<span class="badge badge-' . ($map[$estado] ?? 'muted') . '">' . e($estado) . '</span>';
}
function urgencia_badge(string $u): string
{
    $map = ['Baja' => 'muted', 'Media' => 'info', 'Alta' => 'warning', 'Crítica' => 'danger'];
    return '<span class="badge badge-' . ($map[$u] ?? 'muted') . '">' . e($u) . '</span>';
}

/** Íconos SVG en línea (trazos) */
function icon(string $name, int $size = 24): string
{
    $p = [
        'tool'     => '<path d="M14.7 6.3a4 4 0 0 0-5.4 5.4L3 18l3 3 6.3-6.3a4 4 0 0 0 5.4-5.4l-2.5 2.5-2.4-.6-.6-2.4z"/>',
        'headset'  => '<path d="M3 14v-2a9 9 0 0 1 18 0v2"/><path d="M21 15a2 2 0 0 1-2 2h-1v-6h1a2 2 0 0 1 2 2zM3 15a2 2 0 0 0 2 2h1v-6H5a2 2 0 0 0-2 2z"/><path d="M18 17v1a3 3 0 0 1-3 3h-3"/>',
        'wifi'     => '<path d="M5 12.5a10 10 0 0 1 14 0M8.5 16a5 5 0 0 1 7 0M2 9a15 15 0 0 1 20 0"/><circle cx="12" cy="19.5" r="1"/>',
        'download' => '<path d="M12 3v12m0 0-4-4m4 4 4-4"/><path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"/>',
        'sparkle'  => '<path d="M12 3l1.8 5.2L19 10l-5.2 1.8L12 17l-1.8-5.2L5 10l5.2-1.8z"/><path d="M19 17l.7 1.8L21.5 19.5l-1.8.7L19 22l-.7-1.8-1.8-.7 1.8-.7z"/>',
        'shield'   => '<path d="M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6z"/><path d="m9 12 2 2 4-4"/>',
        'monitor'  => '<rect x="3" y="4" width="18" height="12" rx="2"/><path d="M8 20h8M12 16v4"/>',
        'whatsapp' => '<path d="M3 21l1.7-5A8.5 8.5 0 1 1 8 19.4z"/><path d="M9 9.5c0 3 2.5 5.5 5.5 5.5l1-1.5-2-1-1 .8a4 4 0 0 1-2-2l.8-1-1-2z"/>',
        'phone'    => '<path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2"/>',
        'mail'     => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
        'clock'    => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'pin'      => '<path d="M12 21s-7-6.2-7-12a7 7 0 0 1 14 0c0 5.8-7 12-7 12z"/><circle cx="12" cy="9" r="2.5"/>',
        'search'   => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
        'ticket'   => '<path d="M3 8a2 2 0 0 0 2-2h14a2 2 0 0 0 2 2v2a2 2 0 0 0 0 4v2a2 2 0 0 0-2 2H5a2 2 0 0 0-2-2v-2a2 2 0 0 0 0-4z"/><path d="M13 6v12" stroke-dasharray="2 2"/>',
        'users'    => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20a6.5 6.5 0 0 1 13 0"/><path d="M16 4.5a3.5 3.5 0 0 1 0 7M18 14a6.5 6.5 0 0 1 3.5 6"/>',
        'user'     => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
        'grid'     => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>',
        'chart'    => '<path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/>',
        'settings' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1.1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.5-1.1 1.7 1.7 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.8.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.8V9a1.7 1.7 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1z"/>',
        'link'     => '<path d="M10 14a4 4 0 0 0 5.7 0l3-3a4 4 0 0 0-5.7-5.7l-1 1"/><path d="M14 10a4 4 0 0 0-5.7 0l-3 3a4 4 0 0 0 5.7 5.7l1-1"/>',
        'file'     => '<path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><path d="M14 3v6h6M8 13h8M8 17h5"/>',
        'help'     => '<circle cx="12" cy="12" r="9"/><path d="M9.5 9a2.5 2.5 0 0 1 4.9.8c0 1.7-2.4 2.2-2.4 3.7M12 17h.01"/>',
        'logout'   => '<path d="M15 4h3a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-3M10 17l5-5-5-5M15 12H3"/>',
        'inbox'    => '<path d="M3 13h5l1.5 3h5L16 13h5"/><path d="M5.5 5h13L21 13v5a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-5z"/>',
        'link2'    => '<path d="M7 17 17 7M8 7h9v9"/>',
        'check'    => '<path d="m5 12 5 5L20 7"/>',
        'menu'     => '<path d="M4 6h16M4 12h16M4 18h16"/>',
        'quote'    => '<path d="M4 4h16v12H5.2L4 17.2z"/><path d="M8 9h8M8 12h5"/>',
        'activity' => '<path d="M3 12h4l3-8 4 16 3-8h4"/>',
        'facebook' => '<path d="M15 3h-2.5A3.5 3.5 0 0 0 9 6.5V9H6.5v3.5H9V21h3.5v-8.5H15l.5-3.5h-3V7a1 1 0 0 1 1-1H15z"/>',
        'instagram'=> '<rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r=".6"/>',
        'linkedin' => '<rect x="3" y="3" width="18" height="18" rx="3"/><path d="M8 10v7M8 7v.01M12 17v-4a2 2 0 0 1 4 0v4M12 10v7"/>',
        'tiktok'   => '<path d="M14 3v11.5a3.5 3.5 0 1 1-3.5-3.5"/><path d="M14 3a5 5 0 0 0 5 5"/>',
    ];
    return '<svg class="icon" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ($p[$name] ?? $p['tool']) . '</svg>';
}
