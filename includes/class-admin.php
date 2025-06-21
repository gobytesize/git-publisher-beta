<?php
if (!defined('ABSPATH')) {
    exit;
}

class EGP_Admin {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'init_settings'));

        // Handle debug actions
        add_action('admin_post_egp_clear_logs', array($this, 'clear_logs'));
        add_action('admin_post_egp_test_workflow', array($this, 'test_workflow'));
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
        $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'settings';
        ?>
        <div class="wrap">
            <h1>🚀 Elementor Git Publisher</h1>

            <nav class="nav-tab-wrapper">
                <a href="?page=elementor-git-publisher&tab=settings" class="nav-tab <?php echo $active_tab == 'settings' ? 'nav-tab-active' : ''; ?>">
                    ⚙️ Settings
                </a>
                <a href="?page=elementor-git-publisher&tab=debug" class="nav-tab <?php echo $active_tab == 'debug' ? 'nav-tab-active' : ''; ?>">
                    🐛 Debug Logs
                </a>
            </nav>

            <?php if ($active_tab == 'settings'): ?>
                <?php $this->render_settings_tab(); ?>
            <?php elseif ($active_tab == 'debug'): ?>
                <?php $this->render_debug_tab(); ?>
            <?php endif; ?>
        </div>
        <?php
    }

    private function render_settings_tab() {
        ?>
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
                <li>Edit a page and check "Enable Git workflow" in the Git Publisher meta box</li>
                <li>Save in Elementor to create your first PR! 🚀</li>
            </ol>
        </div>

        <?php $this->connection_test(); ?>
        <?php
    }

    private function render_debug_tab() {
        $logger = EGP_Logger::get_instance();
        $logs = $logger->get_logs();

        ?>
        <div style="margin: 20px 0;">
            <h2>🐛 Debug Information</h2>

            <div style="margin-bottom: 20px;">
                <a href="<?php echo admin_url('admin-post.php?action=egp_clear_logs'); ?>" class="button" onclick="return confirm('Clear all logs?');">
                    🗑️ Clear Logs
                </a>
                <a href="<?php echo admin_url('admin-post.php?action=egp_test_workflow'); ?>" class="button button-primary">
                    🧪 Test Workflow
                </a>
            </div>

            <div style="background: white; border: 1px solid #ddd; border-radius: 5px; padding: 15px;">
                <h3>System Status</h3>
                <table class="widefat">
                    <tr>
                        <td><strong>Plugin Version:</strong></td>
                        <td><?php echo EGP_VERSION; ?></td>
                    </tr>
                    <tr>
                        <td><strong>Elementor Active:</strong></td>
                        <td><?php echo class_exists('Elementor\Plugin') ? '✅ Yes' : '❌ No'; ?></td>
                    </tr>
                    <tr>
                        <td><strong>GitHub Token Set:</strong></td>
                        <td><?php echo get_option('egp_github_token') ? '✅ Yes' : '❌ No'; ?></td>
                    </tr>
                    <tr>
                        <td><strong>GitHub Repo Set:</strong></td>
                        <td><?php echo get_option('egp_github_repo') ? '✅ Yes (' . esc_html(get_option('egp_github_repo')) . ')' : '❌ No'; ?></td>
                    </tr>
                    <tr>
                        <td><strong>Plugin Enabled:</strong></td>
                        <td><?php echo get_option('egp_enabled') === 'yes' ? '✅ Yes' : '❌ No'; ?></td>
                    </tr>
                    <tr>
                        <td><strong>WordPress Version:</strong></td>
                        <td><?php echo get_bloginfo('version'); ?></td>
                    </tr>
                    <tr>
                        <td><strong>PHP Version:</strong></td>
                        <td><?php echo PHP_VERSION; ?></td>
                    </tr>
                </table>
            </div>

            <div style="margin-top: 20px; background: white; border: 1px solid #ddd; border-radius: 5px; padding: 15px;">
                <h3>Recent Activity Logs (<?php echo count($logs); ?> entries)</h3>

                <?php if (empty($logs)): ?>
                    <p>No logs yet. Try editing an Elementor page with Git workflow enabled!</p>
                <?php else: ?>
                    <div style="max-height: 500px; overflow-y: auto;">
                        <?php foreach ($logs as $log): ?>
                            <div style="margin-bottom: 15px; padding: 10px; border-left: 4px solid <?php
                            echo $log['level'] === 'error' ? '#dc3545' :
                                ($log['level'] === 'debug' ? '#17a2b8' : '#28a745');
                            ?>; background: #f8f9fa;">
                                <div style="font-weight: bold; margin-bottom: 5px;">
                                    <?php
                                    $level_emoji = $log['level'] === 'error' ? '❌' : ($log['level'] === 'debug' ? '🔍' : 'ℹ️');
                                    echo $level_emoji . ' ' . ucfirst($log['level']);
                                    ?>
                                    <span style="float: right; font-weight: normal; font-size: 12px;">
                                        <?php echo esc_html($log['timestamp']); ?>
                                    </span>
                                </div>
                                <div style="margin-bottom: 5px;">
                                    <?php echo esc_html($log['message']); ?>
                                </div>
                                <?php if (!empty($log['context'])): ?>
                                    <details style="margin-top: 5px;">
                                        <summary style="cursor: pointer; font-size: 12px;">View Context</summary>
                                        <pre style="background: #e9ecef; padding: 10px; margin-top: 5px; font-size: 11px; overflow-x: auto;"><?php echo esc_html(json_encode($log['context'], JSON_PRETTY_PRINT)); ?></pre>
                                    </details>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    public function clear_logs() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        EGP_Logger::get_instance()->clear_logs();
        egp_log_info('Debug logs cleared by user');

        wp_redirect(admin_url('options-general.php?page=elementor-git-publisher&tab=debug&cleared=1'));
        exit;
    }

    public function test_workflow() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        egp_log_info('Manual workflow test triggered');

        // Find a page with Elementor to test
        $pages = get_posts([
            'post_type' => 'page',
            'meta_key' => '_elementor_edit_mode',
            'meta_value' => 'builder',
            'posts_per_page' => 1
        ]);

        if (empty($pages)) {
            egp_log_error('No Elementor pages found for testing');
            wp_redirect(admin_url('options-general.php?page=elementor-git-publisher&tab=debug&test_error=1'));
            exit;
        }

        $test_page = $pages[0];
        egp_log_debug('Testing with page', ['page_id' => $test_page->ID, 'title' => $test_page->post_title]);

        // Simulate the workflow
        if (class_exists('EGP_Elementor_Hooks')) {
            $hooks = new EGP_Elementor_Hooks();
            // We'll add a public test method to the hooks class
        }

        wp_redirect(admin_url('options-general.php?page=elementor-git-publisher&tab=debug&test_complete=1'));
        exit;
    }

    // ... rest of the existing methods stay the same ...

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

    egp_log_info('GitHub connection test initiated');

    $token = get_option('egp_github_token');
    $repo = get_option('egp_github_repo');

    if (empty($token) || empty($repo)) {
        egp_log_error('Connection test failed: Missing token or repo');
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
        egp_log_error('GitHub API connection failed', ['error' => $response->get_error_message()]);
        wp_send_json_error('Failed to connect to GitHub: ' . $response->get_error_message());
    }

    $code = wp_remote_retrieve_response_code($response);
    if ($code !== 200) {
        egp_log_error('GitHub API returned error', ['code' => $code, 'response' => wp_remote_retrieve_body($response)]);
        wp_send_json_error('GitHub API returned error code: ' . $code);
    }

    egp_log_info('GitHub connection test successful');
    wp_send_json_success('Connection successful!');
}