<?php
/**
 * Plugin Name: Elementor ARIA Translator for WPML
 * Plugin URI: https://github.com/marioalmonte/elementor-aria-translator
 * Description: Permite traducir atributos ARIA en Elementor utilizando WPML. Desarrollado por un profesional certificado en Accesibilidad Web (CPWA). Probado con WordPress 6.7, Elementor 3.28.3, WPML 4.7.3 y WPML ST 3.3.2.
 * Version: 1.3.0
 * Author: Mario Germán Almonte Moreno
 * Author URI: https://www.linkedin.com/in/marioalmonte/
 * Text Domain: elementor-aria-translator
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if (!defined('ABSPATH')) {
    exit; // Salir si se accede directamente
}

class WPML_Aria_Translator {
    
    // Singleton
    private static $instance = null;
    
    // Contexto único para todas las traducciones ARIA
    private $context = 'Elementor ARIA Attributes';
    private $debug = true;
    
    // Lista de atributos ARIA que contienen texto traducible
    private $traducible_attrs = [
        'aria-label',
        'aria-description',
        'aria-roledescription',
        'aria-placeholder',
        'aria-valuetext'
    ];
    
    // Constructor privado
    private function __construct() {
        // Verificar dependencias básicas
        if (!did_action('elementor/loaded') || !defined('ICL_SITEPRESS_VERSION')) {
            return;
        }
        
        // MÉTODO 1: Capturar el HTML completo de la página para buscar atributos ARIA
        add_action('wp_footer', [$this, 'capturar_todo_html'], 999);
        
        // MÉTODO 2: Hook para procesar el contenido de Elementor
        add_filter('elementor/frontend/the_content', [$this, 'capturar_aria_en_content'], 999);
        
        // MÉTODO 3: Hook para traducir el contenido final
        add_filter('elementor/frontend/the_content', [$this, 'translate_aria_attributes'], 1000);
        add_filter('the_content', [$this, 'translate_aria_attributes'], 1000);
        
        // MÉTODO 4: Hooks de Elementor para capturar widgets y elementos
        add_action('elementor/frontend/widget/before_render_content', [$this, 'process_element_attributes'], 10, 1);
        add_action('elementor/frontend/before_render', [$this, 'process_element_attributes'], 10, 1);
        
        // MÉTODO 5: Hook para templates de Elementor
        add_action('elementor/frontend/builder_content_data', [$this, 'process_template_data'], 10, 2);
        
        // Debug
        if ($this->debug) $this->log_debug('Plugin inicializado - Versión captura total');
    }
    
    // Método singleton
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Captura el HTML completo de la página para buscar atributos ARIA
     * Este método es muy agresivo pero efectivo para encontrar todos los atributos
     */
    public function capturar_todo_html() {
        // Solo ejecutamos esto cuando el usuario está logueado como admin
        if (!is_admin() && is_user_logged_in() && current_user_can('manage_options')) {
            // Iniciamos la captura de salida
            ob_start();
            
            // Al finalizar la página, procesamos el HTML completo
            add_action('shutdown', function() {
                $html_completo = ob_get_clean();
                if (!empty($html_completo)) {
                    $this->extract_aria_from_html($html_completo);
                    echo $html_completo; // Devolvemos el HTML al navegador
                }
            }, 0);
        }
    }
    
    /**
     * Extrae y registra todos los atributos ARIA del HTML
     */
    private function extract_aria_from_html($html) {
        if (empty($html) || !is_string($html)) {
            return;
        }
        
        if ($this->debug) {
            $this->log_debug("Procesando HTML completo para buscar atributos ARIA");
        }
        
        foreach ($this->traducible_attrs as $attr) {
            // Patrón para buscar el atributo con cualquier tipo de comillas
            $pattern = '/' . preg_quote($attr, '/') . '\s*=\s*([\'"])((?:(?!\1).)*)\1/is';
            
            if (preg_match_all($pattern, $html, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $attr_value = $match[2];
                    
                    // No procesamos valores vacíos o puramente numéricos
                    if (empty($attr_value) || is_numeric($attr_value)) {
                        continue;
                    }
                    
                    // Extraer información contextual para mejorar el registro
                    $element_id = $this->extract_element_id_from_context($html, $match[0]);
                    
                    // Registrar el valor para traducción con varios nombres
                    $this->register_value_for_translation($attr, $attr_value, $element_id);
                    
                    if ($this->debug) {
                        $this->log_debug("CAPTURA TOTAL - Encontrado $attr = \"$attr_value\" " . ($element_id ? "en elemento ID: $element_id" : ""));
                    }
                }
            }
        }
    }
    
    /**
     * Extrae el ID del elemento que contiene el atributo ARIA
     */
    private function extract_element_id_from_context($html, $attr_match) {
        // Buscar la posición del atributo en el HTML
        $pos = strpos($html, $attr_match);
        if ($pos === false) {
            return '';
        }
        
        // Extraer un fragmento de HTML antes del atributo
        $start = max(0, $pos - 200);
        $fragment = substr($html, $start, 400);
        
        // Buscar el atributo "id" o "data-id" en este fragmento
        $id_pattern = '/\s(?:id|data-id|data-element-id)=[\'"]([^\'"]+)[\'"]/i';
        if (preg_match($id_pattern, $fragment, $matches)) {
            return $matches[1];
        }
        
        return '';
    }
    
    /**
     * Procesa el contenido de Elementor para buscar atributos ARIA
     */
    public function capturar_aria_en_content($content) {
        if (!empty($content) && is_string($content)) {
            $this->extract_aria_from_html($content);
        }
        return $content;
    }
    
    /**
     * Procesa cualquier elemento de Elementor
     */
    public function process_element_attributes($element) {
        if (!is_object($element)) {
            return;
        }
        
        try {
            // Intentar obtener settings
            $settings = null;
            if (method_exists($element, 'get_settings_for_display')) {
                $settings = $element->get_settings_for_display();
            } elseif (method_exists($element, 'get_settings')) {
                $settings = $element->get_settings();
            }
            
            if (!is_array($settings)) {
                return;
            }
            
            // Obtener ID del elemento
            $element_id = method_exists($element, 'get_id') ? $element->get_id() : '';
            if (empty($element_id)) {
                return;
            }
            
            // Procesar custom_attributes si existen
            if (isset($settings['custom_attributes'])) {
                $this->process_custom_attributes($settings['custom_attributes'], $element_id);
            }
            
            // También buscar en otras propiedades del elemento
            foreach ($settings as $key => $value) {
                // Buscar propiedades que podrían contener atributos ARIA
                if (is_string($key) && (
                    strpos($key, 'aria_') === 0 || 
                    strpos($key, 'aria-') === 0 || 
                    strpos($key, 'role') === 0 ||
                    strpos($key, 'accessibility') !== false
                )) {
                    // Si es un valor string, registrarlo para traducción
                    if (is_string($value) && !empty($value)) {
                        // Determinar el nombre del atributo
                        $attr_name = str_replace('_', '-', $key);
                        if (strpos($attr_name, 'aria-') !== 0) {
                            $attr_name = 'aria-label'; // Default
                        }
                        
                        // Registrar el valor para traducción
                        $this->register_value_for_translation($attr_name, $value, $element_id);
                        
                        if ($this->debug) {
                            $this->log_debug("Encontrado en settings: $attr_name = \"$value\" en elemento ID: $element_id");
                        }
                    }
                }
            }
            
        } catch (\Exception $e) {
            if ($this->debug) {
                $this->log_debug("Error en process_element_attributes: " . $e->getMessage());
            }
        }
    }
    
    /**
     * Procesa datos de template de Elementor
     */
    public function process_template_data($data, $post_id) {
        if (empty($data) || !is_array($data)) {
            return $data;
        }
        
        if ($this->debug) {
            $this->log_debug("Procesando template data para post ID: $post_id");
        }
        
        $this->process_template_elements($data);
        
        return $data;
    }
    
    /**
     * Procesa recursivamente elementos de template
     */
    private function process_template_elements($elements) {
        if (!is_array($elements)) {
            return;
        }
        
        foreach ($elements as $element) {
            if (!is_array($element)) {
                continue;
            }
            
            // Procesar settings si existen
            if (isset($element['settings']) && is_array($element['settings']) && 
                isset($element['settings']['custom_attributes'])) {
                
                $element_id = isset($element['id']) ? $element['id'] : '';
                if (!empty($element_id)) {
                    $this->process_custom_attributes($element['settings']['custom_attributes'], $element_id);
                }
            }
            
            // Procesar elementos hijos
            if (isset($element['elements']) && is_array($element['elements'])) {
                $this->process_template_elements($element['elements']);
            }
        }
    }
    
    /**
     * Procesa atributos personalizados de Elementor
     */
    private function process_custom_attributes($custom_attributes, $element_id) {
        if (empty($custom_attributes)) {
            return;
        }
        
        // CASO 1: Array de objetos (formato normal)
        if (is_array($custom_attributes)) {
            foreach ($custom_attributes as $attribute) {
                if (is_array($attribute) && isset($attribute['key']) && isset($attribute['value'])) {
                    $key = $attribute['key'];
                    $value = $attribute['value'];
                    
                    if (in_array($key, $this->traducible_attrs)) {
                        $this->register_value_for_translation($key, $value, $element_id);
                        
                        if ($this->debug) {
                            $this->log_debug("Encontrado atributo: $key = \"$value\" en elemento ID: $element_id");
                        }
                    }
                }
                // Formato key|value como string
                elseif (is_string($attribute) && strpos($attribute, '|') !== false) {
                    list($key, $value) = explode('|', $attribute, 2);
                    $key = trim($key);
                    $value = trim($value);
                    
                    if (in_array($key, $this->traducible_attrs)) {
                        $this->register_value_for_translation($key, $value, $element_id);
                        
                        if ($this->debug) {
                            $this->log_debug("Encontrado atributo pipe: $key = \"$value\" en elemento ID: $element_id");
                        }
                    }
                }
            }
        }
        // CASO 2: String multilínea
        elseif (is_string($custom_attributes)) {
            $lines = preg_split('/\r\n|\r|\n/', $custom_attributes);
            
            foreach ($lines as $line) {
                if (strpos($line, '|') !== false) {
                    list($key, $value) = explode('|', $line, 2);
                    $key = trim($key);
                    $value = trim($value);
                    
                    if (in_array($key, $this->traducible_attrs)) {
                        $this->register_value_for_translation($key, $value, $element_id);
                        
                        if ($this->debug) {
                            $this->log_debug("Encontrado atributo multilínea: $key = \"$value\" en elemento ID: $element_id");
                        }
                    }
                }
            }
        }
    }
    
    /**
     * Registra un valor para traducción con varios nombres para garantizar que se capture
     */
    private function register_value_for_translation($attr, $value, $element_id = '') {
        if (empty($value)) {
            return;
        }
        
        // IMPORTANTE: Registrar con múltiples nombres para garantizar la compatibilidad
        
        // 1. Valor directamente como nombre (el más importante)
        do_action('wpml_register_single_string', $this->context, $value, $value);
        
        // 2. Formato aria-label_valor
        $aria_label_name = "aria-label_{$value}";
        do_action('wpml_register_single_string', $this->context, $aria_label_name, $value);
        
        // Si tenemos ID de elemento, registrar formatos específicos
        if (!empty($element_id)) {
            // 3. Formato aria_elemento_atributo
            $id_format = "aria_{$element_id}_{$attr}";
            do_action('wpml_register_single_string', $this->context, $id_format, $value);
            
            // 4. Formato con prefijo widget
            $widget_format = "aria_widget_{$element_id}_{$attr}";
            do_action('wpml_register_single_string', $this->context, $widget_format, $value);
        }
    }
    
    /**
     * Traduce atributos ARIA en el HTML final
     */
    public function translate_aria_attributes($content) {
        if (empty($content) || !is_string($content)) {
            return $content;
        }
        
        // Verificar si hay algún atributo ARIA
        $encontrado = false;
        foreach ($this->traducible_attrs as $attr) {
            if (strpos($content, $attr) !== false) {
                $encontrado = true;
                break;
            }
        }
        
        if (!$encontrado) {
            return $content;
        }
        
        // Crear patrón regex para todos los atributos traducibles
        $attrs_pattern = implode('|', array_map(function($attr) {
            return preg_quote($attr, '/');
        }, $this->traducible_attrs));
        
        $pattern = '/\s(' . $attrs_pattern . ')\s*=\s*([\'"])((?:(?!\2).)*)\2/is';
        
        // Guardar referencia al HTML completo
        $full_html = $content;
        
        // Traducir atributos
        $result = preg_replace_callback($pattern, function($matches) use ($full_html) {
            $attr_name = $matches[1];
            $quote_type = $matches[2];
            $attr_value = $matches[3];
            
            if (empty($attr_value)) {
                return $matches[0];
            }
            
            // Estrategia de traducción en cascada
            
            // 1. Intentar traducir directamente con el valor como clave
            $translated = apply_filters('wpml_translate_single_string', $attr_value, $this->context, $attr_value);
            
            // 2. Intentar con formato aria-label_valor
            if ($translated === $attr_value) {
                $translated = apply_filters('wpml_translate_single_string', $attr_value, $this->context, "aria-label_{$attr_value}");
            }
            
            // 3. Intentar con formato attr_valor
            if ($translated === $attr_value) {
                $translated = apply_filters('wpml_translate_single_string', $attr_value, $this->context, "{$attr_name}_{$attr_value}");
            }
            
            // 4. Intentar extraer contexto del elemento
            if ($translated === $attr_value) {
                $element_id = $this->extract_element_id_from_context($full_html, $matches[0]);
                
                if (!empty($element_id)) {
                    // Formato específico con ID
                    $id_format = "aria_{$element_id}_{$attr_name}";
                    $translated = apply_filters('wpml_translate_single_string', $attr_value, $this->context, $id_format);
                    
                    // Formato con widget
                    if ($translated === $attr_value) {
                        $widget_format = "aria_widget_{$element_id}_{$attr_name}";
                        $translated = apply_filters('wpml_translate_single_string', $attr_value, $this->context, $widget_format);
                    }
                }
            }
            
            return " {$attr_name}{$quote_type}{$translated}{$quote_type}";
        }, $content);
        
        return $result !== null ? $result : $content;
    }
    
    /**
     * Logger para debug
     */
    private function log_debug($message) {
        if (!$this->debug) return;
        
        $log_file = WP_CONTENT_DIR . '/debug-aria-wpml.log';
        
        if (is_array($message) || is_object($message)) {
            error_log(date('[Y-m-d H:i:s] ') . print_r($message, true) . PHP_EOL, 3, $log_file);
        } else {
            error_log(date('[Y-m-d H:i:s] ') . $message . PHP_EOL, 3, $log_file);
        }
    }
}

/**
 * Añade información en la lista de plugins
 */
function eariawpml_after_plugin_row($plugin_file, $plugin_data, $status) {
    if (plugin_basename(__FILE__) == $plugin_file) {
        echo '<tr class="plugin-update-tr active"><td colspan="4" class="plugin-update colspanchange"><div class="notice inline notice-info" style="margin:0; padding:5px;">';
        echo '<strong>Compatibilidad verificada:</strong> WordPress 6.7, Elementor 3.28.3, WPML Multilingual CMS 4.7.3 y WPML String Translation 3.3.2.';
        echo '</div></td></tr>';
    }
}
add_action('after_plugin_row', 'eariawpml_after_plugin_row', 10, 3);

// Inicializar el plugin
add_action('plugins_loaded', function() {
    WPML_Aria_Translator::get_instance();
}, 20); // Prioridad 20 para asegurarnos que WPML y Elementor estén cargados