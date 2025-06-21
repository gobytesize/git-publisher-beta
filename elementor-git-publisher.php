<?php
/**
 * Plugin Name: Elementor Git Publisher
 * Description: Publish Elementor pages through Git workflow
 * Version: 1.0.0
 * Author: Your Name
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define constants
define('EGP_VERSION', '1.0.0');
define('EGP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('EGP_PLUGIN_URL', plugin_dir_url(__FILE__));

// Main plugin class
class ElementorGitPublisher {
    
    public function __construct() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        add_action('init', array($this, 'init'));
    }
    
    public function init() {
        // Check if Elementor is active
        if (!class_exists('Elementor\Plugin')) {
            add_action('admin_notices', array($this, 'elementor_missing_notice'));
            return;
        }
        
        // Load our classes
        $this->load_classes();
        
        // Initialize admin area
        if (is_admin()) {
            new EGP_Admin();
        }
        
        // Initialize Elementor hooks
        if (class_exists('EGP_Elementor_Hooks')) {
            new EGP_Elementor_Hooks();
        }
    }
    
    private function load_classes() {
        // Only load if files exist
        $files = array(
            'admin' => EGP_PLUGIN_DIR . 'includes/class-admin.php',
            'github-manager' => EGP_PLUGIN_DIR . 'includes/class-github-manager.php',
            'elementor-hooks' => EGP_PLUGIN_DIR . 'includes/class-elementor-hooks.php'
        );
        
        foreach ($files as $key => $file) {
            if (file_exists($file)) {
                require_once $file;
            }
        }
    }
    
    public function activate() {
        // Create options with defaults
        add_option('egp_github_token', '');
        add_option('egp_github_repo', '');
        add_option('egp_enabled', 'no');
    }
    
    public function deactivate() {
        // Cleanup if needed
    }
    
    public function elementor_missing_notice() {
        ?>
        <div class="notice notice-error">
            <p><strong>Elementor Git Publisher:</strong> This plugin requires Elementor to be installed and activated.</p>
        </div>
        <?php
    }
}

// Initialize plugin
new ElementorGitPublisher();