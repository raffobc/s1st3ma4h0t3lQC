# Deploy Gratis y Permanente (InfinityFree)

Este proyecto es compatible con InfinityFree porque usa PHP + MySQL.

## 1) Crear hosting gratis

1. Crea cuenta en InfinityFree.
2. Crea un hosting account y dominio gratuito (`*.epizy.com`) o conecta uno propio.
3. En el panel, crea una base de datos MySQL y guarda:
- Host
- DB name
- DB user
- DB password

## 2) Configurar conexión MySQL en el proyecto

Edita `config/config.php` con los datos del hosting:

```php
define('MASTER_DB_HOST', 'SQL_HOST_DE_INFINITYFREE');
define('MASTER_DB_NAME', 'NOMBRE_DB');
define('MASTER_DB_USER', 'USUARIO_DB');
define('MASTER_DB_PASS', 'PASSWORD_DB');
```

## 3) Inicializar tablas en el hosting

Sube el proyecto y ejecuta una vez:

- `https://TU_DOMINIO/public/create_db_mysql.php`

Luego valida:

- `https://TU_DOMINIO/public/check_system.php`

## 4) URL final de acceso

- Admin Hotel: `https://TU_DOMINIO/public/hotel/login`
- Super Admin: `https://TU_DOMINIO/public/super/login`

## 5) Deploy automático desde GitHub (ya preparado)

Este repo incluye workflow: `.github/workflows/deploy-infinityfree.yml`

Configura estos `Repository Secrets` en GitHub:

- `FTP_SERVER`
- `FTP_USERNAME`
- `FTP_PASSWORD`

Ruta de deploy configurada:

- `htdocs/`

Cada push a `main` desplegará automáticamente.

## Nota importante

En hosting compartido gratuito, la app puede ser más lenta que en local. Es normal.
