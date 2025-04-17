=== AccessiTrans - ARIA Translator for WPML & Elementor ===
Contributors: marioalmonte
Tags: accessibility, aria, elementor, wpml, translation, wcag, multilingual, a11y
Requires at least: 5.6
Tested up to: 6.8
Stable tag: 0.2.0
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
* Mecanismo de reintento para traducciones fallidas
* Función para forzar la actualización y limpiar cachés
* Prioridad de traducción configurable
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

**Importante:** Se recomienda activar los tres formatos para obtener la máxima robustez.

= Configuración avanzada =

* **Reintentar traducciones fallidas**: Reintenta automáticamente las traducciones que fallaron en el primer intento
* **Prioridad de traducción**: Configura la prioridad de los filtros de traducción
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
* WordPress 6.8
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

= ¿Qué debo hacer si las traducciones no funcionan correctamente? =

Prueba a utilizar el botón "Forzar actualización" en la configuración del plugin. Esto limpiará todas las cachés y reinicializará el sistema de traducción. Además, asegúrate de tener activados los tres formatos de registro para máxima compatibilidad.

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

Elementor indica: "Configura atributos personalizados para el elemento contenedor. Cada atributo en una línea separada. Separa la clave del atributo del valor usando el carácter `|`."

Puedes añadir los atributos ARIA de dos formas:

**Formato básico (una línea por atributo):**
```
aria-label|Texto a traducir
```

**Formato multilínea (varios atributos):**
```
aria-label|Texto a traducir
aria-description|Otra descripción
```

Esto generará los atributos HTML correspondientes en el frontend:
`aria-label="Texto a traducir" aria-description="Otra descripción"`

= Cómo traducir los atributos =

1. Una vez añadidos los atributos, guarda la página o template
2. Ve a WPML → String Translation (Traducción de cadenas)
3. Filtra por alguno de los contextos "AccessiTrans ARIA Attributes_XXX"
4. Traduce las cadenas como harías con cualquier otro texto en WPML

= Mejores prácticas para un rendimiento óptimo =

Para la mejor experiencia y rendimiento del sitio web, sigue estas recomendaciones:

1. **Activa los tres formatos de registro** para obtener máxima robustez:
   * Formato directo
   * Formato con prefijo
   * Formato con ID de elemento

2. **Utiliza la función Forzar actualización** cuando las traducciones no aparezcan como se esperaba

3. **Navega por el sitio solo en el idioma principal** mientras generas cadenas para traducir. Esto evita que el plugin registre cadenas que ya hayan sido traducidas a través de otros sistemas.

4. **Desactiva los métodos de captura después de la configuración inicial**:
   * Una vez que hayas capturado todos los atributos ARIA para traducción, recomendamos desactivar todos los métodos de captura en la configuración del plugin
   * Esto mejora significativamente el rendimiento del sitio y evita que se registren cadenas adicionales en WPML
   * Vuelve a activar los métodos de captura temporalmente cuando realices cambios en tu sitio que incluyan nuevos atributos ARIA

= Ejemplos prácticos =

**Para un botón de menú:**
* Atributo: `aria-label|Abrir menú`

**Para un enlace de teléfono:**
* Atributo: `aria-label|Llamar a atención al cliente`

**Para un icono sin texto:**
* Atributo: `aria-label|Enviar email`

**Para un slider:**
* Atributo: `aria-label|Galería de imágenes`
* Atributo: `aria-description|Navega por las imágenes del producto`

== Registro de cambios ==

= 0.2.0 =
* Nueva característica: Múltiples formatos de registro de traducción con contextos separados
* Nueva característica: Mecanismo de reintento para traducciones fallidas
* Nueva característica: Botón para forzar actualización y limpiar todas las cachés
* Nueva característica: Prioridad de traducción configurable
* Información de depuración mejorada con registro detallado de métodos de traducción
* Robustez mejorada con métodos de respaldo en cascada para traducciones
* Mejora de la accesibilidad de la página de configuración
* Actualizada compatibilidad a WordPress 6.8

= 0.1.0 =
* Mejorada la accesibilidad de la página de configuración:
  * Estructura semántica mejorada con landmarks ARIA adecuados
  * Título de página mejorado para mejor identificación
  * Añadidos elementos de sección con encabezados semánticos
  * Mensajes de notificación mejorados para lectores de pantalla
  * Región de información de estado correctamente identificable
* Cambiado el título de la página de administración a "Configuración de AccessiTrans"

= 0.0.0 =
* Plugin renombrado de "Elementor ARIA Translator for WPML" a "AccessiTrans - ARIA Translator for WPML & Elementor"
* Actualizado el slug del plugin y referencias internas

= 2.0.2 =
* Añadido soporte para internacionalización
* Preparado el plugin para traducción con directorio de archivos de idioma
* Corregido el escape en páginas de administración
* Añadido enlace al sitio web y blog en la información del autor

= 2.0.1 =
* Corrección para el registro de traducciones en plantillas complejas
* Mejoras de rendimiento en los métodos de captura
* Añadidas mejoras en el registro de depuración

= 2.0.0 =
* Añadidos múltiples métodos de captura para detección exhaustiva
* Introducidos formatos de registro de traducción configurables
* Añadida configuración avanzada para optimización de rendimiento
* Mejorada compatibilidad con plantillas de Elementor y widgets globales
* Añadida página de configuración administrativa

= 1.3.0 =
* Refactorizado para capturar cadenas que no estaban siendo detectadas
* Varias preparaciones y mejoras

= 1.2.3 =
* Mejorada compatibilidad con Elementor 3.14+
* Correcciones menores de errores

= 1.2.2 =
* Soporte para atributos en Headers y Footers

= 1.2.1 =
* Añadido soporte para aria-valuetext

= 1.2.0 =
* Implementación del modo multilínea para múltiples atributos
* Mejora de rendimiento

= 1.1.0 =
* Añadido soporte para aria-description, aria-roledescription y aria-placeholder

= 1.0.0 =
* Versión inicial

== Aviso de actualización ==

= 0.2.0 =
Esta versión introduce múltiples formatos de traducción con contextos separados, un mecanismo de reintento para traducciones fallidas, una función para forzar actualizaciones y mayor robustez en la traducción de atributos ARIA.

= 0.1.0 =
Esta actualización mejora la accesibilidad de la página de configuración del plugin con mejor estructura semántica, landmarks ARIA y soporte para lectores de pantalla.

= 0.0.0 =
Esta actualización incluye un cambio de nombre a "AccessiTrans - ARIA Translator for WPML & Elementor".

= 2.0.2 =
Esta actualización añade soporte para internacionalización, preparando el plugin para su traducción a múltiples idiomas.

= 2.0.1 =
Esta actualización incluye mejoras de rendimiento y correcciones para el registro de traducciones en plantillas complejas.

= 2.0.0 =
Esta actualización añade múltiples métodos de captura, formatos de traducción configurables, configuración avanzada y compatibilidad mejorada con todos los tipos de contenido de Elementor.

== Autor ==

Desarrollado por Mario Germán Almonte Moreno:

* Miembro de IAAP (International Association of Accessibility Professionals)
* Certificado CPWA (CPACC y WAS)
* Profesor en el Curso de especialización en Accesibilidad Digital (Universidad de Lleida)
* 20 años de experiencia en ámbitos digitales y educativos