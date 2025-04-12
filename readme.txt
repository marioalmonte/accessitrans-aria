=== Elementor ARIA Translator for WPML ===
Contributors: marioalmonte
Tags: accessibility, aria, elementor, wpml, translation, wcag
Requires at least: 5.6
Tested up to: 6.7
Stable tag: 1.2.3
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Translate ARIA attributes in Elementor using WPML, improving the accessibility of your multilingual website.

== Description ==

The Elementor ARIA Translator for WPML plugin facilitates the translation of ARIA attributes in sites developed with Elementor and WPML, ensuring that accessibility information is available in all languages of your website.

= Compatible ARIA attributes =

The plugin allows you to translate the following attributes:

* `aria-label`: To provide an accessible name for an element
* `aria-description`: To provide an accessible description
* `aria-roledescription`: To customize the role description of an element
* `aria-placeholder`: For placeholder text in input fields
* `aria-valuetext`: To provide textual representation of numeric values

= Compatibility =

Works with all types of Elementor content:

* Regular pages
* Templates
* Global sections
* Headers and footers
* Popups and other dynamic elements

Tested with:
* WordPress 6.7
* Elementor 3.28.3
* WPML Multilingual CMS 4.7.3
* WPML String Translation 3.3.2

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/elementor-aria-translator/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Configure ARIA attributes in Elementor (see usage instructions)

== Usage Instructions ==

= How to add ARIA attributes in Elementor =

1. Edit any element in Elementor
2. Go to the "Advanced" tab
3. Find the "Custom Attributes" section
4. Add the ARIA attributes you want to translate

= Compatible formats =

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

= How to translate the attributes =

1. Once you've added the attributes, save the page or template
2. Go to WPML → String Translation
3. Filter by the context "Elementor ARIA Attributes"
4. Translate the strings as you would with any other text in WPML

**Note:** Each text will appear multiple times with different identifiers. This is normal and part of the mechanism that ensures translations work in all content types.

= Practical examples =

**For a menu button:**
* Attribute: `aria-label|Open menu`

**For a phone link:**
* Attribute: `aria-label|Call customer service phone`

**For an icon without text:**
* Attribute: `aria-label|Send an email`

== Frequently Asked Questions ==

= Does it work with other page builders besides Elementor? =

No, currently the plugin is specifically designed to work with Elementor.

= Is it compatible with the latest version of WPML? =

Yes, the plugin has been tested with version 4.7.3 of WPML Multilingual CMS and 3.3.2 of WPML String Translation.

== Author ==

Developed by Mario Germán Almonte Moreno:

* Member of IAAP (International Association of Accessibility Professionals)
* CPWA Certified (CPACC and WAS)
* Professor in the Digital Accessibility Specialization Course (University of Lleida)
* 20 years of experience in digital and educational fields

Professional services:
* Web accessibility audits according to EN 301 549 (WCAG 2.2, ATAG 2.0)
* Training and consulting in Web Accessibility and eLearning
* Advice on implementing eLearning technologies

Contact:
* Email: mario.almonte@aprendizajeenred.com
* Web: https://aprendizajeenred.es
* LinkedIn: https://www.linkedin.com/in/marioalmonte/

== Changelog ==

= 1.2.3 =
* Improved compatibility with Elementor 3.14+
* Minor bug fixes

= 1.2.2 =
* Support for attributes in Headers and Footers

= 1.2.1 =
* Added support for aria-valuetext

= 1.2.0 =
* Implementation of multiline mode for multiple attributes
* Performance improvement

= 1.1.0 =
* Added support for aria-description, aria-roledescription and aria-placeholder

= 1.0.0 =
* Initial release