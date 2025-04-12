<?php
/**
 * Plugin Name: Elementor ARIA Translator for WPML
 * Plugin URI: https://github.com/marioalmonte/elementor-aria-translator
 * Description: Permite traducir atributos ARIA en Elementor utilizando WPML. Desarrollado por un profesional certificado en Accesibilidad Web (CPWA). Probado con WordPress 6.7, Elementor 3.28.3, WPML 4.7.3 y WPML ST 3.3.2.
 * Version: 1.2.3
 * Author: Mario Germán Almonte Moreno
 * Author URI: https://www.linkedin.com/in/marioalmonte/
 * Text Domain: elementor-aria-translator
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if (!defined('ABSPATH')) {
    exit; // Salir si se accede directamente
}

class Elementor_ARIA_Translator {
    
    // Instancia singleton
    private static $instance = null;
    
    // Contexto y cache
    private $context = 'Elementor ARIA Attributes';
    private $processed_ids = [];
    private $has_aria_attributes = false;
    
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
        // Verificar dependencias
        if (!$this->check_dependencies()) {
            return;
        }
        
        // Hooks para elementos normales de Elementor
        add_action('elementor/element/after_section_end', [$this, 'register_aria_attributes'], 10, 3);
        add_action('elementor/frontend/widget/before_render_content', [$this, 'before_render_widget'], 10, 1);
        
        // Soporte específico para templates
        add_action('elementor/frontend/before_render', [$this, 'before_render_element'], 10, 1);
        
        // Soporte para templates globales
        add_action('elementor/frontend/builder_content_data', [$this, 'scan_template_data'], 10, 2);
        
        // Hooks para el frontend
        add_filter('elementor/frontend/the_content', [$this, 'translate_aria_attributes'], 999);
        
        // Filtro genérico para capturar otros contenidos (cabeceras, footers, etc.)
        add_filter('the_content', [$this, 'maybe_translate_aria_attributes'], 999);
    }
    
    /**
     * Método singleton
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Verifica dependencias
     */
    private function check_dependencies() {
        // Verificar Elementor
        if (!did_action('elementor/loaded')) {
            add_action('admin_notices', function() {
                echo '<div class="error"><p>' . 
                     esc_html__('Elementor ARIA Translator requiere que Elementor esté instalado y activo.', 'elementor-aria-translator') . 
                     '</p></div>';
            });
            return false;
        }
        
        // Verificación más completa de WPML
        if (!defined('ICL_SITEPRESS_VERSION')) {
            add_action('admin_notices', function() {
                echo '<div class="error"><p>' . 
                     esc_html__('Elementor ARIA Translator requiere que WPML esté instalado y activo.', 'elementor-aria-translator') . 
                     '</p></div>';
            });
            return false;
        }
        
        return true;
    }
    
    /**
     * Escanea los datos del template para registrar atributos ARIA
     */
    public function scan_template_data($data, $post_id) {
        if (empty($data) || !is_array($data)) {
            return $data;
        }
        
        foreach ($data as $element_data) {
            if (is_array($element_data)) {
                $this->process_template_element($element_data);
            }
        }
        
        return $data;
    }
    
    /**
     * Procesa recursivamente los elementos del template
     */
    private function process_template_element($element_data) {
        // Verificar que element_data es un array válido
        if (!is_array($element_data)) {
            return;
        }
        
        // Verificar si hay atributos personalizados en este elemento
        if (isset($element_data['settings']) && is_array($element_data['settings']) && 
            isset($element_data['settings']['custom_attributes'])) {
            
            // Crear información del elemento con valores por defecto seguros
            $element_type = isset($element_data['elType']) ? sanitize_text_field($element_data['elType']) : 'widget';
            $element_id = isset($element_data['id']) ? sanitize_text_field($element_data['id']) : uniqid('el_');
            $widget_type = isset($element_data['widgetType']) ? sanitize_text_field($element_data['widgetType']) : '';
            
            // Procesar atributos
            $this->process_custom_attributes(
                $element_data['settings']['custom_attributes'],
                $element_id,
                $element_type,
                $widget_type
            );
        }
        
        // Procesar elementos hijos
        if (isset($element_data['elements']) && is_array($element_data['elements'])) {
            foreach ($element_data['elements'] as $child_element) {
                if (is_array($child_element)) {
                    $this->process_template_element($child_element);
                }
            }
        }
    }
    
    /**
     * Maneja atributos personalizados enfocándose en los que necesitan traducción
     */
    private function process_custom_attributes($custom_attributes, $element_id, $element_type, $element_name) {
        // Si es array de objetos (formato estándar de Elementor)
        if (is_array($custom_attributes)) {
            foreach ($custom_attributes as $attribute) {
                // Formato estándar: array con key y value
                if (is_array($attribute) && isset($attribute['key']) && isset($attribute['value'])) {
                    $key = sanitize_text_field($attribute['key']);
                    if (in_array($key, $this->traducible_attrs, true)) {
                        $this->register_attribute_for_translation(
                            $key, 
                            sanitize_text_field($attribute['value']), 
                            $element_id, 
                            $element_type, 
                            $element_name
                        );
                    }
                }
                // Formato alternativo: string con formato key|value
                else if (is_string($attribute) && strpos($attribute, '|') !== false) {
                    $parts = explode('|', $attribute, 2);
                    if (count($parts) === 2) {
                        $key = trim(sanitize_text_field($parts[0]));
                        if (in_array($key, $this->traducible_attrs, true)) {
                            $this->register_attribute_for_translation(
                                $key, 
                                trim(sanitize_text_field($parts[1])), 
                                $element_id, 
                                $element_type, 
                                $element_name
                            );
                        }
                    }
                }
            }
        } 
        // Si es string multilínea (formato alternativo)
        else if (is_string($custom_attributes) && !empty($custom_attributes)) {
            $lines = preg_split('/\r\n|\r|\n/', $custom_attributes);
            if (is_array($lines)) {
                foreach ($lines as $line) {
                    if (is_string($line) && strpos($line, '|') !== false) {
                        $parts = explode('|', $line, 2);
                        if (count($parts) === 2) {
                            $key = trim(sanitize_text_field($parts[0]));
                            if (in_array($key, $this->traducible_attrs, true)) {
                                $this->register_attribute_for_translation(
                                    $key, 
                                    trim(sanitize_text_field($parts[1])), 
                                    $element_id, 
                                    $element_type, 
                                    $element_name
                                );
                            }
                        }
                    }
                }
            }
        }
    }
    
    /**
     * Registra un único atributo para traducción
     */
    private function register_attribute_for_translation($key, $value, $element_id, $element_type, $element_name) {
        // Safety checks
        if (empty($key) || empty($value) || empty($element_id)) {
            return;
        }
        
        $this->has_aria_attributes = true;
        
        // Sanitizar todos los valores para mayor seguridad
        $key = sanitize_text_field($key);
        $value = sanitize_text_field($value);
        $element_id = sanitize_text_field($element_id);
        $element_type = sanitize_text_field($element_type);
        $element_name = sanitize_text_field($element_name);
        
        // Identificador detallado
        $string_name = "aria_{$element_type}_{$element_name}_{$element_id}_{$key}";
        
        // Registrar para traducción
        do_action('wpml_register_single_string', $this->context, $string_name, $value);
        
        // También registrar el valor como clave (para casos más simples)
        do_action('wpml_register_single_string', $this->context, $value, $value);
        
        // Añadir una clave adicional para mayor compatibilidad
        do_action('wpml_register_single_string', $this->context, "{$key}_{$value}", $value);
    }
    
    /**
     * Registra atributos ARIA para traducción durante la edición
     */
    public function register_aria_attributes($element, $section_id, $args) {
        // Comprobar que el elemento existe y no es null
        if (!is_object($element) || !method_exists($element, 'get_id') || !method_exists($element, 'get_settings')) {
            return;
        }
        
        // Varios nombres de sección posibles según versión de Elementor
        $valid_sections = ['section_custom_attributes_pro', 'section_custom_attributes'];
        if (!in_array($section_id, $valid_sections, true)) {
            return;
        }
        
        try {
            // Intentar obtener el ID del elemento de manera segura
            $element_id = $element->get_id();
            if (empty($element_id)) {
                return;
            }
            
            // Evitar procesar el mismo elemento varias veces
            if (in_array($element_id, $this->processed_ids, true)) {
                return;
            }
            
            $this->processed_ids[] = $element_id;
            
            // Obtener settings de manera segura
            $settings = null;
            try {
                $settings = $element->get_settings();
            } catch (\Exception $e) {
                // Si hay error al obtener settings, intentar get_settings_for_display
                try {
                    if (method_exists($element, 'get_settings_for_display')) {
                        $settings = $element->get_settings_for_display();
                    } else {
                        return;
                    }
                } catch (\Exception $e) {
                    // Si ambos métodos fallan, salir
                    return;
                }
            }
            
            // Verificar que settings es un array y contiene custom_attributes
            if (!is_array($settings) || !isset($settings['custom_attributes'])) {
                return;
            }
            
            // Obtener tipo y nombre del elemento de manera segura
            $element_type = method_exists($element, 'get_type') ? $element->get_type() : 'unknown';
            $element_name = method_exists($element, 'get_name') ? $element->get_name() : 'unknown';
            
            // Sanitizar valores
            $element_id = sanitize_text_field($element_id);
            $element_type = sanitize_text_field($element_type);
            $element_name = sanitize_text_field($element_name);
            
            // Procesar atributos personalizados
            $this->process_custom_attributes(
                $settings['custom_attributes'],
                $element_id,
                $element_type,
                $element_name
            );
            
        } catch (\Exception $e) {
            // Si ocurre cualquier error, simplemente salir sin procesar
            return;
        }
    }
    
    /**
     * Capturar elementos antes de renderizar (para templates y widgets no estándar)
     */
    public function before_render_element($element) {
        // Verificar que el elemento no es null y tiene los métodos necesarios
        if (!is_object($element) || !method_exists($element, 'get_id') || !method_exists($element, 'get_settings_for_display')) {
            return;
        }
        
        try {
            // Obtener datos del elemento de manera segura
            $element_id = $element->get_id();
            if (empty($element_id)) {
                return;
            }
            
            // Evitar procesar el mismo elemento varias veces
            if (in_array($element_id, $this->processed_ids, true)) {
                return;
            }
            
            $this->processed_ids[] = $element_id;
            
            // Obtener settings para display de manera segura
            $settings = $element->get_settings_for_display();
            
            // Verificar que settings es un array y contiene custom_attributes
            if (!is_array($settings) || !isset($settings['custom_attributes'])) {
                return;
            }
            
            // Obtener tipo y nombre del elemento de manera segura
            $element_type = method_exists($element, 'get_type') ? $element->get_type() : 'unknown';
            $element_name = method_exists($element, 'get_name') ? $element->get_name() : 'unknown';
            
            // Sanitizar valores
            $element_id = sanitize_text_field($element_id);
            $element_type = sanitize_text_field($element_type);
            $element_name = sanitize_text_field($element_name);
            
            // Procesar atributos personalizados
            $this->process_custom_attributes(
                $settings['custom_attributes'],
                $element_id,
                $element_type,
                $element_name
            );
            
        } catch (\Exception $e) {
            // Si ocurre cualquier error, simplemente salir sin procesar
            return;
        }
    }
    
    /**
     * Procesamiento específico para widgets
     */
    public function before_render_widget($widget) {
        $this->before_render_element($widget);
    }
    
    /**
     * Verifica si hay atributos ARIA antes de procesar
     */
    public function maybe_translate_aria_attributes($content) {
        // Si no hay contenido, devolver vacío
        if (empty($content) || !is_string($content)) {
            return $content;
        }
        
        // Comprobación rápida para evitar procesamiento innecesario
        $encontrado = false;
        foreach ($this->traducible_attrs as $attr) {
            if (strpos($content, $attr) !== false) {
                $encontrado = true;
                break;
            }
        }
        
        if ($encontrado || $this->has_aria_attributes) {
            return $this->translate_aria_attributes($content);
        }
        
        return $content;
    }
    
    /**
     * Traduce atributos ARIA en el HTML generado
     * Versión corregida que no depende de $content fuera del callback
     */
    public function translate_aria_attributes($content) {
        // Si no hay contenido, devolver vacío
        if (empty($content) || !is_string($content)) {
            return $content;
        }
        
        // Capturar el contenido completo en una variable estática para el callback
        $html_to_process = $content;
        
        // Preparar una expresión regular que maneje todos los atributos traducibles
        $attrs_pattern = implode('|', array_map(function($attr) {
            return preg_quote($attr, '/');
        }, $this->traducible_attrs));
        
        // Patrón mejorado que maneja espacios, saltos de línea y diferentes tipos de comillas
        $pattern = '/\s(' . $attrs_pattern . ')\s*=\s*([\'"])((?:(?!\2).)*)\2/is';
        
        $result = preg_replace_callback($pattern, function($matches) use ($html_to_process) {
            $attr_name = $matches[1];    // Nombre del atributo
            $quote_type = $matches[2];   // Tipo de comilla (simple o doble)
            $attr_value = $matches[3];   // Valor original
            
            // Si el valor está vacío, no hay nada que traducir
            if (empty($attr_value)) {
                return $matches[0];
            }
            
            // 1. Intentar traducir el valor directamente (caso más común)
            $translated = apply_filters('wpml_translate_single_string', $attr_value, $this->context, $attr_value);
            
            // 2. Intentar también con la clave adicional que registramos
            if ($translated === $attr_value) {
                $translated = apply_filters('wpml_translate_single_string', $attr_value, $this->context, "{$attr_name}_{$attr_value}");
            }
            
            // 3. Si no se tradujo, buscar por información del contexto HTML
            if ($translated === $attr_value) {
                // Extraer información del entorno HTML para mejorar búsqueda
                $id_pattern = '/id\s*=\s*([\'"])((?:(?!\1).)*)\1/is';
                $data_id_pattern = '/data-id\s*=\s*([\'"])((?:(?!\1).)*)\1/is';
                $data_element_pattern = '/data-element_type\s*=\s*([\'"])((?:(?!\1).)*)\1/is';
                $data_widget_pattern = '/data-widget_type\s*=\s*([\'"])((?:(?!\1).)*)\1/is';
                
                $element_id = '';
                $element_type = '';
                $widget_type = '';
                
                // Encontrar la posición del match actual en el contenido completo
                $match_pos = strpos($html_to_process, $matches[0]);
                
                // Si encontramos la posición, extraer un contexto alrededor
                if ($match_pos !== false) {
                    // Buscar 200 caracteres antes y después para contexto
                    $start_pos = max(0, $match_pos - 200);
                    $context_length = min(strlen($html_to_process) - $start_pos, strlen($matches[0]) + 400);
                    $context_text = substr($html_to_process, $start_pos, $context_length);
                    
                    // Intentar obtener ID
                    if (preg_match($id_pattern, $context_text, $id_matches)) {
                        $element_id = $id_matches[2];
                    } elseif (preg_match($data_id_pattern, $context_text, $data_id_matches)) {
                        $element_id = $data_id_matches[2];
                    }
                    
                    // Intentar obtener tipo de elemento
                    if (preg_match($data_element_pattern, $context_text, $element_matches)) {
                        $element_type = $element_matches[2];
                    }
                    
                    // Intentar obtener tipo de widget
                    if (preg_match($data_widget_pattern, $context_text, $widget_matches)) {
                        $widget_type = $widget_matches[2];
                        // Eliminar namespace de Elementor para simplificar
                        $widget_type = str_replace('elementor-', '', $widget_type);
                    }
                    
                    // Probar con diferentes combinaciones de nombres para encontrar la traducción
                    if (!empty($element_id) && !empty($element_type) && !empty($widget_type)) {
                        $string_name = "aria_{$element_type}_{$widget_type}_{$element_id}_{$attr_name}";
                        $translated = apply_filters('wpml_translate_single_string', $attr_value, $this->context, $string_name);
                    }
                    
                    if ($translated === $attr_value && !empty($element_id) && !empty($element_type)) {
                        $string_name = "aria_{$element_type}_{$element_id}_{$attr_name}";
                        $translated = apply_filters('wpml_translate_single_string', $attr_value, $this->context, $string_name);
                    }
                    
                    if ($translated === $attr_value && !empty($element_id)) {
                        $string_name = "aria_{$element_id}_{$attr_name}";
                        $translated = apply_filters('wpml_translate_single_string', $attr_value, $this->context, $string_name);
                    }
                }
            }
            
            // Devolver con la traducción o valor original
            return " {$attr_name}{$quote_type}{$translated}{$quote_type}";
        }, $content);
        
        // En caso de error en preg_replace_callback, devolver el contenido original
        return $result !== null ? $result : $content;
    }
}

/**
 * Añade una fila de información adicional después de la fila del plugin
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
    Elementor_ARIA_Translator::get_instance();
}, 20); // Prioridad 20 para asegurarnos que WPML y Elementor estén cargados