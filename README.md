# Sistema Hotel Demo

Demo funcional de un sistema de gestión hotelera en PHP + MySQL, pensado para ejecutar en entorno local con XAMPP.

[![Estado](https://img.shields.io/badge/status-MVP-success)](README.md)
[![PHP](https://img.shields.io/badge/PHP-8%2B-777bb4)](README.md)
[![MySQL](https://img.shields.io/badge/MySQL-XAMPP-00758f)](README.md)
[![Licencia](https://img.shields.io/badge/licencia-MIT-blue)](LICENSE)

## Stack

- PHP 8+
- MySQL (XAMPP)
- HTML/CSS/JS
- Arquitectura simple MVC (controllers, models, views)

## Funcionalidades MVP

- Login de administrador de hotel
- Dashboard con métricas básicas
- Gestión de habitaciones (listar, crear, editar, eliminar)
- Gestión de clientes (listar, crear, editar, eliminar)
- Gestión de reservas (crear, check-in, check-out, cancelación)
- Calendario de ocupación por habitación (vista mensual)

## Requisitos

- XAMPP con Apache y MySQL activos
- PHP 8 o superior
- Git (opcional, para clonar)

## Instalación rápida

1. Clona o descarga el proyecto en htdocs:

```bash
cd c:\xampp\htdocs
git clone https://github.com/raffobc/s1st3ma4h0t3lQC.git hotel-system
```

2. Entra al proyecto y crea la base de datos demo:

```bash
cd c:\xampp\htdocs\hotel-system
php create_db_mysql.php
```

3. Verifica el estado del sistema:

```bash
php check_system.php
```

4. Abre en navegador:

- Admin Hotel: http://localhost/hotel-system/public/hotel/login
- Super Admin: http://localhost/hotel-system/public/super/login

## Credenciales de demo

- Usuario: admin@hotel.com
- Contraseña: admin123

## Rutas principales

- Dashboard: http://localhost/hotel-system/public/hotel/dashboard
- Habitaciones: http://localhost/hotel-system/public/hotel/habitaciones
- Reservas: http://localhost/hotel-system/public/hotel/reservas
- Clientes: http://localhost/hotel-system/public/hotel/clientes
- Calendario: http://localhost/hotel-system/public/hotel/calendario

## Estructura del proyecto

- Configuración: config
- Controladores: controllers
- Modelos: models
- Vistas: views
- Front controller: public/index.php

## Troubleshooting

- Error de conexión MySQL: verifica que MySQL esté iniciado en XAMPP.
- Página no carga: confirma que Apache esté activo y la URL incluya /hotel-system/public.
- Base incompleta: vuelve a ejecutar php create_db_mysql.php.

## Estado

Proyecto en modo demo/MVP, listo para pruebas funcionales y presentación.

## Roadmap

### Hecho

- Base MVP funcional en MySQL
- Flujo de reservas con check-in y check-out
- Calendario de ocupación mensual por habitación
- Publicación inicial en GitHub

### Siguiente versión

- Vista semanal del calendario
- Modal de detalle al hacer click en reserva del calendario
- Reportes básicos de ocupación e ingresos
- Hardening de validaciones y mensajes de error

## Licencia

Este proyecto está bajo licencia MIT. Revisa el archivo LICENSE.

## Deploy gratis y permanente

Este proyecto puede publicarse en InfinityFree (PHP + MySQL) con URL pública permanente.

Guía completa:

- DEPLOY_INFINITYFREE.md

Incluye workflow de GitHub Actions para deploy automático por FTP:

- .github/workflows/deploy-infinityfree.yml