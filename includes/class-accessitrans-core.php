<?php
/**
 * Clase principal AccessiTrans
 *
 * @package AccessiTrans
 */

if (!defined('ABSPATH')) {
    exit; // Salir si se accede directamente
}

/**
 * Clase principal del plugin AccessiTrans
 */
class AccessiTrans_ARIA_Translator {
    
    /**
     * Instancia singleton
     */
    private static $instance = null;
    
    /**
     * Control de inicialización para evitar logs duplicados
     */
    private static $initialization_logged = false;
    
    /**
     * Contexto para todas las traducciones ARIA
     */
    private $context = 'AccessiTrans ARIA Attributes';
    
    /**
     * Opciones del plugin
     */
    public $options;
    
    /**
     * Lista de atributos ARIA que contienen texto traducible
     */
    private $traducible_attrs = [
        'aria-label',
        'aria-description',
        'aria-roledescription',
        'aria-placeholder',
        'aria-valuetext'
    ];
    
    /**
     * Versión del plugin (para gestión de caché)
     */
    private $version = '1.0.5';
    
    /**
     * Instancias de los componentes
     */
    public $admin;
    public $capture;
    public $translator;
    public $diagnostics;
    
    /**
     * Constructor privado
     */
    private function __construct() {
        // Inicializar valores por defecto para nuevas instalaciones
        if (!get_option('accessitrans_aria_options')) {
            update_option('accessitrans_aria_options', [
                'captura_total' => true,
                'captura_elementor' => true,
                'procesar_templates' => true,
                'procesar_elementos' => true,
                'modo_debug' => false,
                'solo_admin' => true,
                'captura_en_idioma_principal' => true,
                'permitir_escaneo' => true // Nuevo: activado por defecto
            ]);
        }
        
        // Cargar opciones
        $this->options = get_option('accessitrans_aria_options', [
            'captura_total' => true,
            'captura_elementor' => true,
            'procesar_templates' => true,
            'procesar_elementos' => true,
            'modo_debug' => false,
            'solo_admin' => true,
            'captura_en_idioma_principal' => true,
            'permitir_escaneo' => true // Nuevo: activado por defecto
        ]);
        
        // Registrar hooks de activación y desactivación
        register_activation_hook(ACCESSITRANS_PATH . 'accessitrans-aria.php', [$this, 'activate']);
        register_deactivation_hook(ACCESSITRANS_PATH . 'accessitrans-aria.php', [$this, 'deactivate']);
        
        // Verificar dependencias básicas
        if (!did_action('elementor/loaded') || !defined('ICL_SITEPRESS_VERSION')) {
            add_action('admin_notices', [$this, 'show_dependencies_notice']);
            return;
        }
        
        // Inicializar componentes
        $this->init_components();
        
        // Borrar caché al guardar configuración de Elementor
        add_action('elementor/editor/after_save', [$this, 'clear_cache_after_elementor_save']);
        
        // Inicializar método específicos si existen dependencias
        if (did_action('elementor/loaded') && defined('ICL_SITEPRESS_VERSION')) {
            $this->init_translation_methods();
        }
    }
    
    /**
     * Inicializa los componentes del plugin
     */
    private function init_components() {
        // Inicializar componentes
        $this->translator = new AccessiTrans_Translator($this);
        $this->capture = new AccessiTrans_Capture($this);
        $this->admin = new AccessiTrans_Admin($this);
        $this->diagnostics = new AccessiTrans_Diagnostics($this);
    }
    
