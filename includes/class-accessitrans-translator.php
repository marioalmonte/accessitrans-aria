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
     * Propiedad para rastrear si la caché ha sido actualizada
     */
    private $cache_updated = false;
    
    /**
     * Constructor
     */
    public function __construct($core) {
        $this->core = $core;
        
        // Cargar caché de traducciones
        $this->translation_cache = get_option('accessitrans_translation_cache', []);
        
        // Registrar hook de shutdown para guardar caché
        add_action('shutdown', [$this, 'save_translation_cache']);
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
     * Registra un valor para traducción con formato de prefijo (mejorado)
     */
    public function register_value_for_translation($attr, $value, $element_id = '') {
        if (empty($value)) {
            return;
        }
        
        // Verificación previa para omitir registro si está desactivado
        if (!isset($this->core->options['permitir_escaneo']) || !$this->core->options['permitir_escaneo']) {
            if ($this->core->options['modo_debug']) {
                $this->core->log_debug("Omitiendo registro de '{$value}' - escaneo desactivado globalmente");
            }
            return;
        }
        
        // Verificación adicional para entorno admin
        if (is_admin()) {
            $current_language = apply_filters('wpml_current_language', null);
            $default_language = apply_filters('wpml_default_language', null);
            
            if ($current_language !== $default_language) {
                if ($this->core->options['modo_debug']) {
                    $this->core->log_debug("Admin: Ignorando registro de cadena '{$value}' porque idioma actual ({$current_language}) != ({$default_language})");
                }
                return;
            }
        }
        
        // Verificar si estamos en el idioma principal (doble verificación)
        if (!$this->core->should_capture_in_current_language()) {
            if ($this->core->options['modo_debug']) {
                $current_language = apply_filters('wpml_current_language', null);
                $default_language = apply_filters('wpml_default_language', null);
                $this->core->log_debug("Ignorando registro de cadena '{$value}' porque idioma actual ({$current_language}) != ({$default_language})");
            }
            return;
        }
        
        // Normalizar el valor
        $value = $this->prepare_string($value);
        
        // Verificar si esta cadena ya existe como traducción en WPML
        if ($this->is_wpml_translation($value)) {
            if ($this->core->options['modo_debug']) {
                $this->core->log_debug("Omitiendo registro de cadena que ya existe como traducción: {$value}");
            }
            return;
        }
        
        // Verificar si esta cadena ya existe en WPML
        if ($this->core->exists_in_wpml($value)) {
            if ($this->core->options['modo_debug']) {
                $this->core->log_debug("Omitiendo registro de cadena que ya existe en WPML: {$value}");
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
            $this->core->log_debug("Registrado: \"{$prefixed_name}\" → \"{$value}\"" . ($element_id ? " (ID: {$element_id})" : ""));
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
            
            // Marcar caché como actualizada
            $this->cache_updated = true;
            
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
        
        // Obtener el idioma actual y predeterminado
        $current_language = apply_filters('wpml_current_language', null);
        $default_language = apply_filters('wpml_default_language', null);
        
        // Crear patrón regex para todos los atributos traducibles
        $attrs_pattern = implode('|', array_map(function($attr) {
            return preg_quote($attr, '/');
        }, $this->core->get_traducible_attrs()));
        
        $pattern = '/\s(' . $attrs_pattern . ')\s*=\s*([\'"])((?:(?!\2).)*)\2/is';
        
        // Traducir atributos
        $result = preg_replace_callback($pattern, function($matches) use ($current_language, $default_language) {
            $attr_name = $matches[1];
            $quote_type = $matches[2];
            $attr_value = $matches[3];
            
            if (empty($attr_value)) {
                return $matches[0];
            }
            
            // Normalizar el valor
            $attr_value = $this->prepare_string($attr_value);
            
            // Si no estamos en el idioma predeterminado, y la cadena ya parece estar traducida,
            // no intentar traducirla nuevamente para evitar mensajes "NO TRADUCIDO" innecesarios
            if ($current_language !== $default_language) {
                // Verificar si la cadena ya está traducida consultando si existe en la base de datos
                global $wpdb;
                
                // Buscar si existe alguna traducción para la cadena original en inglés
                // que coincida con el valor actual (posiblemente ya traducido)
                $is_already_translated = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}icl_string_translations st
                    JOIN {$wpdb->prefix}icl_strings s ON st.string_id = s.id
                    WHERE st.value = %s AND st.language = %s",
                    $attr_value,
                    $current_language
                ));
                
                if ($is_already_translated) {
                    // La cadena actual ya es una traducción, mantenerla como está
                    return " {$attr_name}={$quote_type}{$attr_value}{$quote_type}";
                }
            }
            
            // Obtener traducción usando nuestro método mejorado
            $translated = $this->get_translation($attr_name, $attr_value, $attr_value);
            
            return " {$attr_name}={$quote_type}{$translated}{$quote_type}";
        }, $content);
        
        return $result !== null ? $result : $content;
    }
    
    /**
     * Guarda la caché de traducciones al final de la solicitud
     */
    public function save_translation_cache() {
        // Solo guardar si hubo cambios
        if (!$this->cache_updated) {
            return;
        }
        
        // Limitar tamaño de caché
        if (count($this->translation_cache) > 1000) {
            // Ordenar por clave para mantener entradas más recientes
            ksort($this->translation_cache);
            // Tomar las últimas 1000 entradas
            $this->translation_cache = array_slice($this->translation_cache, -1000, 1000, true);
        }
        
        update_option('accessitrans_translation_cache', $this->translation_cache);
        
        if ($this->core->options['modo_debug']) {
            $this->core->log_debug("Caché de traducciones guardada con " . count($this->translation_cache) . " entradas");
        }
        
        // Resetear flag
        $this->cache_updated = false;
    }
    
    /**
     * Obtiene el tamaño actual de la caché de traducciones
     * @return int Número de entradas en la caché
     */
    public function get_translation_cache_size() {
        return count($this->translation_cache);
    }
    
    /**
     * Limpia la caché de traducciones
     */
    public function clear_cache() {
        $this->processed_values = [];
    }
}