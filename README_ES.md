# Elementor ARIA Translator for WPML

[![WordPress Compatible](https://img.shields.io/badge/WordPress-6.7-green.svg)](https://wordpress.org/)
[![Elementor Compatible](https://img.shields.io/badge/Elementor-3.28.3-red.svg)](https://elementor.com/)
[![WPML Compatible](https://img.shields.io/badge/WPML-4.7.3-blue.svg)](https://wpml.org/)

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

1. Clona este repositorio o descarga el ZIP
2. Sube los archivos al directorio `/wp-content/plugins/elementor-aria-translator/` de tu WordPress
3. Activa el plugin a través del menú 'Plugins' en WordPress
4. Configura los atributos ARIA en Elementor (ver instrucciones de uso)

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

### Cómo traducir los atributos

1. Una vez añadidos los atributos, guarda la página o template
2. Ve a WPML → String Translation
3. Filtra por el contexto "Elementor ARIA Attributes"
4. Traduce las cadenas como harías con cualquier otro texto en WPML

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

---

[Documentación en inglés](https://github.com/marioalmonte/elementor-aria-translator/blob/main/readme.txt)