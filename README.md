# SoportesRD

Sitio web y sistema de gestión de soporte técnico de **SoportesRD**: portal público para clientes y panel administrativo privado (backoffice).

Hecho en **PHP 8 + PDO**, sin frameworks ni dependencias externas. Usa **SQLite** por defecto (sin configurar nada) o **MySQL**.

## Área pública
- **Inicio**: logo, nombre comercial, menú, frase del negocio, botones de WhatsApp y de solicitar servicio, ilustración tecnológica y servicios.
- **Servicios**: soporte técnico, soporte informático, redes y conectividad, instalación de software, mantenimiento preventivo, seguridad informática y asistencia remota (administrables desde el panel).
- **Sobre nosotros**: quiénes somos, misión, visión, valores y compromiso.
- **Solicitar soporte**: nombre, correo, teléfono, tipo de servicio, descripción, nivel de urgencia y adjuntos (imágenes o documentos). Genera un número de ticket `SRD-AAAA-0001`.
- **Consultar ticket**: con número de ticket + correo, muestra la barra de progreso de estados (Recibido → Pendiente → En proceso → En espera del cliente → Resuelto → Cerrado), historial y adjuntos; el cliente puede responder.
- **Preguntas frecuentes** (base de conocimientos con buscador).
- **Contacto**: WhatsApp, correo, teléfono, horario, ubicación/zona de cobertura, formulario y redes sociales.
- **Portal de clientes**: registro, inicio de sesión, historial de servicios y cotizaciones.

## Panel administrativo (`/admin`)
| Módulo | Funcionalidad |
|---|---|
| Dashboard | Totales, tickets recientes, por prioridad y estado, gráficas por mes, rendimiento de técnicos, clientes y actividad reciente |
| Tickets | Filtros, crear tickets manuales, cambiar estado, responder al cliente, notas internas, adjuntos |
| Clientes | Registrar, editar, eliminar y ver historial de servicios |
| Servicios | Crear, editar y eliminar servicios |
| Técnicos | Administrar personal y roles (Administrador / Técnico) |
| Asignaciones | Asignar tickets en lote y ver la carga de trabajo |
| Cotizaciones | Cotizaciones con ITBIS, envío al cliente e impresión en PDF |
| Reportes | Estadísticas por período, reporte PDF y exportación a Excel (CSV) |
| Base de conocimientos | Administrar preguntas frecuentes |
| Mensajes | Mensajes de contacto, convertibles en ticket |
| Configuración | Datos del negocio, contacto, redes sociales, ITBIS y notificaciones |

**Permisos:** el Administrador ve todo; el Técnico ve el dashboard, solo sus tickets asignados y la lista de clientes.

**Notificaciones por correo:** nueva solicitud, cambios de estado, respuestas, asignaciones y cotizaciones. Mientras `SRD_MAIL` no esté en `1`, los correos se guardan en `storage/mail.log` para probar.

## Instalación local (XAMPP / Laragon)
1. Copia la carpeta en `htdocs` (ej. `htdocs/SoportesRD`).
2. Si está en una subcarpeta, en `config/config.php` pon `'base_url' => '/SoportesRD'`.
3. Abre `http://localhost/SoportesRD`. La base de datos se crea sola.

O con el servidor de PHP: `php -S localhost:8000` y abre `http://localhost:8000`.

### Accesos iniciales
| Usuario | Correo | Contraseña |
|---|---|---|
| Administrador | admin@soportesrd.com | `Admin123!` |
| Técnicos demo | carlos@ / ana@ / luis@soportesrd.com | `Tecnico123!` |
| Clientes demo | cliente1@ejemplo.com … | `Cliente123!` |

> ⚠️ Cambia la contraseña del administrador en **Mi perfil** y desactiva los datos demo (`SRD_DEMO=0`) antes de usarlo en producción.

## Variables de entorno (Docker / Render)
| Variable | Uso | Por defecto |
|---|---|---|
| `SRD_DB_DRIVER` | `sqlite` o `mysql` | `sqlite` |
| `SRD_DB_HOST`, `SRD_DB_PORT`, `SRD_DB_NAME`, `SRD_DB_USER`, `SRD_DB_PASS` | Conexión MySQL | — |
| `SRD_DEMO` | Cargar datos de demostración (`1`/`0`) | `1` |
| `SRD_MAIL` | Enviar correos reales con `mail()` | `0` |
| `SRD_MAIL_FROM` | Remitente | no-responder@soportesrd.com |
| `SRD_BASE_URL` | Subcarpeta, si aplica | vacío |

### Docker
```bash
docker build -t soportesrd .
docker run -p 8080:80 soportesrd
```
En Render: nuevo **Web Service** desde este repositorio, entorno **Docker**. (Con SQLite en el plan gratis los datos se reinician en cada despliegue; para datos permanentes usa MySQL.)

## Estructura
```
index.php, servicios.php, nosotros.php, solicitar.php, consultar.php, faq.php, contacto.php, descargar.php
cliente/   portal de clientes (login, registro, panel, cotización)
admin/     panel administrativo
includes/  conexión, funciones, plantillas
assets/    css, js, imágenes (logo e ilustraciones)
config/    configuración
storage/   base SQLite y registro de correos (no se publica)
uploads/   adjuntos (protegidos, se sirven con permiso)
```

## Paleta
Azul oscuro `#0B1F3A` · Azul `#1677FF` · Gris claro `#F4F7FB` · Verde `#16A34A`
