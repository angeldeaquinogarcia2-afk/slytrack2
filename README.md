# 🏛️ Inventario Municipal — Guía de instalación con XAMPP

## Archivos del proyecto

```
inventario/
├── index.html          ← Frontend (toda la interfaz)
├── setup.sql           ← Script para crear la base de datos
├── api/
│   ├── config.php      ← Configuración de conexión a MySQL
│   └── index.php       ← API REST (toda la lógica del servidor)
└── uploads/
    └── fotos/          ← Aquí se guardan las fotografías (se crea solo)
```

---

## Paso 1 — Copiar el proyecto a XAMPP

1. Abre la carpeta `htdocs` de tu XAMPP.
   - Windows: `C:\xampp\htdocs\`
   - Mac/Linux: `/opt/lampp/htdocs/`
2. Copia la carpeta completa `inventario/` dentro de `htdocs`.

Resultado: `C:\xampp\htdocs\inventario\`

---

## Paso 2 — Crear la base de datos

1. Abre XAMPP Control Panel y arranca **Apache** y **MySQL**.
2. Abre tu navegador y entra a: `http://localhost/phpmyadmin`
3. Haz clic en **"Nueva"** (crear nueva base de datos).
4. Escribe `inventario_municipal` y presiona **Crear**.
5. Selecciona la base que acabas de crear, luego haz clic en la pestaña **SQL**.
6. Copia y pega el contenido del archivo `setup.sql` y presiona **Continuar**.

✅ Listo — se crearán las tablas y los 6 departamentos de ejemplo.

---

## Paso 3 — Configurar la conexión (si tu MySQL tiene contraseña)

Si tu MySQL tiene contraseña (en XAMPP normalmente no la tiene), abre el archivo:

```
inventario/api/config.php
```

Y cambia las líneas:
```php
define('DB_USER', 'root');   // tu usuario de MySQL
define('DB_PASS', '');       // tu contraseña (vacía por defecto en XAMPP)
```

---

## Paso 4 — Abrir la aplicación

Abre tu navegador y entra a:

```
http://localhost/inventario/
```

---

## Tokens de acceso por área

| Token     | Área               |
|-----------|--------------------|
| DSR2024   | Desarrollo Social  |
| OBR2024   | Obras Públicas     |
| SAL2024   | Salud              |
| EDU2024   | Educación          |
| TES2024   | Tesorería          |
| ADM2024   | Administración     |

### Agregar nuevas áreas o cambiar tokens

En phpMyAdmin ejecuta:
```sql
USE inventario_municipal;
INSERT INTO areas (nombre, token) VALUES ('Mi Nuevo Departamento', 'TOKEN123');
```

Para cambiar un token:
```sql
UPDATE areas SET token = 'NUEVO_TOKEN' WHERE nombre = 'Desarrollo Social';
```

---

## Uso en teléfono (red local)

1. Conoce la IP de tu computadora (en Windows: ejecuta `ipconfig` en cmd).
2. En tu teléfono (conectado al mismo WiFi) abre:
   ```
   http://192.168.X.X/inventario/
   ```
   (reemplaza con tu IP real)
3. Al tomar fotos, el teléfono abrirá la cámara directamente.

---

## Notas importantes

- Las fotos se guardan en `inventario/uploads/fotos/` en tu servidor.
- El reporte Excel incluye la URL de las fotos (accesibles si la app está en red).
- En producción real, cambia los tokens por valores más seguros.
- La carpeta `uploads/fotos/` necesita permisos de escritura (en XAMPP ya los tiene por defecto).
