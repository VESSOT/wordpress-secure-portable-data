<?php
/**
 * Plugin Name: VESSOT Secure Portable Data
 * Plugin URI: https://vessot.tech/wordpress
 * Description: Zero visibility data storage and consolidation with client-side encryption
 * Version: 1.0.1
 * Author: VESSOT
 * Author URI: https://vessot.tech
 * License: MIT
 * Text Domain: vessot-secure-portable-data
 * Requires PHP: 8.2
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('VESSOT_SECURE_PORTABLE_DATA_VERSION', '1.0.1');
define('VESSOT_SECURE_PORTABLE_DATA_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('VESSOT_SECURE_PORTABLE_DATA_PLUGIN_URL', plugin_dir_url(__FILE__));

// Autoload classes
spl_autoload_register(function ($class) {
    $prefix = 'Vessot\\';
    $base_dir = VESSOT_SECURE_PORTABLE_DATA_PLUGIN_DIR . 'src/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

/**
 * Initialize the plugin
 */
function vessot_secure_portable_data_init() {
    // Check PHP version
    if (version_compare(PHP_VERSION, '8.2', '<')) {
        add_action('admin_notices', function() {
            echo '<div class="error"><p>' .
                 esc_html__('VESSOT Secure Portable Data requires PHP 8.2 or higher.', 'vessot-secure-portable-data') .
                 '</p></div>';
        });
        return;
    }

    // Check OpenSSL extension
    if (!extension_loaded('openssl')) {
        add_action('admin_notices', function() {
            echo '<div class="error"><p>' .
                 esc_html__('VESSOT Secure Portable Data requires the OpenSSL PHP extension.', 'vessot-secure-portable-data') .
                 '</p></div>';
        });
        return;
    }
}
add_action('plugins_loaded', 'vessot_secure_portable_data_init');

/**
 * Helper function to get Vessot Data instance
 *
 * @param string $api_url Optional custom API URL
 * @return \Vessot\Service\Data
 */
function vessot_secure_portable_data($api_url = 'https://vessot.tech/api') {
    return new \Vessot\Service\Data($api_url);
}

/**
 * Activation hook
 */
function vessot_secure_portable_data_activate() {
    // Check requirements on activation
    if (version_compare(PHP_VERSION, '8.2', '<')) {
        wp_die(
            esc_html__('VESSOT Secure Portable Data requires PHP 8.2 or higher.', 'vessot-secure-portable-data'),
            esc_html__('Plugin Activation Error', 'vessot-secure-portable-data'),
            array('back_link' => true)
        );
    }

    if (!extension_loaded('openssl')) {
        wp_die(
            esc_html__('VESSOT Secure Portable Data requires the OpenSSL PHP extension.', 'vessot-secure-portable-data'),
            esc_html__('Plugin Activation Error', 'vessot-secure-portable-data'),
            array('back_link' => true)
        );
    }
}
register_activation_hook(__FILE__, 'vessot_secure_portable_data_activate');

/**
 * Add settings page to admin menu
 */
function vessot_secure_portable_data_admin_menu() {
    $hook = add_options_page(
        esc_html__('VESSOT Secure Portable Data', 'vessot-secure-portable-data'),
        esc_html__('VESSOT Secure Portable Data', 'vessot-secure-portable-data'),
        'manage_options',
        'vessot-secure-portable-data',
        'vessot_secure_portable_data_settings_page'
    );

    // Enqueue styles only on this plugin's settings page
    add_action('admin_print_styles-' . $hook, 'vessot_secure_portable_data_enqueue_admin_styles');
}
add_action('admin_menu', 'vessot_secure_portable_data_admin_menu');

/**
 * Enqueue admin styles for settings page
 */
