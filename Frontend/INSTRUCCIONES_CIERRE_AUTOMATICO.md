# Sistema de Cierre Automático de Chats

Este sistema cierra automáticamente los chats después de un tiempo determinado desde que se confirma la compra.

## ⚙️ Configuración

1. Abre el archivo `Frontend/api/config_cierre_automatico.php`
2. Ajusta el valor de `DIAS_ESPERA_CIERRE` según tus necesidades:
   - `1` = 1 día
   - `3` = 3 días
   - `7` = 1 semana (por defecto)
   - `14` = 2 semanas
   - `30` = 1 mes

**Nota:** El sistema usa el campo `fecha_venta` existente en la tabla `chats` para calcular el tiempo transcurrido.

## 🚀 Métodos de Ejecución

### Opción 1: Ejecución Manual (Recomendado para pruebas)

Puedes ejecutar el script manualmente desde el navegador:
```
http://tu-dominio.com/api/cerrar_chats_automatico.php
```

O desde línea de comandos:
```bash
php Frontend/api/cerrar_chats_automatico.php
```

### Opción 2: Cron Job (Recomendado para producción)

Configura un cron job para ejecutar el script automáticamente cada día:

**En Linux/Mac:**
```bash
# Editar crontab
crontab -e

# Agregar esta línea (ejecuta diariamente a las 2 AM)
0 2 * * * php /ruta/completa/a/Frontend/api/cerrar_chats_automatico.php
```

**En Windows (Programador de Tareas):**
1. Abre el "Programador de tareas"
2. Crea una nueva tarea básica
3. Configura para que se ejecute diariamente
4. Acción: Iniciar programa
5. Programa: `php.exe`
6. Argumentos: `C:\ruta\completa\a\Frontend\api\cerrar_chats_automatico.php`

### Opción 3: Llamada desde otro script PHP

Puedes incluir el script en otro proceso que ya se ejecute periódicamente:
```php
include_once 'api/cerrar_chats_automatico.php';
```

## 📊 Logs

El sistema guarda un registro de cada ejecución en:
```
Frontend/logs/chats_automaticos.log
```

Ejemplo de log:
```
[2025-02-28 02:00:00] Chats cerrados automáticamente: 3
[2025-03-01 02:00:00] Chats cerrados automáticamente: 0
```

## 🔄 Flujo del Sistema

1. Usuario confirma la compra → Se guarda `fecha_venta` en la base de datos
2. El script se ejecuta periódicamente (manual o automático)
3. Busca chats donde han pasado X días desde `fecha_venta`
4. Cambia el `estado_id` a `8` (cerrado)
5. El chat se bloquea automáticamente y aparece en "Mis Conversaciones" con badge "Cerrado"
6. Los usuarios pueden eliminar manualmente el chat cerrado

## ⚠️ Notas Importantes

- Los chats cerrados NO se eliminan automáticamente, solo se bloquean
- Los usuarios pueden eliminar manualmente los chats cerrados desde "Mis Conversaciones"
- El historial de mensajes se mantiene para posibles denuncias
- Solo se cierran chats que tengan `fecha_venta` registrada
- No afecta a chats ya cerrados o eliminados
- **No requiere modificaciones en la base de datos** - usa el campo `fecha_venta` existente

## 🧪 Pruebas

Para probar el sistema:

1. Confirma una compra en un chat
2. Modifica temporalmente `DIAS_ESPERA_CIERRE` a `0` en `config_cierre_automatico.php`
3. Ejecuta manualmente el script desde el navegador
4. Verifica que el chat se cierre automáticamente
5. Restaura el valor original de `DIAS_ESPERA_CIERRE`

## 🛠️ Solución de Problemas

**El script no cierra ningún chat:**
- Confirma que hay chats con `fecha_venta` registrada
- Revisa el archivo de logs para ver si hay errores
- Usa `test_cierre_automatico.php` para diagnosticar

**Error de permisos en logs:**
- Asegúrate de que el directorio `Frontend/logs/` tenga permisos de escritura
- En Linux: `chmod 755 Frontend/logs/`

**El cron job no se ejecuta:**
- Verifica la ruta completa al archivo PHP
- Asegúrate de que el usuario del cron tenga permisos
- Revisa los logs del sistema: `/var/log/syslog` o `/var/log/cron`
