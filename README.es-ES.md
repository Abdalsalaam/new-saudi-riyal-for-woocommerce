

![riyal-cover.png](riyal-cover.png)

# Símbolos de Monedas del Golfo para WooCommerce
[Repositorio en WordPress.org](https://wordpress.org/plugins/saudi-riyal-symbol-for-woocommerce/)

## Descripción

### Inglés

Añade soporte para los nuevos símbolos del Riyal Saudí (SAR), el Dirham de los EAU (AED) y el Rial Omaní (OMR) en WooCommerce.

Este reemplaza los símbolos de moneda predeterminados con los nuevos símbolos oficiales:
- **Riyal Saudí (SAR)** - Nuevo símbolo anunciado por el Banco Central de Arabia Saudita (SAMA)
- **Dirham de los EAU (AED)** - Símbolo oficial del Dirham
- **Rial Omaní (OMR)** - Símbolo oficial del Rial

Para obtener más detalles sobre el símbolo del Riyal Saudí, consulte el [anuncio del Banco Central de Arabia Saudita](https://www.sama.gov.sa/en-US/Currency/SRS/Pages/default.aspx).

### العربية

إضافة ووردبريس تضيف دعم رموز العملات الخليجية الجديدة في WooCommerce:
- **(SAR)** - رمز الريال السعودي
- **(AED)** - رمز الدرهم الإماراتي
- **(OMR)** - رمز الريال العماني

## Características
- Soporta los símbolos del Riyal Saudí (SAR), Dirham de los EAU (AED) y Rial Omaní (OMR).
- Muestra los símbolos de moneda en el front-end, el panel de administración, los correos de WooCommerce y las facturas en PDF.
- Soporta entornos RTL forzando que el símbolo aparezca a la izquierda.
- Compatible con temas basados en bloques (bloques de Carrito/Pago).
- Compatible con populares plugins de cambio de moneda (WOOCS, Multi Currency for WooCommerce y más).

## Compatible Con
- Correos de WooCommerce
- Plugin PDF Invoices & Packing Slips for WooCommerce
- Plugin Challan - PDF Invoice & Packing Slip for WooCommerce
- WOOCS - WooCommerce Currency Switcher
- Multi Currency for WooCommerce (VillaTheme)
- WooCommerce Multi-Currency

## Registro de Cambios

### 2.1
- Compatibilidad con WordPress 7.0 y WooCommerce 10.8.
- Restaura el símbolo de moneda predeterminado de WooCommerce para los rastreadores de SEO y LLM, de modo que los precios sigan siendo legibles por máquinas en los datos estructurados y los resultados de búsqueda con IA.
- Mejora la compatibilidad con plugins de terceros que leen símbolos de moneda mediante `get_woocommerce_currency_symbols()`.
- Garantiza que la fuente de moneda del panel de administración se aplique incluso cuando las hojas de estilo de administración de terceros vuelven a declarar `font-family` en el mismo elemento.

### 2.0
- Añade soporte para los símbolos del Dirham de los EAU (AED) y Rial Omaní (OMR).
- Mejor renderización de símbolos en el panel de administración.

### 1.9
- Compatibilidad con WordPress 6.9 y WooCommerce 10.3.

### 1.8
- Añade soporte para plugins de múltiples monedas (WOOCS, Multi Currency for WooCommerce y otros selectores de moneda populares).

### 1.7
- Añade compatibilidad con `Challan - PDF Invoice & Packing Slip for WooCommerce`.
- Corrige el símbolo de moneda en la factura PDF adjunta a los correos.

### 1.6
- Añade compatibilidad con PDF Invoices & Packing Slips for WooCommerce.

### 1.5
- Corregida la visualización del símbolo de moneda en correos RTL.

### 1.4
- Corrige el símbolo de moneda del precio de oferta en temas basados en bloques.

### 1.3
- Se corrigió un problema donde el símbolo de moneda no se actualizaba correctamente al cambiar las cantidades de productos en los bloques de Carrito/Pago.
- Corrige el símbolo de moneda en los correos de WooCommerce.

### 1.2
- Para utilizar la posición de moneda "izquierda con espacio".

### 1.1
- Corrige el reemplazo del símbolo dentro del panel de administración.
- Declara compatibilidad con características de WooCommerce para ocultar advertencias.

### 1.0
- Lanzamiento inicial.
