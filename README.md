# Elementor ARIA Translator for WPML

[![WordPress Compatible](https://img.shields.io/badge/WordPress-6.7-green.svg)](https://wordpress.org/)
[![Elementor Compatible](https://img.shields.io/badge/Elementor-3.28.3-red.svg)](https://elementor.com/)
[![WPML Compatible](https://img.shields.io/badge/WPML-4.7.3-blue.svg)](https://wpml.org/)

WordPress plugin that allows translation of ARIA attributes in Elementor sites with WPML, improving accessibility in multilingual environments.

## Description

The **Elementor ARIA Translator for WPML** plugin facilitates the translation of ARIA attributes in sites developed with Elementor and WPML, ensuring that accessibility information is available in all languages of your website.

### Compatible ARIA attributes

The plugin allows you to translate the following attributes:

* `aria-label`: To provide an accessible name for an element
* `aria-description`: To provide an accessible description
* `aria-roledescription`: To customize the role description of an element
* `aria-placeholder`: For placeholder text in input fields
* `aria-valuetext`: To provide textual representation of numeric values

### Compatibility

Works with all types of Elementor content:

* Regular pages
* Templates
* Global sections
* Headers and footers
* Popups and other dynamic elements

**Tested with:**
* WordPress 6.7
* Elementor 3.28.3
* WPML Multilingual CMS 4.7.3
* WPML String Translation 3.3.2

## Installation

1. Clone this repository or download the ZIP
2. Upload the files to the `/wp-content/plugins/elementor-aria-translator/` directory of your WordPress installation
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Configure ARIA attributes in Elementor (see usage instructions)

## Usage

### How to add ARIA attributes in Elementor

1. Edit any element in Elementor
2. Go to the "Advanced" tab
3. Find the "Custom Attributes" section
4. Add the ARIA attributes you want to translate

### Compatible formats

Elementor indicates: "Set custom attributes for the wrapper element. Each attribute in a separate line. Separate attribute key from the value using `|` character."

You can add ARIA attributes in two ways:

**Basic format (one attribute per line):**
```
aria-label|Text to translate
```

**Multiline format (multiple attributes):**
```
aria-label|Text to translate
aria-description|Another description
```

This will generate the corresponding HTML attributes in the frontend:
`aria-label="Text to translate" aria-description="Another description"`

### How to translate the attributes

1. Once you've added the attributes, save the page or template
2. Go to WPML → String Translation
3. Filter by the context "Elementor ARIA Attributes"
4. Translate the strings as you would with any other text in WPML

## Contributions

Contributions are welcome. If you want to improve this plugin:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## License

Distributed under the GPL v2 or later license. See `LICENSE` for more information.

## Author

Developed by Mario Germán Almonte Moreno:

* Member of IAAP (International Association of Accessibility Professionals)
* CPWA Certified (CPACC and WAS)
* Professor in the Digital Accessibility Specialization Course (University of Lleida)
* 20 years of experience in digital and educational fields

---

[Documentación en español](https://github.com/marioalmonte/elementor-aria-translator/blob/main/readme_es.md)