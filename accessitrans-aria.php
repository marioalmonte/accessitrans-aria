<?php
/**
 * Plugin Name: AccessiTrans - ARIA Translator for WPML & Elementor
 * Plugin URI: https://github.com/marioalmonte/accessitrans-aria
 * Description: Translate ARIA attributes in Elementor using WPML, improving the accessibility of your multilingual website. Developed by a certified Web Accessibility Professional (CPWA).
 * Version: 1.0.3
 * Author: Mario Germán Almonte Moreno
 * Author URI: https://www.linkedin.com/in/marioalmonte/
 * Text Domain: accessitrans-aria
 * Domain Path: /languages
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 5.6
 * Tested up to: 6.8
 * Requires PHP: 7.2
 */

if (!defined('ABSPATH')) {
    exit; // Salir si se accede directamente
}

// Definir constantes del plugin usando funciones seguras
define('ACCESSITRANS_VERSION', '1.0.3');
define('ACCESSITRANS_PATH', plugin_dir_path(__FILE__));
define('ACCESSITRANS_URL', plugin_dir_url(__FILE__));

// Incluir archivos necesarios
require_once ACCESSITRANS_PATH . 'includes/class-accessitrans-core.php';
require_once ACCESSITRANS_PATH . 'includes/class-accessitrans-admin.php';
require_once ACCESSITRANS_PATH . 'includes/class-accessitrans-capture.php';
require_once ACCESSITRANS_PATH . 'includes/class-accessitrans-translator.php';
require_once ACCESSITRANS_PATH . 'includes/class-accessitrans-diagnostics.php';

// Inicializar el plugin
add_action('plugins_loaded', function() {
    AccessiTrans_ARIA_Translator::get_instance();
}, 20); // Prioridad 20 para asegurarnos que WPML y Elementor estén cargados