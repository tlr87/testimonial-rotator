<?php
/**
 * Plugin Name:       Testimonial Rotator
 * Description:       Beautiful rotating testimonials with speed control, transitions, pause on hover, arrows & dots (toggleable).
 * Version:           1.5
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

// Register Settings
function tr_register_settings() {
    register_setting('tr_settings_group', 'tr_interval');
    register_setting('tr_settings_group', 'tr_unit');
    register_setting('tr_settings_group', 'tr_transition');
    register_setting('tr_settings_group', 'tr_show_arrows', ['default' => '1']);
    register_setting('tr_settings_group', 'tr_show_dots',   ['default' => '1']);
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
                    </td>
                </tr>
                <tr>
                    <th>Transition Type</th>
                    <td>
                        <select name="tr_transition">
                            <option value="fade" <?php selected(get_option('tr_transition', 'fade'), 'fade'); ?>>Fade</option>
                            <option value="slide" <?php selected(get_option('tr_transition', 'fade'), 'slide'); ?>>Slide</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th>Navigation Arrows</th>
                    <td>
                        <label>
                            <input type="checkbox" name="tr_show_arrows" value="1" <?php checked(get_option('tr_show_arrows', '1'), '1'); ?>>
                            Show left/right arrows
                        </label>
                    </td>
                </tr>
                <tr>
                    <th>Navigation Dots</th>
                    <td>
                        <label>
                            <input type="checkbox" name="tr_show_dots" value="1" <?php checked(get_option('tr_show_dots', '1'), '1'); ?>>
                            Show clickable dots at the bottom
                        </label>
                    </td>
                </tr>
            </table>
            <?php submit_button('Save Settings'); ?>
        </form>

        <!-- How to Use -->
        <div style="margin-top: 40px; background: #f9f9f9; padding: 25px; border: 1px solid #ddd; border-radius: 8px;">
            <h2>How to Use Testimonial Rotator</h2>
            
            <h3>1. Add Testimonials</h3>
            <p>Go to <strong>Testimonials → Add New</strong>.</p>
            <ul>
                <li><strong>Title</strong> → Person’s name</li>
                <li><strong>Content</strong> → Testimonial quote</li>
                <li><strong>Featured Image</strong> → Optional avatar</li>
            </ul>

            <h3>2. Display</h3>
            <pre><code>[rotating-testimonials]</code></pre>

            <h3>3. Customize Per Shortcode</h3>
            <pre><code>[rotating-testimonials interval="15" unit="seconds" transition="slide" arrows="0" dots="1"]</code></pre>
            <p>New parameters: <code>arrows</code> (1/0), <code>dots</code> (1/0)</p>

            <h3>Features</h3>
            <ul>
                <li>Auto-rotation with pause on hover</li>
                <li>Fade or Slide transition</li>
                <li>Navigation arrows &amp; dots (can be toggled)</li>
            </ul>
        </div>
    </div>
    <?php
}

// Convert to milliseconds
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

// Shortcode
function tr_rotating_testimonials_shortcode($atts = []) {
    $atts = shortcode_atts([
        'interval'   => get_option('tr_interval', 10),
        'unit'       => get_option('tr_unit', 'seconds'),
        'transition' => get_option('tr_transition', 'fade'),
        'arrows'     => get_option('tr_show_arrows', '1'),
        'dots'       => get_option('tr_show_dots', '1'),
    ], $atts, 'rotating-testimonials');

    $ms = tr_calculate_ms($atts['interval'], $atts['unit']);

    wp_enqueue_style('tr-style', plugin_dir_url(__FILE__) . 'css/testimonial-rotator.css', [], '1.5');
    wp_enqueue_script('tr-script', plugin_dir_url(__FILE__) . 'js/testimonial-rotator.js', [], '1.5', true);

    $testimonials = get_posts([
        'post_type'      => 'testimonial',
        'posts_per_page' => -1,
        'orderby'        => 'date',
        'order'          => 'ASC',
    ]);

    if (empty($testimonials)) {
        return '<p>No testimonials found. Add some via the Testimonials menu.</p>';
    }

    $show_arrows = filter_var($atts['arrows'], FILTER_VALIDATE_BOOLEAN);
    $show_dots   = filter_var($atts['dots'],   FILTER_VALIDATE_BOOLEAN);

    $output = '<div class="testimonial-rotator" 
                    data-interval="' . esc_attr($ms) . '" 
                    data-transition="' . esc_attr($atts['transition']) . '"
                    data-show-arrows="' . ($show_arrows ? '1' : '0') . '"
                    data-show-dots="'   . ($show_dots   ? '1' : '0') . '">';

    foreach ($testimonials as $t) {
        $author = $t->post_title ?: 'Anonymous';
        $text   = apply_filters('the_content', $t->post_content);
        $text   = preg_replace('/<p>\s*<\/p>/', '', $text);
        $avatar = get_the_post_thumbnail_url($t->ID, 'thumbnail');

        $output .= '<div class="testimonial-item">';
        if ($avatar) $output .= '<img src="' . esc_url($avatar) . '" alt="" class="testimonial-avatar">';
        $output .= '<div class="quote">' . $text . '</div>';
        $output .= '<div class="author">— ' . esc_html($author) . '</div>';
        $output .= '</div>';
    }

    // Navigation controls (always output, JS will hide if disabled)
    $output .= '<button class="tr-prev" aria-label="Previous testimonial">‹</button>';
    $output .= '<button class="tr-next" aria-label="Next testimonial">›</button>';
    $output .= '<div class="tr-dots"></div>';

    $output .= '</div>';

    return $output;
}
add_shortcode('rotating-testimonials', 'tr_rotating_testimonials_shortcode');