function vessot_secure_portable_data_enqueue_admin_styles() {
    $custom_css = "
        .vessot-readme-content {
            background: #fff;
            padding: 20px;
            margin-top: 20px;
            border: 1px solid #ccd0d4;
            box-shadow: 0 1px 1px rgba(0,0,0,.04);
        }
        .vessot-readme-content h1 {
            font-size: 2em;
            margin-top: 0.67em;
            margin-bottom: 0.67em;
        }
        .vessot-readme-content h2 {
            font-size: 1.5em;
            margin-top: 1em;
            margin-bottom: 0.5em;
            border-bottom: 1px solid #eee;
            padding-bottom: 0.3em;
        }
        .vessot-readme-content h3 {
            font-size: 1.25em;
            margin-top: 1em;
            margin-bottom: 0.5em;
        }
        .vessot-readme-content h4 {
            font-size: 1.1em;
            margin-top: 1em;
            margin-bottom: 0.5em;
        }
        .vessot-readme-content pre {
            background: #f6f8fa;
            padding: 16px;
            overflow: auto;
            border-radius: 3px;
            border: 1px solid #e1e4e8;
        }
        .vessot-readme-content code {
            background: #f6f8fa;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: monospace;
        }
        .vessot-readme-content pre code {
            background: none;
            padding: 0;
        }
        .vessot-readme-content ul,
        .vessot-readme-content ol {
            padding-left: 2em;
            margin: 1em 0;
            list-style-position: outside;
        }
        .vessot-readme-content ul {
            list-style-type: disc;
        }
        .vessot-readme-content ol {
            list-style-type: decimal;
        }
        .vessot-readme-content li {
            margin: 0.5em 0;
            display: list-item;
        }
        .vessot-readme-content p {
            margin: 1em 0;
            line-height: 1.6;
        }
        .vessot-readme-content a {
            color: #0073aa;
            text-decoration: none;
        }
        .vessot-readme-content a:hover {
            text-decoration: underline;
        }
    ";

    wp_register_style('vessot-secure-portable-data-admin', false, array(), VESSOT_SECURE_PORTABLE_DATA_VERSION);
    wp_enqueue_style('vessot-secure-portable-data-admin');
    wp_add_inline_style('vessot-secure-portable-data-admin', $custom_css);
}

/**
 * Render settings page
 */
function vessot_secure_portable_data_settings_page() {
    // Only allow administrators to view this page
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'vessot-secure-portable-data'));
    }

    $readme_file = VESSOT_SECURE_PORTABLE_DATA_PLUGIN_DIR . 'README.md';
    $readme_content = file_exists($readme_file) ? file_get_contents($readme_file) : '';

    // Convert markdown to HTML (basic conversion)
    $html_content = vessot_secure_portable_data_markdown_to_html($readme_content);

    // Define allowed HTML tags for the documentation
    $allowed_html = array(
        'h1' => array(),
        'h2' => array(),
        'h3' => array(),
        'h4' => array(),
        'p' => array(),
        'a' => array('href' => array(), 'target' => array(), 'rel' => array()),
        'strong' => array(),
        'em' => array(),
        'ul' => array(),
        'ol' => array(),
        'li' => array(),
        'pre' => array(),
        'code' => array(),
        'br' => array(),
    );

    ?>
    <div class="wrap">
        <h1><?php echo esc_html__('VESSOT Secure Portable Data - Documentation', 'vessot-secure-portable-data'); ?></h1>
        <div class="vessot-readme-content">
            <?php echo wp_kses($html_content, $allowed_html); ?>
        </div>
    </div>
    <?php
}

/**
 * Convert markdown to HTML (basic conversion)
 */