    /**
     * Inicializa los métodos de traducción
     */
    private function init_translation_methods() {
        // Aplicar traducciones al contenido final
        add_filter('elementor/frontend/the_content', [$this->translator, 'translate_aria_attributes'], 1000);
        add_filter('the_content', [$this->translator, 'translate_aria_attributes'], 1000);
        
        // Registro de debug (solo una vez)
        if ($this->options['modo_debug'] && !self::$initialization_logged) {
            $this->log_debug('Plugin inicializado - Versión ' . $this->version);
            self::$initialization_logged = true;
        }
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
     * Activación del plugin
     */
    public function activate() {
        // Asegurarse de que las opciones predeterminadas están configuradas
        if (!get_option('accessitrans_aria_options')) {
            update_option('accessitrans_aria_options', [
                'captura_total' => true,
                'captura_elementor' => true,
                'procesar_templates' => true,
                'procesar_elementos' => true,
                'modo_debug' => false,
                'solo_admin' => true,
                'captura_en_idioma_principal' => true,
                'permitir_escaneo' => true // Nuevo: activado por defecto
            ]);
        }
        
        // Reiniciar flags estáticos
        self::$initialization_logged = false;
    }
    
    /**
     * Desactivación del plugin
     */
    public function deactivate() {
        // Limpiar archivos temporales o caché si es necesario
    }
    
    /**
     * Muestra notificación de dependencias faltantes
     */
    public function show_dependencies_notice() {
        $class = 'notice notice-error';
        $message = __('AccessiTrans requires Elementor and WPML to be installed and activated.', 'accessitrans-aria');
        printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), esc_html($message));
    }
    
    /**
     * Borra la caché después de guardar en Elementor
     */
    public function clear_cache_after_elementor_save() {
        if ($this->translator) {
            $this->translator->clear_cache();
        }
        
        if ($this->capture) {
            $this->capture->clear_cache();
        }
        
        if ($this->options['modo_debug']) {
            $this->log_debug("Caché limpiada después de guardar en Elementor");
        }
    }
    
    /**
     * Obtiene la lista de atributos traducibles
     */
    public function get_traducible_attrs() {
        return $this->traducible_attrs;
    }
    
    /**
     * Obtiene el contexto de las traducciones
     */
    public function get_context() {
        return $this->context;
    }
    
    /**
     * Obtiene la versión del plugin
     */
    public function get_version() {
        return $this->version;
    }
    
    /**
     * Logger para debug optimizado y seguro
     * 
     * @param mixed $message Mensaje o datos a registrar
     * @return void
     */
    public function log_debug($message) {
        // Verificar primero si el modo debug está activo
        if (empty($this->options['modo_debug'])) {
            return;
        }
        
        // Obtener el directorio de uploads
        $upload_dir = wp_upload_dir();
        $log_directory = $upload_dir['basedir'] . '/accessitrans-logs';
        
        // Crear directorio si no existe
        if (!file_exists($log_directory)) {
            wp_mkdir_p($log_directory);
        }
        
        $log_file = $log_directory . '/debug-aria-wpml.log';
        $timestamp = gmdate('[Y-m-d H:i:s] ');
        
        // Convertir arrays/objetos a string de forma segura sin usar funciones de depuración
        if (is_array($message) || is_object($message)) {
            // Usar json_encode como alternativa segura a var_export/print_r
            $formatted_message = 'Array/Object data: ' . json_encode($message);
            // Evitar error_log usando file_put_contents
            @file_put_contents($log_file, $timestamp . $formatted_message . PHP_EOL, FILE_APPEND);
        } else {
            // Asegurar que el mensaje es una cadena
            $safe_message = sanitize_text_field($message);
            // Evitar error_log usando file_put_contents
            @file_put_contents($log_file, $timestamp . $safe_message . PHP_EOL, FILE_APPEND);
        }
    }
    
    /**
     * Verifica si una cadena ya existe en WPML
     * @param string $value Valor a verificar
     * @param string $context Contexto de la cadena (opcional)
     * @return bool True si existe, False si no
     */
    public function exists_in_wpml($value, $context = null) {
        if (empty($value)) {
            return false;
        }
        
        $context = $context ?: $this->get_context();
        
        global $wpdb;
        
        // Buscar por valor exacto
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}icl_strings 
             WHERE value = %s AND context = %s",
            $value,
            $context
        ));
        
        if ($exists) {
            return true;
        }
        
        // También buscar por prefijo para cada atributo
        foreach ($this->get_traducible_attrs() as $attr) {
            $prefixed_name = "{$attr}_{$value}";
            
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}icl_strings 
                 WHERE name = %s AND context = %s",
                $prefixed_name,
                $context
            ));
            
            if ($exists) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Verifica si debe capturar en el idioma actual (método mejorado)
     * @return bool True si debe capturar, False si no
     */
    public function should_capture_in_current_language() {
        // Si la opción está desactivada, siempre permitir captura
        if (!isset($this->options['captura_en_idioma_principal']) || !$this->options['captura_en_idioma_principal']) {
            return true;
        }
        
        // Verificar si WPML está disponible antes de usar sus filtros
        if (!function_exists('icl_object_id') || !function_exists('apply_filters')) {
            if ($this->options['modo_debug']) {
                $this->log_debug("WPML no está completamente inicializado - omitiendo captura por precaución");
            }
            return false;
        }
        
        // Obtener idioma actual y predeterminado con verificación
        $current_language = apply_filters('wpml_current_language', null);
        $default_language = apply_filters('wpml_default_language', null);
        
        // Si alguno de los idiomas no está definido, ser cauteloso y no capturar
        if (empty($current_language) || empty($default_language)) {
            if ($this->options['modo_debug']) {
                $this->log_debug("No se puede determinar idioma actual o predeterminado");
            }
            return false;
        }
        
        $result = ($current_language === $default_language);
        
        if ($this->options['modo_debug'] && !$result) {
            $this->log_debug("Captura omitida - Idioma actual ({$current_language}) no es el principal ({$default_language})");
        }
        
        return $result;
    }
    
    /**
     * Verifica si debe realizar captura basado en opciones generales
     * @return bool True si debe capturar, False si no
     */
    public function should_capture() {
        // Si la captura está globalmente desactivada, no realizar captura
        if (!isset($this->options['permitir_escaneo']) || !$this->options['permitir_escaneo']) {
            if ($this->options['modo_debug']) {
                $this->log_debug("Captura omitida - Escaneo global desactivado");
            }
            return false;
        }
        
        // Verificación especial para entorno de administración
        if (is_admin() && !wp_doing_ajax()) {
            // En admin, ser extra cauteloso y verificar explícitamente idioma
            $current_language = apply_filters('wpml_current_language', null);
            $default_language = apply_filters('wpml_default_language', null);
            
            if ($current_language !== $default_language) {
                if ($this->options['modo_debug']) {
                    $this->log_debug("Captura omitida en admin - Idioma actual ({$current_language}) no es el principal ({$default_language})");
                }
                return false;
            }
        }
        
        // Verificar idioma actual
        return $this->should_capture_in_current_language();
    }
}