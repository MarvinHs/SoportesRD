<?php
/**
 * Conexión PDO e instalación automática del esquema (SQLite o MySQL).
 */
function db(): PDO
{
    static $pdo = null;
    if ($pdo) return $pdo;

    $c = config('db');
    if ($c['driver'] === 'mysql') {
        $dsn = "mysql:host={$c['host']};port={$c['port']};dbname={$c['name']};charset=utf8mb4";
        $pdo = new PDO($dsn, $c['user'], $c['pass']);
    } else {
        $dir = dirname($c['sqlite']);
        if (!is_dir($dir)) mkdir($dir, 0775, true);
        $pdo = new PDO('sqlite:' . $c['sqlite']);
        $pdo->exec('PRAGMA foreign_keys = ON');
    }
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    db_install($pdo, $c['driver']);
    return $pdo;
}

function db_is_mysql(): bool
{
    return config('db')['driver'] === 'mysql';
}

/** Ejecuta una consulta preparada y devuelve el statement */
function q(string $sql, array $params = []): PDOStatement
{
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st;
}
function q_all(string $sql, array $p = []): array { return q($sql, $p)->fetchAll(); }
function q_one(string $sql, array $p = []): ?array { $r = q($sql, $p)->fetch(); return $r ?: null; }
function q_val(string $sql, array $p = []) { $r = q($sql, $p)->fetchColumn(); return $r === false ? null : $r; }

function db_install(PDO $pdo, string $driver): void
{
    $mysql = $driver === 'mysql';
    try {
        $pdo->query('SELECT 1 FROM configuracion LIMIT 1');
        return; // ya instalado
    } catch (PDOException $e) {
        // continuar con la instalación
    }

    $pk   = $mysql ? 'INT AUTO_INCREMENT PRIMARY KEY' : 'INTEGER PRIMARY KEY AUTOINCREMENT';
    $txt  = $mysql ? 'TEXT' : 'TEXT';
    $vc   = fn($n) => $mysql ? "VARCHAR($n)" : 'TEXT';
    $dt   = $mysql ? 'DATETIME' : 'TEXT';
    $int  = 'INTEGER';
    $dec  = $mysql ? 'DECIMAL(12,2)' : 'REAL';
    $eng  = $mysql ? ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4' : '';

    $sql = [
        "CREATE TABLE configuracion (clave {$vc(60)} PRIMARY KEY, valor $txt)$eng",

        "CREATE TABLE usuarios (
            id $pk, nombre {$vc(120)} NOT NULL, email {$vc(150)} NOT NULL UNIQUE,
            telefono {$vc(40)}, password {$vc(255)} NOT NULL,
            rol {$vc(20)} NOT NULL DEFAULT 'tecnico', especialidad {$vc(120)},
            activo $int NOT NULL DEFAULT 1, creado $dt
        )$eng",

        "CREATE TABLE clientes (
            id $pk, nombre {$vc(120)} NOT NULL, email {$vc(150)} NOT NULL UNIQUE,
            telefono {$vc(40)}, empresa {$vc(120)}, direccion {$vc(255)},
            password {$vc(255)}, notas $txt, creado $dt
        )$eng",

        "CREATE TABLE servicios (
            id $pk, nombre {$vc(120)} NOT NULL, descripcion $txt, icono {$vc(40)},
            precio_desde $dec DEFAULT 0, activo $int NOT NULL DEFAULT 1, orden $int DEFAULT 0
        )$eng",

        "CREATE TABLE tickets (
            id $pk, codigo {$vc(30)} NOT NULL UNIQUE, cliente_id $int NOT NULL,
            servicio_id $int, tecnico_id $int, titulo {$vc(200)},
            descripcion $txt NOT NULL, urgencia {$vc(20)} NOT NULL DEFAULT 'Media',
            estado {$vc(40)} NOT NULL DEFAULT 'Recibido',
            creado $dt, actualizado $dt, resuelto $dt
        )$eng",

        "CREATE TABLE ticket_historial (
            id $pk, ticket_id $int NOT NULL, usuario_id $int, autor {$vc(120)},
            tipo {$vc(20)} NOT NULL DEFAULT 'comentario', estado {$vc(40)},
            mensaje $txt, publico $int NOT NULL DEFAULT 1, creado $dt
        )$eng",

        "CREATE TABLE adjuntos (
            id $pk, ticket_id $int NOT NULL, nombre_original {$vc(255)},
            archivo {$vc(255)} NOT NULL, tamano $int, creado $dt
        )$eng",

        "CREATE TABLE cotizaciones (
            id $pk, numero {$vc(30)} NOT NULL, cliente_id $int NOT NULL, ticket_id $int,
            detalle $txt, items $txt, subtotal $dec DEFAULT 0, itbis $dec DEFAULT 0,
            total $dec DEFAULT 0, estado {$vc(20)} NOT NULL DEFAULT 'Borrador',
            validez_dias $int DEFAULT 15, creado $dt
        )$eng",

        "CREATE TABLE faq (
            id $pk, pregunta {$vc(255)} NOT NULL, respuesta $txt NOT NULL,
            categoria {$vc(80)}, activo $int NOT NULL DEFAULT 1, orden $int DEFAULT 0
        )$eng",

        "CREATE TABLE mensajes (
            id $pk, nombre {$vc(120)}, email {$vc(150)}, telefono {$vc(40)},
            asunto {$vc(200)}, mensaje $txt, leido $int NOT NULL DEFAULT 0, creado $dt
        )$eng",

        "CREATE TABLE actividad (
            id $pk, usuario {$vc(120)}, accion $txt, creado $dt
        )$eng",
    ];
    foreach ($sql as $s) $pdo->exec($s);

    seed_base($pdo);
    if (config('demo_data')) seed_demo($pdo);
}

