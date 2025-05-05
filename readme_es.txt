=== AccessiTrans - ARIA Translator for WPML & Elementor ===
Contributors: marioalmonte
Tags: accessibility, aria, elementor, wpml, translation
Requires at least: 5.6
Tested up to: 6.8
Stable tag: 1.0.0
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
* Mecanismo de reintento para traducciones fallidas
* Función para forzar la actualización y limpiar cachés
* Modo de depuración para solución de problemas
* Configuraciones de optimización de rendimiento
* Sistema de caché de traducciones para mejorar rendimiento
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

= Configuración avanzada =

* **Reintentar traducciones fallidas**: Reintenta automáticamente las traducciones que fallaron en el primer intento
* **Modo de depuración**: Activa el registro detallado de eventos
* **Captura solo para administradores**: Limita los métodos de captura intensivos en recursos a usuarios administradores
* **Captura solo en idioma principal**: Solo captura cadenas al navegar en el idioma predeterminado

= Compatibilidad =

Funciona con todo tipo de contenido de Elementor:

* Páginas regulares
* Templates
* Secciones globales
* Headers y footers
* Popups y otros elementos dinámicos

**Versiones probadas:**
* WordPress 6.8
* Elementor 3.28.4
* WPML Multilingual CMS 4.7.4
* WPML String Translation 3.3.3

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

= ¿Por qué veo entradas duplicadas en WPML String Translation? =

El plugin registra las cadenas utilizando diferentes formatos para garantizar la máxima compatibilidad con todos los tipos de contenido de Elementor. Solo necesitas traducir cada texto único una vez.

= ¿Este plugin ralentizará mi sitio web? =

El plugin incluye varias opciones de optimización de rendimiento. Por defecto, el método de captura más intensivo en recursos (Captura total de HTML) solo se ejecuta para usuarios administradores. Puedes ajustar estas configuraciones en la página de configuración del plugin.

= ¿Puedo usar este plugin con Elementor Free? =

Sí, el plugin funciona tanto con Elementor Free como con Elementor Pro.

= ¿Qué debo hacer si las traducciones no funcionan correctamente? =

Prueba a utilizar el botón "Forzar actualización" en la configuración del plugin. Esto limpiará todas las cachés y reinicializará el sistema de traducción.

= ¿Necesito mantener todos los métodos de captura habilitados? =

No. Se recomienda habilitar todos los métodos durante la configuración inicial para capturar todos los atributos ARIA. Después de eso, puedes deshabilitarlos para mejorar el rendimiento y solo reactivarlos cuando añadas nuevos atributos ARIA a tu sitio.

= ¿Cómo sé si los atributos ARIA se están traduciendo correctamente? =

Puedes verificarlo:
1. Añadiendo atributos ARIA en Elementor
2. Traduciéndolos en WPML String Translation
3. Cambiando a un idioma diferente en tu frontend
4. Inspeccionando el elemento con las herramientas de desarrollo del navegador para ver si aparece el texto traducido

= ¿Funciona con widgets globales y plantillas de Elementor? =

Sí, el plugin funciona con todos los tipos de contenido de Elementor, incluidos widgets globales, plantillas y elementos dinámicos.

== Capturas de pantalla ==

1. Interfaz de configuración mostrando los diferentes métodos de captura
2. Pestaña Avanzado de Elementor mostrando dónde añadir atributos ARIA
3. Proceso de transformación de código mostrando cómo se procesan los atributos ARIA
4. Interfaz de WPML String Translation mostrando atributos ARIA listos para traducción
5. Interfaz de herramientas de diagnóstico para solucionar problemas de traducción

== Instrucciones de uso ==

= Cómo añadir atributos ARIA en Elementor =

1. Edita cualquier elemento en Elementor
2. Ve a la pestaña "Avanzado"
3. Busca la sección "Atributos personalizados"
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
3. Filtra por el contexto "AccessiTrans ARIA Attributes"
4. Traduce las cadenas como harías con cualquier otro texto en WPML

= Mejores prácticas para un rendimiento óptimo =

Para la mejor experiencia y rendimiento del sitio web, sigue estas recomendaciones:

1. **Navega por el sitio solo en el idioma principal** mientras generas cadenas para traducción. Esto evita el registro de cadenas duplicadas.

2. **Utiliza la función Forzar actualización** cuando las traducciones no aparezcan como se esperaba.

