<?php
if (!defined('ABSPATH')) {
    exit;
}

class EGP_Admin {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'init_settings'));
    }
    
    public function add_admin_menu() {
        add_options_page(
            'Git Publisher Settings',
            'Git Publisher', 
            'manage_options',
            'elementor-git-publisher',
            array($this, 'settings_page')
        );
    }
    
    public function init_settings() {
        register_setting('egp_settings', 'egp_github_token');
        register_setting('egp_settings', 'egp_github_repo');
        register_setting('egp_settings', 'egp_enabled');
        
        add_settings_section(
            'egp_github_section',
            'GitHub Configuration',
            array($this, 'github_section_callback'),
            'egp_settings'
        );
        
        add_settings_field(
            'egp_github_token',
            'GitHub Token',
            array($this, 'github_token_callback'),
            'egp_settings',
            'egp_github_section'
        );
        
        add_settings_field(
            'egp_github_repo',
            'GitHub Repository',
            array($this, 'github_repo_callback'),
            'egp_settings', 
            'egp_github_section'
        );
        
        add_settings_field(
            'egp_enabled',
            'Enable Git Publishing',
            array($this, 'enabled_callback'),
            'egp_settings',
            'egp_github_section'
        );
    }
    
    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>🚀 Elementor Git Publisher</h1>
            <p>Configure your GitHub integration for Elementor page publishing.</p>
            
            <?php if (isset($_GET['settings-updated'])): ?>
                <div class="notice notice-success">
                    <p>Settings saved! 🎉</p>
                </div>
            <?php endif; ?>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('egp_settings');
                do_settings_sections('egp_settings');
                submit_button('Save Settings');
                ?>
            </form>
            
            <div style="margin-top: 30px; padding: 20px; background: #f9f9f9; border-radius: 5px;">
                <h3>📋 Setup Instructions</h3>
                <ol>
                    <li>Create a GitHub repository for your page backups</li>
                    <li>Generate a GitHub Personal Access Token with 'repo' permissions</li>
                    <li>Enter your token and repository above</li>
                    <li>Enable Git Publishing</li>
                    <li>You're ready to go! 🚀</li>
                </ol>
            </div>
            
            <?php $this->connection_test(); ?>
        </div>
        <?php
    }
    
    public function github_section_callback() {
        echo '<p>Configure your GitHub repository for storing page versions.</p>';
    }
    
    public function github_token_callback() {
        $value = get_option('egp_github_token', '');
        echo '<input type="password" name="egp_github_token" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">Generate at: <a href="https://github.com/settings/tokens" target="_blank">GitHub Settings → Developer settings → Personal access tokens</a></p>';
    }
    
    public function github_repo_callback() {
        $value = get_option('egp_github_repo', '');
        echo '<input type="text" name="egp_github_repo" value="' . esc_attr($value) . '" class="regular-text" placeholder="username/repository-name" />';
        echo '<p class="description">Format: username/repository-name (e.g., johndoe/my-website-pages)</p>';
    }
    
    public function enabled_callback() {
        $value = get_option('egp_enabled', 'no');
        echo '<label><input type="checkbox" name="egp_enabled" value="yes" ' . checked($value, 'yes', false) . ' /> Enable Git workflow for Elementor pages</label>';
    }
    
    private function connection_test() {
        $token = get_option('egp_github_token');
        $repo = get_option('egp_github_repo');
        
        if (empty($token) || empty($repo)) {
            return;
        }
        
        ?>
        <div style="margin-top: 20px; padding: 15px; background: white; border: 1px solid #ddd; border-radius: 5px;">
            <h4>🔍 Connection Test</h4>
            <p id="connection-status">Testing connection...</p>
            <button type="button" id="test-connection" class="button">Test GitHub Connection</button>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('#test-connection').click(function() {
                const button = $(this);
                const status = $('#connection-status');
                
                button.prop('disabled', true).text('Testing...');
                status.text('Testing GitHub connection...');
                
                $.post(ajaxurl, {
                    action: 'egp_test_connection',
                    nonce: '<?php echo wp_create_nonce('egp_test_connection'); ?>'
                })
                .done(function(response) {
                    if (response.success) {
                        status.html('✅ <strong>Connection successful!</strong> Ready to publish pages.');
                    } else {
                        status.html('❌ <strong>Connection failed:</strong> ' + response.data);
                    }
                })
                .fail(function() {
                    status.html('❌ <strong>Connection failed:</strong> Please check your settings.');
                })
                .always(function() {
                    button.prop('disabled', false).text('Test GitHub Connection');
                });
            });
            
            // Auto-test on page load
            $('#test-connection').click();
        });
        </script>
        <?php
    }
}

// Add AJAX handler for connection test
add_action('wp_ajax_egp_test_connection', 'egp_test_github_connection');

function egp_test_github_connection() {
    if (!wp_verify_nonce($_POST['nonce'], 'egp_test_connection')) {
        wp_die('Security check failed');
    }
    
    $token = get_option('egp_github_token');
    $repo = get_option('egp_github_repo');
    
    if (empty($token) || empty($repo)) {
        wp_send_json_error('Please enter both GitHub token and repository.');
    }
    
    // Test GitHub API connection
    $response = wp_remote_get('https://api.github.com/repos/' . $repo, array(
        'headers' => array(
            'Authorization' => 'token ' . $token,
            'User-Agent' => 'Elementor-Git-Publisher'
        )
    ));
    
    if (is_wp_error($response)) {
        wp_send_json_error('Failed to connect to GitHub: ' . $response->get_error_message());
    }
    
    $code = wp_remote_retrieve_response_code($response);
    if ($code !== 200) {
        wp_send_json_error('GitHub API returned error code: ' . $code);
    }
    
    wp_send_json_success('Connection successful!');
}