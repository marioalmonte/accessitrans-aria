<?php
/**
 * Clase para herramientas de diagnóstico
 *
 * @package AccessiTrans
 */

if (!defined('ABSPATH')) {
    exit; // Salir si se accede directamente
}

/**
 * Clase para las herramientas de diagnóstico y mantenimiento
 */
class AccessiTrans_Diagnostics {
    
    /**
     * Referencia a la clase principal
     */
    private $core;
    
    /**
     * Constructor
     */
    public function __construct($core) {
        $this->core = $core;
        
        // Agregar funciones AJAX
        add_action('wp_ajax_accessitrans_force_refresh', [$this, 'force_refresh_callback']);
        add_action('wp_ajax_accessitrans_diagnostics', [$this, 'diagnostics_callback']);
        add_action('wp_ajax_accessitrans_check_health', [$this, 'check_health_callback']);
    }
    
    /**
     * Callback para la acción AJAX de forzar actualización
     */
    public function force_refresh_callback() {
        // Verificar nonce
        check_ajax_referer('accessitrans-force-refresh', 'nonce');
        
        // Verificar permisos
        if (!current_user_can('manage_options')) {
            wp_send_json_error(esc_html__('You don\'t have permission to perform this action.', 'accessitrans-aria'));
            return;
        }
        
        // Limpiar cachés internas de los componentes
        if ($this->core->translator) {
            $this->core->translator->clear_cache();
        }
        
        if ($this->core->capture) {
            $this->core->capture->clear_cache();
        }
        
        // Limpiar la caché persistente
        update_option('accessitrans_translation_cache', []);
        
        // Limpiar caché de WPML si está disponible
        if (function_exists('icl_cache_clear')) {
            icl_cache_clear();
        }
        
        if (function_exists('wpml_st_flush_caches')) {
            wpml_st_flush_caches();
        }
        
        // Limpiar caché de objetos de WordPress
        wp_cache_flush();
        
        // Limpiar opciones transientes
        global $wpdb;
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '%_transient_%'");
        
        // Reiniciar el flag de inicialización
        $init_logged_prop = new ReflectionProperty('AccessiTrans_ARIA_Translator', 'initialization_logged');
        $init_logged_prop->setAccessible(true);
        $init_logged_prop->setValue(false);
        
        // Registrar en el log
        if ($this->core->options['modo_debug']) {
            $this->core->log_debug("Forzada actualización manual de traducciones y caché");
        }
        
        // Responder al AJAX
        wp_send_json_success(esc_html__('Update completed successfully.', 'accessitrans-aria'));
    }
    
    /**
     * Callback para la acción AJAX de diagnóstico
     */
    public function diagnostics_callback() {
        // Verificar nonce
        check_ajax_referer('accessitrans-diagnostics', 'nonce');
        
        // Verificar permisos
        if (!current_user_can('manage_options')) {
            wp_send_json_error(esc_html__('You don\'t have permission to perform this action.', 'accessitrans-aria'));
            return;
        }
        
        $string_to_check = isset($_POST['string']) ? sanitize_text_field(wp_unslash($_POST['string'])) : '';
        
        if (empty($string_to_check)) {
            wp_send_json_error(esc_html__('Please provide a string to verify.', 'accessitrans-aria'));
            return;
        }
        
        // Realizar diagnóstico mejorado
        $diagnostic_results = $this->diagnose_translation_improved($string_to_check);
        
        // Responder al AJAX
        wp_send_json_success($diagnostic_results);
    }
    
    /**
     * Callback para la acción AJAX de verificar salud
     */
    public function check_health_callback() {
        // Verificar nonce
        check_ajax_referer('accessitrans-check-health', 'nonce');
        
        // Verificar permisos
        if (!current_user_can('manage_options')) {
            wp_send_json_error(esc_html__('You don\'t have permission to perform this action.', 'accessitrans-aria'));
            return;
        }
        
        // Realizar verificación de salud
        $health_results = $this->check_translation_health();
        
        // Responder al AJAX
        wp_send_json_success($health_results);
    }
    
    /**
     * Verifica el estado general de las traducciones y el plugin
     * Método público para ser llamado desde otras clases
     * 
     * @return array Datos de salud del sistema
     */
    public function check_health() {
        return $this->check_translation_health();
    }
    
