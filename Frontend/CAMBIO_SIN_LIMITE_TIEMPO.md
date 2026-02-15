# ⏰ Eliminación del Límite de 7 Días - Sistema de Devoluciones

## 🎯 Cambio Realizado

Se ha **eliminado completamente** la restricción de tiempo para solicitar devoluciones.

### ❌ ANTES (Con límite de 7 días)

```php
// Verificar si no han pasado más de 7 días
$fechaVenta = strtotime($chat['fecha_venta']);
$diasTranscurridos = (time() - $fechaVenta) / (60 * 60 * 24);
if ($diasTranscurridos <= 7):
    // Mostrar botón
endif;
```

**Problemas:**
- ❌ Límite arbitrario de 7 días
- ❌ Comprador pierde el derecho después de una semana
- ❌ No flexible para casos especiales

### ✅ AHORA (Sin límite de tiempo)

```php
// Solo verificar que esté vendido
if ($es_comprador && $chat['estado_id'] == 5):
    // Mostrar botón
endif;
```

**Ventajas:**
- ✅ Sin límite de tiempo
- ✅ Mayor flexibilidad
- ✅ Mejor experiencia de usuario
- ✅ El vendedor decide si acepta o no

---

## 📁 Archivos Modificados

### 1. `chat.php`

**Cambio en el header:**
```php
// ANTES
<?php if ($es_comprador && $chat['estado_id'] == 5): ?>
    <?php
    $fechaVenta = strtotime($chat['fecha_venta']);
    $diasTranscurridos = (time() - $fechaVenta) / (60 * 60 * 24);
    if ($diasTranscurridos <= 7):
    ?>
    <button>Devolver</button>
    <?php endif; ?>
<?php endif; ?>

// AHORA
<?php if ($es_comprador && $chat['estado_id'] == 5): ?>
    <button>Devolver</button>
<?php endif; ?>
```

**Cambio en el input:**
```php
// ANTES
<?php if ($es_comprador && $chat['estado_id'] == 5): ?>
    <?php if ($diasTranscurridos <= 7): ?>
        <button>Solicitar devolución</button>
    <?php endif; ?>
<?php endif; ?>

// AHORA
<?php if ($es_comprador && $chat['estado_id'] == 5): ?>
    <button>Solicitar devolución</button>
<?php endif; ?>
```

### 2. `api/solicitar_devolucion.php`

**Código eliminado:**
```php
// ❌ ELIMINADO
// Verificar que no hayan pasado más de 7 días desde la venta
$fechaVenta = strtotime($chat['fecha_venta']);
$diasTranscurridos = (time() - $fechaVenta) / (60 * 60 * 24);
if ($diasTranscurridos > 7) {
    echo json_encode([
        'success' => false, 
        'message' => 'El plazo de 7 días para solicitar devolución ha expirado'
    ]);
    $conn->close();
    exit;
}
```

---

## ✅ Condiciones Actuales

### Para que aparezca el botón de devolución:

1. ✅ El usuario debe ser el **comprador** (no el vendedor)
2. ✅ El estado del chat debe ser **5 (Vendido)**
3. ✅ ~~No deben haber pasado más de 7 días~~ **ELIMINADO**

### Estados del chat:

| Estado | Nombre | ¿Puede solicitar devolución? |
|--------|--------|------------------------------|
| 5 | Vendido | ✅ SÍ |
| 6 | Esperando confirmación | ❌ NO |
| 7 | Devolviendo (pendiente) | ❌ NO (ya hay una solicitud) |
| 8 | Devuelto | ❌ NO (ya fue devuelto) |
| 9 | Censurado | ❌ NO |

---

## 🎯 Flujo Actualizado

### Comprador:

```
1. Compra un producto
   ↓
2. Estado cambia a "Vendido" (5)
   ↓
3. Ve botones de devolución (SIEMPRE)
   ↓
4. Puede solicitar devolución CUANDO QUIERA
   ↓
5. Vendedor decide si acepta o rechaza
```

### Vendedor:

```
1. Recibe solicitud de devolución
   ↓
2. Ve notificación flotante
   ↓
3. Decide: Aceptar o Rechazar
   ↓
4. No importa cuánto tiempo haya pasado
```

---

## 💡 Ventajas del Cambio

### Para el Comprador:
✅ **Más tiempo para detectar problemas**
- Algunos defectos no son inmediatos
- Mayor tranquilidad al comprar

✅ **Sin presión de tiempo**
- No hay que apresurarse
- Puede evaluar bien el producto

✅ **Más justo**
- No pierde el derecho arbitrariamente
- Protección del consumidor

### Para el Vendedor:
✅ **Control total**
- Decide si acepta o rechaza
- Puede evaluar cada caso

✅ **Flexibilidad**
- Puede aceptar devoluciones legítimas
- Puede rechazar abusos

✅ **Mejor reputación**
- Muestra confianza en sus productos
- Genera más ventas

### Para la Plataforma:
✅ **Menos conflictos**
- No hay quejas por "se venció el plazo"
- Más satisfacción general

✅ **Más simple**
- Menos código
- Menos validaciones

---

## 🔍 Comparación

### Antes (Con límite)
```
Día 1-7: ✅ Puede solicitar devolución
Día 8+:  ❌ No puede solicitar devolución
         ❌ Botón desaparece
         ❌ API rechaza la solicitud
```

### Ahora (Sin límite)
```
Día 1:   ✅ Puede solicitar devolución
Día 30:  ✅ Puede solicitar devolución
Día 100: ✅ Puede solicitar devolución
Día ∞:   ✅ Puede solicitar devolución
         ✅ Botón siempre visible
         ✅ Vendedor decide
```

---

## 📊 Impacto

### Código eliminado:
- **chat.php**: 8 líneas eliminadas (2 bloques)
- **api/solicitar_devolucion.php**: 7 líneas eliminadas

### Complejidad reducida:
- ❌ Sin cálculo de días transcurridos
- ❌ Sin validación de fecha
- ❌ Sin mensaje de "plazo expirado"

### Lógica simplificada:
```
ANTES: ¿Es comprador? + ¿Estado vendido? + ¿Menos de 7 días?
AHORA: ¿Es comprador? + ¿Estado vendido?
```

---

## ✅ Resultado Final

El sistema de devoluciones ahora es:
- **Más simple**: Menos validaciones
- **Más flexible**: Sin límites arbitrarios
- **Más justo**: El vendedor decide en cada caso
- **Más confiable**: Menos código = menos bugs

El comprador puede solicitar devolución **en cualquier momento** mientras el producto esté en estado "Vendido", y el vendedor tiene el control final para aceptar o rechazar según el caso.

---

*Implementado: 2026-02-14*
*Versión: 2.2*
