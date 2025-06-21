<?php
if (!defined('ABSPATH')) {
    exit;
}

class EGP_Elementor_Hooks {

    public function __construct() {
        egp_log_info('Elementor hooks initialized');

        // Add meta box to enable Git workflow per page
        add_action('add_meta_boxes', array($this, 'add_git_meta_box'));
        add_action('save_post', array($this, 'save_git_meta_box'));

        // Hook into Elementor save
        add_action('elementor/document/after_save', array($this, 'after_elementor_save'), 10, 2);

        // Add admin notices
        add_action('admin_notices', array($this, 'show_git_notices'));
    }

    public function add_git_meta_box() {
        add_meta_box(
            'egp_git_settings',
            '🚀 Git Publisher',
            array($this, 'git_meta_box_html'),
            'page',
            'side',
            'high'
        );
    }

    public function git_meta_box_html($post) {
        wp_nonce_field('egp_git_meta_box', 'egp_git_meta_box_nonce');

        $git_enabled = get_post_meta($post->ID, '_egp_git_enabled', true);
        $pending_pr = get_post_meta($post->ID, '_egp_pending_pr', true);

        ?>
        <div style="margin: 10px 0;">
            <label>
                <input type="checkbox" name="egp_git_enabled" value="1" <?php checked($git_enabled, '1'); ?> />
                Enable Git workflow for this page
            </label>
        </div>

        <?php if ($pending_pr): ?>
            <div style="margin: 15px 0; padding: 10px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 4px;">
                <strong>⏳ Pending Review</strong><br>
                <small>This page has changes waiting for review.</small><br>
                <a href="<?php echo esc_url($pending_pr['pr_url']); ?>" target="_blank" class="button button-small" style="margin-top: 5px;">
                    View Pull Request
                </a>
            </div>
        <?php endif; ?>

        <div style="margin: 15px 0; font-size: 12px; color: #666;">
            <strong>How it works:</strong><br>
            • Save changes in Elementor<br>
            • Creates GitHub pull request<br>
            • Review and merge to publish<br>
            • Full version history
        </div>

        <div style="margin: 15px 0; padding: 8px; background: #e7f3ff; border-radius: 3px; font-size: 11px;">
            <strong>Debug Info:</strong><br>
            Post ID: <?php echo $post->ID; ?><br>
            Git Enabled: <?php echo $git_enabled ? 'Yes' : 'No'; ?><br>
            Is Elementor: <?php echo get_post_meta($post->ID, '_elementor_edit_mode', true) === 'builder' ? 'Yes' : 'No'; ?>
        </div>
        <?php
    }

    public function save_git_meta_box($post_id) {
        if (!isset($_POST['egp_git_meta_box_nonce']) ||
            !wp_verify_nonce($_POST['egp_git_meta_box_nonce'], 'egp_git_meta_box')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        $git_enabled = isset($_POST['egp_git_enabled']) ? '1' : '0';
        update_post_meta($post_id, '_egp_git_enabled', $git_enabled);

        egp_log_debug('Git workflow setting updated', [
            'post_id' => $post_id,
            'enabled' => $git_enabled
        ]);
    }

    public function after_elementor_save($document, $data) {
        $post_id = $document->get_post()->ID;

        egp_log_info('Elementor save detected', [
            'post_id' => $post_id,
            'title' => $document->get_post()->post_title
        ]);

        // Only process if Git is enabled for this page
        if (!$this->is_git_enabled($post_id)) {
            egp_log_debug('Git workflow not enabled for this page', ['post_id' => $post_id]);
            return;
        }

        egp_log_info('Processing Git workflow for page', ['post_id' => $post_id]);

        // Check if plugin is properly configured
        if (!$this->is_plugin_configured()) {
            egp_log_error('Plugin not properly configured');
            $this->add_admin_notice('error', 'Git Publisher is not configured. Please configure GitHub settings first.');
            return;
        }

        egp_log_debug('Plugin configuration verified');

        // Prepare page data
        $page_data = $this->prepare_page_data($document->get_post(), $data);
        egp_log_debug('Page data prepared', ['data_keys' => array_keys($page_data)]);

        // Send to GitHub
        $github_manager = new EGP_GitHub_Manager();
        egp_log_info('Sending to GitHub...');

        $result = $github_manager->create_page_branch($post_id, $page_data);

        if (is_wp_error($result)) {
            egp_log_error('GitHub operation failed', [
                'error_code' => $result->get_error_code(),
                'error_message' => $result->get_error_message()
            ]);
            $this->add_admin_notice('error', 'Failed to create GitHub pull request: ' . $result->get_error_message());
            return;
        }

        egp_log_info('GitHub PR created successfully', [
            'pr_url' => $result['pr_url'],
            'pr_number' => $result['pr_number']
        ]);

        // Store PR information
        update_post_meta($post_id, '_egp_pending_pr', [
            'pr_url' => $result['pr_url'],
            'pr_number' => $result['pr_number'],
            'branch' => $result['branch'],
            'created_at' => current_time('mysql')
        ]);

        // Add success notice
        $this->add_admin_notice('success',
            'Page changes sent to GitHub for review! <a href="' . esc_url($result['pr_url']) . '" target="_blank">View Pull Request</a>'
        );
    }

    private function prepare_page_data($post, $elementor_data) {
        return [
            'post_id' => $post->ID,
            'title' => $post->post_title,
            'slug' => $post->post_name,
            'status' => $post->post_status,
            'elementor_data' => $elementor_data,
            'template' => get_page_template_slug($post->ID),
            'featured_image_id' => get_post_thumbnail_id($post->ID),
            'updated_at' => current_time('mysql'),
            'author' => wp_get_current_user()->display_name,
            'author_id' => get_current_user_id()
        ];
    }

    private function is_git_enabled($post_id) {
        $enabled = get_post_meta($post_id, '_egp_git_enabled', true) === '1';
        egp_log_debug('Checking if Git enabled for post', [
            'post_id' => $post_id,
            'enabled' => $enabled
        ]);
        return $enabled;
    }

    private function is_plugin_configured() {
        $token = get_option('egp_github_token');
        $repo = get_option('egp_github_repo');
        $enabled = get_option('egp_enabled');

        $configured = !empty($token) && !empty($repo) && $enabled === 'yes';

        egp_log_debug('Plugin configuration check', [
            'has_token' => !empty($token),
            'has_repo' => !empty($repo),
            'plugin_enabled' => $enabled === 'yes',
            'fully_configured' => $configured
        ]);

        return $configured;
    }

    private function add_admin_notice($type, $message) {
        $notices = get_transient('egp_admin_notices') ?: [];
        $notices[] = ['type' => $type, 'message' => $message];
        set_transient('egp_admin_notices', $notices, 30);

        egp_log_info('Admin notice added', ['type' => $type, 'message' => $message]);
    }

    public function show_git_notices() {
        $notices = get_transient('egp_admin_notices');
        if (!$notices) {
            return;
        }

        foreach ($notices as $notice) {
            $class = $notice['type'] === 'error' ? 'notice-error' : 'notice-success';
            echo '<div class="notice ' . esc_attr($class) . ' is-dismissible">';
            echo '<p>' . wp_kses_post($notice['message']) . '</p>';
            echo '</div>';
        }

        delete_transient('egp_admin_notices');
    }
}