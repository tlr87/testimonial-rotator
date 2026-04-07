<?php
/**
 * Plugin Name:       Testimonial Rotator
 * Description:       Rotating testimonials with customizable speed, transitions, arrows & dots, separate Title, Job Title, Company, and per-testimonial link.
 * Version:           2.3
 * Author:            Grok
 * License:           GPL-2.0+
 * Text Domain:       testimonial-rotator
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

// Meta boxes for Job Title, Company, and Link
function tr_add_testimonial_meta_boxes() {
    add_meta_box(
        'tr_testimonial_meta',
        'Testimonial Details',
        'tr_testimonial_meta_callback',
        'testimonial',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'tr_add_testimonial_meta_boxes');

function tr_testimonial_meta_callback($post) {
    wp_nonce_field('tr_save_meta', 'tr_meta_nonce');
    
    $job_title   = get_post_meta($post->ID, '_tr_job_title', true);
    $company     = get_post_meta($post->ID, '_tr_company', true);
    $company_url = get_post_meta($post->ID, '_tr_company_url', true);
    $link_url    = get_post_meta($post->ID, '_tr_link_url', true);   // New: Link URL
    ?>
    <p>
        <label for="tr_job_title"><strong>Job Title / Position</strong></label><br>
        <input type="text" id="tr_job_title" name="tr_job_title" value="<?php echo esc_attr($job_title); ?>" style="width:100%;">
    </p>
    <p>
        <label for="tr_company"><strong>Company Name</strong></label><br>
        <input type="text" id="tr_company" name="tr_company" value="<?php echo esc_attr($company); ?>" style="width:100%;">
    </p>
    <p>
        <label for="tr_company_url"><strong>Company Website URL</strong> (optional)</label><br>
        <input type="url" id="tr_company_url" name="tr_company_url" value="<?php echo esc_attr($company_url); ?>" style="width:100%;">
    </p>
    <p>
        <label for="tr_link_url"><strong>Link URL</strong> (optional)</label><br>
        <input type="url" id="tr_link_url" name="tr_link_url" value="<?php echo esc_attr($link_url); ?>" style="width:100%;">
        <span class="description">When filled, clicking the testimonial will open this URL in a new tab.</span>
    </p>
    <?php
}

function tr_save_testimonial_meta($post_id) {
    if (!isset($_POST['tr_meta_nonce']) || !wp_verify_nonce($_POST['tr_meta_nonce'], 'tr_save_meta')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    update_post_meta($post_id, '_tr_job_title',   sanitize_text_field($_POST['tr_job_title'] ?? ''));
    update_post_meta($post_id, '_tr_company',     sanitize_text_field($_POST['tr_company'] ?? ''));
    update_post_meta($post_id, '_tr_company_url', esc_url_raw($_POST['tr_company_url'] ?? ''));
    update_post_meta($post_id, '_tr_link_url',    esc_url_raw($_POST['tr_link_url'] ?? ''));   // New
}
add_action('save_post_testimonial', 'tr_save_testimonial_meta');

// Settings (unchanged)
function tr_register_settings() {
    register_setting('tr_settings_group', 'tr_interval');
    register_setting('tr_settings_group', 'tr_unit');
    register_setting('tr_settings_group', 'tr_transition');
    register_setting('tr_settings_group', 'tr_show_arrows', ['default' => '1']);
    register_setting('tr_settings_group', 'tr_show_dots',   ['default' => '1']);
    register_setting('tr_settings_group', 'tr_show_title',  ['default' => '1']);
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
                    <td><label><input type="checkbox" name="tr_show_arrows" value="1" <?php checked(get_option('tr_show_arrows', '1'), '1'); ?>> Show left/right arrows</label></td>
                </tr>
                <tr>
                    <th>Navigation Dots</th>
                    <td><label><input type="checkbox" name="tr_show_dots" value="1" <?php checked(get_option('tr_show_dots', '1'), '1'); ?>> Show clickable dots at the bottom</label></td>
                </tr>
                <tr>
                    <th>Show Title</th>
                    <td>
                        <label>
                            <input type="checkbox" name="tr_show_title" value="1" <?php checked(get_option('tr_show_title', '1'), '1'); ?>>
                            Show Title (main author name from the Title field)
                        </label>
                        <p class="description">Job Title and Company will continue to show even if Title is hidden.</p>
                    </td>
                </tr>
            </table>
            <?php submit_button('Save Settings'); ?>
        </form>

        <div style="margin-top: 40px; background: #f9f9f9; padding: 30px; border: 1px solid #ddd; border-radius: 8px;">
            <h2>How to Use Testimonial Rotator</h2>
            
            <h3>1. Adding Testimonials</h3>
            <p>Go to <strong>Testimonials → Add New</strong>.</p>
            <ul>
                <li><strong>Title field</strong> → Person’s name (toggleable)</li>
                <li><strong>Content</strong> → Testimonial quote</li>
                <li><strong>Featured Image</strong> → Optional avatar</li>
                <li><strong>Testimonial Details box</strong> → Job Title, Company Name, Company URL, and new <strong>Link URL</strong></li>
            </ul>

            <h3>2. Displaying</h3>
            <pre><code>[rotating-testimonials]</code></pre>

            <h3>New in v2.3</h3>
            <p>Each testimonial can now have its own link. Clicking the testimonial opens the Link URL in a new tab.</p>
        </div>
    </div>
    <?php
}

// Helper
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

// Shortcode - Added per-testimonial link support
function tr_rotating_testimonials_shortcode($atts = []) {
    $atts = shortcode_atts([
        'interval'   => get_option('tr_interval', 10),
        'unit'       => get_option('tr_unit', 'seconds'),
        'transition' => get_option('tr_transition', 'fade'),
        'arrows'     => get_option('tr_show_arrows', '1'),
        'dots'       => get_option('tr_show_dots', '1'),
        'title'      => get_option('tr_show_title', '1'),
    ], $atts, 'rotating-testimonials');

    $ms = tr_calculate_ms($atts['interval'], $atts['unit']);

    wp_enqueue_style('tr-style', plugin_dir_url(__FILE__) . 'css/testimonial-rotator.css', [], '2.3');
    wp_enqueue_script('tr-script', plugin_dir_url(__FILE__) . 'js/testimonial-rotator.js', [], '2.3', true);

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
    $show_title  = filter_var($atts['title'],  FILTER_VALIDATE_BOOLEAN);

    $output = '<div class="testimonial-rotator" 
                    data-interval="' . esc_attr($ms) . '" 
                    data-transition="' . esc_attr($atts['transition']) . '"
                    data-show-arrows="' . ($show_arrows ? '1' : '0') . '"
                    data-show-dots="'   . ($show_dots   ? '1' : '0') . '"
                    data-show-title="'  . ($show_title  ? '1' : '0') . '">';

    foreach ($testimonials as $t) {
        $author      = trim($t->post_title ?? '');
        $job_title   = get_post_meta($t->ID, '_tr_job_title', true);
        $company     = get_post_meta($t->ID, '_tr_company', true);
        $company_url = get_post_meta($t->ID, '_tr_company_url', true);
        $link_url    = get_post_meta($t->ID, '_tr_link_url', true);   // New

        $is_default_title = empty($author) || preg_match('/^Testimonial \d+$/i', $author);

        $text   = apply_filters('the_content', $t->post_content);
        $text   = preg_replace('/<p>\s*<\/p>/', '', $text);
        $avatar = get_the_post_thumbnail_url($t->ID, 'thumbnail');

        // Make the whole item clickable if link exists
        $link_attr = !empty($link_url) ? ' data-link="' . esc_url($link_url) . '"' : '';

        $output .= '<div class="testimonial-item"' . $link_attr . '>';
        if ($avatar) {
            $output .= '<img src="' . esc_url($avatar) . '" alt="" class="testimonial-avatar">';
        }
        $output .= '<div class="quote">' . $text . '</div>';

        if ($show_title && !$is_default_title && !empty($author)) {
            $output .= '<div class="author-name">— ' . esc_html($author) . '</div>';
        }

        if (!empty($job_title) || !empty($company)) {
            $output .= '<div class="testimonial-meta">';
            if (!empty($job_title)) {
                $output .= '<span class="job-title">' . esc_html($job_title) . '</span>';
            }
            if (!empty($company)) {
                if (!empty($company_url)) {
                    $output .= ' <a href="' . esc_url($company_url) . '" target="_blank" class="company">' . esc_html($company) . '</a>';
                } else {
                    $output .= '<span class="company">' . esc_html($company) . '</span>';
                }
            }
            $output .= '</div>';
        }

        $output .= '</div>';
    }

    $output .= '<button class="tr-prev" aria-label="Previous testimonial">‹</button>';
    $output .= '<button class="tr-next" aria-label="Next testimonial">›</button>';
    $output .= '<div class="tr-dots"></div>';

    $output .= '</div>';

    return $output;
}
add_shortcode('rotating-testimonials', 'tr_rotating_testimonials_shortcode');