<?php
/**
 * Clase para el sistema de traducción
 *
 * @package AccessiTrans
 */

if (!defined('ABSPATH')) {
    exit; // Salir si se accede directamente
}

/**
 * Clase para gestionar el sistema de traducción
 */
class AccessiTrans_Translator {
    
    /**
     * Referencia a la clase principal
     */
    private $core;
    
    /**
     * Cache para evitar procesar múltiples veces el mismo valor
     */
    private $processed_values = [];
    
    /**
     * Caché de traducciones encontradas para mejorar rendimiento
     * Se guarda como opción en la base de datos para persistencia
     */
    private $translation_cache = [];
    
    /**
     * Constructor
     */
    public function __construct($core) {
        $this->core = $core;
        
        // Cargar caché de traducciones
        $this->translation_cache = get_option('accessitrans_translation_cache', []);
    }
    
    /**
     * Prepara y normaliza una cadena para consistencia
     */
    public function prepare_string($string) {
        // Eliminar espacios innecesarios
        $string = trim($string);
        // Normalizar codificación
        $string = htmlspecialchars_decode($string);
        return $string;
    }
    
    /**
     * Verifica si una cadena ya existe como traducción en WPML
     */
    public function is_wpml_translation($value) {
        global $wpdb;
        
        // Si está vacío, no es una traducción
        if (empty($value)) {
            return false;
        }
        
        // Verificar si existe como traducción en WPML
        $translation_exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}icl_string_translations 
             WHERE value = %s",
            $value
        ));
        
        return ($translation_exists > 0);
    }
    
    /**
     * Registra un valor para traducción con formato de prefijo
     */
    public function register_value_for_translation($attr, $value, $element_id = '') {
        if (empty($value)) {
            return;
        }
        
        // Verificar si estamos en el idioma principal (doble verificación)
        if (!$this->core->should_capture_in_current_language()) {
            return;
        }
        
        // Verificar si esta cadena ya existe como traducción en WPML
        if ($this->is_wpml_translation($value)) {
            if ($this->core->options['modo_debug']) {
                $this->core->log_debug("Omitiendo registro de cadena que ya existe como traducción: {$value}");
            }
            return;
        }
        
        // Usar el cache para evitar procesar el mismo valor varias veces
        $cache_key = md5($attr . '_' . $value);
        if (isset($this->processed_values[$cache_key])) {
            return;
        }
        $this->processed_values[$cache_key] = true;
        
        // Registrar con formato de prefijo
        $prefixed_name = "{$attr}_{$value}";
        do_action('wpml_register_single_string', $this->core->get_context(), $prefixed_name, $value);
        
        if ($this->core->options['modo_debug']) {
            $this->core->log_debug("Registrado: \"{$prefixed_name}\" → \"{$value}\"");
        }
    }
    
    /**
     * Obtiene la traducción de una cadena desde WPML o caché
     * Realiza múltiples comprobaciones para asegurar que se encuentra la traducción correcta
     */
    public function get_translation($attr_name, $attr_value, $default = null) {
        // Si no hay valor, devolver el valor por defecto
        if (empty($attr_value)) {
            return $default !== null ? $default : $attr_value;
        }
        
        // Verificar en la caché primero
        $cache_key = md5($attr_name . '_' . $attr_value . '_' . apply_filters('wpml_current_language', null));
        if (isset($this->translation_cache[$cache_key])) {
            return $this->translation_cache[$cache_key];
        }
        
        // 1. Intentar con el formato de prefijo (más común)
        $prefixed_name = "{$attr_name}_{$attr_value}";
        $translated = apply_filters('wpml_translate_single_string', $attr_value, $this->core->get_context(), $prefixed_name);
        
        // 2. Si no funciona, intentar obtener la traducción directamente desde la base de datos
        if ($translated === $attr_value) {
            global $wpdb;
            $current_language = apply_filters('wpml_current_language', null);
            
            // Buscar la cadena por prefijo
            $string_id = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}icl_strings 
                 WHERE name = %s AND context = %s",
                $prefixed_name,
                $this->core->get_context()
            ));
            
            if ($string_id) {
                // Buscar traducción para el idioma actual
                $db_translation = $wpdb->get_var($wpdb->prepare(
                    "SELECT value FROM {$wpdb->prefix}icl_string_translations 
                     WHERE string_id = %d AND language = %s AND status = 1",
                    $string_id,
                    $current_language
                ));
                
                if ($db_translation) {
                    $translated = $db_translation;
                }
            }
        }
        
        // 3. Como último recurso, intentar con el valor exacto
        if ($translated === $attr_value) {
            global $wpdb;
            $current_language = apply_filters('wpml_current_language', null);
            
            // Buscar por valor exacto
            $string_id = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}icl_strings 
                 WHERE value = %s AND context = %s",
                $attr_value,
                $this->core->get_context()
            ));
            
            if ($string_id) {
                // Buscar traducción para el idioma actual
                $db_translation = $wpdb->get_var($wpdb->prepare(
                    "SELECT value FROM {$wpdb->prefix}icl_string_translations 
                     WHERE string_id = %d AND language = %s AND status = 1",
                    $string_id,
                    $current_language
                ));
                
                if ($db_translation) {
                    $translated = $db_translation;
                }
            }
        }
        
        // Almacenar en caché y en opción persistente
        if ($translated !== $attr_value) {
            $this->translation_cache[$cache_key] = $translated;
            
            // Actualizar la caché persistente (con límite para evitar que crezca demasiado)
            if (count($this->translation_cache) <= 1000) {
                update_option('accessitrans_translation_cache', $this->translation_cache);
            }
            
            if ($this->core->options['modo_debug']) {
                $this->core->log_debug("TRADUCIDO: {$attr_value} → {$translated}");
            }
        } else if ($this->core->options['modo_debug']) {
            $this->core->log_debug("NO TRADUCIDO: {$attr_value}");
        }
        
        return $translated;
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
        foreach ($this->core->get_traducible_attrs() as $attr) {
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
        }, $this->core->get_traducible_attrs()));
        
        $pattern = '/\s(' . $attrs_pattern . ')\s*=\s*([\'"])((?:(?!\2).)*)\2/is';
        
        // Traducir atributos
        $result = preg_replace_callback($pattern, function($matches) {
            $attr_name = $matches[1];
            $quote_type = $matches[2];
            $attr_value = $matches[3];
            
            if (empty($attr_value)) {
                return $matches[0];
            }
            
            // Normalizar el valor
            $attr_value = $this->prepare_string($attr_value);
            
            // Obtener traducción usando nuestro método mejorado
            $translated = $this->get_translation($attr_name, $attr_value, $attr_value);
            
            return " {$attr_name}={$quote_type}{$translated}{$quote_type}";
        }, $content);
        
        return $result !== null ? $result : $content;
    }
    
    /**
     * Limpia la caché de traducciones
     */
    public function clear_cache() {
        $this->processed_values = [];
    }
}