=== Elementor ARIA Translator for WPML ===
Contributors: marioalmonte
Tags: accessibility, aria, elementor, wpml, translation, wcag
Requires at least: 5.6
Tested up to: 6.7
Stable tag: 1.2.3
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Permite traducir atributos ARIA en Elementor utilizando WPML, mejorando la accesibilidad de tu sitio web multilingüe.

== Description ==

El plugin Elementor ARIA Translator for WPML facilita la traducción de atributos ARIA en sitios desarrollados con Elementor y WPML, garantizando que la información de accesibilidad esté disponible en todos los idiomas de tu sitio web.

= Atributos ARIA compatibles =

El plugin permite traducir los siguientes atributos:

* `aria-label`: Para proporcionar un nombre accesible a un elemento
* `aria-description`: Para proporcionar una descripción accesible
* `aria-roledescription`: Para personalizar la descripción del rol de un elemento
* `aria-placeholder`: Para textos de ejemplo en campos de entrada
* `aria-valuetext`: Para proporcionar representación textual de valores numéricos

= Compatibilidad =

Funciona con todo tipo de contenido de Elementor:

* Páginas regulares
* Templates
* Secciones globales
* Headers y footers
* Popups y otros elementos dinámicos

Probado con:
* WordPress 6.7
* Elementor 3.28.3
* WPML Multilingual CMS 4.7.3
* WPML String Translation 3.3.2

== Instalación ==

1. Sube los archivos del plugin al directorio `/wp-content/plugins/elementor-aria-translator/`
2. Activa el plugin a través del menú 'Plugins' en WordPress
3. Configura los atributos ARIA en Elementor (ver instrucciones de uso)

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
2. Ve a WPML → String Translation
3. Filtra por el contexto "Elementor ARIA Attributes"
4. Traduce las cadenas como harías con cualquier otro texto en WPML

**Nota:** Cada texto aparecerá varias veces con diferentes identificadores. Esto es normal y forma parte del mecanismo que garantiza que las traducciones funcionen en todos los tipos de contenido.

= Ejemplos prácticos =

**Para un botón de menú:**
* Atributo: `aria-label|Abrir menú`

**Para un enlace de teléfono:**
* Atributo: `aria-label|Llamar al teléfono de atención al cliente`

**Para un icono sin texto:**
* Atributo: `aria-label|Enviar un email`

== Preguntas frecuentes ==

= ¿Funciona con otros constructores de páginas además de Elementor? =

No, actualmente el plugin está diseñado específicamente para trabajar con Elementor.

= ¿Es compatible con la última versión de WPML? =

Sí, el plugin ha sido probado con la versión 4.7.3 de WPML Multilingual CMS y 3.3.2 de WPML String Translation.

== Autor ==

Desarrollado por Mario Germán Almonte Moreno:

* Miembro de la IAAP (International Association of Accessibility Professionals)
* Certificado CPWA (CPACC y WAS)
* Profesor en el Curso de especialización en Accesibilidad Digital (Universidad de Lleida)
* 20 años de experiencia en ámbitos digitales y educativos

Servicios profesionales:
* Auditorías de accesibilidad web según EN 301 549 (WCAG 2.2, ATAG 2.0)
* Formación y consultoría en Accesibilidad Web y eLearning
* Asesoramiento en implementación de tecnologías eLearning

Contacto:
* Email: mario.almonte@aprendizajeenred.com
* Web: https://aprendizajeenred.es
* LinkedIn: https://www.linkedin.com/in/marioalmonte/

== Changelog ==

= 1.2.3 =
* Mejora de la compatibilidad con Elementor 3.14+
* Corrección de errores menores

= 1.2.2 =
* Soporte para atributos en Headers y Footers

= 1.2.1 =
* Añadido soporte para aria-valuetext

= 1.2.0 =
* Implementación de modo multilínea para múltiples atributos
* Mejora del rendimiento

= 1.1.0 =
* Añadido soporte para aria-description, aria-roledescription y aria-placeholder

= 1.0.0 =
* Versión inicial