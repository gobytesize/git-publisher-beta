<?php
if (!defined('ABSPATH')) {
    exit;
}

class EGP_Logger {
    private static $instance = null;
    private $log_option = 'egp_debug_logs';
    private $max_logs = 100;

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function log($level, $message, $context = []) {
        $logs = get_option($this->log_option, []);

        $log_entry = [
            'timestamp' => current_time('mysql'),
            'level' => $level,
            'message' => $message,
            'context' => $context,
            'user_id' => get_current_user_id(),
            'page_url' => isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '',
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : ''
        ];

        // Add to beginning of array
        array_unshift($logs, $log_entry);

        // Keep only recent logs
        $logs = array_slice($logs, 0, $this->max_logs);

        // Save to database
        update_option($this->log_option, $logs);

        // Also log to WP error log if it's an error
        if ($level === 'error') {
            error_log("EGP Error: {$message} | Context: " . json_encode($context));
        }

        // Console log for admin users
        if (current_user_can('manage_options')) {
            add_action('admin_footer', function() use ($level, $message, $context) {
                ?>
                <script>
                    console.log('EGP <?php echo esc_js(ucfirst($level)); ?>: <?php echo esc_js($message); ?>', <?php echo json_encode($context); ?>);
                </script>
                <?php
            });
        }
    }

    public function info($message, $context = []) {
        $this->log('info', $message, $context);
    }

    public function error($message, $context = []) {
        $this->log('error', $message, $context);
    }

    public function debug($message, $context = []) {
        $this->log('debug', $message, $context);
    }

    public function get_logs($limit = 50) {
        $logs = get_option($this->log_option, []);
        return array_slice($logs, 0, $limit);
    }

    public function clear_logs() {
        delete_option($this->log_option);
    }
}

// Helper functions
function egp_log($level, $message, $context = []) {
    EGP_Logger::get_instance()->log($level, $message, $context);
}

function egp_log_info($message, $context = []) {
    EGP_Logger::get_instance()->info($message, $context);
}

function egp_log_error($message, $context = []) {
    EGP_Logger::get_instance()->error($message, $context);
}

function egp_log_debug($message, $context = []) {
    EGP_Logger::get_instance()->debug($message, $context);
}