    /**
     * Diagnóstica problemas con una traducción específica (método mejorado)
     * Este método busca la cadena directamente en la base de datos de WPML
     * 
     * @param string $string_to_check Cadena a diagnosticar
     * @return array Resultados del diagnóstico
     */
    public function diagnose_translation_improved($string_to_check) {
        global $wpdb;
        
        // Preparar la cadena (normalización)
        $string_to_check = $this->core->translator->prepare_string($string_to_check);
        
        // Información sobre idiomas
        $current_language = apply_filters('wpml_current_language', null);
        $default_language = apply_filters('wpml_default_language', null);
        $is_default_language = ($current_language === $default_language);
        
        $found_strings = [];
        $has_translations = false;
        $translations = [];
        $has_current_language_translation = false;
        
        // Búsqueda directa por valor
        $exact_results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT s.id, s.name, s.value 
                 FROM {$wpdb->prefix}icl_strings s
                 WHERE s.value = %s 
                 AND s.context = %s",
                $string_to_check,
                $this->core->get_context()
            )
        );
        
        // Búsqueda por nombre (formato de prefijo)
        $prefix_results = [];
        foreach ($this->core->get_traducible_attrs() as $attr) {
            $prefixed_name = "{$attr}_{$string_to_check}";
            
            $result = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT s.id, s.name, s.value 
                     FROM {$wpdb->prefix}icl_strings s
                     WHERE s.name = %s 
                     AND s.context = %s",
                    $prefixed_name,
                    $this->core->get_context()
                )
            );
            
            if (!empty($result)) {
                $prefix_results = array_merge($prefix_results, $result);
            }
        }
        
        // Búsqueda parcial (para textos que contienen la cadena buscada)
        $partial_results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT s.id, s.name, s.value 
                 FROM {$wpdb->prefix}icl_strings s
                 WHERE (s.value LIKE %s OR s.name LIKE %s)
                 AND s.context = %s
                 LIMIT 10", // Limitamos para evitar muchos resultados irrelevantes
                '%' . $wpdb->esc_like($string_to_check) . '%',
                '%' . $wpdb->esc_like($string_to_check) . '%',
                $this->core->get_context()
            )
        );
        
        // Combinamos resultados, priorizando coincidencias exactas
        $all_results = array_merge($exact_results, $prefix_results);
        
        // Solo añadir resultados parciales si no hay coincidencias exactas
        if (empty($all_results)) {
            $all_results = $partial_results;
        }
        
        // Eliminar duplicados basados en ID
        $unique_results = [];
        $seen_ids = [];
        
        foreach ($all_results as $result) {
            if (!in_array($result->id, $seen_ids)) {
                $seen_ids[] = $result->id;
                $unique_results[] = $result;
            }
        }
        
        // Para guardar las consultas SQL para debug
        $debug_queries = [];
        
        // Procesamos los resultados encontrados
        if (!empty($unique_results)) {
            foreach ($unique_results as $result) {
                $found_strings[] = [
                    'id' => $result->id,
                    'name' => $result->name,
                    'value' => $result->value
                ];
                
                // Verificar si tiene traducciones
                $trans_results = $wpdb->get_results(
                    $wpdb->prepare(
                        "SELECT st.language, st.value 
                         FROM {$wpdb->prefix}icl_string_translations st
                         WHERE st.string_id = %d",
                        $result->id
                    )
                );
                
                if (!empty($trans_results)) {
                    $has_translations = true;
                    foreach ($trans_results as $trans) {
                        $translations[$trans->language] = $trans->value;
                        if ($trans->language === $current_language) {
                            $has_current_language_translation = true;
                        }
                    }
                }
            }
        }
        
        // Información de depuración avanzada (solo para admin)
        $debug_info = null;
        if (current_user_can('manage_options')) {
            // Obtener el tamaño de la caché
            $translation_cache_size = $this->core->translator->get_translation_cache_size();
            
            $debug_info = [
                'exact_count' => count($exact_results),
                'prefix_count' => count($prefix_results),
                'partial_count' => count($partial_results),
                'unique_count' => count($unique_results),
                'total_matches' => count($all_results),
                'default_language' => $default_language,
                'cache_size' => $translation_cache_size
            ];
        }
        
        return [
            'string' => $string_to_check,
            'current_language' => $current_language,
            'default_language' => $default_language,
            'is_default_language' => $is_default_language,
            'found_in_wpml' => !empty($found_strings),
            'string_forms' => $found_strings,
            'has_translation' => $has_translations,
            'has_current_language_translation' => $has_current_language_translation,
            'translations' => $translations,
            'debug_info' => $debug_info
        ];
    }
    
    /**
     * Verifica el estado general de las traducciones y el plugin
     *
     * @return array Datos sobre el estado general del sistema
     */
    private function check_translation_health() {
        global $wpdb;
        
        // Contar cadenas registradas
        $string_count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}icl_strings WHERE context = %s",
                $this->core->get_context()
            )
        );
        
        // Contar traducciones
        $translation_count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}icl_string_translations st 
                 JOIN {$wpdb->prefix}icl_strings s ON st.string_id = s.id
                 WHERE s.context = %s",
                $this->core->get_context()
            )
        );
        
        // Obtener idiomas
        $languages = [];
        if (function_exists('icl_get_languages')) {
            $languages = [
                'default' => apply_filters('wpml_default_language', null),
                'current' => apply_filters('wpml_current_language', null),
                'available' => []
            ];
            
            $wpml_languages = apply_filters('wpml_active_languages', []);
            foreach ($wpml_languages as $lang) {
                $languages['available'][$lang['code']] = $lang['native_name'];
            }
        }
        
        return [
            'string_count' => (int)$string_count,
            'translation_count' => (int)$translation_count,
            'wpml_active' => defined('ICL_SITEPRESS_VERSION'),
            'elementor_active' => did_action('elementor/loaded'),
            'languages' => $languages,
            'options' => $this->core->options,
            'system_time' => gmdate('Y-m-d H:i:s'),
            'plugin_version' => $this->core->get_version()
        ];
    }
}