# Base de Datos - Sistema de Gestion Empresarial

## Archivos incluidos

### 1. sistemadgestion5_completo_2025-08-08_13-52-24.sql
- **Descripcion**: Estructura completa + datos de ejemplo
- **Uso**: Para instalacion completa con datos de prueba
- **Comando de importacion**:
```sql
mysql -u root -p sistemadgestion5 < sistemadgestion5_completo_2025-08-08_13-52-24.sql
```

### 2. sistemadgestion5_estructura_2025-08-08_13-52-24.sql
- **Descripcion**: Solo estructura de tablas (sin datos)
- **Uso**: Para instalacion limpia sin datos
- **Comando de importacion**:
```sql
mysql -u root -p sistemadgestion5 < sistemadgestion5_estructura_2025-08-08_13-52-24.sql
```

## Instalacion de la Base de Datos

### Opcion 1: Instalacion Completa (Recomendada)
```bash
# 1. Crear la base de datos
mysql -u root -p -e "CREATE DATABASE sistemadgestion5;"

# 2. Importar estructura y datos
mysql -u root -p sistemadgestion5 < sistemadgestion5_completo_2025-08-08_13-52-24.sql
```

### Opcion 2: Instalacion Solo Estructura
```bash
# 1. Crear la base de datos
mysql -u root -p -e "CREATE DATABASE sistemadgestion5;"

# 2. Importar solo la estructura
mysql -u root -p sistemadgestion5 < sistemadgestion5_estructura_2025-08-08_13-52-24.sql
```

## Requisitos
- MySQL 8.0+
- PHP 8.1+
- Extensiones PHP: mysqli, pdo_mysql

## Configuracion
Despues de importar la base de datos, configura la conexion en:
```php
// config/config.php
define('DB_HOST', 'localhost');
define('DB_NAME', 'sistemadgestion5');
define('DB_USER', 'tu_usuario');
define('DB_PASS', 'tu_password');
```

## Usuarios por Defecto
- **Admin**: admin / admin
- **Usuario**: usuario / usuario

**IMPORTANTE**: Cambia las contraseÃ±as por defecto despues de la instalacion.
