<?php
/**
 * Plugin Name:       Testimonial Rotator
 * Description:       Rotating testimonials with customizable speed, transitions, arrows & dots, separate Title, Job Title, Company, and Read More link button.
 * Version:           2.5
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

// Meta boxes
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
    $link_url    = get_post_meta($post->ID, '_tr_link_url', true);
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
        <label for="tr_link_url"><strong>Link URL for "Read More" button</strong> (optional)</label><br>
        <input type="url" id="tr_link_url" name="tr_link_url" value="<?php echo esc_attr($link_url); ?>" style="width:100%;">
        <span class="description">If filled, a "Read More" button will appear.</span>
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
    update_post_meta($post_id, '_tr_link_url',    esc_url_raw($_POST['tr_link_url'] ?? ''));
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
    register_setting('tr_settings_group', 'tr_read_more_css');
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



// ============================================================
// Testimonials Admin Screen - Shortcode Copy
// ============================================================

function tr_add_testimonial_shortcode_to_admin_title() {

    $screen = get_current_screen();

    if (!$screen || $screen->post_type !== 'testimonial') {
        return;
    }

    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function () {

        const heading = document.querySelector('.wrap h1');

        if (!heading) {
            return;
        }

        const control = document.createElement('span');

        control.style.cssText = `
            margin-left:15px;
            font-size:14px;
            font-weight:400;
        `;

        control.innerHTML = `
            Copy:
            <input
                type="text"
                value="[rotating-testimonials]"
                readonly
                style="
                    width:220px;
                    font-family:monospace;
                    padding:3px 6px;
                    margin-left:5px;
                "
            >
            <button
                type="button"
                class="button"
                style="margin-left:5px;"
                id="tr-copy-testimonial-shortcode"
            >
                Copy
            </button>
                <a
        href="<?php echo esc_url(admin_url('options-general.php?page=testimonial-rotator')); ?>"
        class="button"
        style="margin-left:5px;"
    >
        Settings
    </a>
        `;

        heading.appendChild(control);

        document
            .getElementById('tr-copy-testimonial-shortcode')
            .addEventListener('click', function () {

                const input = control.querySelector('input');

                const button = this;

if (navigator.clipboard && window.isSecureContext) {

    navigator.clipboard.writeText(input.value)
        .then(function () {

            button.innerText = 'Copied';

            setTimeout(function () {
                button.innerText = 'Copy';
            }, 1500);

        });

} else {

    input.select();
    input.setSelectionRange(0, 99999);

    document.execCommand('copy');

    button.innerText = 'Copied';

    setTimeout(function () {
        button.innerText = 'Copy';
    }, 1500);

}

            });

    });
    </script>
    <?php
}

add_action(
    'admin_footer-edit.php',
    'tr_add_testimonial_shortcode_to_admin_title'
);


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
                    <td><label><input type="checkbox" name="tr_show_arrows" value="1" <?php checked(get_option('tr_show_arrows', '1'), '1'); ?>> Show arrows</label></td>
                </tr>
                <tr>
                    <th>Navigation Dots</th>
                    <td><label><input type="checkbox" name="tr_show_dots" value="1" <?php checked(get_option('tr_show_dots', '1'), '1'); ?>> Show dots</label></td>
                </tr>
                <tr>
                    <th>Show Title</th>
                    <td>
                        <label>
                            <input type="checkbox" name="tr_show_title" value="1" <?php checked(get_option('tr_show_title', '1'), '1'); ?>>
                            Show Title (main author name)
                        </label>
                    </td>
                </tr>
                <tr>
                    <th>Read More Button Custom CSS</th>
                        <td>
                        <textarea name="tr_read_more_css" rows="6" style="width:100%; font-family: monospace;"><?php 
                        echo esc_textarea(get_option('tr_read_more_css', ''));
                        ?></textarea>
                        <p class="description">
                        Add CSS rules for the Read More button (.tr-read-more)
                        </p>
                    </td>
                </tr>
            </table>
            <?php submit_button('Save Settings'); ?>
        </form>

        <div style="margin-top: 40px; background: #f9f9f9; padding: 30px; border: 1px solid #ddd; border-radius: 8px;">
        <h2>How to Use Testimonial Rotator</h2>

        <p><strong>Step 1:</strong> Go to <strong>Testimonials → Add New</strong> and create your testimonials.</p>
        
        <p><strong>Step 2:</strong> Fill in the details:</p>
        <ul style="list-style: disc; margin-left: 20px;">
            <li><strong>Title:</strong> Author name</li>
            <li><strong>Content:</strong> Testimonial text</li>
            <li><strong>Featured Image:</strong> Avatar/photo (optional)</li>
            <li><strong>Job Title & Company:</strong> Additional info (optional)</li>
            <li><strong>Company URL:</strong> Link company name (optional)</li>
            <li><strong>Read More URL:</strong> Adds a clickable button and makes the testimonial clickable</li>
        </ul>

        <p><strong>Step 3:</strong> Add the shortcode to any page or post:</p>
        <code>[rotating-testimonials]</code>

        <p><strong>Optional Shortcode Settings:</strong></p>
        <ul style="list-style: disc; margin-left: 20px;">
            <li><code>interval="5"</code> (number)</li>
            <li><code>unit="seconds|minutes|hours|months"</code></li>
            <li><code>transition="fade|slide"</code></li>
            <li><code>arrows="true|false"</code></li>
            <li><code>dots="true|false"</code></li>
            <li><code>title="true|false"</code></li>
        </ul>

        <p><strong>Example:</strong></p>
        <code>[rotating-testimonials interval="5" unit="seconds" transition="slide"]</code>

        <hr>

        <p><strong>Tips:</strong></p>
        <ul style="list-style: disc; margin-left: 20px;">
            <li>Clicking a testimonial will open the Read More link (if set)</li>
            <li>The Read More button is optional and only appears when a URL is provided</li>
            <li>Hover pauses rotation (desktop)</li>
            <li>Swipe left/right on mobile to navigate</li>
        </ul>
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

// Shortcode
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

    wp_enqueue_style('tr-style', plugin_dir_url(__FILE__) . 'css/testimonial-rotator.css', [], '2.5');
    wp_enqueue_script('tr-script', plugin_dir_url(__FILE__) . 'js/testimonial-rotator.js', [], '2.5', true);

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
        $link_url    = get_post_meta($t->ID, '_tr_link_url', true);

        $is_default_title = empty($author) || preg_match('/^Testimonial \d+$/i', $author);

        $text   = apply_filters('the_content', $t->post_content);
        $text   = preg_replace('/<p>\s*<\/p>/', '', $text);
        $avatar = get_the_post_thumbnail_url($t->ID, 'thumbnail');

        $output .= '<div class="testimonial-item">';
        if ($avatar) {
            $output .= '<img src="' . esc_url($avatar) . '" alt="" class="testimonial-avatar">';
        }
        $output .= '<div class="quote">' . $text . '</div>';

        if ($show_title && !$is_default_title && !empty($author)) {
            $output .= '<div class="author-name">— ' . esc_html($author) . '</div>';
        }

        if (!empty($job_title) || !empty($company)) {
            $output .= '<div class="testimonial-meta">';
            if (!empty($job_title)) $output .= '<span class="job-title">' . esc_html($job_title) . '</span>';
            if (!empty($company)) {
                if (!empty($company_url)) {
                    $output .= ' <a href="' . esc_url($company_url) . '" target="_blank" class="company">' . esc_html($company) . '</a>';
                } else {
                    $output .= '<span class="company">' . esc_html($company) . '</span>';
                }
            }
            $output .= '</div>';
        }

        // Read More Button
        if (!empty($link_url)) {
            $read_more_text = apply_filters('tr_read_more_text', 'Read More');

            $output .= '<a href="' . esc_url($link_url) . '" target="_blank" rel="noopener noreferrer" class="tr-read-more">'
                    . esc_html($read_more_text) .
                    '</a>';
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

add_action('wp_head', function () {

    $css = get_option('tr_read_more_css', '');

    if (!empty($css)) {
        echo '<style>
        .testimonial-rotator .testimonial-item .tr-read-more {
            ' . $css . '
        }
        </style>';
    }

}, 999);