# Sistema de Gestion Empresarial

Sistema integral de gestion empresarial desarrollado en PHP para administracion de inventarios, compras, ventas y reportes.

## Caracteristicas Principales

- **Gestion de Inventarios**: Control completo de productos y stock
- **Modulo de Compras**: Gestion de proveedores y ordenes de compra
- **Sistema de Ventas**: Facturacion y control de clientes
- **Reportes Avanzados**: Generacion de reportes en PDF y Excel
- **Panel de Administracion**: Gestion de usuarios y configuracion
- **Scanner OCR**: Procesamiento automatico de remitos (modulo avanzado)

## Requisitos del Sistema

- PHP 7.4 o superior
- MySQL 5.7 o superior
- Apache/Nginx
- Extensiones PHP: mysqli, gd, mbstring, zip

## Instalacion

1. Clona el repositorio:
`ash
git clone https://github.com/tu-usuario/sistemadgestion5.git
cd sistemadgestion5
`

2. Configura la base de datos:
   - Importa el archivo SQL desde config/
   - Configura las credenciales en config/config.php

3. Configura permisos:
`ash
chmod 755 assets/uploads
chmod 755 assets/scanner_input
chmod 755 assets/scanner_processed
`

## Estructura del Proyecto

`
sistemadgestion5/
â”œâ”€â”€ modulos/           # Modulos principales del sistema
â”‚   â”œâ”€â”€ admin/         # Panel de administracion
â”‚   â”œâ”€â”€ compras/       # Gestion de compras y proveedores
â”‚   â”œâ”€â”€ inventario/    # Control de inventarios
â”‚   â”œâ”€â”€ clientes/      # Gestion de clientes
â”‚   â”œâ”€â”€ facturas/      # Sistema de facturacion
â”‚   â””â”€â”€ productos/     # Catalogo de productos
â”œâ”€â”€ config/            # Configuracion del sistema
â”œâ”€â”€ assets/            # Recursos estaticos
â”œâ”€â”€ ajax/              # Endpoints AJAX
â””â”€â”€ index.php          # Punto de entrada
`

## Configuracion

1. Copia config/config.example.php a config/config.php
2. Ajusta las credenciales de base de datos
3. Configura las rutas segun tu servidor



## Modulos Disponibles

### Administracion
- Gestion de usuarios y permisos
- Configuracion del sistema
- Logs y auditoria

### Inventarios
- Control de stock
- Categorias y ubicaciones
- Reportes de inventario

### Compras
- Gestion de proveedores
- Ordenes de compra
- Scanner OCR para remitos

### Ventas
- Facturacion
- Gestion de clientes
- Reportes de ventas

## Contribucion

1. Fork el proyecto
2. Crea tu rama de caracteristicas (git checkout -b feature/AmazingFeature)
3. Commit tus cambios (git commit -m 'Add some AmazingFeature')
4. Push a la rama (git push origin feature/AmazingFeature)
5. Abre un Pull Request

## Licencia

Este proyecto esta bajo la Licencia MIT - ver el archivo [LICENSE](LICENSE) para detalles.

## Soporte

Para soporte y consultas, por favor abre un issue en este repositorio.
