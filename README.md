# Sistema Hotel - Instrucciones de Uso

## Configuración actual

El sistema está configurado para usar MySQL en XAMPP.

## Acceso al sistema

- URL Hotel Admin: http://localhost/hotel-system/public/hotel/login
- URL Super Admin: http://localhost/hotel-system/public/super/login
- Usuario: admin@hotel.com
- Contraseña: admin123

## Requisitos

- Apache activo en XAMPP
- MySQL activo en XAMPP
- PHP 8+

## Inicialización rápida

1. Ejecutar: php create_db_mysql.php
2. Verificar: php check_system.php
3. Abrir la URL de acceso en navegador

## Notas

- El sistema usa la base de datos hotel_master.
- La configuración está en config/config.php y config/MasterDatabase.php.