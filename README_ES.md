# Elementor ARIA Translator for WPML

[![WordPress Compatible](https://img.shields.io/badge/WordPress-6.7-green.svg)](https://wordpress.org/)
[![Elementor Compatible](https://img.shields.io/badge/Elementor-3.28.3-red.svg)](https://elementor.com/)
[![WPML Compatible](https://img.shields.io/badge/WPML-4.7.3-blue.svg)](https://wpml.org/)
[![Version](https://img.shields.io/badge/Version-2.0.2-purple.svg)]()

Plugin para WordPress que permite traducir atributos ARIA en sitios Elementor con WPML, mejorando la accesibilidad en entornos multilingües.

## Descripción

El plugin **Elementor ARIA Translator for WPML** facilita la traducción de atributos ARIA en sitios desarrollados con Elementor y WPML, garantizando que la información de accesibilidad esté disponible en todos los idiomas de tu sitio web.

### Atributos ARIA compatibles

El plugin permite traducir los siguientes atributos:

* `aria-label`: Para proporcionar un nombre accesible a un elemento
* `aria-description`: Para proporcionar una descripción accesible
* `aria-roledescription`: Para personalizar la descripción del rol de un elemento
* `aria-placeholder`: Para textos de ejemplo en campos de entrada
* `aria-valuetext`: Para proporcionar representación textual de valores numéricos

### Métodos de captura

El plugin ofrece varios métodos de captura para asegurar que todos los atributos ARIA sean detectados y estén disponibles para traducción:

1. **Captura total de HTML**: Captura todo el HTML de la página para encontrar atributos ARIA (altamente efectivo pero puede afectar al rendimiento)
2. **Filtro de contenido de Elementor**: Procesa el contenido generado por Elementor
3. **Procesamiento de templates de Elementor**: Procesa los datos de templates de Elementor
4. **Procesamiento de elementos individuales**: Procesa cada widget y elemento de Elementor individualmente

Estos métodos pueden ser activados o desactivados desde la página de configuración del plugin según las necesidades de tu sitio.

### Formatos de registro para traducción

El plugin admite múltiples formatos para registrar cadenas para traducción:

1. **Formato directo**: Registra el valor literal como nombre
2. **Formato con prefijo**: Registra con el formato `aria-atributo_valor`
3. **Formato con ID de elemento**: Registra con un formato que incluye el ID del elemento

### Compatibilidad

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

## Instalación

1. Descarga el archivo `elementor-aria-translator.zip` desde la página de releases de GitHub
2. Sube los archivos al directorio `/wp-content/plugins/elementor-aria-translator/` de tu WordPress o instala directamente a través de WordPress subiendo el archivo ZIP
3. Activa el plugin a través del menú 'Plugins' en WordPress
4. Ve a Ajustes → Elementor ARIA Translator para configurar las opciones del plugin
5. Configura los atributos ARIA en Elementor (ver instrucciones de uso)

## Uso

### Cómo añadir atributos ARIA en Elementor

1. Edita cualquier elemento en Elementor
2. Ve a la pestaña "Avanzado"
3. Busca la sección "Atributos personalizados" (Custom Attributes)
4. Añade los atributos ARIA que deseas traducir

### Formatos compatibles

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

### Cómo traducir los atributos

1. Una vez añadidos los atributos, guarda la página o template
2. Ve a WPML → String Translation (Traducción de cadenas)
3. Filtra por el contexto "Elementor ARIA Attributes"
4. Traduce las cadenas como harías con cualquier otro texto en WPML

**Nota:** Cada texto puede aparecer varias veces con diferentes identificadores. Esto es normal y forma parte del mecanismo que garantiza que las traducciones funcionen en todos los tipos de contenido y métodos de captura.

## Mejores prácticas

Para un rendimiento y eficiencia óptimos, sigue estas prácticas recomendadas:

1. **Navega por el sitio solo en el idioma principal** mientras generas cadenas para traducir. Esto evita que el plugin registre cadenas que ya hayan sido traducidas a través de otros sistemas.

2. **Desactiva los métodos de captura después de la configuración inicial**:
   * Una vez que hayas capturado todos los atributos ARIA para traducción, considera desactivar todos los métodos de captura y formatos de registro
   * Esto mejorará el rendimiento del sitio y evitará que se registren cadenas adicionales en WPML
   * Vuelve a activar los métodos de captura temporalmente cuando realices cambios en tu sitio que incluyan nuevos atributos ARIA

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

El plugin incluye una página de configuración que permite configurar los métodos de captura y otras opciones:

### Métodos de captura
* **Captura total de HTML**: Captura todo el HTML (más exhaustivo pero puede afectar al rendimiento)
* **Filtro de contenido de Elementor**: Procesa el contenido generado por Elementor
* **Procesar templates de Elementor**: Procesa los datos de templates de Elementor
* **Procesar elementos individuales**: Procesa cada widget de Elementor individualmente

### Formatos de registro
* **Formato directo**: Registrar el valor literal como nombre
* **Formato con prefijo**: Registrar con formato aria-atributo_valor
* **Formato con ID de elemento**: Registrar con formato incluyendo ID del elemento

### Configuración avanzada
* **Modo de depuración**: Activa el registro detallado de eventos (se almacena en wp-content/debug-aria-wpml.log)
* **Captura solo para administradores**: Solo procesa la captura total cuando un administrador está conectado

## Internacionalización

La versión 2.0.2 introduce soporte para internacionalización, preparando el plugin para su traducción a múltiples idiomas. Los archivos de traducción deben colocarse en el directorio `/languages`.

## Contribuciones

Las contribuciones son bienvenidas. Si quieres mejorar este plugin:

1. Haz un fork del repositorio
2. Crea una rama para tu característica (`git checkout -b feature/amazing-feature`)
3. Haz commit de tus cambios (`git commit -m 'Add some amazing feature'`)
4. Push a la rama (`git push origin feature/amazing-feature`)
5. Abre un Pull Request

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
* Sitio web y blog: [https://aprendizajeenred.es](https://aprendizajeenred.es)

---

[Documentation in English](https://github.com/marioalmonte/elementor-aria-translator/blob/main/README.md)