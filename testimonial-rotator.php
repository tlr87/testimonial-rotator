<?php
/**
 * Plugin Name:       Testimonial Rotator
 * Description:       Rotates testimonials with customizable interval, pause on hover, and fade/slide transitions.
 * Version:           1.3
 * Author:            Grok
 */

if (!defined('ABSPATH')) exit;

// Register Custom Post Type
function tr_register_testimonial_cpt() {
    register_post_type('testimonial', [
        'labels' => [
            'name'          => 'Testimonials',
            'singular_name' => 'Testimonial',
            'add_new_item'  => 'Add New Testimonial',
        ],
        'public'              => true,
        'menu_icon'           => 'dashicons-format-quote',
        'supports'            => ['title', 'editor', 'thumbnail'],
        'show_in_rest'        => true,
    ]);
}
add_action('init', 'tr_register_testimonial_cpt');

// Settings
function tr_register_settings() {
    register_setting('tr_settings_group', 'tr_interval');
    register_setting('tr_settings_group', 'tr_unit');
    register_setting('tr_settings_group', 'tr_transition');
}
add_action('admin_init', 'tr_register_settings');

function tr_add_settings_page() {
    add_options_page(
        'Testimonial Rotator Settings',
        'Testimonial Rotator',
        'manage_options',
        'testimonial-rotator',
        'tr_settings_page_html'
    );
}
add_action('admin_menu', 'tr_add_settings_page');

function tr_settings_page_html() {
    ?>
    <div class="wrap">
        <h1>Testimonial Rotator</h1>
        
        <!-- Settings Form -->
        <form method="post" action="options.php">
            <?php settings_fields('tr_settings_group'); ?>
            <h2>General Settings</h2>
            <table class="form-table">
                <tr>
                    <th>Rotation every</th>
                    <td>
                        <input type="number" name="tr_interval" value="<?php echo esc_attr(get_option('tr_interval', 10)); ?>" min="1" style="width:80px;">
                        <select name="tr_unit">
                            <option value="seconds" <?php selected(get_option('tr_unit', 'seconds'), 'seconds'); ?>>seconds</option>
                            <option value="minutes" <?php selected(get_option('tr_unit', 'seconds'), 'minutes'); ?>>minutes</option>
                            <option value="hours"   <?php selected(get_option('tr_unit', 'seconds'), 'hours');   ?>>hours</option>
                            <option value="months"  <?php selected(get_option('tr_unit', 'seconds'), 'months');  ?>>months</option>
                        </select>
                        <p class="description">Default rotation speed for all rotators.</p>
                    </td>
                </tr>
                <tr>
                    <th>Transition Type</th>
                    <td>
                        <select name="tr_transition">
                            <option value="fade" <?php selected(get_option('tr_transition', 'fade'), 'fade'); ?>>Fade (smooth cross-fade)</option>
                            <option value="slide" <?php selected(get_option('tr_transition', 'fade'), 'slide'); ?>>Slide (horizontal slide)</option>
                        </select>
                        <p class="description">Default animation style.</p>
                    </td>
                </tr>
            </table>
            <?php submit_button('Save Settings'); ?>
        </form>

        <!-- How to Use Section -->
        <div style="margin-top: 40px; background: #f9f9f9; padding: 25px; border: 1px solid #ddd; border-radius: 8px;">
            <h2>How to Use Testimonial Rotator</h2>
            
            <h3>1. Add Testimonials</h3>
            <p>Go to <strong>Testimonials → Add New</strong> in the WordPress admin menu.</p>
            <ul>
                <li><strong>Title</strong> → Name of the person (e.g., "Sarah Johnson")</li>
                <li><strong>Content</strong> → The testimonial text/quote (you can use the visual editor or blocks)</li>
                <li><strong>Featured Image</strong> → Optional avatar/photo (recommended 300×300px)</li>
            </ul>

            <h3>2. Display the Rotating Testimonials</h3>
            <p>Use this shortcode anywhere:</p>
            <pre><code>[rotating-testimonials]</code></pre>
            
            <h3>3. Customize Per Instance</h3>
            <pre><code>[rotating-testimonials interval="15" unit="seconds" transition="fade"]</code></pre>
            <p>Available options: <code>interval</code>, <code>unit</code> (seconds/minutes/hours/months), <code>transition</code> (fade/slide)</p>

            <h3>4. Pause on Hover</h3>
            <p>Automatically pauses when the mouse hovers over the rotator.</p>

            <h3>Tips</h3>
            <ul>
                <li>Add at least 2–3 testimonials.</li>
                <li>Clear your site cache after changes if you use a caching plugin.</li>
            </ul>
        </div>
    </div>
    <?php
}

// Helper: convert interval + unit → milliseconds
function tr_calculate_ms($num, $unit) {
    $num = (int) $num;
    switch ($unit) {
        case 'seconds': return $num * 1000;
        case 'minutes': return $num * 60 * 1000;
        case 'hours':   return $num * 3600 * 1000;
        case 'months':  return $num * 30 * 24 * 3600 * 1000;
        default:        return 10000;
    }
}

// Shortcode - FIXED: Proper content rendering
function tr_rotating_testimonials_shortcode($atts = []) {
    $atts = shortcode_atts([
        'interval'   => get_option('tr_interval', 10),
        'unit'       => get_option('tr_unit', 'seconds'),
        'transition' => get_option('tr_transition', 'fade'),
    ], $atts, 'rotating-testimonials');

    $ms = tr_calculate_ms($atts['interval'], $atts['unit']);

    wp_enqueue_style('tr-style', plugin_dir_url(__FILE__) . 'css/testimonial-rotator.css', [], '1.3');
    wp_enqueue_script('tr-script', plugin_dir_url(__FILE__) . 'js/testimonial-rotator.js', [], '1.3', true);

    $testimonials = get_posts([
        'post_type'      => 'testimonial',
        'posts_per_page' => -1,
        'orderby'        => 'date',
        'order'          => 'ASC',
    ]);

    if (empty($testimonials)) {
        return '<p>No testimonials found. Add some via the Testimonials menu!</p>';
    }

    $output = '<div class="testimonial-rotator" 
                    data-interval="' . esc_attr($ms) . '" 
                    data-transition="' . esc_attr($atts['transition']) . '">';

    foreach ($testimonials as $t) {
        $author = $t->post_title ?: 'Anonymous';
        
        // FIXED: Properly render Gutenberg blocks and apply formatting
        $text = apply_filters('the_content', $t->post_content);
        
        // Optional: Remove any remaining empty paragraphs if they appear
        $text = preg_replace('/<p>\s*<\/p>/', '', $text);
        
        $avatar = get_the_post_thumbnail_url($t->ID, 'thumbnail');

        $output .= '<div class="testimonial-item">';
        if ($avatar) {
            $output .= '<img src="' . esc_url($avatar) . '" alt="" class="testimonial-avatar">';
        }
        $output .= '<div class="quote">' . $text . '</div>';   // Note: no extra escaping here because the_content already outputs safe HTML
        $output .= '<div class="author">— ' . esc_html($author) . '</div>';
        $output .= '</div>';
    }

    $output .= '</div>';

    return $output;
}
add_shortcode('rotating-testimonials', 'tr_rotating_testimonials_shortcode');