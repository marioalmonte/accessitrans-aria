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
    private $version = '0.2.3r';
    
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
                'captura_en_idioma_principal' => true
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
            'captura_en_idioma_principal' => true
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
                'captura_en_idioma_principal' => true
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
        $message = __('AccessiTrans requiere que Elementor y WPML estén instalados y activados.', 'accessitrans-aria');
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
     * Logger para debug optimizado
     */
    public function log_debug($message) {
        if (!$this->options['modo_debug']) {
            return;
        }
        
        $log_file = WP_CONTENT_DIR . '/debug-aria-wpml.log';
        
        if (is_array($message) || is_object($message)) {
            error_log(date('[Y-m-d H:i:s] ') . print_r($message, true) . PHP_EOL, 3, $log_file);
        } else {
            error_log(date('[Y-m-d H:i:s] ') . $message . PHP_EOL, 3, $log_file);
        }
    }
    
    /**
     * Verifica si debe capturar en el idioma actual
     */
    public function should_capture_in_current_language() {
        if (!$this->options['captura_en_idioma_principal']) {
            return true;
        }
        
        $current_language = apply_filters('wpml_current_language', null);
        $default_language = apply_filters('wpml_default_language', null);
        
        return ($current_language === $default_language);
    }
}