function vessot_secure_portable_data_markdown_to_html($markdown) {
    if (empty($markdown)) {
        return '<p>README file not found.</p>';
    }

    // Remove WordPress.org plugin header section (=== Plugin Name === until the # title)
    // This removes everything from === until we hit a line starting with #
    $markdown = preg_replace('/^===.*?\n\n(?=#)/s', '', $markdown);

    // Escape HTML first
    $html = htmlspecialchars($markdown, ENT_QUOTES, 'UTF-8');

    // Code blocks (```language...```)
    $html = preg_replace('/```[\w]*\n(.*?)\n```/s', '<pre><code>$1</code></pre>', $html);

    // Headings
    $html = preg_replace('/^#### (.+)$/m', '<h4>$1</h4>', $html);
    $html = preg_replace('/^### (.+)$/m', '<h3>$1</h3>', $html);
    $html = preg_replace('/^## (.+)$/m', '<h2>$1</h2>', $html);
    $html = preg_replace('/^# (.+)$/m', '<h1>$1</h1>', $html);

    // Bold and italic
    $html = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $html);
    $html = preg_replace('/\*(.+?)\*/s', '<em>$1</em>', $html);

    // Links [text](url)
    $html = preg_replace('/\[([^\]]+)\]\(([^\)]+)\)/', '<a href="$2" target="_blank" rel="noopener noreferrer">$1</a>', $html);

    // Inline code (before auto-linking URLs)
    $html = preg_replace('/`([^`]+)`/', '<code>$1</code>', $html);

    // Auto-link plain URLs (but not if already in a link or code block)
    // Exclude trailing punctuation (. , ; : ! ?) from URLs
    $html = preg_replace('/(?<!href="|">|<code>)(https?:\/\/[^\s<]+?)([.,;:!?]?)(\s|<|$)/', '<a href="$1" target="_blank" rel="noopener noreferrer">$1</a>$2$3', $html);

    // Convert lists properly - line by line
    $lines = explode("\n", $html);
    $in_list = false;
    $in_ordered_list = false;
    $result = [];

    foreach ($lines as $line) {
        // Unordered list item
        if (preg_match('/^- (.+)$/', $line, $matches)) {
            if (!$in_list) {
                $result[] = '<ul>';
                $in_list = true;
            }
            $result[] = '<li>' . $matches[1] . '</li>';
        }
        // Ordered list item
        elseif (preg_match('/^\d+\. (.+)$/', $line, $matches)) {
            if (!$in_ordered_list) {
                $result[] = '<ol>';
                $in_ordered_list = true;
            }
            $result[] = '<li>' . $matches[1] . '</li>';
        }
        // Not a list item
        else {
            if ($in_list) {
                $result[] = '</ul>';
                $in_list = false;
            }
            if ($in_ordered_list) {
                $result[] = '</ol>';
                $in_ordered_list = false;
            }
            $result[] = $line;
        }
    }

    // Close any open lists
    if ($in_list) {
        $result[] = '</ul>';
    }
    if ($in_ordered_list) {
        $result[] = '</ol>';
    }

    $html = implode("\n", $result);

    // Paragraphs (lines separated by blank lines)
    $html = preg_replace('/\n\n+/', '</p><p>', $html);
    $html = '<p>' . $html . '</p>';

    // Clean up empty paragraphs and improper paragraph wrapping
    $html = preg_replace('/<p>\s*<\/p>/', '', $html);
    $html = preg_replace('/<p>\s*(<h[1-6]>)/', '$1', $html);
    $html = preg_replace('/(<\/h[1-6]>)\s*<\/p>/', '$1', $html);
    $html = preg_replace('/<p>\s*(<pre>)/', '$1', $html);
    $html = preg_replace('/(<\/pre>)\s*<\/p>/', '$1', $html);
    $html = preg_replace('/<p>\s*(<ul>)/', '$1', $html);
    $html = preg_replace('/(<\/ul>)\s*<\/p>/', '$1', $html);
    $html = preg_replace('/<p>\s*(<ol>)/', '$1', $html);
    $html = preg_replace('/(<\/ol>)\s*<\/p>/', '$1', $html);

    return $html;
}

/**
 * Add settings link on plugin page
 */
function vessot_secure_portable_data_settings_link($links) {
    $settings_link = '<a href="' . admin_url('options-general.php?page=vessot-secure-portable-data') . '">' .
                     esc_html__('Settings', 'vessot-secure-portable-data') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'vessot_secure_portable_data_settings_link');
