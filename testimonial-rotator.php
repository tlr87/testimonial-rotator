<?php
/**
 * Plugin Name:       Testimonial Rotator
 * Plugin URI:        https://example.com
 * Description:       Rotates testimonials with fully customizable interval (seconds, minutes, hours, months).
 * Version:           1.0
 * Author:            Grok (built for you)
 * License:           GPL-2.0+
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
        'capability_type'     => 'post',
        'rewrite'             => ['slug' => 'testimonial'],
    ]);
}
add_action('init', 'tr_register_testimonial_cpt');

// Settings
function tr_register_settings() {
    register_setting('tr_settings_group', 'tr_interval');
    register_setting('tr_settings_group', 'tr_unit');
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
        <h1>Testimonial Rotator Settings</h1>
        <form method="post" action="options.php">
            <?php settings_fields('tr_settings_group'); ?>
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
                        <p class="description">How often the testimonial should change. (Months are approximated as 30 days.)</p>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

// Helper: convert interval + unit → milliseconds for JS
function tr_calculate_ms($num, $unit) {
    $num = (int) $num;
    switch ($unit) {
        case 'seconds': return $num * 1000;
        case 'minutes': return $num * 60 * 1000;
        case 'hours':   return $num * 3600 * 1000;
        case 'months':  return $num * 30 * 24 * 3600 * 1000; // ≈ 30 days
        default:        return 10000; // fallback 10 seconds
    }
}

// Shortcode
function tr_rotating_testimonials_shortcode($atts = []) {
    $atts = shortcode_atts([
        'interval' => get_option('tr_interval', 10),
        'unit'     => get_option('tr_unit', 'seconds'),
    ], $atts, 'rotating-testimonials');

    $ms = tr_calculate_ms($atts['interval'], $atts['unit']);

    // Enqueue assets
    wp_enqueue_style('tr-style', plugin_dir_url(__FILE__) . 'css/testimonial-rotator.css', [], '1.0');
    wp_enqueue_script('tr-script', plugin_dir_url(__FILE__) . 'js/testimonial-rotator.js', [], '1.0', true);

    $testimonials = get_posts([
        'post_type'      => 'testimonial',
        'posts_per_page' => -1,
        'orderby'        => 'date',
        'order'          => 'ASC',
    ]);

    if (empty($testimonials)) {
        return '<p>No testimonials found. Add some in the Testimonials menu!</p>';
    }

    $output = '<div class="testimonial-rotator" data-interval="' . esc_attr($ms) . '">';

    foreach ($testimonials as $t) {
        $author = $t->post_title ?: 'Anonymous';
        $text   = $t->post_content;
        $avatar = get_the_post_thumbnail_url($t->ID, 'thumbnail');

        $output .= '<div class="testimonial-item">';
        if ($avatar) {
            $output .= '<img src="' . esc_url($avatar) . '" alt="" class="testimonial-avatar">';
        }
        $output .= '<div class="quote">“' . esc_html($text) . '”</div>';
        $output .= '<div class="author">— ' . esc_html($author) . '</div>';
        $output .= '</div>';
    }

    $output .= '</div>';

    return $output;
}
add_shortcode('rotating-testimonials', 'tr_rotating_testimonials_shortcode');