function seed_base(PDO $pdo): void
{
    $now = date('Y-m-d H:i:s');
    $cfg = [
        'nombre_comercial' => 'SoportesRD',
        'eslogan'          => 'Soluciones tecnológicas confiables, cuando más las necesitas.',
        'whatsapp'         => '18095551234',
        'whatsapp_texto'   => '+1 (809) 555-1234',
        'telefono'         => '+1 (809) 555-1234',
        'email'            => 'info@soportesrd.com',
        'horario'          => 'Lunes a viernes: 8:00 a.m. – 6:00 p.m. · Sábados: 9:00 a.m. – 1:00 p.m.',
        'zona'             => 'Santo Domingo y Santiago. Asistencia remota en todo el país.',
        'direccion'        => 'Santo Domingo, República Dominicana',
        'facebook'         => 'https://facebook.com/soportesrd',
        'instagram'        => 'https://instagram.com/soportesrd',
        'linkedin'         => 'https://linkedin.com/company/soportesrd',
        'tiktok'           => '',
        'itbis'            => '18',
        'notificar_email'  => '1',
    ];
    $st = $pdo->prepare('INSERT INTO configuracion (clave, valor) VALUES (?, ?)');
    foreach ($cfg as $k => $v) $st->execute([$k, $v]);

    $pdo->prepare('INSERT INTO usuarios (nombre, email, telefono, password, rol, especialidad, activo, creado) VALUES (?,?,?,?,?,?,1,?)')
        ->execute(['Administrador', 'admin@soportesrd.com', '', password_hash('Admin123!', PASSWORD_DEFAULT), 'admin', 'Administración', $now]);

    $servicios = [
        ['Soporte técnico', 'Diagnóstico, mantenimiento y reparación de computadoras, laptops, impresoras y periféricos.', 'tool', 1500],
        ['Soporte informático', 'Asistencia a usuarios y resolución de problemas de software, correo y sistemas.', 'headset', 1000],
        ['Redes y conectividad', 'Configuración de redes, routers, Wi-Fi, cableado estructurado y puntos de acceso.', 'wifi', 2500],
        ['Instalación de software', 'Instalación, configuración y actualización de sistemas operativos y programas.', 'download', 800],
        ['Mantenimiento preventivo', 'Limpieza interna, cambio de pasta térmica y optimización del rendimiento.', 'sparkle', 1200],
        ['Seguridad informática', 'Antivirus, respaldos, firewall y recomendaciones para proteger tu información.', 'shield', 2000],
        ['Asistencia remota', 'Soporte técnico a distancia, rápido y seguro, sin que tengas que moverte.', 'monitor', 700],
    ];
    $st = $pdo->prepare('INSERT INTO servicios (nombre, descripcion, icono, precio_desde, activo, orden) VALUES (?,?,?,?,1,?)');
    foreach ($servicios as $i => $s) $st->execute([$s[0], $s[1], $s[2], $s[3], $i + 1]);

    $faq = [
        ['¿Cómo solicito un servicio?', 'Completa el formulario de "Solicitar soporte" o escríbenos por WhatsApp. Recibirás un número de ticket para dar seguimiento.', 'General'],
        ['¿Cómo consulto el estado de mi solicitud?', 'Entra en "Consultar ticket" e introduce tu número de ticket y el correo con el que hiciste la solicitud.', 'General'],
        ['¿Ofrecen servicio a domicilio?', 'Sí, dentro de nuestra zona de cobertura. Fuera de ella ofrecemos asistencia remota.', 'Servicios'],
        ['¿Cuánto tarda una reparación?', 'Depende del diagnóstico. La mayoría de los casos se resuelven entre 24 y 72 horas.', 'Servicios'],
        ['¿Qué métodos de pago aceptan?', 'Efectivo, transferencia bancaria y tarjetas de crédito o débito.', 'Pagos'],
        ['¿Es segura la asistencia remota?', 'Sí. Usamos herramientas cifradas y solo nos conectamos con tu autorización, y tú puedes ver todo lo que hacemos.', 'Seguridad'],
    ];
    $st = $pdo->prepare('INSERT INTO faq (pregunta, respuesta, categoria, activo, orden) VALUES (?,?,?,1,?)');
    foreach ($faq as $i => $f) $st->execute([$f[0], $f[1], $f[2], $i + 1]);
}