3. **Desactiva los métodos de captura después de la configuración inicial**:
   * Una vez que hayas capturado todos los atributos ARIA para traducción, recomendamos desactivar todos los métodos de captura
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

= 1.0.1 =
* Mejora de seguridad: Implementada sanitización adecuada de inputs
* Optimizado el sistema de logging para entornos de producción
* Testeado con WPML 4.7.4 y String Translations 3.3.3
* Corregido el uso de register_setting() para mejor seguridad
* Implementado correcto encolado de JavaScript y CSS:
  - Eliminados scripts y estilos inline del código PHP
  - Creada estructura de carpetas assets/css y assets/js
  - Extraídos estilos a admin-styles.css y scripts a admin-scripts.js
  - Implementados wp_enqueue_style() y wp_enqueue_script()
  - Usado wp_localize_script() para pasar variables a JavaScript
* Corrección de ubicación de archivos/directorios:
  - Reemplazado uso directo de WP_CONTENT_DIR para archivos de log
  - Implementado wp_upload_dir() para determinar ubicación correcta
  - Creado directorio específico 'accessitrans-logs' en uploads
* Eliminación de load_plugin_textdomain():
  - Eliminada función innecesaria desde WordPress 4.6+
  - Mantenida estructura de carpetas /languages para traducciones
* Actualizada la documentación

= 1.0.0 =
* Primera versión pública
* Corregidos y actualizados los archivos de traducción
* Listo para el repositorio de WordPress.org

= 0.2.5 =
* Añadido interruptor principal para activar/desactivar el escaneo de nuevas cadenas
* Mejorada toda la interfaz de administración con estructura semántica accesible 
* Implementados anuncios para lectores de pantalla en procesos interactivos
* Rediseñada la interfaz con fieldset/legend reemplazando tablas
* Mejorados los estilos visuales y la activación/desactivación de campos dependientes
* Optimizado el rendimiento cuando el escaneo está desactivado

= 0.2.4 =
* Mejorado sistema de verificación de idioma para captura de cadenas
* Implementado sistema robusto para prevenir duplicados en WPML
* Optimizado el sistema de caché con persistencia mejorada y control de tamaño
* Corregido problema de entradas duplicadas en la herramienta de diagnóstico
* Mejorado rendimiento general reduciendo consultas a la base de datos

= 0.2.3 =
* Reestructuración del sistema de archivos del plugin

= 0.2.3 =
* Añadido sistema de caché de traducciones persistente para mejorar el rendimiento
* Mejorado algoritmo de búsqueda de traducciones con múltiples métodos alternativos
* Mejorada la accesibilidad de la interfaz de administración para lectores de pantalla
* Añadida herramienta de diagnóstico para solucionar problemas de traducción
* Solucionados problemas con la detección de atributos en plantillas complejas

= 0.2.2 =
* Añadida detección del atributo aria-valuetext
* Mejorado soporte para plantillas de Elementor y widgets globales
* Mejorada compatibilidad con las últimas versiones de WPML y Elementor

= 0.2.1 =
* Solucionados problemas con el registro de cadenas en contextos específicos
* Mejorado manejo de errores y registro de depuración
* Mejoras menores en la interfaz de usuario de la página de configuración

= 0.2.0 =
* Añadido mecanismo de reintento para traducciones fallidas
* Añadido botón para forzar actualización y limpiar todas las cachés
* Mejorada información de depuración con registro detallado
* Mejorada compatibilidad con WordPress 6.8

= 0.1.0 =
* Mejorada la accesibilidad de la página de configuración
* Mejorada estructura semántica con landmarks ARIA adecuados
* Mejorado título de página para mejor identificación
* Añadidos elementos de sección con encabezados semánticos

= 0.0.0 =
* Versión inicial con funcionalidad básica
* Soporte para traducir aria-label, aria-description, aria-roledescription y aria-placeholder
* Múltiples métodos de captura para detección exhaustiva
* Integración con WPML String Translation
* Página de configuración administrativa
* Modo de depuración para solución de problemas
* Compatibilidad con todos los tipos de contenido de Elementor

== Autor ==

Desarrollado por Mario Germán Almonte Moreno:

* Miembro de IAAP (International Association of Accessibility Professionals)
* Certificado CPWA (CPACC y WAS)
* Profesor en el Curso de especialización en Accesibilidad Digital (Universidad de Lleida)
* 20 años de experiencia en ámbitos digitales y educativos