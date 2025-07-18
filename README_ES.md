# AccessiTrans - ARIA Translator for WPML & Elementor

[![WordPress Compatible](https://img.shields.io/badge/WordPress-6.8-green.svg)](https://wordpress.org/)
[![Elementor Compatible](https://img.shields.io/badge/Elementor-3.28.4-red.svg)](https://elementor.com/)
[![WPML Compatible](https://img.shields.io/badge/WPML-4.7.4-blue.svg)](https://wpml.org/)
[![Version](https://img.shields.io/badge/Version-1.0.5-purple.svg)]()

Plugin de WordPress que permite traducir atributos ARIA en sitios Elementor con WPML, mejorando la accesibilidad en entornos multilingües.

![AccessiTrans. ARIA Translator for WPML & Elementor](/.github/assets/banner-accessitrans.png)

## Descripción

El plugin **AccessiTrans - ARIA Translator for WPML & Elementor** facilita la traducción de atributos ARIA en sitios desarrollados con Elementor y WPML, garantizando que la información de accesibilidad esté disponible en todos los idiomas de tu sitio web.

### Atributos ARIA compatibles

El plugin permite traducir los siguientes atributos:

* `aria-label`: Para proporcionar un nombre accesible a un elemento
* `aria-description`: Para proporcionar una descripción accesible
* `aria-roledescription`: Para personalizar la descripción del rol de un elemento
* `aria-placeholder`: Para textos de ejemplo en campos de entrada
* `aria-valuetext`: Para proporcionar representación textual de valores numéricos

### Métodos de captura

El plugin ofrece varios métodos de captura para asegurar que todos los atributos ARIA sean detectados y disponibles para traducción:

1. **Captura total de HTML**: Captura todo el HTML de la página para encontrar atributos ARIA (altamente efectivo pero puede afectar al rendimiento)
2. **Filtro de contenido de Elementor**: Procesa el contenido generado por Elementor
3. **Procesamiento de templates de Elementor**: Procesa los datos de templates de Elementor
4. **Procesamiento de elementos individuales**: Procesa cada widget y elemento de Elementor individualmente

![Página de configuración del plugin mostrando los métodos de captura disponibles](/.github/assets/screenshots/captura-configuracion-metodos-de-captura.png)

Estos métodos pueden activarse o desactivarse desde la página de configuración del plugin según las necesidades de tu sitio.

### Formatos de registro para traducción

El plugin registra las cadenas para traducción utilizando el formato `aria-atributo_valor` para obtener la máxima robustez y compatibilidad con el sistema de traducción de cadenas de WPML.

![Interfaz de WPML String Translation con atributos ARIA listos para traducción](/.github/assets/screenshots/screenshot-string-translation-interface.png)

### Características adicionales

* **Mecanismo de reintento de traducciones**: Reintenta automáticamente las traducciones fallidas para mejorar la tasa de éxito
* **Función de actualización forzada**: Botón para limpiar todas las cachés y forzar la actualización de traducciones
* **Modo de depuración**: Registro detallado para solución de problemas
* **Sistema de caché de traducciones**: Sistema de caché persistente que mejora el rendimiento al almacenar las traducciones encontradas

![Interfaz de herramientas de diagnóstico para solucionar problemas de traducción](/.github/assets/screenshots/captura-herramientas-diagnostico.png)

### Compatibilidad

Funciona con todo tipo de contenido de Elementor:

* Páginas regulares
* Templates
* Secciones globales
* Headers y footers
* Popups y otros elementos dinámicos

**Probado con:**
* WordPress 6.8
* Elementor 3.28.4
* WPML Multilingual CMS 4.7.4
* WPML String Translation 3.3.3

## Instalación

1. Descarga el archivo `accessitrans-aria.zip` desde la página de releases de GitHub
2. Sube los archivos al directorio `/wp-content/plugins/accessitrans-aria/` de tu instalación de WordPress o instala directamente a través de WordPress subiendo el archivo ZIP
3. Activa el plugin desde el menú 'Plugins' en WordPress
4. Ve a Ajustes → AccessiTrans para configurar las opciones del plugin
5. Configura los atributos ARIA en Elementor (ver instrucciones de uso)

## Uso

### Cómo añadir atributos ARIA en Elementor

1. Edita cualquier elemento en Elementor
2. Ve a la pestaña "Avanzado"
3. Busca la sección "Atributos personalizados"
4. Añade los atributos ARIA que quieras traducir

![Interfaz de Elementor mostrando cómo añadir atributos ARIA personalizados](/.github/assets/screenshots/screenshot-set-custom-aria-attributes.png)

### Formatos compatibles

Elementor indica: "Configura atributos personalizados para el elemento contenedor. Cada atributo en una línea separada. Separa la clave del atributo del valor usando el carácter `|`."

Puedes añadir atributos ARIA de dos formas:

**Formato básico (un atributo por línea):**
```
aria-label|Texto a traducir
```

**Formato multilínea (múltiples atributos):**
```
aria-label|Texto a traducir
aria-description|Otra descripción
```

Esto generará los atributos HTML correspondientes en el frontend:
`aria-label="Texto a traducir" aria-description="Otra descripción"`

### Cómo traducir los atributos

1. Una vez añadidos los atributos, guarda la página o template
2. Ve a WPML → Traducción de cadenas
3. Filtra por el contexto "AccessiTrans ARIA Attributes"
4. Traduce las cadenas como lo harías normalmente en WPML

**Nota:** Cada texto puede aparecer varias veces con diferentes identificadores. Esto es normal y forma parte del mecanismo que asegura que las traducciones funcionen en todos los tipos de contenido y métodos de captura.

![Representación visual de cómo se transforman y procesan los atributos ARIA](/.github/assets/screenshots/screenshot-code-transformation.png)

## Mejores prácticas

Para un rendimiento y eficiencia óptimos, sigue estas prácticas recomendadas:

1. **Navega por el sitio solo en el idioma principal** mientras generas cadenas para traducción. Esto evita que el plugin registre cadenas que ya han sido traducidas a través de otros sistemas.

2. **Utiliza la función "Forzar actualización" cuando sea necesario**:
   * Si una traducción no aparece como se esperaba
   * Después de hacer cambios significativos en tu sitio
   * Al añadir nuevos atributos ARIA a elementos existentes

3. **Desactiva los métodos de captura después de la configuración inicial**:
   * Una vez que hayas capturado todos los atributos ARIA para traducción, considera desactivar todos los métodos de captura
   * Esto mejorará el rendimiento del sitio y evitará que se registren cadenas adicionales en WPML
   * Vuelve a activar los métodos de captura temporalmente cuando hagas cambios en tu sitio que incluyan nuevos atributos ARIA

### Ejemplos prácticos

**Para un botón de menú:**
```
aria-label|Abrir menú
```

**Para un enlace de teléfono:**
```
aria-label|Llamar a atención al cliente
```

**Para un icono sin texto:**
```
aria-label|Enviar email
```

**Para un slider:**
```
aria-label|Galería de imágenes
aria-description|Navega por las imágenes del producto
```

## Configuración del plugin

El plugin incluye una página de configuración que te permite configurar los métodos de captura y otras opciones:

### Métodos de captura
* **Captura total de HTML**: Captura todo el HTML (más exhaustivo pero puede afectar al rendimiento)
* **Filtro de contenido de Elementor**: Procesa el contenido generado por Elementor
* **Procesar templates de Elementor**: Procesa los datos de templates de Elementor
* **Procesar elementos individuales**: Procesa cada widget y elemento de Elementor individualmente

### Configuración avanzada
* **Reintentar traducciones fallidas**: Intenta volver a aplicar traducciones que fallaron en el primer intento
* **Prioridad de traducción**: Prioridad de los filtros de traducción (valores más altos se ejecutan más tarde)
* **Modo de depuración**: Habilita el registro detallado de eventos (almacenado en uploads/accessitrans-logs)
* **Captura solo para administradores**: Solo procesa la captura completa cuando un administrador está conectado
* **Captura solo en idioma principal**: Solo captura cadenas al navegar en el idioma predeterminado

## Internacionalización

El plugin incluye soporte para internacionalización, lo que lo hace listo para su traducción a múltiples idiomas. Los archivos de traducción deben colocarse en el directorio `/languages`.

## Registro de cambios

### 1.0.3/1.0.4/1.0.5
* Mejoras de internacionalización: Actualizadas todas las cadenas del plugin a inglés para cumplir con las directrices de WordPress.org
* Plugin completamente compatible con el sistema de traducción de WordPress.org
* Mejorado el flujo de trabajo de traducción para un mejor soporte de idiomas
* Refinamientos menores del código para mejor mantenibilidad

### 1.0.2
* Corrección de seguridad: Implementado sanitize_callback en register_setting()
* Mejorada la estructura de sanitización para cumplir con las mejores prácticas de WordPress

### 1.0.1
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

### 1.0.0
* Primera versión pública
* Corregidos y actualizados los archivos de traducción

### 0.2.5
* Añadido interruptor principal para activar/desactivar el escaneo de nuevas cadenas
* Mejorada toda la interfaz de administración con estructura semántica accesible 
* Implementados anuncios para lectores de pantalla en procesos interactivos
* Rediseñada la interfaz con fieldset/legend reemplazando tablas
* Mejorados los estilos visuales y la activación/desactivación de campos dependientes
* Optimizado el rendimiento cuando el escaneo está desactivado

### 0.2.4
* Mejorado sistema de verificación de idioma para captura de cadenas
* Implementado sistema robusto para prevenir duplicados en WPML
* Optimizado el sistema de caché con persistencia mejorada y control de tamaño
* Corregido problema de entradas duplicadas en la herramienta de diagnóstico
* Mejorado rendimiento general reduciendo consultas a la base de datos

### 0.2.3
* Añadido sistema de caché de traducciones persistente para mejorar el rendimiento
* Mejorado algoritmo de búsqueda de traducciones con múltiples métodos alternativos
* Mejorada la accesibilidad de la interfaz de administración para lectores de pantalla
* Añadida herramienta de diagnóstico para solucionar problemas de traducción
* Solucionados problemas con la detección de atributos en plantillas complejas

### 0.2.2
* Añadida detección del atributo aria-valuetext
* Mejorado soporte para plantillas de Elementor y widgets globales
* Mejorada compatibilidad con las últimas versiones de WPML y Elementor

### 0.2.1
* Solucionados problemas con el registro de cadenas en contextos específicos
* Mejorado manejo de errores y registro de depuración
* Mejoras menores en la interfaz de usuario de la página de configuración

### 0.2.0
* Añadido mecanismo de reintento para traducciones fallidas
* Añadido botón para forzar actualización y limpiar todas las cachés
* Mejorada información de depuración con registro detallado
* Mejorada compatibilidad con WordPress 6.8

### 0.1.0
* Mejorada la accesibilidad de la página de configuración
* Mejorada estructura semántica con landmarks ARIA adecuados
* Mejorado título de página para mejor identificación
* Añadidos elementos de sección con encabezados semánticos

### 0.0.0
* Versión inicial con funcionalidad básica
* Soporte para traducir aria-label, aria-description, aria-roledescription y aria-placeholder
* Múltiples métodos de captura para detección exhaustiva
* Integración con WPML String Translation
* Página de configuración administrativa
* Modo de depuración para solución de problemas
* Compatibilidad con todos los tipos de contenido de Elementor

## Contribuciones

Las contribuciones son bienvenidas. Si quieres mejorar este plugin:

1. Haz un fork del repositorio
2. Crea una rama para tu función (`git checkout -b feature/funcion-increible`)
3. Confirma tus cambios (`git commit -m 'Añadir alguna función increíble'`)
4. Envía la rama (`git push origin feature/funcion-increible`)
5. Abre una Pull Request

## Licencia

Distribuido bajo la licencia GPL v2 o posterior. Ver `LICENSE` para más información.

## Autor

Desarrollado por Mario Germán Almonte Moreno:

* Miembro de IAAP (International Association of Accessibility Professionals)
* Certificado CPWA (CPACC y WAS)
* Profesor en el Curso de especialización en Accesibilidad Digital (Universidad de Lleida)
* 20 años de experiencia en ámbitos digitales y educativos

Servicios profesionales:
* Auditorías de accesibilidad web según EN 301 549 (WCAG 2.2, ATAG 2.0)
* Formación y consultoría en Accesibilidad Web y eLearning
* Asesoramiento en implementación de tecnologías eLearning

Contacto:
* LinkedIn: [https://www.linkedin.com/in/marioalmonte/](https://www.linkedin.com/in/marioalmonte/)
* Web y Blog: [https://aprendizajeenred.es](https://aprendizajeenred.es)

---

[Documentation in English](https://github.com/marioalmonte/accessitrans-aria/blob/main/README.md)