function seed_demo(PDO $pdo): void
{
    mt_srand(2026);
    $tecnicos = [
        ['Carlos Méndez', 'carlos@soportesrd.com', 'Redes y conectividad'],
        ['Ana Rodríguez', 'ana@soportesrd.com', 'Soporte técnico'],
        ['Luis Peña', 'luis@soportesrd.com', 'Seguridad informática'],
    ];
    $st = $pdo->prepare("INSERT INTO usuarios (nombre, email, telefono, password, rol, especialidad, activo, creado) VALUES (?,?,?,?, 'tecnico', ?, 1, ?)");
    foreach ($tecnicos as $t) $st->execute([$t[0], $t[1], '809-555-0000', password_hash('Tecnico123!', PASSWORD_DEFAULT), $t[2], date('Y-m-d H:i:s', strtotime('-200 days'))]);

    $nombres = ['María Pérez', 'José Martínez', 'Rosa Jiménez', 'Pedro Santana', 'Laura Castillo', 'Miguel Reyes', 'Carmen Núñez', 'Ramón Taveras', 'Sofía Almonte', 'Juan Vásquez', 'Farmacia La Esperanza', 'Colegio San Pablo'];
    $stc = $pdo->prepare('INSERT INTO clientes (nombre, email, telefono, empresa, password, creado) VALUES (?,?,?,?,?,?)');
    foreach ($nombres as $i => $n) {
        $email = 'cliente' . ($i + 1) . '@ejemplo.com';
        $stc->execute([$n, $email, '809-555-' . str_pad((string)(1000 + $i), 4, '0'), $i >= 10 ? $n : '', password_hash('Cliente123!', PASSWORD_DEFAULT), date('Y-m-d H:i:s', strtotime('-' . (180 - $i * 10) . ' days'))]);
    }

    $problemas = [
        ['La computadora no enciende', 1], ['Internet lento en la oficina', 3], ['Instalar Office y antivirus', 4],
        ['Laptop muy lenta', 5], ['Configurar router nuevo', 3], ['Posible virus en el equipo', 6],
        ['Impresora no imprime', 1], ['Ayuda con el correo Outlook', 2], ['Conexión remota para revisar sistema', 7],
        ['Pantalla azul al iniciar Windows', 1], ['Wi-Fi no llega a la segunda planta', 3], ['Respaldo de información', 6],
    ];
    $estados  = ['Recibido', 'Pendiente', 'En proceso', 'En espera del cliente', 'Resuelto', 'Resuelto', 'Resuelto', 'Cerrado'];
    $urg      = ['Baja', 'Media', 'Media', 'Alta', 'Crítica'];
    $stt = $pdo->prepare('INSERT INTO tickets (codigo, cliente_id, servicio_id, tecnico_id, titulo, descripcion, urgencia, estado, creado, actualizado, resuelto) VALUES (?,?,?,?,?,?,?,?,?,?,?)');
    $sth = $pdo->prepare('INSERT INTO ticket_historial (ticket_id, usuario_id, autor, tipo, estado, mensaje, publico, creado) VALUES (?,?,?,?,?,?,1,?)');

    for ($i = 1; $i <= 40; $i++) {
        $p = $problemas[array_rand($problemas)];
        $estado = $estados[array_rand($estados)];
        $diasAtras = mt_rand(0, 170);
        $creado = date('Y-m-d H:i:s', strtotime("-$diasAtras days -" . mt_rand(0, 600) . ' minutes'));
        $tec = in_array($estado, ['Recibido']) ? null : mt_rand(2, 4);
        $resuelto = in_array($estado, ['Resuelto', 'Cerrado']) ? date('Y-m-d H:i:s', strtotime($creado . ' +' . mt_rand(4, 90) . ' hours')) : null;
        $codigo = 'SRD-' . date('Y', strtotime($creado)) . '-' . str_pad((string)$i, 4, '0', STR_PAD_LEFT);
        $stt->execute([$codigo, mt_rand(1, count($nombres)), $p[1], $tec, $p[0], $p[0] . '. Necesito ayuda lo antes posible.', $urg[array_rand($urg)], $estado, $creado, $resuelto ?: $creado, $resuelto]);
        $tid = (int)$pdo->lastInsertId();
        $sth->execute([$tid, null, 'Sistema', 'estado', 'Recibido', 'Solicitud recibida.', $creado]);
        if ($estado !== 'Recibido') {
            $sth->execute([$tid, $tec, 'Técnico', 'estado', $estado, 'Estado actualizado a ' . $estado . '.', $resuelto ?: $creado]);
        }
    }

    $sta = $pdo->prepare('INSERT INTO actividad (usuario, accion, creado) VALUES (?,?,?)');
    $sta->execute(['Sistema', 'Instalación inicial con datos de demostración', date('Y-m-d H:i:s')]);
}
