=== AccessiTrans - ARIA Translator for WPML & Elementor ===
Contributors: marioalmonte
Tags: accessibility, aria, elementor, wpml, translation, wcag, multilingual, a11y
Requires at least: 5.6
Tested up to: 6.7
Stable tag: 0.1.0
Requires PHP: 7.2
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Plugin para WordPress que permite traducir atributos ARIA en sitios Elementor con WPML, mejorando la accesibilidad en entornos multilingües.

== Descripción ==

El plugin **AccessiTrans - ARIA Translator for WPML & Elementor** facilita la traducción de atributos ARIA en sitios desarrollados con Elementor y WPML, garantizando que la información de accesibilidad esté disponible en todos los idiomas de tu sitio web.

= Características principales =

* Detecta automáticamente y hace disponibles los atributos ARIA para traducción
* Integración completa con WPML String Translation
* Compatible con todos los elementos y plantillas de Elementor
* Múltiples métodos de captura para garantizar una detección exhaustiva
* Formatos de registro de traducción configurables
* Modo de depuración para solución de problemas
* Configuraciones de optimización de rendimiento
* Soporte para internacionalización

= Atributos ARIA compatibles =

El plugin permite traducir los siguientes atributos:

* `aria-label`: Para proporcionar un nombre accesible a un elemento
* `aria-description`: Para proporcionar una descripción accesible
* `aria-roledescription`: Para personalizar la descripción del rol de un elemento
* `aria-placeholder`: Para textos de ejemplo en campos de entrada
* `aria-valuetext`: Para proporcionar representación textual de valores numéricos

= Métodos de captura =

El plugin ofrece varios métodos de captura para asegurar que todos los atributos ARIA sean detectados:

1. **Captura total de HTML**: Captura todo el HTML de la página (altamente efectivo pero puede afectar al rendimiento)
2. **Filtro de contenido de Elementor**: Procesa el contenido generado por Elementor
3. **Procesamiento de templates de Elementor**: Procesa los datos de templates de Elementor
4. **Procesamiento de elementos individuales**: Procesa cada widget y elemento de Elementor individualmente

= Formatos de registro para traducción =

El plugin admite múltiples formatos para registrar cadenas para traducción:

1. **Formato directo**: Registra el valor literal como nombre
2. **Formato con prefijo**: Registra con el formato `aria-atributo_valor`
3. **Formato con ID de elemento**: Registra con un formato que incluye el ID del elemento

= Configuración avanzada =

* **Modo de depuración**: Activa el registro detallado de eventos
* **Captura solo para administradores**: Limita los métodos de captura intensivos en recursos a usuarios administradores

= Compatibilidad =

Funciona con todo tipo de contenido de Elementor:

* Páginas regulares
* Templates
* Secciones globales
* Headers y footers
* Popups y otros elementos dinámicos

**Versiones probadas:**
* WordPress 6.7
* Elementor 3.28.3
* WPML Multilingual CMS 4.7.3
* WPML String Translation 3.3.2

= Por qué este plugin es importante para la accesibilidad =

En sitios web multilingües, la información de accesibilidad debe estar disponible en todos los idiomas. Los atributos ARIA proporcionan información esencial de accesibilidad que ayuda a las tecnologías de asistencia a entender y navegar por tu sitio web. Al hacer que estos atributos sean traducibles, garantizas que todos los usuarios, independientemente de su idioma o capacidad, puedan acceder a tu contenido de manera efectiva.

== Instalación ==

1. Descarga el archivo `accessitrans-aria.zip` desde la página de releases de GitHub
2. Sube los archivos al directorio `/wp-content/plugins/accessitrans-aria/` de tu WordPress o instala directamente a través de WordPress subiendo el archivo ZIP
3. Activa el plugin a través del menú 'Plugins' en WordPress
4. Ve a Ajustes → AccessiTrans para configurar las opciones del plugin
5. Configura los atributos ARIA en Elementor (ver instrucciones de uso)

== Preguntas frecuentes ==

= ¿Funciona con otros constructores de páginas además de Elementor? =

No, actualmente el plugin está diseñado específicamente para trabajar con Elementor.

= ¿Es compatible con la última versión de WPML? =

Sí, el plugin ha sido probado con la versión 4.7.3 de WPML Multilingual CMS y 3.3.2 de WPML String Translation.

= ¿Por qué veo la misma cadena varias veces en WPML? =

Esto es por diseño. El plugin registra las cadenas en múltiples formatos para garantizar que puedan ser capturadas y traducidas independientemente de cómo se utilicen en tu contenido de Elementor. Solo necesitas traducir cada texto único una vez.

= ¿Este plugin ralentizará mi sitio web? =

El plugin incluye varias opciones de optimización de rendimiento. Por defecto, el método de captura más intensivo en recursos (Captura total de HTML) solo se ejecuta para usuarios administradores. Puedes ajustar estas configuraciones en la página de configuración del plugin.

= ¿Puedo usar este plugin con Elementor Free? =

Sí, el plugin funciona tanto con Elementor Free como con Elementor Pro.

= ¿Cómo sé si los atributos ARIA se están traduciendo correctamente? =

Puedes verificarlo:
1. Añadiendo atributos ARIA en Elementor
2. Traduciéndolos en WPML String Translation
3. Cambiando a un idioma diferente en tu frontend
4. Inspeccionando el elemento con las herramientas de desarrollo del navegador para ver si aparece el texto traducido

= ¿Funciona con widgets globales y plantillas de Elementor? =

Sí, el plugin funciona con todos los tipos de contenido de Elementor, incluidos widgets globales, plantillas y elementos dinámicos.

== Capturas de pantalla ==

1. Página de configuración del plugin con métodos de captura
2. Añadiendo atributos ARIA en la pestaña Avanzado de Elementor
3. Interfaz de WPML String Translation mostrando cadenas ARIA
4. Ejemplo de atributos ARIA traducidos en diferentes idiomas

== Instrucciones de uso ==

= Cómo añadir atributos ARIA en Elementor =

1. Edita cualquier elemento en Elementor
2. Ve a la pestaña "Avanzado"
3. Busca la sección "Atributos personalizados" (Custom Attributes)
4. Añade los atributos ARIA que deseas traducir

= Formatos compatibles =

Elementor indica: "Configura atributos personalizados para el elemento contenedor. Cada atributo en una línea separada. Separa la clave del atributo del valor usando el car