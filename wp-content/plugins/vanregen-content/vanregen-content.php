<?php
/**
 * Plugin Name: Van Regen Content
 * Description: Content types and inquiry handling for the Van Regen website.
 * Version: 1.0.0
 * Requires PHP: 7.4
 * Text Domain: vanregen-content
 */

if (!defined('ABSPATH')) {
    exit;
}

function vanregen_content_logo_url() {
    $logo_id = get_theme_mod('custom_logo');
    if ($logo_id) {
        $logo_url = wp_get_attachment_image_url($logo_id, 'full');
        if ($logo_url) {
            return $logo_url;
        }
    }
    return get_site_icon_url(512);
}

function vanregen_content_login_branding() {
    $logo_url = vanregen_content_logo_url();
    if (!$logo_url) {
        return;
    }
    echo '<style id="vanregen-login-branding">body.login{background:#f5f6ef}body.login #login h1 a{width:128px;height:128px;background-image:url(' . esc_url($logo_url) . ');background-size:contain;background-position:center;background-repeat:no-repeat;filter:brightness(0) invert(1)}body.login #loginform{border-top:3px solid #a7c957}</style>';
}
add_action('login_enqueue_scripts', 'vanregen_content_login_branding');

function vanregen_content_login_header_url() {
    return home_url('/');
}
add_filter('login_headerurl', 'vanregen_content_login_header_url');

function vanregen_content_login_header_text() {
    return get_bloginfo('name');
}
add_filter('login_headertext', 'vanregen_content_login_header_text');

function vanregen_content_admin_bar_branding() {
    if (!is_admin_bar_showing()) {
        return;
    }
    $logo_url = vanregen_content_logo_url();
    if (!$logo_url) {
        return;
    }
    echo '<style id="vanregen-admin-bar-branding">#wpadminbar #wp-admin-bar-wp-logo>.ab-item .ab-icon:before{content:"";display:block;width:20px;height:20px;background-image:url(' . esc_url($logo_url) . ');background-position:center;background-repeat:no-repeat;background-size:contain;filter:brightness(0) invert(1)}</style>';
}
add_action('admin_head', 'vanregen_content_admin_bar_branding');
add_action('wp_head', 'vanregen_content_admin_bar_branding');

function vanregen_register_content_types() {
    register_post_type('services', array(
        'labels' => array(
            'name' => __('Services', 'vanregen-content'),
            'singular_name' => __('Service', 'vanregen-content'),
            'add_new_item' => __('Add Service', 'vanregen-content'),
            'edit_item' => __('Edit Service', 'vanregen-content'),
        ),
        'public' => true,
        'show_in_rest' => true,
        'has_archive' => false,
        'rewrite' => array('slug' => 'capabilities'),
        'menu_icon' => 'dashicons-recycle',
        'supports' => array('title', 'editor', 'excerpt', 'thumbnail', 'custom-fields', 'revisions'),
    ));

    register_post_type('projects', array(
        'labels' => array(
            'name' => __('Projects', 'vanregen-content'),
            'singular_name' => __('Project', 'vanregen-content'),
            'add_new_item' => __('Add Project', 'vanregen-content'),
            'edit_item' => __('Edit Project', 'vanregen-content'),
        ),
        'public' => true,
        'show_in_rest' => true,
        'has_archive' => false,
        'rewrite' => array('slug' => 'projects'),
        'menu_icon' => 'dashicons-hammer',
        'supports' => array('title', 'editor', 'excerpt', 'thumbnail', 'custom-fields', 'revisions'),
    ));

    register_post_type('vanregen_inquiry', array(
        'labels' => array(
            'name' => __('Website Inquiries', 'vanregen-content'),
            'singular_name' => __('Website Inquiry', 'vanregen-content'),
            'menu_name' => __('Website Inquiries', 'vanregen-content'),
            'all_items' => __('All Inquiries', 'vanregen-content'),
            'edit_item' => __('View Inquiry', 'vanregen-content'),
            'search_items' => __('Search Inquiries', 'vanregen-content'),
        ),
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        'show_in_rest' => false,
        'exclude_from_search' => true,
        'rewrite' => false,
        'menu_icon' => 'dashicons-email-alt',
        'supports' => array('title', 'editor'),
        'capability_type' => 'post',
        'capabilities' => array('create_posts' => 'do_not_allow'),
        'map_meta_cap' => true,
    ));
}
add_action('init', 'vanregen_register_content_types');

function vanregen_inquiry_admin_columns($columns) {
    $date_column = $columns['date'] ?? __('Date', 'vanregen-content');
    unset($columns['date']);
    $columns['inquiry_type'] = __('Type', 'vanregen-content');
    $columns['inquiry_contact'] = __('Contact', 'vanregen-content');
    $columns['inquiry_company'] = __('Company', 'vanregen-content');
    $columns['inquiry_request'] = __('Request', 'vanregen-content');
    $columns['inquiry_email_delivery'] = __('Email', 'vanregen-content');
    $columns['date'] = $date_column;
    return $columns;
}
add_filter('manage_vanregen_inquiry_posts_columns', 'vanregen_inquiry_admin_columns');

function vanregen_inquiry_admin_field($post_id, $meta_key, $content_label) {
    $value = get_post_meta($post_id, $meta_key, true);
    if ($value !== '') {
        return is_scalar($value) ? (string) $value : '';
    }

    $content = (string) get_post_field('post_content', $post_id);
    $pattern = '/^' . preg_quote($content_label, '/') . ':\s*(.*?)\s*$/mi';
    return preg_match($pattern, $content, $matches) ? trim($matches[1]) : '';
}

function vanregen_inquiry_admin_column($column, $post_id) {
    if ($column === 'inquiry_type') {
        $type = vanregen_inquiry_admin_field($post_id, 'vanregen_inquiry_type', 'Inquiry type');
        echo esc_html($type !== '' ? ucfirst($type) : '—');
        return;
    }

    if ($column === 'inquiry_contact') {
        $name = vanregen_inquiry_admin_field($post_id, 'vanregen_inquiry_name', 'Name');
        $email = vanregen_inquiry_admin_field($post_id, 'vanregen_inquiry_email', 'Email');
        $phone = vanregen_inquiry_admin_field($post_id, 'vanregen_inquiry_phone', 'Phone');
        echo esc_html($name !== '' ? $name : '—');
        if ($email !== '') {
            echo '<br><a href="mailto:' . esc_attr($email) . '">' . esc_html($email) . '</a>';
        }
        if ($phone !== '') {
            echo '<br>' . esc_html($phone);
        }
        return;
    }

    if ($column === 'inquiry_company') {
        $company = vanregen_inquiry_admin_field($post_id, 'vanregen_inquiry_company', 'Company');
        echo esc_html($company !== '' ? $company : '—');
        return;
    }

    if ($column === 'inquiry_request') {
        $summary = get_post_meta($post_id, 'vanregen_inquiry_request_summary', true);
        if ($summary === '') {
            $content = (string) get_post_field('post_content', $post_id);
            if (preg_match_all('/^Service:\s*(.+)$/mi', $content, $matches) && !empty($matches[1])) {
                $summary = implode(', ', array_unique(array_map('trim', $matches[1])));
            } else {
                $summary = vanregen_inquiry_admin_field($post_id, 'vanregen_inquiry_context_service', 'Related service');
            }
        }
        echo esc_html($summary !== '' ? $summary : 'General inquiry');
        return;
    }

    if ($column === 'inquiry_email_delivery') {
        $status = get_post_meta($post_id, 'vanregen_inquiry_email_status', true);
        $labels = array(
            'sent' => __('Sent', 'vanregen-content'),
            'failed' => __('Failed', 'vanregen-content'),
            'not_configured' => __('Not configured', 'vanregen-content'),
        );
        echo esc_html($labels[$status] ?? __('Unknown', 'vanregen-content'));
    }
}
add_action('manage_vanregen_inquiry_posts_custom_column', 'vanregen_inquiry_admin_column', 10, 2);

function vanregen_add_inquiry_details_meta_box($post) {
    if ($post->post_type !== 'vanregen_inquiry') {
        return;
    }
    add_meta_box(
        'vanregen-inquiry-details',
        __('Inquiry Contact & Delivery', 'vanregen-content'),
        'vanregen_render_inquiry_details_meta_box',
        'vanregen_inquiry',
        'side',
        'high'
    );
}
add_action('add_meta_boxes_vanregen_inquiry', 'vanregen_add_inquiry_details_meta_box');

function vanregen_render_inquiry_details_meta_box($post) {
    $name = vanregen_inquiry_admin_field($post->ID, 'vanregen_inquiry_name', 'Name');
    $email = vanregen_inquiry_admin_field($post->ID, 'vanregen_inquiry_email', 'Email');
    $phone = vanregen_inquiry_admin_field($post->ID, 'vanregen_inquiry_phone', 'Phone');
    $status = get_post_meta($post->ID, 'vanregen_inquiry_email_status', true);
    $error = get_post_meta($post->ID, 'vanregen_inquiry_email_error', true);
    $status_labels = array(
        'sent' => __('Sent', 'vanregen-content'),
        'failed' => __('Failed', 'vanregen-content'),
        'not_configured' => __('Not configured', 'vanregen-content'),
    );

    if ($name !== '') {
        echo '<p><strong>' . esc_html($name) . '</strong></p>';
    }
    if (is_email($email)) {
        $subject = sprintf(__('Re: Website inquiry - %s', 'vanregen-content'), $name);
        $body = sprintf(__('Hello %s,', 'vanregen-content'), $name) . "\n\n";
        $gmail_url = add_query_arg(array(
            'view' => 'cm',
            'fs' => '1',
            'to' => $email,
            'su' => $subject,
            'body' => $body,
        ), 'https://mail.google.com/mail/');
        $mailto_url = 'mailto:' . rawurlencode($email) . '?subject=' . rawurlencode($subject) . '&body=' . rawurlencode($body);
        echo '<p><a class="button button-primary" href="' . esc_url($gmail_url) . '" target="_blank" rel="noopener noreferrer">' . esc_html__('Reply to submitter in Gmail', 'vanregen-content') . '</a></p>';
        echo '<p><a class="button" href="' . esc_url($mailto_url) . '">' . esc_html__('Open in email app', 'vanregen-content') . '</a><br>' . esc_html($email) . '</p>';
    }
    if ($phone !== '') {
        echo '<p><a href="tel:' . esc_attr(preg_replace('/[^\d+]/', '', $phone)) . '">' . esc_html($phone) . '</a></p>';
    }
    echo '<hr><p><strong>' . esc_html__('Email delivery:', 'vanregen-content') . '</strong> ' . esc_html($status_labels[$status] ?? __('Unknown for older inquiry', 'vanregen-content')) . '</p>';
    if ($error !== '') {
        echo '<p class="description">' . esc_html($error) . '</p>';
    }
}

function vanregen_inquiry_admin_filter($post_type) {
    if ($post_type !== 'vanregen_inquiry') {
        return;
    }
    $selected = isset($_GET['inquiry_type']) && is_string($_GET['inquiry_type'])
        ? sanitize_key($_GET['inquiry_type'])
        : '';
    echo '<select name="inquiry_type">';
    echo '<option value="">' . esc_html__('All inquiry types', 'vanregen-content') . '</option>';
    foreach (array('contact' => __('Contact', 'vanregen-content'), 'quote' => __('Quote', 'vanregen-content')) as $value => $label) {
        echo '<option value="' . esc_attr($value) . '"' . selected($selected, $value, false) . '>' . esc_html($label) . '</option>';
    }
    echo '</select>';
}
add_action('restrict_manage_posts', 'vanregen_inquiry_admin_filter');

function vanregen_filter_inquiry_admin_query($query) {
    if (!is_admin() || !$query->is_main_query() || $query->get('post_type') !== 'vanregen_inquiry') {
        return;
    }
    $type = isset($_GET['inquiry_type']) && is_string($_GET['inquiry_type'])
        ? sanitize_key($_GET['inquiry_type'])
        : '';
    if (!in_array($type, array('contact', 'quote'), true)) {
        return;
    }
    $meta_query = (array) $query->get('meta_query');
    $meta_query[] = array(
        'key' => 'vanregen_inquiry_type',
        'value' => $type,
    );
    $query->set('meta_query', $meta_query);
}
add_action('pre_get_posts', 'vanregen_filter_inquiry_admin_query');

function vanregen_migrate_inquiry_admin_metadata() {
    $version = '2026-11-22-inquiry-admin-1';
    if (get_option('vanregen_inquiry_admin_metadata_version') === $version) {
        return;
    }

    $inquiry_ids = get_posts(array(
        'post_type' => 'vanregen_inquiry',
        'post_status' => array('private'),
        'posts_per_page' => -1,
        'fields' => 'ids',
        'no_found_rows' => true,
    ));
    foreach ($inquiry_ids as $post_id) {
        $content = (string) get_post_field('post_content', $post_id);
        $type = strtolower(vanregen_inquiry_admin_field($post_id, 'vanregen_inquiry_type', 'Inquiry type'));
        if (in_array($type, array('contact', 'quote'), true)) {
            update_post_meta($post_id, 'vanregen_inquiry_type', $type);
        }

        foreach (array(
            'vanregen_inquiry_name' => 'Name',
            'vanregen_inquiry_company' => 'Company',
            'vanregen_inquiry_email' => 'Email',
            'vanregen_inquiry_phone' => 'Phone',
            'vanregen_inquiry_context_service' => 'Related service',
        ) as $meta_key => $content_label) {
            if (get_post_meta($post_id, $meta_key, true) === '') {
                $value = vanregen_inquiry_admin_field($post_id, $meta_key, $content_label);
                if ($value !== '') {
                    update_post_meta($post_id, $meta_key, $value);
                }
            }
        }

        if (get_post_meta($post_id, 'vanregen_inquiry_request_summary', true) === '') {
            if (preg_match_all('/^Service:\s*(.+)$/mi', $content, $matches) && !empty($matches[1])) {
                update_post_meta($post_id, 'vanregen_inquiry_request_summary', implode(', ', array_unique(array_map('trim', $matches[1]))));
            } else {
                $related_service = vanregen_inquiry_admin_field($post_id, 'vanregen_inquiry_context_service', 'Related service');
                if ($related_service !== '') {
                    update_post_meta($post_id, 'vanregen_inquiry_request_summary', $related_service);
                }
            }
        }
    }

    update_option('vanregen_inquiry_admin_metadata_version', $version, false);
}
add_action('admin_init', 'vanregen_migrate_inquiry_admin_metadata');

function vanregen_page_copy_defaults() {
    return array(
        'vanregen_page_summary' => '',
        'vanregen_global_company_name' => 'VAN REGEN PRIVATE LIMITED',
        'vanregen_global_brand_name' => 'VAN REGEN',
        'vanregen_global_brand_tagline' => 'RESPONSIBLE RESOURCE RECOVERY',
        'vanregen_global_topline_descriptor' => 'RESOURCE RECOVERY / INDUSTRIAL SERVICES',
        'vanregen_global_topline_cta' => 'Start a conversation',
        'vanregen_global_footer_statement' => "Resource recovery for a world\nthat keeps moving.",
        'vanregen_global_footer_cta' => 'Start a conversation',
        'vanregen_global_footer_note' => '',
        'vanregen_global_footer_credit' => 'Website By Money Way Digital Marketing Solutions',
        'vanregen_home_hero_eyebrow' => 'CIRCULAR THINKING. INDUSTRIAL SCALE.',
        'vanregen_home_hero_heading' => 'VAN REGEN',
        'vanregen_home_hero_tagline' => 'Resourceful by nature.',
        'vanregen_home_hero_summary' => 'A thoughtful partner for material recovery, equipment retirement and industrial change.',
        'vanregen_home_intro_eyebrow' => 'WHAT WE DO',
        'vanregen_home_intro_heading' => "Making the next use\npart of the plan.",
        'vanregen_home_intro_body' => 'We help businesses think differently about end-of-life assets and industrial materials. From careful collection to considered dismantling, every project starts with understanding what needs to move forward.',
        'vanregen_home_intro_link' => 'Get to know us',
        'vanregen_home_intro_stamp' => "BUILT FOR\nWHAT COMES NEXT",
        'vanregen_home_primary_link' => 'Explore our services',
        'vanregen_home_secondary_link' => 'Meet Van Regen',
        'vanregen_home_services_eyebrow' => 'OUR CAPABILITIES',
        'vanregen_home_services_heading' => "Different materials.\nOne considered approach.",
        'vanregen_home_services_link' => 'All services',
        'vanregen_home_impact_eyebrow' => 'MATERIALS, MEASURED',
        'vanregen_home_impact_heading' => "See your material\nin a new light.",
        'vanregen_home_impact_body' => 'Explore an illustrative view of material volume and potential tree-planting equivalents. Actual environmental outcomes depend on verified project data.',
        'vanregen_home_impact_link' => 'Our approach to impact',
        'vanregen_home_calculator_label' => 'Estimated material volume',
        'vanregen_home_calculator_badge' => 'ILLUSTRATIVE ONLY',
        'vanregen_home_calculator_volume_label' => 'Estimated material volume',
        'vanregen_home_calculator_tree_label' => 'illustrative tree equivalents*',
        'vanregen_home_calculator_notice' => '*Based on a hypothetical ratio of one tree per 1,000 kg. No planting or carbon credit is guaranteed by this estimate.',
        'vanregen_home_project_eyebrow' => 'SELECTED WORK',
        'vanregen_home_project_heading' => "On the ground,\nwith purpose.",
        'vanregen_home_project_link' => 'All projects',
        'vanregen_home_cta_eyebrow' => 'BEGIN WITH A CONVERSATION',
        'vanregen_home_cta_heading' => "Every project starts\nwith a better question.",
        'vanregen_home_cta_button' => 'Talk to our team',
        'vanregen_services_heading' => 'Explore our capabilities',
        'vanregen_sustainability_heading' => 'Model an illustrative equivalent',
        'vanregen_sustainability_description' => 'Model illustrative material recovery and emissions equivalents from collected electronic waste. Results are not measured environmental outcomes.',
        'vanregen_sustainability_processed_kg' => '',
        'vanregen_sustainability_metal_recovery_percent' => '10',
        'vanregen_sustainability_co2e_avoided_per_kg' => '0.5',
        'vanregen_sustainability_it_metal_recovery_percent' => '12',
        'vanregen_sustainability_it_co2e_avoided_per_kg' => '0.6',
        'vanregen_sustainability_mobile_metal_recovery_percent' => '20',
        'vanregen_sustainability_mobile_co2e_avoided_per_kg' => '0.8',
        'vanregen_sustainability_energy_recovered_kwh_per_kg' => '0.25',
        'vanregen_sustainability_landfill_diverted_percent' => '85',
        'vanregen_sustainability_water_saved_l_per_kg' => '0.1',
        'vanregen_sustainability_scenario_conservative_multiplier' => '0.75',
        'vanregen_sustainability_scenario_base_multiplier' => '1',
        'vanregen_sustainability_scenario_optimistic_multiplier' => '1.25',
        'vanregen_sustainability_factor_note' => 'Illustrative starter assumptions only. These factors are not measured outcomes; replace them with owner-approved, verified project data before making environmental claims.',
        'vanregen_projects_empty_heading' => 'Project stories are being prepared.',
        'vanregen_projects_empty_description' => 'Verified project details will appear here once they are added in WordPress.',
        'vanregen_projects_section_heading' => 'Selected projects',
        'vanregen_projects_section_description' => 'Explore project work and case studies published by Van Regen.',
        'vanregen_projects_gallery_heading' => 'Project gallery',
        'vanregen_projects_gallery_description' => 'Photos from project work and material recovery.',
        'vanregen_projects_gallery_empty' => 'Gallery photos will appear here once they are added in WordPress.',
        'vanregen_contact_heading' => 'Tell us what you are working through.',
        'vanregen_contact_description' => 'Share a few details and the team can follow up about your request.',
        'vanregen_contact_address' => 'Plant Gat No. 182, Baramati Road, Near Palkhi Mahamarg, Patas, Tal. Daund, Dist. Pune, Maharashtra - 412219',
        'vanregen_contact_emails' => "info@vanregen.in\nranjit@vanregen.in",
        'vanregen_contact_phones' => "+91 96655 55591\n+91 94231 37475",
        'vanregen_contact_privacy_note' => 'Your information is used to respond to this inquiry. Please do not include sensitive personal information.',
    );
}

function vanregen_page_copy_fields($slug) {
    $fields = array(
        'vanregen_page_summary' => $slug === 'contact-quote'
            ? __('Quote page hero summary', 'vanregen-content')
            : __('Page introduction', 'vanregen-content'),
    );

    if ($slug === 'home') {
        return array_merge($fields, array(
            'vanregen_global_company_name' => __('Company name', 'vanregen-content'),
            'vanregen_global_brand_name' => __('Brand wordmark', 'vanregen-content'),
            'vanregen_global_brand_tagline' => __('Brand tagline', 'vanregen-content'),
            'vanregen_global_topline_descriptor' => __('Top bar descriptor', 'vanregen-content'),
            'vanregen_global_topline_cta' => __('Top bar link label', 'vanregen-content'),
            'vanregen_global_footer_statement' => __('Footer statement', 'vanregen-content'),
            'vanregen_global_footer_cta' => __('Footer link label', 'vanregen-content'),
            'vanregen_global_footer_credit' => __('Website credit', 'vanregen-content'),
            'vanregen_home_hero_eyebrow' => __('Hero eyebrow', 'vanregen-content'),
            'vanregen_home_hero_heading' => __('Hero heading', 'vanregen-content'),
            'vanregen_home_hero_tagline' => __('Hero tagline', 'vanregen-content'),
            'vanregen_home_hero_summary' => __('Hero summary', 'vanregen-content'),
            'vanregen_home_intro_eyebrow' => __('Introduction eyebrow', 'vanregen-content'),
            'vanregen_home_intro_heading' => __('Introduction heading', 'vanregen-content'),
            'vanregen_home_intro_body' => __('Introduction text', 'vanregen-content'),
            'vanregen_home_intro_link' => __('Introduction link label', 'vanregen-content'),
            'vanregen_home_intro_stamp' => __('Introduction stamp', 'vanregen-content'),
            'vanregen_home_primary_link' => __('Primary hero link', 'vanregen-content'),
            'vanregen_home_secondary_link' => __('Secondary hero link', 'vanregen-content'),
            'vanregen_home_services_eyebrow' => __('Services eyebrow', 'vanregen-content'),
            'vanregen_home_services_heading' => __('Services heading', 'vanregen-content'),
            'vanregen_home_services_link' => __('Services link label', 'vanregen-content'),
            'vanregen_home_impact_eyebrow' => __('Impact eyebrow', 'vanregen-content'),
            'vanregen_home_impact_heading' => __('Impact heading', 'vanregen-content'),
            'vanregen_home_impact_body' => __('Impact text', 'vanregen-content'),
            'vanregen_home_impact_link' => __('Impact link label', 'vanregen-content'),
            'vanregen_home_calculator_label' => __('Calculator label', 'vanregen-content'),
            'vanregen_home_calculator_badge' => __('Calculator badge', 'vanregen-content'),
            'vanregen_home_calculator_volume_label' => __('Calculator volume label', 'vanregen-content'),
            'vanregen_home_calculator_tree_label' => __('Calculator result label', 'vanregen-content'),
            'vanregen_home_calculator_notice' => __('Calculator disclaimer', 'vanregen-content'),
            'vanregen_home_project_eyebrow' => __('Projects eyebrow', 'vanregen-content'),
            'vanregen_home_project_heading' => __('Projects heading', 'vanregen-content'),
            'vanregen_home_project_link' => __('Projects link label', 'vanregen-content'),
            'vanregen_home_cta_eyebrow' => __('Closing call-to-action eyebrow', 'vanregen-content'),
            'vanregen_home_cta_heading' => __('Closing call-to-action heading', 'vanregen-content'),
            'vanregen_home_cta_button' => __('Closing call-to-action button', 'vanregen-content'),
        ));
    }

    $page_fields = array(
        'services' => array(
            'vanregen_services_heading' => __('Service directory heading', 'vanregen-content'),
        ),
        'sustainability' => array(
            'vanregen_sustainability_heading' => __('Calculator heading', 'vanregen-content'),
            'vanregen_sustainability_description' => __('Calculator explanation', 'vanregen-content'),
            'vanregen_sustainability_processed_kg' => __('Verified e-waste collected and processed total (kg)', 'vanregen-content'),
            'vanregen_sustainability_metal_recovery_percent' => __('Illustrative recovered-metal rate (%)', 'vanregen-content'),
            'vanregen_sustainability_co2e_avoided_per_kg' => __('Illustrative CO2e avoided per kg collected (kg/kg)', 'vanregen-content'),
            'vanregen_sustainability_it_metal_recovery_percent' => __('IT equipment recovered-metal rate (%)', 'vanregen-content'),
            'vanregen_sustainability_it_co2e_avoided_per_kg' => __('IT equipment CO2e avoided per kg (kg/kg)', 'vanregen-content'),
            'vanregen_sustainability_mobile_metal_recovery_percent' => __('Mobile-device recovered-metal rate (%)', 'vanregen-content'),
            'vanregen_sustainability_mobile_co2e_avoided_per_kg' => __('Mobile-device CO2e avoided per kg (kg/kg)', 'vanregen-content'),
            'vanregen_sustainability_energy_recovered_kwh_per_kg' => __('Energy recovered per kg (kWh/kg)', 'vanregen-content'),
            'vanregen_sustainability_landfill_diverted_percent' => __('Landfill diversion rate (%)', 'vanregen-content'),
            'vanregen_sustainability_water_saved_l_per_kg' => __('Water saved per kg (L/kg)', 'vanregen-content'),
            'vanregen_sustainability_scenario_conservative_multiplier' => __('Conservative scenario multiplier', 'vanregen-content'),
            'vanregen_sustainability_scenario_base_multiplier' => __('Illustrative scenario multiplier', 'vanregen-content'),
            'vanregen_sustainability_scenario_optimistic_multiplier' => __('Optimistic scenario multiplier', 'vanregen-content'),
            'vanregen_sustainability_factor_note' => __('Calculation assumptions and disclaimer', 'vanregen-content'),
        ),
        'projects' => array(
            'vanregen_projects_empty_heading' => __('Projects heading', 'vanregen-content'),
            'vanregen_projects_empty_description' => __('Projects description', 'vanregen-content'),
            'vanregen_projects_section_heading' => __('Projects section heading', 'vanregen-content'),
            'vanregen_projects_section_description' => __('Projects section description', 'vanregen-content'),
            'vanregen_projects_gallery_heading' => __('Gallery section heading', 'vanregen-content'),
            'vanregen_projects_gallery_description' => __('Gallery section description', 'vanregen-content'),
            'vanregen_projects_gallery_empty' => __('Gallery empty-state message', 'vanregen-content'),
        ),
        'contact' => array(
            'vanregen_contact_heading' => __('Contact heading', 'vanregen-content'),
            'vanregen_contact_description' => __('Contact description', 'vanregen-content'),
            'vanregen_contact_address' => __('Office address', 'vanregen-content'),
            'vanregen_contact_emails' => __('Contact email addresses (one per line)', 'vanregen-content'),
            'vanregen_contact_phones' => __('Contact phone numbers (one per line)', 'vanregen-content'),
            'vanregen_contact_privacy_note' => __('Privacy note', 'vanregen-content'),
        ),
        'contact-quote' => array(
            'vanregen_contact_heading' => __('Quote form heading', 'vanregen-content'),
            'vanregen_contact_description' => __('Quote form description', 'vanregen-content'),
            'vanregen_contact_address' => __('Office address', 'vanregen-content'),
            'vanregen_contact_emails' => __('Contact email addresses (one per line)', 'vanregen-content'),
            'vanregen_contact_phones' => __('Contact phone numbers (one per line)', 'vanregen-content'),
            'vanregen_contact_privacy_note' => __('Privacy note', 'vanregen-content'),
        ),
    );

    return array_merge($fields, $page_fields[$slug] ?? array());
}

function vanregen_can_edit_page_copy_meta($allowed, $meta_key, $post_id) {
    return current_user_can('edit_post', $post_id);
}

function vanregen_sanitize_service_products($value) {
    if (is_string($value)) {
        $value = preg_split('/\r\n|\r|\n/', $value);
    }
    if (!is_array($value)) {
        return array();
    }

    $products = array();
    foreach ($value as $product) {
        if (!is_string($product)) {
            continue;
        }
        $product = sanitize_text_field($product);
        if ($product !== '') {
            $products[$product] = $product;
        }
    }

    return array_values($products);
}

function vanregen_sanitize_seo_title($value) {
    $value = sanitize_text_field($value);
    return function_exists('mb_substr') ? mb_substr($value, 0, 70) : substr($value, 0, 70);
}

function vanregen_sanitize_seo_description($value) {
    $value = sanitize_text_field($value);
    return function_exists('mb_substr') ? mb_substr($value, 0, 160) : substr($value, 0, 160);
}

function vanregen_sanitize_seo_robots($value) {
    $allowed = array('', 'index,follow', 'noindex,follow', 'noindex,nofollow');
    $value = sanitize_text_field($value);
    return in_array($value, $allowed, true) ? $value : '';
}

function vanregen_sanitize_metal_recovery_percent($value) {
    if (!is_numeric($value)) {
        return '';
    }
    return number_format(min(100, max(0, (float) $value)), 2, '.', '');
}

function vanregen_sanitize_co2e_factor($value) {
    if (!is_numeric($value)) {
        return '';
    }
    return number_format(min(1000, max(0, (float) $value)), 3, '.', '');
}

function vanregen_sanitize_scenario_multiplier($value) {
    if (!is_numeric($value)) {
        return '';
    }
    return number_format(min(5, max(0, (float) $value)), 2, '.', '');
}

function vanregen_sanitize_processed_kg($value) {
    if ($value === '' || !is_numeric($value)) {
        return '';
    }
    return number_format(min(1000000000, max(0, (float) $value)), 3, '.', '');
}
function vanregen_sanitize_project_gallery($value) {
    if (is_string($value)) {
        $value = json_decode($value, true);
    }
    if (!is_array($value)) {
        return array();
    }

    $urls = array();
    foreach ($value as $url) {
        if (!is_string($url)) {
            continue;
        }
        $url = esc_url_raw($url);
        if ($url !== '') {
            $urls[] = $url;
        }
    }
    return array_values(array_unique($urls));
}

function vanregen_site_seo_defaults() {
    $home_id = (int) get_option('page_on_front');
    $contact = get_page_by_path('contact', OBJECT, 'page') ?: get_page_by_path('contact-quote', OBJECT, 'page');
    $home = $home_id ? get_post($home_id) : null;
    $site_name = $home ? trim((string) get_post_meta($home->ID, 'vanregen_global_brand_name', true)) : '';
    if ($site_name === '') {
        $site_name = get_bloginfo('name');
    }
    $emails = $contact ? preg_split('/\r\n|\r|\n/', (string) get_post_meta($contact->ID, 'vanregen_contact_emails', true)) : array();
    $phones = $contact ? preg_split('/\r\n|\r|\n/', (string) get_post_meta($contact->ID, 'vanregen_contact_phones', true)) : array();
    $description = $home ? get_post_meta($home->ID, 'vanregen_seo_description', true) : '';
    if (!$description && $home) {
        $description = get_post_meta($home->ID, 'vanregen_page_summary', true);
    }

    return array(
        'title_suffix' => $site_name,
        'site_name' => $site_name,
        'default_description' => $description,
        'default_social_image_url' => '',
        'default_social_image_alt' => '',
        'twitter_card' => 'summary_large_image',
        'twitter_site' => '',
        'social_profile_urls' => '',
        'locale' => str_replace('-', '_', get_locale()),
        'default_robots' => 'index',
        'organization_name' => $site_name,
        'organization_legal_name' => $home ? get_post_meta($home->ID, 'vanregen_global_company_name', true) : get_bloginfo('name'),
        'organization_email' => trim((string) ($emails[0] ?? '')),
        'organization_phone' => trim((string) ($phones[0] ?? '')),
        'organization_address' => $contact ? get_post_meta($contact->ID, 'vanregen_contact_address', true) : '',
        'organization_locality' => 'Daund',
        'organization_region' => 'Maharashtra',
        'organization_postal_code' => '412219',
        'organization_country' => 'IN',
    );
}

function vanregen_get_site_seo_settings() {
    $settings = get_option('vanregen_seo_settings', array());
    return array_merge(vanregen_site_seo_defaults(), is_array($settings) ? $settings : array());
}

function vanregen_sanitize_site_seo_settings($input) {
    $input = is_array($input) ? $input : array();
    $defaults = vanregen_site_seo_defaults();
    $settings = array();
    foreach ($defaults as $key => $default) {
        $value = isset($input[$key]) && is_scalar($input[$key]) ? wp_unslash($input[$key]) : $default;
        if (in_array($key, array('default_social_image_url'), true)) {
            $settings[$key] = esc_url_raw($value);
        } elseif ($key === 'default_description' || $key === 'organization_address' || $key === 'social_profile_urls') {
            $settings[$key] = sanitize_textarea_field($value);
        } else {
            $settings[$key] = sanitize_text_field($value);
        }
    }
    $settings['default_robots'] = in_array($settings['default_robots'], array('index', 'noindex'), true) ? $settings['default_robots'] : 'index';
    $settings['twitter_card'] = in_array($settings['twitter_card'], array('summary', 'summary_large_image'), true) ? $settings['twitter_card'] : 'summary_large_image';
    return $settings;
}

function vanregen_site_seo_fields() {
    return array(
        'title_suffix' => array('label' => __('Title suffix', 'vanregen-content'), 'type' => 'text', 'description' => __('Appended to generated page titles when no SEO title is set.', 'vanregen-content')),
        'site_name' => array('label' => __('Site name', 'vanregen-content'), 'type' => 'text'),
        'default_description' => array('label' => __('Default meta description', 'vanregen-content'), 'type' => 'textarea'),
        'default_social_image_url' => array('label' => __('Default social image', 'vanregen-content'), 'type' => 'image'),
        'default_social_image_alt' => array('label' => __('Default social image alt text', 'vanregen-content'), 'type' => 'text'),
        'twitter_card' => array('label' => __('Twitter card type', 'vanregen-content'), 'type' => 'select', 'options' => array('summary_large_image' => __('Large image', 'vanregen-content'), 'summary' => __('Summary', 'vanregen-content'))),
        'twitter_site' => array('label' => __('X / Twitter account', 'vanregen-content'), 'type' => 'text', 'description' => __('Use the account handle including @.', 'vanregen-content')),
        'social_profile_urls' => array('label' => __('Social profile URLs', 'vanregen-content'), 'type' => 'textarea', 'description' => __('One public profile URL per line for organization structured data.', 'vanregen-content')),
        'locale' => array('label' => __('Content locale', 'vanregen-content'), 'type' => 'text', 'description' => __('Use a locale such as en_IN.', 'vanregen-content')),
        'default_robots' => array('label' => __('Default indexing', 'vanregen-content'), 'type' => 'select', 'options' => array('index' => __('Index pages', 'vanregen-content'), 'noindex' => __('Do not index pages', 'vanregen-content'))),
        'organization_name' => array('label' => __('Organization name', 'vanregen-content'), 'type' => 'text'),
        'organization_legal_name' => array('label' => __('Legal name', 'vanregen-content'), 'type' => 'text'),
        'organization_email' => array('label' => __('Organization email', 'vanregen-content'), 'type' => 'email'),
        'organization_phone' => array('label' => __('Organization phone', 'vanregen-content'), 'type' => 'text'),
        'organization_address' => array('label' => __('Street address', 'vanregen-content'), 'type' => 'textarea'),
        'organization_locality' => array('label' => __('City / locality', 'vanregen-content'), 'type' => 'text'),
        'organization_region' => array('label' => __('State / region', 'vanregen-content'), 'type' => 'text'),
        'organization_postal_code' => array('label' => __('Postal code', 'vanregen-content'), 'type' => 'text'),
        'organization_country' => array('label' => __('Country code', 'vanregen-content'), 'type' => 'text'),
    );
}

function vanregen_render_site_seo_field($args) {
    $settings = vanregen_get_site_seo_settings();
    $key = $args['key'];
    $type = $args['type'];
    $id = 'vanregen_seo_' . $key;
    $name = 'vanregen_seo_settings[' . $key . ']';
    $value = $settings[$key] ?? '';
    if ($type === 'textarea') {
        echo '<textarea class="large-text" rows="3" id="' . esc_attr($id) . '" name="' . esc_attr($name) . '">' . esc_textarea($value) . '</textarea>';
    } elseif ($type === 'select') {
        echo '<select id="' . esc_attr($id) . '" name="' . esc_attr($name) . '">';
        foreach ($args['options'] as $option => $label) {
            echo '<option value="' . esc_attr($option) . '" ' . selected($value, $option, false) . '>' . esc_html($label) . '</option>';
        }
        echo '</select>';
    } else {
        echo '<input class="regular-text" type="' . esc_attr($type === 'image' ? 'url' : $type) . '" id="' . esc_attr($id) . '" name="' . esc_attr($name) . '" value="' . esc_attr($value) . '">';
        if ($type === 'image') {
            echo ' <button type="button" class="button vanregen-select-media" data-target="' . esc_attr($id) . '" data-type="image" data-title="' . esc_attr__('Choose default social image', 'vanregen-content') . '" data-button="' . esc_attr__('Use this image', 'vanregen-content') . '">' . esc_html__('Choose image', 'vanregen-content') . '</button> <button type="button" class="button vanregen-clear-media" data-target="' . esc_attr($id) . '">' . esc_html__('Clear', 'vanregen-content') . '</button>';
        }
    }
    if (!empty($args['description'])) {
        echo '<p class="description">' . esc_html($args['description']) . '</p>';
    }
}

function vanregen_register_site_seo_settings() {
    register_setting('vanregen_seo_settings_group', 'vanregen_seo_settings', array('sanitize_callback' => 'vanregen_sanitize_site_seo_settings'));
    add_settings_section('vanregen_seo_sitewide', __('Site-wide SEO defaults', 'vanregen-content'), '__return_false', 'vanregen-seo');
    foreach (vanregen_site_seo_fields() as $key => $field) {
        add_settings_field($key, $field['label'], 'vanregen_render_site_seo_field', 'vanregen-seo', 'vanregen_seo_sitewide', array_merge($field, array('key' => $key)));
    }
}
add_action('admin_init', 'vanregen_register_site_seo_settings');

function vanregen_add_site_seo_settings_page() {
    add_options_page(__('Van Regen SEO', 'vanregen-content'), __('Van Regen SEO', 'vanregen-content'), 'manage_options', 'vanregen-seo', 'vanregen_render_site_seo_settings_page');
}
add_action('admin_menu', 'vanregen_add_site_seo_settings_page');

function vanregen_render_site_seo_settings_page() {
    if (!current_user_can('manage_options')) {
        return;
    }
    echo '<div class="wrap"><h1>' . esc_html__('Van Regen SEO', 'vanregen-content') . '</h1><p>' . esc_html__('Manage site-wide SEO defaults and Organization structured data. Item-specific fields are available in each content editor.', 'vanregen-content') . '</p><form method="post" action="options.php">';
    settings_fields('vanregen_seo_settings_group');
    do_settings_sections('vanregen-seo');
    submit_button();
    echo '</form></div>';
}

function vanregen_get_smtp_settings() {
    $defaults = array(
        'enabled' => false,
        'host' => 'smtp.gmail.com',
        'port' => 587,
        'encryption' => 'tls',
        'username' => 'info@vanregen.in',
        'password' => '',
        'from_email' => 'info@vanregen.in',
        'from_name' => 'Van Regen Private Limited',
        'notification_email' => 'info@vanregen.in',
    );
    $settings = get_option('vanregen_smtp_settings', array());
    $settings = wp_parse_args(is_array($settings) ? $settings : array(), $defaults);
    $settings['enabled'] = !empty($settings['enabled']);
    $settings['port'] = absint($settings['port']) ?: 587;
    if (!is_email($settings['notification_email'])) {
        $settings['notification_email'] = $defaults['notification_email'];
    }
    return $settings;
}

function vanregen_sanitize_smtp_settings($input) {
    $input = is_array($input) ? $input : array();
    $existing = vanregen_get_smtp_settings();
    $password = isset($input['password']) ? sanitize_text_field(wp_unslash($input['password'])) : '';
    $password = preg_replace('/\s+/', '', $password);
    $encryption = sanitize_key($input['encryption'] ?? 'tls');

    return array(
        'enabled' => !empty($input['enabled']),
        'host' => sanitize_text_field(wp_unslash($input['host'] ?? 'smtp.gmail.com')),
        'port' => absint($input['port'] ?? 587),
        'encryption' => in_array($encryption, array('tls', 'ssl', ''), true) ? $encryption : 'tls',
        'username' => sanitize_text_field(wp_unslash($input['username'] ?? '')),
        'password' => $password !== '' ? $password : $existing['password'],
        'from_email' => sanitize_email(wp_unslash($input['from_email'] ?? '')),
        'from_name' => sanitize_text_field(wp_unslash($input['from_name'] ?? 'Van Regen Private Limited')),
        'notification_email' => sanitize_email(wp_unslash($input['notification_email'] ?? 'info@vanregen.in')),
    );
}

function vanregen_smtp_configuration_error($settings = null) {
    $settings = is_array($settings) ? $settings : vanregen_get_smtp_settings();
    if (!$settings['enabled']) {
        return __('SMTP is disabled.', 'vanregen-content');
    }
    if ($settings['host'] === '' || !is_email($settings['username']) || !is_email($settings['from_email']) || !is_email($settings['notification_email'])) {
        return __('Complete the SMTP host, username, sender, and notification recipient.', 'vanregen-content');
    }
    if ($settings['password'] === '') {
        return __('Enter the SMTP account app password.', 'vanregen-content');
    }
    $normalized_password = preg_replace('/\s+/', '', (string) $settings['password']);
    if (stripos($settings['host'], 'smtp.gmail.com') !== false && !preg_match('/^[a-zA-Z]{16}$/', $normalized_password)) {
        return __('The Google app password is incomplete or invalid. Replace it with the 16-character app password generated by Google Account security settings.', 'vanregen-content');
    }
    return '';
}

function vanregen_register_smtp_settings() {
    register_setting('vanregen_smtp_settings_group', 'vanregen_smtp_settings', array(
        'type' => 'array',
        'sanitize_callback' => 'vanregen_sanitize_smtp_settings',
        'default' => vanregen_get_smtp_settings(),
    ));
}
add_action('admin_init', 'vanregen_register_smtp_settings');

function vanregen_add_smtp_settings_page() {
    add_options_page(
        __('Van Regen SMTP', 'vanregen-content'),
        __('Van Regen SMTP', 'vanregen-content'),
        'manage_options',
        'vanregen-smtp',
        'vanregen_render_smtp_settings_page'
    );
}
add_action('admin_menu', 'vanregen_add_smtp_settings_page');

function vanregen_render_smtp_settings_page() {
    if (!current_user_can('manage_options')) {
        return;
    }
    $settings = vanregen_get_smtp_settings();
    $configuration_error = vanregen_smtp_configuration_error($settings);
    echo '<div class="wrap"><h1>' . esc_html__('Van Regen SMTP', 'vanregen-content') . '</h1>';
    echo '<p>' . esc_html__('Configure Gmail SMTP for website inquiry notifications. Use a Google app password rather than your normal account password.', 'vanregen-content') . '</p>';
    if ($configuration_error !== '') {
        echo '<div class="notice notice-warning inline"><p><strong>' . esc_html__('Email delivery needs attention:', 'vanregen-content') . '</strong> ' . esc_html($configuration_error) . '</p></div>';
    }
    echo '<form method="post" action="options.php">';
    settings_fields('vanregen_smtp_settings_group');
    echo '<table class="form-table" role="presentation"><tbody>';
    echo '<tr><th scope="row">' . esc_html__('Enable SMTP', 'vanregen-content') . '</th><td><label><input type="checkbox" name="vanregen_smtp_settings[enabled]" value="1" ' . checked($settings['enabled'], true, false) . '> ' . esc_html__('Send WordPress email through SMTP', 'vanregen-content') . '</label></td></tr>';
    echo '<tr><th scope="row"><label for="vanregen_smtp_host">' . esc_html__('SMTP host', 'vanregen-content') . '</label></th><td><input class="regular-text" type="text" id="vanregen_smtp_host" name="vanregen_smtp_settings[host]" value="' . esc_attr($settings['host']) . '"></td></tr>';
    echo '<tr><th scope="row"><label for="vanregen_smtp_port">' . esc_html__('SMTP port', 'vanregen-content') . '</label></th><td><input class="small-text" type="number" min="1" max="65535" id="vanregen_smtp_port" name="vanregen_smtp_settings[port]" value="' . esc_attr($settings['port']) . '"></td></tr>';
    echo '<tr><th scope="row"><label for="vanregen_smtp_encryption">' . esc_html__('Encryption', 'vanregen-content') . '</label></th><td><select id="vanregen_smtp_encryption" name="vanregen_smtp_settings[encryption]"><option value="tls" ' . selected($settings['encryption'], 'tls', false) . '>TLS</option><option value="ssl" ' . selected($settings['encryption'], 'ssl', false) . '>SSL</option><option value="" ' . selected($settings['encryption'], '', false) . '>None</option></select></td></tr>';
    echo '<tr><th scope="row"><label for="vanregen_smtp_username">' . esc_html__('SMTP username', 'vanregen-content') . '</label></th><td><input class="regular-text" type="email" id="vanregen_smtp_username" name="vanregen_smtp_settings[username]" value="' . esc_attr($settings['username']) . '" autocomplete="username"></td></tr>';
    echo '<tr><th scope="row"><label for="vanregen_smtp_password">' . esc_html__('Google app password', 'vanregen-content') . '</label></th><td><input class="regular-text" type="password" id="vanregen_smtp_password" name="vanregen_smtp_settings[password]" value="" autocomplete="new-password"><p class="description">' . esc_html($settings['password'] !== '' ? 'A password is saved. Leave this blank to keep it.' : 'Generate this in Google Account > Security > 2-Step Verification > App passwords.', 'vanregen-content') . '</p></td></tr>';
    echo '<tr><th scope="row"><label for="vanregen_smtp_from_email">' . esc_html__('From email', 'vanregen-content') . '</label></th><td><input class="regular-text" type="email" id="vanregen_smtp_from_email" name="vanregen_smtp_settings[from_email]" value="' . esc_attr($settings['from_email']) . '"></td></tr>';
    echo '<tr><th scope="row"><label for="vanregen_smtp_from_name">' . esc_html__('From name', 'vanregen-content') . '</label></th><td><input class="regular-text" type="text" id="vanregen_smtp_from_name" name="vanregen_smtp_settings[from_name]" value="' . esc_attr($settings['from_name']) . '"></td></tr>';
    echo '<tr><th scope="row"><label for="vanregen_smtp_notification_email">' . esc_html__('Inquiry notification recipient', 'vanregen-content') . '</label></th><td><input class="regular-text" type="email" id="vanregen_smtp_notification_email" name="vanregen_smtp_settings[notification_email]" value="' . esc_attr($settings['notification_email']) . '"><p class="description">' . esc_html__('Contact and Quote inquiries are emailed here after they are saved in WordPress.', 'vanregen-content') . '</p></td></tr>';
    echo '</tbody></table>';
    submit_button();
    echo '</form>';
    echo '<hr><h2>' . esc_html__('Test email delivery', 'vanregen-content') . '</h2>';
    echo '<p>' . esc_html__('Send one test email to the inquiry notification recipient. This does not create an inquiry.', 'vanregen-content') . '</p>';
    echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
    echo '<input type="hidden" name="action" value="vanregen_test_smtp">';
    wp_nonce_field('vanregen_test_smtp', 'vanregen_test_smtp_nonce');
    echo '<p><button type="submit" class="button">' . esc_html__('Send test email', 'vanregen-content') . '</button></p>';
    echo '</form>';
    echo '</div>';
}

function vanregen_send_mail($recipient, $subject, $body, $reply_to = '') {
    $recipient = sanitize_email($recipient);
    if (!is_email($recipient)) {
        return array('sent' => false, 'error' => __('No valid notification recipient is configured.', 'vanregen-content'));
    }
    $configuration_error = vanregen_smtp_configuration_error();
    if ($configuration_error !== '') {
        return array('sent' => false, 'error' => $configuration_error);
    }

    $last_error = '';
    $capture_error = function ($error) use (&$last_error) {
        if (is_wp_error($error)) {
            $last_error = implode('; ', $error->get_error_messages());
        }
    };
    add_action('wp_mail_failed', $capture_error);
    $headers = array('Content-Type: text/plain; charset=UTF-8');
    if (is_email($reply_to)) {
        $headers[] = 'Reply-To: <' . $reply_to . '>';
    }
    $sent = wp_mail($recipient, $subject, $body, $headers);
    remove_action('wp_mail_failed', $capture_error);

    return array(
        'sent' => (bool) $sent,
        'error' => $sent ? '' : ($last_error !== '' ? sanitize_text_field($last_error) : __('WordPress could not send the email.', 'vanregen-content')),
    );
}

function vanregen_test_smtp() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('You do not have permission to test email delivery.', 'vanregen-content'));
    }
    check_admin_referer('vanregen_test_smtp', 'vanregen_test_smtp_nonce');

    $settings = vanregen_get_smtp_settings();
    $result = vanregen_send_mail(
        $settings['notification_email'],
        __('Van Regen SMTP test', 'vanregen-content'),
        sprintf(
            "This is a test of the Van Regen website inquiry email settings.\n\nRecipient: %s\nSite: %s",
            $settings['notification_email'],
            home_url('/')
        )
    );
    $redirect_url = add_query_arg(
        array('page' => 'vanregen-smtp', 'vanregen_smtp_test' => $result['sent'] ? 'sent' : 'failed'),
        admin_url('options-general.php')
    );
    if (!$result['sent']) {
        error_log('Van Regen SMTP test failed: ' . $result['error']);
    }
    wp_safe_redirect($redirect_url);
    exit;
}
add_action('admin_post_vanregen_test_smtp', 'vanregen_test_smtp');

function vanregen_show_smtp_test_notice() {
    $page = isset($_GET['page']) && is_string($_GET['page']) ? sanitize_key($_GET['page']) : '';
    $result = isset($_GET['vanregen_smtp_test']) && is_string($_GET['vanregen_smtp_test'])
        ? sanitize_key($_GET['vanregen_smtp_test'])
        : '';
    if ($page !== 'vanregen-smtp' || !in_array($result, array('sent', 'failed'), true)) {
        return;
    }

    $class = $result === 'sent' ? 'notice-success' : 'notice-error';
    $message = $result === 'sent'
        ? __('The test email was sent. Check the notification recipient inbox.', 'vanregen-content')
        : __('The test email failed. Confirm SMTP is enabled and the saved app password is valid, then check the PHP error log.', 'vanregen-content');
    echo '<div class="notice ' . esc_attr($class) . ' is-dismissible"><p>' . esc_html($message) . '</p></div>';
}
add_action('admin_notices', 'vanregen_show_smtp_test_notice');

function vanregen_configure_smtp($phpmailer) {
    $settings = vanregen_get_smtp_settings();
    if (!$settings['enabled'] || $settings['host'] === '' || $settings['username'] === '' || $settings['password'] === '') {
        return;
    }

    $phpmailer->isSMTP();
    $phpmailer->Host = $settings['host'];
    $phpmailer->Port = $settings['port'];
    $phpmailer->SMTPAuth = true;
    $phpmailer->Username = $settings['username'];
    $phpmailer->Password = preg_replace('/\s+/', '', (string) $settings['password']);
    $phpmailer->SMTPSecure = $settings['encryption'];
    $phpmailer->From = $settings['from_email'];
    $phpmailer->FromName = $settings['from_name'];
}
add_action('phpmailer_init', 'vanregen_configure_smtp');

function vanregen_smtp_from_email($email) {
    $settings = vanregen_get_smtp_settings();
    return $settings['enabled'] && is_email($settings['from_email']) ? $settings['from_email'] : $email;
}
add_filter('wp_mail_from', 'vanregen_smtp_from_email');

function vanregen_smtp_from_name($name) {
    $settings = vanregen_get_smtp_settings();
    return $settings['enabled'] && $settings['from_name'] !== '' ? $settings['from_name'] : $name;
}
add_filter('wp_mail_from_name', 'vanregen_smtp_from_name');

function vanregen_register_page_copy_meta() {
    $post_types = array('page', 'services', 'projects', 'post');
    foreach ($post_types as $post_type) {
        foreach (array(
            'vanregen_seo_title' => 'vanregen_sanitize_seo_title',
            'vanregen_seo_description' => 'vanregen_sanitize_seo_description',
            'vanregen_seo_canonical_url' => 'esc_url_raw',
            'vanregen_seo_social_title' => 'vanregen_sanitize_seo_title',
            'vanregen_seo_social_description' => 'vanregen_sanitize_seo_description',
            'vanregen_seo_social_image_url' => 'esc_url_raw',
            'vanregen_seo_social_image_alt' => 'sanitize_text_field',
            'vanregen_seo_robots' => 'vanregen_sanitize_seo_robots',
            'vanregen_seo_sitemap_exclude' => 'rest_sanitize_boolean',
        ) as $key => $sanitize_callback) {
            register_post_meta($post_type, $key, array(
                'type' => 'string',
                'single' => true,
                'show_in_rest' => true,
                'default' => '',
                'sanitize_callback' => $sanitize_callback,
                'auth_callback' => 'vanregen_can_edit_page_copy_meta',
            ));
        }
    }

    foreach ($post_types as $post_type) {
        foreach (array(
            'vanregen_page_hero_image_url' => 'esc_url_raw',
            'vanregen_page_hero_video_url' => 'esc_url_raw',
            'vanregen_page_hero_video_credit' => 'sanitize_text_field',
        ) as $key => $sanitize_callback) {
            register_post_meta($post_type, $key, array(
                'type' => 'string',
                'single' => true,
                'show_in_rest' => true,
                'default' => '',
                'sanitize_callback' => $sanitize_callback,
                'auth_callback' => 'vanregen_can_edit_page_copy_meta',
            ));
        }
    }

    register_post_meta('projects', 'vanregen_project_gallery', array(
        'type' => 'array',
        'single' => true,
        'show_in_rest' => array('schema' => array('type' => 'array', 'items' => array('type' => 'string'))),
        'default' => array(),
        'sanitize_callback' => 'vanregen_sanitize_project_gallery',
        'auth_callback' => 'vanregen_can_edit_page_copy_meta',
    ));

    register_post_meta('services', 'vanregen_service_products', array(
        'type' => 'array',
        'single' => true,
        'show_in_rest' => array('schema' => array('type' => 'array', 'items' => array('type' => 'string'))),
        'default' => array(),
        'sanitize_callback' => 'vanregen_sanitize_service_products',
        'auth_callback' => 'vanregen_can_edit_page_copy_meta',
    ));

    register_post_meta('page', 'vanregen_projects_gallery', array(
        'type' => 'array',
        'single' => true,
        'show_in_rest' => array('schema' => array('type' => 'array', 'items' => array('type' => 'string'))),
        'default' => array(),
        'sanitize_callback' => 'vanregen_sanitize_project_gallery',
        'auth_callback' => 'vanregen_can_edit_page_copy_meta',
    ));

    foreach (vanregen_page_copy_defaults() as $key => $default) {
        if ($key === 'vanregen_seo_title' || $key === 'vanregen_seo_description') {
            continue;
        }
        register_post_meta('page', $key, array(
            'type' => 'string',
            'single' => true,
            'show_in_rest' => true,
            'default' => $default,
            'sanitize_callback' => $key === 'vanregen_sustainability_processed_kg'
                ? 'vanregen_sanitize_processed_kg'
                : (strpos($key, '_percent') !== false
                    ? 'vanregen_sanitize_metal_recovery_percent'
                    : (strpos($key, '_multiplier') !== false
                        ? 'vanregen_sanitize_scenario_multiplier'
                        : (strpos($key, '_per_kg') !== false
                            ? 'vanregen_sanitize_co2e_factor'
                            : (strpos($key, '_hero_video_url') !== false ? 'esc_url_raw' : 'sanitize_textarea_field')))),
            'auth_callback' => 'vanregen_can_edit_page_copy_meta',
        ));
    }
}
add_action('init', 'vanregen_register_page_copy_meta', 5);

function vanregen_seed_page_copy_meta() {
    $copy_meta_version = '19';
    if (get_option('vanregen_page_copy_meta_version') === $copy_meta_version) {
        return;
    }

    $summary_defaults = array(
        'home' => 'A thoughtful partner for material recovery, equipment retirement and industrial change.',
        'about-us' => 'Company background, leadership, and operating information.',
        'services' => 'Explore Van Regen services for electronic waste, secure IT asset retirement, and EPR documentation. Each project is scoped to the materials and requirements confirmed with the team.',
        'e-waste-recycling' => 'Discuss end-of-life electronics, business equipment, data-handling needs, and relevant downstream processes.',
        'battery-recycling' => 'Discuss end-of-life battery materials, handling requirements, and project-specific logistics.',
        'industrial-demolition-dismantling' => 'Discuss equipment or facility decommissioning needs with the team.',
        'port-crane-dismantling' => 'Discuss port crane dismantling requirements with the team.',
        'sustainability' => 'Environmental outcomes depend on project-specific measurement and verification.',
        'projects' => 'Project stories are added after scope, client permissions, measures, and outcomes have been approved.',
        'contact' => 'Reach the Van Regen team for general questions, partnerships, and other inquiries.',
        'contact-quote' => 'Choose one or more services, products, and quantities, then share your contact details.',
    );

    $defaults = vanregen_page_copy_defaults();
    $quote_defaults = array(
        'vanregen_contact_heading' => 'Tell us which services and products you need.',
        'vanregen_contact_description' => 'Choose the services, products and quantities for your quote, then share your contact details.',
        'vanregen_page_summary' => $summary_defaults['contact-quote'],
    );
    $legacy_quote_copy = array(
        'vanregen_page_summary' => 'Contact Van Regen Private Limited in Patas, Pune about enterprise e-waste and ITAD services.',
        'vanregen_contact_heading' => "Let's talk about your e-waste and ITAD requirements.",
        'vanregen_contact_description' => 'Contact our team directly or send an inquiry. We will follow up about your enterprise recycling and IT asset needs.',
    );
    foreach (get_pages(array('post_status' => 'publish')) as $page) {
        $summary = $summary_defaults[$page->post_name] ?? '';
        foreach (vanregen_page_copy_fields($page->post_name) as $key => $label) {
            $value = $page->post_name === 'contact-quote' && isset($quote_defaults[$key])
                ? $quote_defaults[$key]
                : ($key === 'vanregen_page_summary' ? $summary : ($defaults[$key] ?? ''));
            $existing_value = trim((string) get_post_meta($page->ID, $key, true));
            $is_legacy_quote_value = $page->post_name === 'contact-quote'
                && isset($legacy_quote_copy[$key])
                && $existing_value === $legacy_quote_copy[$key];
            if ($value !== '' && ($existing_value === '' || $is_legacy_quote_value)) {
                update_post_meta($page->ID, $key, $value);
            }
        }
    }

    if (get_option('vanregen_contact_address_pdf_migration') !== '1') {
        $contact_page = get_page_by_path('contact-quote', OBJECT, 'page');
        if ($contact_page) {
            update_post_meta($contact_page->ID, 'vanregen_contact_address', $defaults['vanregen_contact_address']);
            update_option('vanregen_contact_address_pdf_migration', '1', false);
        }
    }

    update_option('vanregen_page_copy_meta_version', $copy_meta_version, false);
}
add_action('init', 'vanregen_seed_page_copy_meta', 20);

function vanregen_migrate_sustainability_calculator_copy() {
    if (get_option('vanregen_sustainability_calculator_copy_migration') === '3') {
        return;
    }

    $page = get_page_by_path('sustainability', OBJECT, 'page');
    if (!$page) {
        return;
    }

    $old_description = 'This estimate is not an emissions calculation or a promise of planting, recycling outcomes, or carbon credits.';
    $description = get_post_meta($page->ID, 'vanregen_sustainability_description', true);
    if ($description === $old_description) {
        update_post_meta($page->ID, 'vanregen_sustainability_description', vanregen_page_copy_defaults()['vanregen_sustainability_description']);
    }

    $old_factor_note = 'Illustrative assumptions only. Replace these factors with verified, project-specific data before making environmental claims.';
    if (get_post_meta($page->ID, 'vanregen_sustainability_factor_note', true) === $old_factor_note) {
        update_post_meta($page->ID, 'vanregen_sustainability_factor_note', vanregen_page_copy_defaults()['vanregen_sustainability_factor_note']);
    }

    update_option('vanregen_sustainability_calculator_copy_migration', '3', false);
}
add_action('init', 'vanregen_migrate_sustainability_calculator_copy', 22);

function vanregen_migrate_hero_media_meta() {
    if (get_option('vanregen_hero_media_migration') === '1') {
        return;
    }

    $legacy_fields = array(
        'vanregen_home_hero_video_url' => 'vanregen_page_hero_video_url',
        'vanregen_home_hero_video_credit' => 'vanregen_page_hero_video_credit',
        'vanregen_services_hero_video_url' => 'vanregen_page_hero_video_url',
        'vanregen_services_hero_video_credit' => 'vanregen_page_hero_video_credit',
        'vanregen_service_hero_video_url' => 'vanregen_page_hero_video_url',
        'vanregen_service_hero_video_credit' => 'vanregen_page_hero_video_credit',
    );
    $records = array();
    $front_page_id = (int) get_option('page_on_front');
    if ($front_page_id) {
        $records[] = $front_page_id;
    }
    $services_page = get_page_by_path('services', OBJECT, 'page');
    if ($services_page) {
        $records[] = $services_page->ID;
    }
    foreach (get_posts(array('post_type' => array('services', 'projects'), 'post_status' => 'any', 'numberposts' => -1, 'fields' => 'ids')) as $record_id) {
        $records[] = $record_id;
    }

    foreach (array_unique($records) as $record_id) {
        foreach ($legacy_fields as $legacy_key => $new_key) {
            $legacy_value = get_post_meta($record_id, $legacy_key, true);
            if ($legacy_value !== '') {
                if (get_post_meta($record_id, $new_key, true) === '') {
                    update_post_meta($record_id, $new_key, $legacy_value);
                }
                delete_post_meta($record_id, $legacy_key);
            }
        }
    }

    update_option('vanregen_hero_media_migration', '1', false);
}
add_action('init', 'vanregen_migrate_hero_media_meta', 21);

function vanregen_add_page_copy_meta_box($post) {
    add_meta_box(
        'vanregen-page-copy',
        $post->post_name === 'contact-quote'
            ? __('Quote Page Content', 'vanregen-content')
            : __('Van Regen Frontend Copy', 'vanregen-content'),
        'vanregen_render_page_copy_meta_box',
        'page',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes_page', 'vanregen_add_page_copy_meta_box');

function vanregen_render_page_copy_meta_box($post) {
    wp_nonce_field('vanregen_save_page_copy', 'vanregen_page_copy_nonce');
    if ($post->post_name === 'contact-quote') {
        echo '<p class="description">' . esc_html__('The page title, page content, and hero summary appear on the public quote page. The form heading, description, contact details, and privacy note below map directly to the form. Service and product choices are managed on each Service under Quote Product Options. Form fields and submission behavior are provided by the theme; submissions appear under Website Inquiries.', 'vanregen-content') . '</p>';
        echo '<p><a href="' . esc_url(get_permalink($post)) . '" target="_blank" rel="noopener noreferrer">' . esc_html__('Preview the public quote page', 'vanregen-content') . '</a></p>';
    }
    foreach (vanregen_page_copy_fields($post->post_name) as $key => $label) {
        $value = get_post_meta($post->ID, $key, true);
        $is_video_url = strpos($key, '_hero_video_url') !== false;
        $is_long_copy = strpos($key, '_body') !== false || strpos($key, '_description') !== false || strpos($key, '_notice') !== false || strpos($key, '_heading') !== false || strpos($key, '_address') !== false || strpos($key, '_emails') !== false || strpos($key, '_phones') !== false;
        $is_numeric_field = $key === 'vanregen_sustainability_metal_recovery_percent' || $key === 'vanregen_sustainability_co2e_avoided_per_kg';
        $is_numeric_field = $key === 'vanregen_sustainability_processed_kg' || strpos($key, '_percent') !== false || strpos($key, '_per_kg') !== false || strpos($key, '_multiplier') !== false;
        $is_long_copy = strpos($key, '_body') !== false || strpos($key, '_description') !== false || strpos($key, '_notice') !== false || strpos($key, '_heading') !== false || strpos($key, '_address') !== false || strpos($key, '_emails') !== false || strpos($key, '_phones') !== false || strpos($key, '_factor_note') !== false;
        echo '<p><label for="' . esc_attr($key) . '"><strong>' . esc_html($label) . '</strong></label><br>';
        if ($is_numeric_field) {
            $max = $key === 'vanregen_sustainability_processed_kg' ? '1000000000' : (strpos($key, '_percent') !== false ? '100' : (strpos($key, '_multiplier') !== false ? '5' : '1000'));
            $step = $key === 'vanregen_sustainability_processed_kg' ? '1' : (strpos($key, '_multiplier') !== false ? '0.05' : (strpos($key, '_percent') !== false ? '0.1' : '0.001'));
            echo '<input class="widefat" type="number" min="0" max="' . esc_attr($max) . '" step="' . esc_attr($step) . '" id="' . esc_attr($key) . '" name="vanregen_page_copy[' . esc_attr($key) . ']" value="' . esc_attr($value) . '">';
        } elseif ($is_video_url) {
            echo '<input class="widefat" type="url" id="' . esc_attr($key) . '" name="vanregen_page_copy[' . esc_attr($key) . ']" value="' . esc_attr($value) . '">';
        } elseif ($is_long_copy) {
            echo '<textarea class="widefat" rows="3" id="' . esc_attr($key) . '" name="vanregen_page_copy[' . esc_attr($key) . ']">' . esc_textarea($value) . '</textarea>';
        } else {
            echo '<input class="widefat" type="text" id="' . esc_attr($key) . '" name="vanregen_page_copy[' . esc_attr($key) . ']" value="' . esc_attr($value) . '">';
        }
        echo '</p>';
    }
}

function vanregen_save_page_copy_meta($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (!isset($_POST['vanregen_page_copy_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['vanregen_page_copy_nonce'])), 'vanregen_save_page_copy')) {
        return;
    }
    if (!current_user_can('edit_post', $post_id) || get_post_type($post_id) !== 'page') {
        return;
    }

    $submitted = isset($_POST['vanregen_page_copy']) && is_array($_POST['vanregen_page_copy'])
        ? wp_unslash($_POST['vanregen_page_copy'])
        : array();
    foreach (vanregen_page_copy_fields(get_post_field('post_name', $post_id)) as $key => $label) {
        if (array_key_exists($key, $submitted)) {
            if ($key === 'vanregen_sustainability_processed_kg') {
                $value = vanregen_sanitize_processed_kg($submitted[$key]);
            } elseif (strpos($key, '_percent') !== false) {
                $value = vanregen_sanitize_metal_recovery_percent($submitted[$key]);
            } elseif (strpos($key, '_multiplier') !== false) {
                $value = vanregen_sanitize_scenario_multiplier($submitted[$key]);
            } elseif (strpos($key, '_per_kg') !== false) {
                $value = vanregen_sanitize_co2e_factor($submitted[$key]);
            } else {
                $value = strpos($key, '_hero_video_url') !== false
                    ? esc_url_raw($submitted[$key])
                    : sanitize_textarea_field($submitted[$key]);
            }
            update_post_meta($post_id, $key, $value);
        }
    }
}
add_action('save_post_page', 'vanregen_save_page_copy_meta');

function vanregen_add_service_products_meta_box() {
    add_meta_box(
        'vanregen-service-products',
        __('Quote Product Options', 'vanregen-content'),
        'vanregen_render_service_products_meta_box',
        'services',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes_services', 'vanregen_add_service_products_meta_box');

function vanregen_render_service_products_meta_box($post) {
    $products = get_post_meta($post->ID, 'vanregen_service_products', true);
    if (!is_array($products)) {
        $products = array();
    }
    if (!$products) {
        $products = array('');
    }

    wp_nonce_field('vanregen_save_service_products', 'vanregen_service_products_nonce');
    echo '<p>' . esc_html__('Add the products or assets customers can select for this service. The order here is the order shown in the quote form.', 'vanregen-content') . '</p>';
    echo '<div id="vanregen-service-products-list">';
    foreach ($products as $index => $product) {
        $input_id = 'vanregen_service_product_' . $post->ID . '_' . $index;
        echo '<div class="vanregen-service-product-row" style="margin-bottom:8px">';
        echo '<label class="screen-reader-text" for="' . esc_attr($input_id) . '">' . esc_html(sprintf(__('Product or asset option %d', 'vanregen-content'), $index + 1)) . '</label>';
        echo '<input class="regular-text" type="text" id="' . esc_attr($input_id) . '" name="vanregen_service_products[]" value="' . esc_attr($product) . '" placeholder="' . esc_attr__('Enter a product or asset', 'vanregen-content') . '"> ';
        echo '<button type="button" class="button-link-delete vanregen-remove-service-product">' . esc_html__('Remove', 'vanregen-content') . '</button>';
        echo '</div>';
    }
    echo '</div>';
    echo '<input type="hidden" name="vanregen_service_products[]" value="">';
    echo '<p><button type="button" class="button" id="vanregen-add-service-product">' . esc_html__('Add product or asset', 'vanregen-content') . '</button></p>';
}

function vanregen_enqueue_service_products_editor_script($hook_suffix) {
    if (!in_array($hook_suffix, array('post.php', 'post-new.php'), true)) {
        return;
    }
    $screen = get_current_screen();
    if (!$screen || $screen->post_type !== 'services') {
        return;
    }

    wp_enqueue_script('jquery');
    $labels = wp_json_encode(array(
        'option' => __('Product or asset option', 'vanregen-content'),
        'placeholder' => __('Enter a product or asset', 'vanregen-content'),
        'remove' => __('Remove', 'vanregen-content'),
    ));
    $script = 'jQuery(function($){'
        . 'var list=$("#vanregen-service-products-list");'
        . 'if(!list.length){return;}'
        . 'var labels=' . $labels . ';'
        . 'var nextIndex=list.children().length;'
        . '$("#vanregen-add-service-product").on("click",function(){'
        . 'var index=nextIndex++;'
        . 'var id="vanregen_service_product_new_"+index;'
        . 'var row=$("<div>",{class:"vanregen-service-product-row",style:"margin-bottom:8px"});'
        . 'row.append($("<label>",{class:"screen-reader-text",for:id}).text(labels.option+" "+(index+1)));'
        . 'row.append($("<input>",{class:"regular-text",type:"text",id:id,name:"vanregen_service_products[]",placeholder:labels.placeholder}));'
        . 'row.append(" ").append($("<button>",{type:"button",class:"button-link-delete vanregen-remove-service-product"}).text(labels.remove));'
        . 'list.append(row);'
        . '});'
        . 'list.on("click",".vanregen-remove-service-product",function(){$(this).closest(".vanregen-service-product-row").remove();});'
        . '});';
    wp_add_inline_script('jquery', $script, 'after');
}
add_action('admin_enqueue_scripts', 'vanregen_enqueue_service_products_editor_script');

function vanregen_save_service_products_meta($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (!isset($_POST['vanregen_service_products_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['vanregen_service_products_nonce'])), 'vanregen_save_service_products')) {
        return;
    }
    if (!current_user_can('edit_post', $post_id) || get_post_type($post_id) !== 'services') {
        return;
    }
    if (!isset($_POST['vanregen_service_products'])) {
        return;
    }

    $products = vanregen_sanitize_service_products(wp_unslash($_POST['vanregen_service_products']));
    update_post_meta($post_id, 'vanregen_service_products', $products);
}
add_action('save_post_services', 'vanregen_save_service_products_meta');

function vanregen_add_hero_media_meta_box() {
    foreach (array('page', 'services', 'projects', 'post') as $post_type) {
        add_meta_box('vanregen-hero-media', __('Hero Media', 'vanregen-content'), 'vanregen_render_hero_media_meta_box', $post_type, 'normal', 'high');
    }
}
add_action('add_meta_boxes', 'vanregen_add_hero_media_meta_box');

function vanregen_enqueue_hero_media_picker($hook_suffix) {
    if (!in_array($hook_suffix, array('post.php', 'post-new.php'), true)) {
        return;
    }

    $screen = get_current_screen();
    $is_seo_settings_page = $screen && $screen->id === 'settings_page_vanregen-seo';
    if (!$screen || (!$is_seo_settings_page && !in_array($screen->post_type, array('page', 'services', 'projects', 'post'), true))) {
        return;
    }

    wp_enqueue_media();
    wp_add_inline_script('media-editor', <<<'JS'
jQuery(function ($) {
    $(document).on('click', '.vanregen-select-media', function (event) {
        event.preventDefault();
        const button = $(this);
        const frame = wp.media({
            title: button.data('title'),
            library: { type: button.data('type') },
            button: { text: button.data('button') },
            multiple: false,
        });
        frame.on('select', function () {
            const attachment = frame.state().get('selection').first().toJSON();
            $('#' + button.data('target')).val(attachment.url).trigger('change');
        });
        frame.open();
    });
    $(document).on('click', '.vanregen-clear-media', function (event) {
        event.preventDefault();
        $('#' + $(this).data('target')).val('').trigger('change');
    });
    $(document).on('click', '.vanregen-select-project-gallery', function (event) {
        event.preventDefault();
        const button = $(this);
        const input = $('#' + button.data('target'));
        const preview = $('#' + button.data('preview'));
        const frame = wp.media({
            title: button.data('title'),
            library: { type: 'image' },
            button: { text: button.data('button') },
            multiple: true,
        });
        frame.on('select', function () {
            let existing = [];
            try { existing = JSON.parse(input.val() || '[]'); } catch {}
            const selected = frame.state().get('selection').toJSON().map(function (attachment) { return attachment.url; });
            const photos = Array.from(new Set(existing.concat(selected)));
            input.val(JSON.stringify(photos));
            preview.empty();
            photos.forEach(function (url, index) {
                $('<img>').attr({ src: url, alt: 'Project photo ' + (index + 1) }).appendTo(preview);
            });
        });
        frame.open();
    });
    $(document).on('click', '.vanregen-clear-project-gallery', function (event) {
        event.preventDefault();
        $('#' + $(this).data('target')).val('[]');
        $('#' + $(this).data('preview')).empty();
    });
});
JS
    );
}
add_action('admin_enqueue_scripts', 'vanregen_enqueue_hero_media_picker');

function vanregen_render_hero_media_meta_box($post) {
    wp_nonce_field('vanregen_save_hero_media', 'vanregen_hero_media_nonce');
    $image_url = get_post_meta($post->ID, 'vanregen_page_hero_image_url', true);
    $video_url = get_post_meta($post->ID, 'vanregen_page_hero_video_url', true);
    $video_credit = get_post_meta($post->ID, 'vanregen_page_hero_video_credit', true);
    echo '<p class="description">' . esc_html__('Choose media for this item only. Select an existing file or upload a new one from the Media Library.', 'vanregen-content') . '</p>';
    echo '<p><label for="vanregen_page_hero_image_url"><strong>' . esc_html__('Hero image', 'vanregen-content') . '</strong></label><br><input class="widefat" type="url" id="vanregen_page_hero_image_url" name="vanregen_page_hero_image_url" value="' . esc_attr($image_url) . '"> <button type="button" class="button vanregen-select-media" data-target="vanregen_page_hero_image_url" data-type="image" data-title="' . esc_attr__('Choose hero image', 'vanregen-content') . '" data-button="' . esc_attr__('Use this image', 'vanregen-content') . '">' . esc_html__('Choose image', 'vanregen-content') . '</button> <button type="button" class="button vanregen-clear-media" data-target="vanregen_page_hero_image_url">' . esc_html__('Clear', 'vanregen-content') . '</button></p>';
    echo '<p><label for="vanregen_page_hero_video_url"><strong>' . esc_html__('Hero video (MP4)', 'vanregen-content') . '</strong></label><br><input class="widefat" type="url" id="vanregen_page_hero_video_url" name="vanregen_page_hero_video_url" value="' . esc_attr($video_url) . '"> <button type="button" class="button vanregen-select-media" data-target="vanregen_page_hero_video_url" data-type="video" data-title="' . esc_attr__('Choose hero video', 'vanregen-content') . '" data-button="' . esc_attr__('Use this video', 'vanregen-content') . '">' . esc_html__('Choose video', 'vanregen-content') . '</button> <button type="button" class="button vanregen-clear-media" data-target="vanregen_page_hero_video_url">' . esc_html__('Clear', 'vanregen-content') . '</button></p>';
    echo '<p><label for="vanregen_page_hero_video_credit"><strong>' . esc_html__('Video credit (optional)', 'vanregen-content') . '</strong></label><br><input class="widefat" type="text" id="vanregen_page_hero_video_credit" name="vanregen_page_hero_video_credit" value="' . esc_attr($video_credit) . '"></p>';
}

function vanregen_save_hero_media_meta($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!isset($_POST['vanregen_hero_media_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['vanregen_hero_media_nonce'])), 'vanregen_save_hero_media')) return;
    if (!current_user_can('edit_post', $post_id) || !in_array(get_post_type($post_id), array('page', 'services', 'projects', 'post'), true)) return;

    foreach (array(
        'vanregen_page_hero_image_url' => 'esc_url_raw',
        'vanregen_page_hero_video_url' => 'esc_url_raw',
        'vanregen_page_hero_video_credit' => 'sanitize_text_field',
    ) as $key => $sanitize_callback) {
        if (!isset($_POST[$key]) || !is_scalar($_POST[$key])) continue;
        update_post_meta($post_id, $key, $sanitize_callback(wp_unslash($_POST[$key])));
    }
}
add_action('save_post', 'vanregen_save_hero_media_meta');

function vanregen_add_project_gallery_meta_box() {
    add_meta_box('vanregen-project-gallery', __('Project Photos', 'vanregen-content'), 'vanregen_render_project_gallery_meta_box', 'projects', 'normal', 'high');
}
add_action('add_meta_boxes_projects', 'vanregen_add_project_gallery_meta_box');

function vanregen_render_project_gallery_meta_box($post) {
    wp_nonce_field('vanregen_save_project_gallery', 'vanregen_project_gallery_nonce');
    $photos = get_post_meta($post->ID, 'vanregen_project_gallery', true);
    $photos = is_array($photos) ? $photos : array();
    $field_id = 'vanregen_project_gallery';
    echo '<p class="description">' . esc_html__('Add project-specific photos. The Featured Image is used as the project card cover; these photos appear in the project gallery.', 'vanregen-content') . '</p>';
    echo '<input type="hidden" id="' . esc_attr($field_id) . '" name="' . esc_attr($field_id) . '" value="' . esc_attr(wp_json_encode($photos)) . '">';
    echo '<p><button type="button" class="button button-primary vanregen-select-project-gallery" data-target="' . esc_attr($field_id) . '" data-preview="vanregen-project-gallery-preview" data-title="' . esc_attr__('Choose project photos', 'vanregen-content') . '" data-button="' . esc_attr__('Add selected photos', 'vanregen-content') . '">' . esc_html__('Choose or upload photos', 'vanregen-content') . '</button> <button type="button" class="button vanregen-clear-project-gallery" data-target="' . esc_attr($field_id) . '" data-preview="vanregen-project-gallery-preview">' . esc_html__('Clear gallery', 'vanregen-content') . '</button></p>';
    echo '<div id="vanregen-project-gallery-preview" class="vanregen-project-gallery-preview">';
    foreach ($photos as $index => $url) {
        echo '<img src="' . esc_url($url) . '" alt="' . esc_attr(sprintf(__('Project photo %d', 'vanregen-content'), $index + 1)) . '">';
    }
    echo '</div>';
}

function vanregen_save_project_gallery_meta($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!isset($_POST['vanregen_project_gallery_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['vanregen_project_gallery_nonce'])), 'vanregen_save_project_gallery')) return;
    if (!current_user_can('edit_post', $post_id) || get_post_type($post_id) !== 'projects') return;
    $submitted = isset($_POST['vanregen_project_gallery']) && is_scalar($_POST['vanregen_project_gallery'])
        ? wp_unslash($_POST['vanregen_project_gallery'])
        : '[]';
    update_post_meta($post_id, 'vanregen_project_gallery', vanregen_sanitize_project_gallery($submitted));
}
add_action('save_post_projects', 'vanregen_save_project_gallery_meta');

function vanregen_add_projects_page_gallery_box($post) {
    if ($post->post_name !== 'projects') {
        return;
    }
    add_meta_box('vanregen-projects-page-gallery', __('Projects Page Gallery', 'vanregen-content'), 'vanregen_render_projects_page_gallery_box', 'page', 'normal', 'high');
}
add_action('add_meta_boxes_page', 'vanregen_add_projects_page_gallery_box');

function vanregen_render_projects_page_gallery_box($post) {
    wp_nonce_field('vanregen_save_projects_page_gallery', 'vanregen_projects_page_gallery_nonce');
    $photos = get_post_meta($post->ID, 'vanregen_projects_gallery', true);
    $photos = is_array($photos) ? $photos : array();
    echo '<p class="description">' . esc_html__('Photos from every Project record are shown automatically on the Projects page. Use this optional gallery to add additional page-level photos.', 'vanregen-content') . '</p>';
    echo '<input type="hidden" id="vanregen_projects_gallery" name="vanregen_projects_gallery" value="' . esc_attr(wp_json_encode($photos)) . '">';
    echo '<p><button type="button" class="button button-primary vanregen-select-project-gallery" data-target="vanregen_projects_gallery" data-preview="vanregen-projects-page-gallery-preview" data-title="' . esc_attr__('Choose Projects page gallery photos', 'vanregen-content') . '" data-button="' . esc_attr__('Add selected photos', 'vanregen-content') . '">' . esc_html__('Choose or upload gallery photos', 'vanregen-content') . '</button> <button type="button" class="button vanregen-clear-project-gallery" data-target="vanregen_projects_gallery" data-preview="vanregen-projects-page-gallery-preview">' . esc_html__('Clear gallery', 'vanregen-content') . '</button></p>';
    echo '<div id="vanregen-projects-page-gallery-preview" class="vanregen-project-gallery-preview">';
    foreach ($photos as $index => $url) {
        echo '<img src="' . esc_url($url) . '" alt="' . esc_attr(sprintf(__('Gallery photo %d', 'vanregen-content'), $index + 1)) . '">';
    }
    echo '</div>';
}

function vanregen_save_projects_page_gallery($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!isset($_POST['vanregen_projects_page_gallery_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['vanregen_projects_page_gallery_nonce'])), 'vanregen_save_projects_page_gallery')) return;
    if (!current_user_can('edit_post', $post_id) || get_post_type($post_id) !== 'page' || get_post_field('post_name', $post_id) !== 'projects') return;
    $submitted = isset($_POST['vanregen_projects_gallery']) && is_scalar($_POST['vanregen_projects_gallery'])
        ? wp_unslash($_POST['vanregen_projects_gallery'])
        : '[]';
    update_post_meta($post_id, 'vanregen_projects_gallery', vanregen_sanitize_project_gallery($submitted));
}
add_action('save_post_page', 'vanregen_save_projects_page_gallery');

function vanregen_add_seo_meta_box() {
    foreach (array('page', 'services', 'projects', 'post') as $post_type) {
        add_meta_box('vanregen-seo', __('SEO Settings', 'vanregen-content'), 'vanregen_render_seo_meta_box', $post_type, 'normal', 'high');
    }
}
add_action('add_meta_boxes', 'vanregen_add_seo_meta_box');

function vanregen_render_seo_meta_box($post) {
    wp_nonce_field('vanregen_save_seo', 'vanregen_seo_nonce');
    echo '<p class="description">' . esc_html__('Leave fields blank to use the site defaults or generated content. Canonical URLs default to this item’s permalink.', 'vanregen-content') . '</p>';
    echo '<p><label for="vanregen_seo_title"><strong>' . esc_html__('SEO title (70 characters max)', 'vanregen-content') . '</strong></label><br><input class="widefat" type="text" maxlength="70" id="vanregen_seo_title" name="vanregen_seo_title" value="' . esc_attr(get_post_meta($post->ID, 'vanregen_seo_title', true)) . '"></p>';
    echo '<p><label for="vanregen_seo_description"><strong>' . esc_html__('Meta description (160 characters recommended)', 'vanregen-content') . '</strong></label><br><textarea class="widefat" rows="3" maxlength="160" id="vanregen_seo_description" name="vanregen_seo_description">' . esc_textarea(get_post_meta($post->ID, 'vanregen_seo_description', true)) . '</textarea></p>';
    echo '<p><label for="vanregen_seo_canonical_url"><strong>' . esc_html__('Canonical URL (optional)', 'vanregen-content') . '</strong></label><br><input class="widefat" type="url" id="vanregen_seo_canonical_url" name="vanregen_seo_canonical_url" value="' . esc_attr(get_post_meta($post->ID, 'vanregen_seo_canonical_url', true)) . '"></p>';
    echo '<hr><h4>' . esc_html__('Social preview', 'vanregen-content') . '</h4>';
    echo '<p><label for="vanregen_seo_social_title"><strong>' . esc_html__('Social title', 'vanregen-content') . '</strong></label><br><input class="widefat" type="text" maxlength="70" id="vanregen_seo_social_title" name="vanregen_seo_social_title" value="' . esc_attr(get_post_meta($post->ID, 'vanregen_seo_social_title', true)) . '"></p>';
    echo '<p><label for="vanregen_seo_social_description"><strong>' . esc_html__('Social description', 'vanregen-content') . '</strong></label><br><textarea class="widefat" rows="3" maxlength="180" id="vanregen_seo_social_description" name="vanregen_seo_social_description">' . esc_textarea(get_post_meta($post->ID, 'vanregen_seo_social_description', true)) . '</textarea></p>';
    echo '<p><label for="vanregen_seo_social_image_url"><strong>' . esc_html__('Social image', 'vanregen-content') . '</strong></label><br><input class="widefat" type="url" id="vanregen_seo_social_image_url" name="vanregen_seo_social_image_url" value="' . esc_attr(get_post_meta($post->ID, 'vanregen_seo_social_image_url', true)) . '"> <button type="button" class="button vanregen-select-media" data-target="vanregen_seo_social_image_url" data-type="image" data-title="' . esc_attr__('Choose social image', 'vanregen-content') . '" data-button="' . esc_attr__('Use this image', 'vanregen-content') . '">' . esc_html__('Choose image', 'vanregen-content') . '</button> <button type="button" class="button vanregen-clear-media" data-target="vanregen_seo_social_image_url">' . esc_html__('Clear', 'vanregen-content') . '</button></p>';
    echo '<p><label for="vanregen_seo_social_image_alt"><strong>' . esc_html__('Social image alt text', 'vanregen-content') . '</strong></label><br><input class="widefat" type="text" id="vanregen_seo_social_image_alt" name="vanregen_seo_social_image_alt" value="' . esc_attr(get_post_meta($post->ID, 'vanregen_seo_social_image_alt', true)) . '"></p>';
    $robots = get_post_meta($post->ID, 'vanregen_seo_robots', true);
    echo '<p><label for="vanregen_seo_robots"><strong>' . esc_html__('Search indexing', 'vanregen-content') . '</strong></label><br><select id="vanregen_seo_robots" name="vanregen_seo_robots">';
    foreach (array('' => __('Use site default', 'vanregen-content'), 'index,follow' => __('Index, follow', 'vanregen-content'), 'noindex,follow' => __('Noindex, follow', 'vanregen-content'), 'noindex,nofollow' => __('Noindex, nofollow', 'vanregen-content')) as $value => $label) {
        echo '<option value="' . esc_attr($value) . '" ' . selected($robots, $value, false) . '>' . esc_html($label) . '</option>';
    }
    echo '</select></p>';
    $sitemap_excluded = (bool) get_post_meta($post->ID, 'vanregen_seo_sitemap_exclude', true);
    echo '<p><label for="vanregen_seo_sitemap_exclude"><input type="checkbox" id="vanregen_seo_sitemap_exclude" name="vanregen_seo_sitemap_exclude" value="1" ' . checked($sitemap_excluded, true, false) . '> <strong>' . esc_html__('Exclude this item from the XML sitemap', 'vanregen-content') . '</strong></label></p>';
}

function vanregen_save_seo_meta($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!isset($_POST['vanregen_seo_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['vanregen_seo_nonce'])), 'vanregen_save_seo')) return;
    if (!current_user_can('edit_post', $post_id) || !in_array(get_post_type($post_id), array('page', 'services', 'projects', 'post'), true)) return;

    foreach (array(
        'vanregen_seo_title' => 'vanregen_sanitize_seo_title',
        'vanregen_seo_description' => 'vanregen_sanitize_seo_description',
        'vanregen_seo_canonical_url' => 'esc_url_raw',
        'vanregen_seo_social_title' => 'vanregen_sanitize_seo_title',
        'vanregen_seo_social_description' => 'vanregen_sanitize_seo_description',
        'vanregen_seo_social_image_url' => 'esc_url_raw',
        'vanregen_seo_social_image_alt' => 'sanitize_text_field',
        'vanregen_seo_robots' => 'vanregen_sanitize_seo_robots',
    ) as $key => $sanitize_callback) {
        if (!isset($_POST[$key]) || !is_scalar($_POST[$key])) continue;
        update_post_meta($post_id, $key, $sanitize_callback(wp_unslash($_POST[$key])));
    }
    update_post_meta($post_id, 'vanregen_seo_sitemap_exclude', !empty($_POST['vanregen_seo_sitemap_exclude']) ? '1' : '0');
}
add_action('save_post', 'vanregen_save_seo_meta');

function vanregen_seed_seo_metadata() {
    $version = '2026-11-page-metadata-5';
    $legacy_quote_seo = array(
        'title' => 'Contact Van Regen | Request an E-Waste Quote',
        'description' => 'Contact Van Regen to discuss e-waste collection, IT asset disposition, secure data destruction, EPR support, recycling, and resource recovery.',
    );
    if (get_option('vanregen_page_seo_metadata_version') === $version) {
        return;
    }

    $metadata = array(
        'page' => array(
            'home' => array(
                'title' => 'Van Regen | Responsible E-Waste Recycling & Resource Recovery',
                'description' => 'Van Regen supports responsible e-waste recycling, secure data destruction, IT asset disposition, material recovery, and circular resource management.',
            ),
            'about-us' => array(
                'title' => 'About Van Regen | Responsible Resource Recovery',
                'description' => 'Learn about Van Regen and our responsible approach to electronic waste, secure asset retirement, material recovery, and sustainable industrial services.',
            ),
            'services' => array(
                'title' => 'E-Waste, ITAD & EPR Services | Van Regen',
                'description' => 'Explore Van Regen services including e-waste recycling, ITAD, secure data destruction, EPR documentation, and responsible material recovery.',
            ),
            'sustainability' => array(
                'title' => 'Sustainability & E-Waste Impact | Van Regen',
                'description' => 'Explore resource recovery, environmental impact, and illustrative sustainability estimates for responsibly managed electronic waste.',
            ),
            'projects' => array(
                'title' => 'E-Waste Projects & Recycling Case Studies | Van Regen',
                'description' => 'View Van Regen projects, responsible recycling work, material recovery stories, and project galleries for managed electronic waste.',
            ),
            'contact' => array(
                'title' => 'Contact Van Regen | General Inquiries',
                'description' => 'Contact Van Regen for general questions, partnerships, or other inquiries about resource recovery and industrial services.',
            ),
            'contact-quote' => array(
                'title' => 'Request a Quote | Van Regen Services',
                'description' => 'Request a quote for e-waste recycling, IT asset disposition, secure data destruction, EPR documentation, and resource recovery.',
            ),
        ),
        'services' => array(
            'e-waste-recycling' => array(
                'title' => 'E-Waste Recycling Services | Van Regen',
                'description' => 'Responsible collection, segregation, dismantling, and recycling for computers, servers, networking equipment, devices, and other electronic waste.',
            ),
            'data-destruction' => array(
                'title' => 'Secure Data Destruction Services | Van Regen',
                'description' => 'Secure data sanitization and physical destruction for laptops, desktops, servers, hard drives, SSDs, and storage media with documented handling.',
            ),
            'itad-asset-buyback' => array(
                'title' => 'ITAD & IT Asset Buyback Services | Van Regen',
                'description' => 'Professional IT asset disposition and buyback services with asset reconciliation, secure data destruction, refurbishment, and responsible recycling.',
            ),
            'epr-documentation' => array(
                'title' => 'EPR Documentation for E-Waste Compliance | Van Regen',
                'description' => 'EPR documentation and compliance support for responsible e-waste management, including recycling records, reporting, and regulatory guidance.',
            ),
        ),
        'projects' => array(
            'e-waste-management' => array(
                'title' => 'E-Waste Management: A Sustainable Future | Van Regen',
                'description' => 'Learn how responsible e-waste collection, secure data destruction, dismantling, recycling, documentation, and material recovery build a circular economy.',
            ),
        ),
    );

    foreach ($metadata as $post_type => $slugs) {
        foreach ($slugs as $slug => $values) {
            $post = get_page_by_path($slug, OBJECT, $post_type);
            if (!$post) {
                continue;
            }
            $seo_title = trim((string) get_post_meta($post->ID, 'vanregen_seo_title', true));
            if ($seo_title === '' || ($slug === 'contact-quote' && $seo_title === $legacy_quote_seo['title'])) {
                update_post_meta($post->ID, 'vanregen_seo_title', vanregen_sanitize_seo_title($values['title']));
            }
            $seo_description = trim((string) get_post_meta($post->ID, 'vanregen_seo_description', true));
            if ($seo_description === '' || ($slug === 'contact-quote' && $seo_description === $legacy_quote_seo['description'])) {
                update_post_meta($post->ID, 'vanregen_seo_description', vanregen_sanitize_seo_description($values['description']));
            }
        }
    }

    foreach (get_posts(array(
        'post_type' => array('page', 'services', 'projects', 'post'),
        'post_status' => 'publish',
        'numberposts' => -1,
        'fields' => 'ids',
    )) as $post_id) {
        $title = get_post_meta($post_id, 'vanregen_seo_title', true);
        $description = get_post_meta($post_id, 'vanregen_seo_description', true);
        $sanitized_title = vanregen_sanitize_seo_title($title);
        $sanitized_description = vanregen_sanitize_seo_description($description);
        if ($title !== $sanitized_title) {
            update_post_meta($post_id, 'vanregen_seo_title', $sanitized_title);
        }
        if ($description !== $sanitized_description) {
            update_post_meta($post_id, 'vanregen_seo_description', $sanitized_description);
        }
    }

    update_option('vanregen_page_seo_metadata_version', $version, false);
}
add_action('init', 'vanregen_seed_seo_metadata', 30);

function vanregen_is_seed_placeholder($content) {
    $markers = array(
        'Draft content: Confirm',
        'Draft for owner review:',
        'Service summaries are a starting point only.',
        'Draft service page:',
        'Every engagement follows an accountable workflow from enterprise intake through documented closure.',
        'Additional services described',
        'Explore Van Regen services for electronic waste, secure IT asset retirement, and EPR documentation.',
        'Impact estimates on this page are illustrative only.',
        'Add only approved case studies',
        '<strong>Draft copy:</strong>',
    );

    foreach ($markers as $marker) {
        if (strpos($content, $marker) !== false) {
            return true;
        }
    }
    return false;
}

function vanregen_projects_page_content() {
    return '<h2>Transforming Electronic Waste into a Sustainable Future</h2>
<p>Technology is evolving rapidly, and electronic devices have become an essential part of our everyday lives. From computers and laptops to smartphones, servers and industrial equipment, electronic products play a major role in modern businesses and households.</p>
<p>However, as technology advances, outdated and unwanted electronic equipment creates a growing environmental challenge: <strong>Electronic Waste (E-Waste).</strong></p>
<p>Responsible e-waste management is no longer just an environmental responsibility; it is an essential step towards building a cleaner, safer and more sustainable future.</p>
<p>Our mission is to transform electronic waste into valuable resources through responsible collection, secure data destruction, systematic dismantling and environmentally responsible recycling.</p>
<h2>What Is E-Waste Management?</h2>
<p>E-waste management refers to the systematic collection, segregation, transportation, dismantling, recycling and safe disposal of discarded electronic and electrical equipment.</p>
<p>Common categories of e-waste include:</p>
<ul>
<li>Computers, laptops and desktops</li>
<li>Servers, networking equipment and IT hardware</li>
<li>Mobile phones, tablets and accessories</li>
<li>Printers, scanners and office electronics</li>
<li>Circuit boards, cables and electronic components</li>
<li>Industrial electrical and electronic equipment</li>
</ul>
<p>When improperly disposed of, electronic waste can release hazardous substances into the environment. Responsible recycling helps recover valuable materials while reducing the need for extracting new natural resources.</p>
<h2>Our Comprehensive E-Waste Management Services</h2>
<h3>1. E-Waste Collection and Recycling</h3>
<p>We support businesses and organizations in managing end-of-life electronic equipment through structured collection, sorting and recycling processes.</p>
<p>Our approach focuses on maximizing material recovery and minimizing waste sent for final disposal.</p>
<h3>2. IT Asset Disposition (ITAD)</h3>
<p>IT Asset Disposition provides a structured approach to managing retired IT equipment.</p>
<p>Our ITAD process includes:</p>
<ul>
<li>Asset identification and inventory management.</li>
<li>Equipment assessment and categorization.</li>
<li>Reuse and refurbishment opportunities.</li>
<li>Responsible dismantling and recycling.</li>
<li>Asset reporting and documentation.</li>
</ul>
<h3>3. Secure Data Destruction</h3>
<p>Protecting sensitive information is an important part of electronic asset disposal.</p>
<p>Our data destruction process focuses on securely sanitizing or physically destroying storage media, depending on the device and required security standards.</p>
<p>This helps organizations protect confidential business information and reduce data security risks.</p>
<h3>4. EPR Documentation and Compliance Support</h3>
<p>Extended Producer Responsibility (EPR) is an important part of India&rsquo;s e-waste regulatory framework.</p>
<p>We support responsible e-waste management through appropriate documentation, recycling records and compliance-related reporting.</p>
<p>Documentation requirements depend on the applicable regulations and the role of the organization in the recycling chain.</p>
<h3>5. Electronic Equipment Dismantling</h3>
<p>Dismantling is a crucial stage in the e-waste recycling process.</p>
<p>Electronic equipment is carefully dismantled into individual components, allowing materials such as metals, plastics, circuit boards and other recoverable resources to be segregated.</p>
<p>The process helps improve material recovery and ensures that non-recyclable and hazardous fractions are handled appropriately.</p>
<h2>Our E-Waste Recycling Process</h2>
<h3>Step 1: Collection</h3>
<p>Electronic waste is collected from businesses, institutions and other authorized sources.</p>
<h3>Step 2: Inspection and Segregation</h3>
<p>Collected equipment is inspected, identified and categorized based on its type, condition and recycling requirements.</p>
<h3>Step 3: Data Destruction</h3>
<p>Storage devices undergo appropriate data sanitization or physical destruction to protect confidential information.</p>
<h3>Step 4: Dismantling</h3>
<p>Equipment is carefully dismantled, and its components are separated for further processing.</p>
<h3>Step 5: Material Recovery</h3>
<p>Recoverable materials are processed through appropriate recycling channels.</p>
<h3>Step 6: Responsible Disposal</h3>
<p>Residual materials that cannot be recovered are directed to authorized facilities for environmentally responsible treatment and disposal.</p>
<h2>Measuring Our Environmental Impact</h2>
<p>Sustainability should be measurable.</p>
<p>We believe that transparent reporting helps organizations understand the environmental value of responsible e-waste management.</p>
<p>Our sustainability dashboard can track:</p>
<ul>
<li><strong>Total E-Waste Processed:</strong> The total weight of electronic waste collected and processed.</li>
<li><strong>CO<sub>2</sub> Emissions Avoided:</strong> Estimated greenhouse gas emissions avoided through recycling and material recovery, using a documented methodology.</li>
<li><strong>Devices Recycled:</strong> The number of electronic devices responsibly processed.</li>
<li><strong>Material Recovery:</strong> The quantity of reusable and recyclable materials recovered.</li>
<li><strong>Responsible Disposal:</strong> The amount of residual waste directed to appropriate treatment facilities.</li>
</ul>
<p>Every kilogram of electronic waste managed responsibly contributes to a more resource-efficient future.</p>
<h2>Why Responsible E-Waste Management Matters</h2>
<p>Improper disposal of electronic equipment can contribute to soil, water and air pollution.</p>
<p>Responsible e-waste management offers several benefits:</p>
<ul>
<li>Reduces environmental pollution.</li>
<li>Recovers valuable metals and reusable materials.</li>
<li>Conserves natural resources.</li>
<li>Supports a circular economy.</li>
<li>Protects sensitive business information.</li>
<li>Helps organizations meet applicable environmental responsibilities.</li>
</ul>
<h2>Building a Circular Economy</h2>
<p>A circular economy focuses on keeping products and materials in use for as long as possible.</p>
<p>Instead of treating outdated electronic equipment as waste, we encourage responsible reuse, refurbishment, component recovery and recycling wherever practical.</p>
<p>This approach reduces resource consumption and supports a more sustainable electronics lifecycle.</p>
<h2>Partner With Us for a Greener Tomorrow</h2>
<p>Electronic waste is a growing challenge, but responsible action can turn that challenge into an opportunity.</p>
<p>Whether you are a corporate organization, educational institution, IT company or industrial enterprise, responsible e-waste management can make a meaningful difference.</p>
<p>Together, we can reduce electronic waste, recover valuable resources, protect sensitive information and contribute to a cleaner environment.</p>
<p><strong>Recycle Responsibly. Recover Resources. Protect the Planet.</strong></p>';
}

function vanregen_seed_pages() {
    $pages = array(
        'home' => array('Home', 0, '<p>Van Regen helps organizations explore next steps for end-of-life materials, equipment, and industrial assets. Contact the team to discuss requirements for a specific project.</p>'),
        'about-us' => array('About Van Regen', 0, '<p>Company, leadership, and operating details will be added as they are confirmed. Contact the team for current information.</p>'),
        'services' => array('Services', 0, '<p>Browse our current capabilities below. Project scope and acceptance criteria are confirmed with the team.</p>'),
        'services/e-waste-recycling' => array('E-Waste Recycling', 'services', '<p>Discuss end-of-life electronics, business equipment, data-handling needs, and relevant downstream processes with the team. Accepted materials and project scope are confirmed for each request.</p>'),
        'services/battery-recycling' => array('Battery Recycling', 'services', '<p>Discuss end-of-life battery materials, handling requirements, and project-specific logistics with the team. Accepted chemistries and scope are confirmed for each request.</p>'),
        'services/industrial-demolition-dismantling' => array('Industrial Demolition and Dismantling', 'services', '<p>Discuss equipment or facility decommissioning needs with the team. Project scope, engineering requirements, and operating arrangements are reviewed for each site.</p>'),
        'services/port-crane-dismantling' => array('Port Crane Dismantling', 'services', '<p>Discuss port crane dismantling requirements with the team. Site conditions, engineering scope, and operating arrangements are reviewed for each project.</p>'),
        'sustainability' => array('Sustainability', 0, '<p>Environmental outcomes, emissions estimates, verified credits, and planting commitments depend on project-specific measurement and verification. The volume estimator below is illustrative only.</p>'),
        'projects' => array('Projects', 0, '<p>Explore project work and case studies published by Van Regen.</p>'),
        'contact' => array('Contact', 0, '<p>Contact Van Regen for general questions, partnerships, or other inquiries.</p>'),
        'contact-quote' => array('Request a Quote', 0, '<p>Request pricing for one or more services. Add each product or asset with its quantity, then include any project details in your message so the team can prepare a useful response.</p>'),
    );
    $page_ids = array();

    foreach ($pages as $path => $details) {
        $existing = get_page_by_path($path, OBJECT, 'page');
        if ($existing) {
            $page_ids[$path] = (int) $existing->ID;
            if (vanregen_is_seed_placeholder($existing->post_content)) {
                wp_update_post(array('ID' => $existing->ID, 'post_content' => $details[2]));
            }

            continue;
        }

        $parent_path = $details[1];
        $parent_id = is_string($parent_path) ? (int) ($page_ids[$parent_path] ?? 0) : (int) $parent_path;
        $page_id = wp_insert_post(array(
            'post_type' => 'page',
            'post_status' => 'publish',
            'post_title' => $details[0],
            'post_name' => basename($path),
            'post_parent' => $parent_id,
            'post_content' => $details[2],
        ), true);

        if (!is_wp_error($page_id)) {
            $page_ids[$path] = (int) $page_id;
        }
    }

    if (!empty($page_ids['home']) && !get_option('page_on_front')) {
        update_option('show_on_front', 'page');
        update_option('page_on_front', $page_ids['home']);
    }
}

function vanregen_seed_quote_page_admin_title() {
    $quote_page = get_page_by_path('contact-quote', OBJECT, 'page');
    if (!$quote_page || $quote_page->post_title !== 'Contact') {
        return;
    }

    $updated = wp_update_post(array(
        'ID' => $quote_page->ID,
        'post_title' => 'Request a Quote',
    ), true);
    if (is_wp_error($updated)) {
        error_log(sprintf('Van Regen: unable to rename the quote page: %s', $updated->get_error_message()));
    }
}
add_action('init', 'vanregen_seed_quote_page_admin_title', 20);

function vanregen_sync_quote_page_backend_content() {
    $quote_page = get_page_by_path('contact-quote', OBJECT, 'page');
    if (!$quote_page || strpos((string) $quote_page->post_content, 'The profile reports experience across IT/ITES') === false) {
        return;
    }

    $updated = wp_update_post(array(
        'ID' => $quote_page->ID,
        'post_content' => '<p>Request pricing for one or more services. Add each product or asset with its quantity, then include any project details in your message so the team can prepare a useful response.</p>',
    ), true);
    if (is_wp_error($updated)) {
        error_log(sprintf('Van Regen: unable to update quote page content: %s', $updated->get_error_message()));
    }
}
add_action('init', 'vanregen_sync_quote_page_backend_content', 21);

function vanregen_seed_contact_page() {
    if (get_page_by_path('contact', OBJECT, 'page')) {
        return;
    }

    $page_id = wp_insert_post(array(
        'post_type' => 'page',
        'post_status' => 'publish',
        'post_title' => 'Contact',
        'post_name' => 'contact',
        'post_content' => '<p>Contact Van Regen for general questions, partnerships, or other inquiries.</p>',
    ), true);

    if (is_wp_error($page_id) || !$page_id) {
        $message = is_wp_error($page_id) ? $page_id->get_error_message() : 'WordPress returned no page ID.';
        error_log(sprintf('Van Regen: unable to create the Contact page: %s', $message));
    }
}
add_action('init', 'vanregen_seed_contact_page', 19);

function vanregen_seed_ewaste_project() {
    if (get_page_by_path('e-waste-management', OBJECT, 'projects')) {
        return;
    }

    wp_insert_post(array(
        'post_type' => 'projects',
        'post_status' => 'publish',
        'post_title' => 'E-Waste Management: Building a Sustainable Future',
        'post_name' => 'e-waste-management',
        'post_excerpt' => 'A complete approach to responsible electronics collection, secure data destruction, dismantling, material recovery, and environmentally responsible recycling.',
        'post_content' => vanregen_projects_page_content(),
    ), true);
}
add_action('init', 'vanregen_seed_ewaste_project', 25);

function vanregen_sync_services_directory_page() {
    $version = '2026-11-22-services-directory-copy-3';
    $page = get_page_by_path('services', OBJECT, 'page');
    if (!$page) {
        return;
    }

    if (get_option('vanregen_services_directory_copy_version') === $version && !vanregen_is_seed_placeholder($page->post_content)) {
        return;
    }

    $summary = 'Explore Van Regen services for electronic waste, secure IT asset retirement, and EPR documentation. Each project is scoped to the materials and requirements confirmed with the team.';
    $intro = 'Browse our current capabilities below. Project scope and acceptance criteria are confirmed with the team.';
    $legacy_summary = 'Explore service areas for electronics and IT equipment, batteries, industrial dismantling, and port crane dismantling.';
    $updates = array('ID' => $page->ID);
    if (vanregen_is_seed_placeholder($page->post_content)) {
        $updates['post_content'] = '<p>' . esc_html($intro) . '</p>';
        if (trim((string) $page->post_excerpt) === $legacy_summary) {
            $updates['post_excerpt'] = $summary;
        }
    }

    if (count($updates) > 1) {
        $updated = wp_update_post($updates, true);
        if (is_wp_error($updated) || !$updated) {
            $message = is_wp_error($updated) ? $updated->get_error_message() : 'WordPress returned no updated page ID.';
            error_log(sprintf('Van Regen: unable to sync the Services page: %s', $message));
            return;
        }
    }

    if (trim((string) get_post_meta($page->ID, 'vanregen_page_summary', true)) === $legacy_summary) {
        update_post_meta($page->ID, 'vanregen_page_summary', $summary);
    }

    if (trim((string) get_post_meta($page->ID, 'vanregen_seo_title', true)) === 'E-Waste & Industrial Recycling Services | Van Regen') {
        update_post_meta($page->ID, 'vanregen_seo_title', vanregen_sanitize_seo_title('E-Waste, ITAD & EPR Services | Van Regen'));
    }

    update_option('vanregen_services_directory_copy_version', $version, false);
}
add_action('init', 'vanregen_sync_services_directory_page', 27);

function vanregen_seed_services() {
    $services = array(
        'epr-documentation' => array('Extended Producer Responsibility (EPR) Documentation', 'Simplifying EPR compliance through organized registration support, records, certificates, reporting and audit preparation.'),
        'itad-asset-buyback' => array('IT Asset Disposition & Buyback Solutions', 'Secure assessment, collection, data sanitization, refurbishment, resale and responsible recycling for retired IT assets.'),
        'data-destruction' => array('Secure Data Destruction & IT Asset Sanitisation', 'Structured data erasure, degaussing, physical destruction, secure handling and documentation for data-bearing equipment.'),
        'e-waste-recycling' => array('E-Waste Recycling', 'Responsible collection, segregation, dismantling, material recovery and recycling for obsolete electronic equipment.'),
    );

    foreach ($services as $slug => $details) {
        $existing = get_page_by_path($slug, OBJECT, 'services');
        if ($existing) {
            if (vanregen_is_seed_placeholder($existing->post_content)) {
                wp_update_post(array(
                    'ID' => $existing->ID,
                    'post_content' => '<p>' . esc_html($details[1]) . '</p><p>Discuss acceptance criteria and project-specific requirements with the team.</p>',
                ));
            }
            continue;
        }
        wp_insert_post(array(
            'post_type' => 'services',
            'post_status' => 'publish',
            'post_name' => $slug,
            'post_title' => $details[0],
            'post_excerpt' => $details[1],
            'post_content' => '<p>' . esc_html($details[1]) . '</p><p>Discuss acceptance criteria and project-specific requirements with the team.</p>',
        ));
    }
}

function vanregen_activate_plugin() {
    vanregen_register_content_types();
    vanregen_seed_pages();
    vanregen_seed_services();
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'vanregen_activate_plugin');

function vanregen_frontend_service_backend_records() {
    return array(
        'e-waste-recycling' => array(
            'title' => 'E-Waste Recycling',
            'excerpt' => 'Responsible collection, segregation, dismantling, material recovery and recycling for obsolete electronic equipment.',
            'content' => '<h2>Responsible Recycling. Sustainable Future.</h2>
<p>Give obsolete electronics a second life through systematic collection, dismantling, material recovery and responsible disposal. Our structured process recovers useful resources and directs hazardous or non-recyclable fractions to appropriate authorized channels.</p>
<h2>Our E-Waste Recycling Process</h2>
<ol>
<li><strong>Collection &amp; Transportation:</strong> Collect obsolete computers, laptops, servers, IT equipment and other electronic assets.</li>
<li><strong>Inspection &amp; Segregation:</strong> Categorize materials by equipment type, composition and recycling potential.</li>
<li><strong>Dismantling &amp; Processing:</strong> Separate circuit boards, cables, metals, plastics, batteries and reusable components.</li>
<li><strong>Material Recovery:</strong> Process recoverable copper, aluminium, iron and other resources for reuse in manufacturing.</li>
<li><strong>Responsible Recycling &amp; Disposal:</strong> Route restricted and non-recyclable fractions through appropriate authorized treatment channels.</li>
</ol>
<h2>Types of E-Waste We Recycle</h2>
<ul><li>Desktop computers and CPUs</li><li>Laptops and notebooks</li><li>IT and networking equipment</li><li>Circuit boards and components</li><li>Office electronics</li><li>Eligible electronic batteries</li></ul>
<h2>Environmental Impact</h2>
<p>Responsible e-waste recycling supports resource conservation, reduced landfill waste, lower environmental impact, material circularity and accountable downstream handling.</p>',
            'seo_title' => 'E-Waste Recycling Services | Responsible Electronic Waste Management',
            'seo_description' => 'Responsible e-waste recycling for computers, laptops, servers, circuit boards and electronic equipment through collection, dismantling and material recovery.',
        ),
        'data-destruction' => array(
            'title' => 'Secure Data Destruction & IT Asset Sanitisation',
            'excerpt' => 'Structured data erasure, degaussing, physical destruction, secure handling and documentation for data-bearing equipment.',
            'content' => '<h2>Your Data. Your Security. Our Responsibility.</h2>
<p>Protect confidential information through structured sanitization and destruction before IT equipment is reused, resold, recycled or disposed of.</p>
<h2>Our Data Destruction &amp; Sanitisation Services</h2>
<ul>
<li><strong>Certified Data Erasure:</strong> Technology-appropriate overwriting or sanitization for supported HDDs and SSDs.</li>
<li><strong>Degaussing:</strong> Magnetic-media sanitization where suitable; not applicable to SSDs or other flash storage.</li>
<li><strong>Physical Data Destruction:</strong> Specialized shredding or crushing where irreversible destruction is required.</li>
<li><strong>IT Asset Sanitisation:</strong> Prepare laptops, desktops, servers and storage equipment for secure disposition.</li>
<li><strong>Certificates &amp; Reporting:</strong> Device records, methods, serial numbers and completion documentation.</li>
<li><strong>Secure Collection &amp; Handling:</strong> Controlled pickup, tracking, transportation and processing.</li>
</ul>
<h2>Our Secure Data Destruction Workflow</h2>
<ol><li>Asset identification and inventory</li><li>Security assessment</li><li>Method selection</li><li>Secure data processing</li><li>Verification and validation</li><li>Certification and reporting</li></ol>
<h2>Storage Security Guidance</h2>
<p>Methods may be aligned with applicable requirements including NIST SP 800-88 Rev. 2, ISO/IEC 27001 and ISO/IEC 27040. Referencing a standard does not imply certification or verified conformity.</p>',
            'seo_title' => 'Secure Data Destruction & IT Asset Sanitisation Services',
            'seo_description' => 'Protect sensitive information with secure HDD and SSD sanitisation, data erasure, physical media destruction, asset tracking and documented IT disposal.',
        ),
        'itad-asset-buyback' => array(
            'title' => 'IT Asset Disposition & Buyback Solutions',
            'excerpt' => 'Secure assessment, collection, data sanitization, refurbishment, resale and responsible recycling for retired IT assets.',
            'content' => '<h2>Maximize Asset Value. Minimize Environmental Impact.</h2>
<p>Transform obsolete IT equipment into recoverable business value through secure assessment, collection, data sanitization, refurbishment, resale and responsible recycling.</p>
<h2>Our ITAD &amp; Buyback Services</h2>
<ul>
<li><strong>IT Asset Assessment &amp; Valuation:</strong> Evaluate condition, specifications, age, functionality and resale potential.</li>
<li><strong>Asset Collection &amp; Logistics:</strong> Coordinate verified pickup and transportation from offices, warehouses and data centres.</li>
<li><strong>Secure Data Destruction:</strong> Apply appropriate sanitization or physical destruction controls.</li>
<li><strong>Refurbishment &amp; Remarketing:</strong> Test, repair and prepare suitable equipment for reuse or resale.</li>
<li><strong>Responsible Recycling:</strong> Direct non-reusable equipment through authorized recovery channels.</li>
<li><strong>Buyback &amp; Reporting:</strong> Coordinate transparent valuation and asset-disposition records.</li>
</ul>
<h2>IT Assets We Accept</h2>
<p>Desktop computers, laptops, servers and storage, networking equipment, office IT equipment, hard drives, SSDs, RAM, motherboards and components.</p>
<h2>Our IT Asset Buyback Process</h2>
<ol><li>Share the asset inventory</li><li>Asset inspection and evaluation</li><li>Buyback quotation</li><li>Collection and secure handling</li><li>Data sanitization and processing</li><li>Settlement and documentation</li></ol>',
            'seo_title' => 'IT Asset Disposition & Buyback Services in India',
            'seo_description' => 'Recover value from obsolete IT equipment through secure ITAD, data destruction, refurbishment, resale, asset reporting and responsible recycling.',
        ),
        'epr-documentation' => array(
            'title' => 'Extended Producer Responsibility (EPR) Documentation',
            'excerpt' => 'Simplifying EPR compliance through organized registration support, records, certificates, reporting and audit preparation.',
            'content' => '<h2>Simplifying EPR Compliance. Strengthening Environmental Responsibility.</h2>
<p>Extended Producer Responsibility assigns producers responsibility for environmentally sound management of products after their useful life. We organize documentation and compliance evidence for applicable e-waste EPR requirements.</p>
<h2>Our EPR Documentation Services</h2>
<ol>
<li><strong>EPR Registration Support:</strong> Prepare information and records for applicable registration through the relevant portal.</li>
<li><strong>Compliance Documentation:</strong> Organize records and supporting information required under applicable EPR regulations.</li>
<li><strong>Recycling Certificate Management:</strong> Maintain eligible recycler certificates and supporting records.</li>
<li><strong>Annual Return &amp; Reporting Support:</strong> Compile recycling, obligation and reporting-period information.</li>
<li><strong>Record Keeping &amp; Audit Support:</strong> Maintain collection, recycling and recovery evidence for verification.</li>
</ol>
<h2>Key EPR Records</h2>
<p>EPR registration details, business registration documents, product and e-waste details, target records, recycling certificates, collection records, annual returns, invoices and agreements.</p>
<p>E-waste EPR requirements in India are principally governed by the E-Waste (Management) Rules, 2022, subsequent amendments and applicable CPCB guidelines. Applicability must be confirmed for each entity and reporting period.</p>',
            'seo_title' => 'EPR Documentation Services in India | E-Waste Compliance',
            'seo_description' => 'EPR documentation support for registration, recycling certificates, compliance records, target tracking and annual reporting under applicable e-waste requirements.',
        ),
    );
}

function vanregen_service_product_defaults() {
    return array(
        'e-waste-recycling' => array(
            'Desktop Computers, Workstations & CPUs',
            'Laptops & Notebooks',
            'Servers & Data-Centre Equipment',
            'IT & Networking Equipment',
            'Mobile Phones & Tablets',
            'Printers, Scanners & Office Electronics',
            'Circuit Boards & Components',
            'Eligible Electronic Batteries',
        ),
        'itad-asset-buyback' => array(
            'Desktop Computers & Workstations',
            'Laptops & Notebooks',
            'Servers & Enterprise Storage',
            'Networking & Telecom Equipment',
            'Mobile Phones & Tablets',
            'Printers, Scanners & Office IT Equipment',
            'IT Components',
        ),
        'data-destruction' => array(
            'Hard Disk Drives (HDDs)',
            'Solid-State Drives (SSDs)',
            'Servers & Enterprise Storage Systems',
            'Laptops & Desktop Computers',
            'Mobile Phones & Tablets',
            'USB Drives, Memory Cards & Embedded Storage',
            'Magnetic Tapes & Backup Media',
        ),
        'epr-documentation' => array(
            'Producer & Company Details',
            'Product & Electronic-Waste Categories',
            'EPR Registration Documents',
            'EPR Targets & Compliance Records',
            'Recycling Certificates',
            'Annual Returns',
            'Supporting Invoices & Agreements',
        ),
    );
}

function vanregen_seed_service_product_options() {
    foreach (vanregen_service_product_defaults() as $slug => $products) {
        $service = get_page_by_path($slug, OBJECT, 'services');
        if (!$service || metadata_exists('post', $service->ID, 'vanregen_service_products')) {
            continue;
        }

        update_post_meta($service->ID, 'vanregen_service_products', vanregen_sanitize_service_products($products));
    }
}
add_action('init', 'vanregen_seed_service_product_options', 27);

function vanregen_migrate_service_product_options() {
    $version = '2026-11-22-service-product-order-1';
    if (get_option('vanregen_service_product_options_version') === $version) {
        return;
    }

    $legacy_defaults = array(
        'e-waste-recycling' => array(
            'Desktop Computers & CPUs',
            'Laptops & Notebooks',
            'IT & Networking Equipment',
            'Circuit Boards & Components',
            'Office Electronics',
            'Electronic Batteries',
        ),
        'data-destruction' => array(
            'HDDs',
            'SSDs',
            'USB Drives & Memory Cards',
            'Enterprise Storage Systems',
        ),
        'itad-asset-buyback' => array(
            'Desktop Computers',
            'Laptops & Notebooks',
            'Servers & Storage',
            'Networking Equipment',
            'Office IT Equipment',
            'IT Components',
        ),
        'epr-documentation' => array(
            'Product & E-Waste Details',
            'EPR Registration Details',
            'EPR Target Records',
            'Recycling Certificates',
            'Annual Returns',
            'Supporting Invoices & Agreements',
        ),
    );

    $sync_succeeded = true;
    foreach (vanregen_service_product_defaults() as $slug => $products) {
        $service = get_page_by_path($slug, OBJECT, 'services');
        if (!$service) {
            $sync_succeeded = false;
            continue;
        }

        $existing = get_post_meta($service->ID, 'vanregen_service_products', true);
        $legacy = $legacy_defaults[$slug] ?? array();
        if (!is_array($existing) || $existing === $legacy) {
            update_post_meta($service->ID, 'vanregen_service_products', vanregen_sanitize_service_products($products));
        }
    }

    if ($sync_succeeded) {
        update_option('vanregen_service_product_options_version', $version, false);
    }
}
add_action('init', 'vanregen_migrate_service_product_options', 28);

function vanregen_sync_frontend_service_records() {
    $version = '2026-11-21-frontend-service-sync-1';
    $provision_version = '2026-11-22-service-record-provision-1';
    $needs_sync = get_option('vanregen_frontend_service_sync_version') !== $version;
    $needs_provision = get_option('vanregen_service_record_provision_version') !== $provision_version;
    if (!$needs_sync && !$needs_provision) {
        return;
    }

    $sync_succeeded = true;

    foreach (vanregen_frontend_service_backend_records() as $slug => $record) {
        $service = get_page_by_path($slug, OBJECT, 'services');
        $created = false;
        if (!$service) {
            $service_id = wp_insert_post(array(
                'post_type' => 'services',
                'post_status' => 'publish',
                'post_name' => $slug,
                'post_title' => $record['title'],
                'post_excerpt' => $record['excerpt'],
                'post_content' => wp_kses_post($record['content']),
            ), true);
            if (is_wp_error($service_id)) {
                error_log(sprintf('Van Regen: unable to create service "%s": %s', $slug, $service_id->get_error_message()));
                $sync_succeeded = false;
                continue;
            }
            $service = get_post($service_id);
            if (!$service) {
                error_log(sprintf('Van Regen: service "%s" was created but could not be loaded.', $slug));
                $sync_succeeded = false;
                continue;
            }
            $created = true;
        }

        if (!$needs_sync && !$created) {
            continue;
        }

        $updated = wp_update_post(array(
            'ID' => $service->ID,
            'post_title' => $record['title'],
            'post_excerpt' => $record['excerpt'],
            'post_content' => wp_kses_post($record['content']),
        ), true);
        if (is_wp_error($updated) || !$updated) {
            $message = is_wp_error($updated) ? $updated->get_error_message() : 'WordPress returned no updated post ID.';
            error_log(sprintf('Van Regen: unable to sync service "%s": %s', $slug, $message));
            $sync_succeeded = false;
            continue;
        }

        update_post_meta($service->ID, 'vanregen_seo_title', vanregen_sanitize_seo_title($record['seo_title']));
        update_post_meta($service->ID, 'vanregen_seo_description', vanregen_sanitize_seo_description($record['seo_description']));
        update_post_meta($service->ID, 'vanregen_page_summary', sanitize_textarea_field($record['excerpt']));
    }

    if ($sync_succeeded) {
        if ($needs_sync) {
            update_option('vanregen_frontend_service_sync_version', $version, false);
        }
        if ($needs_provision) {
            update_option('vanregen_service_record_provision_version', $provision_version, false);
        }
    }
}
add_action('init', 'vanregen_sync_frontend_service_records', 26);

function vanregen_refresh_rewrite_rules() {
    $rewrite_version = '2026-11-service-routes-1';
    if (get_option('vanregen_rewrite_version') === $rewrite_version) {
        return;
    }
    flush_rewrite_rules(false);
    update_option('vanregen_rewrite_version', $rewrite_version, false);
}
add_action('init', 'vanregen_refresh_rewrite_rules', 99);

function vanregen_register_inquiry_route() {
    register_rest_route('vanregen/v1', '/inquiries', array(
        'methods' => WP_REST_Server::CREATABLE,
        'permission_callback' => '__return_true',
        'callback' => 'vanregen_create_inquiry',
    ));
}
add_action('rest_api_init', 'vanregen_register_inquiry_route');

function vanregen_create_inquiry(WP_REST_Request $request) {
    $data = $request->get_json_params();
    if (!is_array($data)) {
        return new WP_Error('invalid_request', __('Please submit the form again.', 'vanregen-content'), array('status' => 400));
    }

    if (!empty($data['website'])) {
        return new WP_REST_Response(array('message' => __('Your inquiry was received.', 'vanregen-content')), 201);
    }

    $name = sanitize_text_field($data['name'] ?? '');
    $company = sanitize_text_field($data['company'] ?? '');
    $email = sanitize_email($data['email'] ?? '');
    $phone = sanitize_text_field($data['phone'] ?? '');
    $message = sanitize_textarea_field($data['message'] ?? '');
    $consent = !empty($data['consent']);
    $request_type = sanitize_key($data['request_type'] ?? '');
    if ($request_type === '') {
        $request_type = isset($data['services']) || isset($data['service']) ? 'quote' : 'contact';
    }
    if (!in_array($request_type, array('contact', 'quote'), true)) {
        return new WP_Error('invalid_inquiry_type', __('Please submit the form again.', 'vanregen-content'), array('status' => 422));
    }

    if (!$name || !$company || !$email || !is_email($email) || !$phone || !$message || !$consent) {
        return new WP_Error('invalid_inquiry', __('Complete all required fields and accept the contact consent.', 'vanregen-content'), array('status' => 422));
    }

    $services = array();
    $service_names = array();
    if ($request_type === 'quote') {
        $raw_services = $data['services'] ?? null;
        if (null === $raw_services && isset($data['service'])) {
            $legacy_products = $data['products'] ?? null;
            if (null === $legacy_products && isset($data['product'])) {
                $legacy_products = array(array('product' => $data['product'], 'quantity' => 1));
            }
            $raw_services = array(array(
                'service' => $data['service'],
                'products' => $legacy_products,
            ));
        }
        if (!is_array($raw_services) || empty($raw_services)) {
            return new WP_Error('invalid_services', __('Select at least one service and at least one product or asset for each.', 'vanregen-content'), array('status' => 422));
        }

        foreach ($raw_services as $service_selection) {
            if (!is_array($service_selection)) {
                return new WP_Error('invalid_services', __('Each service needs at least one product or asset.', 'vanregen-content'), array('status' => 422));
            }

            $service = sanitize_text_field($service_selection['service'] ?? '');
            $raw_products = $service_selection['products'] ?? null;
            if (!$service || !is_array($raw_products) || empty($raw_products)) {
                return new WP_Error('invalid_services', __('Select a service and at least one product or asset for each service.', 'vanregen-content'), array('status' => 422));
            }

            $products = array();
            foreach ($raw_products as $product_selection) {
                if (!is_array($product_selection)) {
                    return new WP_Error('invalid_products', __('Each product or asset needs a name and a positive whole-number quantity.', 'vanregen-content'), array('status' => 422));
                }

                $product = sanitize_text_field($product_selection['product'] ?? '');
                $quantity = filter_var($product_selection['quantity'] ?? null, FILTER_VALIDATE_INT, array(
                    'options' => array('min_range' => 1),
                ));
                if (!$product || false === $quantity) {
                    return new WP_Error('invalid_products', __('Each product or asset needs a name and a positive whole-number quantity.', 'vanregen-content'), array('status' => 422));
                }

                $products[] = array(
                    'product' => $product,
                    'quantity' => $quantity,
                );
            }

            $services[] = array(
                'service' => $service,
                'products' => $products,
            );
            $service_names[] = $service;
        }
    }
    $context_service = $request_type === 'contact'
        ? sanitize_text_field($data['context_service'] ?? '')
        : '';
    if (strlen($message) > 5000) {
        return new WP_Error('message_too_long', __('Please keep your message under 5,000 characters.', 'vanregen-content'), array('status' => 422));
    }

    $ip = sanitize_text_field($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    $rate_key = 'vanregen_inquiry_' . hash_hmac('sha256', $ip, wp_salt('auth'));
    if (get_transient($rate_key)) {
        return new WP_Error('rate_limited', __('Please wait a few minutes before sending another inquiry.', 'vanregen-content'), array('status' => 429));
    }
    set_transient($rate_key, 1, 60);

    $submitted_at = current_time('mysql');
    $service_lines = array();
    foreach ($services as $service_selection) {
        $product_lines = array();
        foreach ($service_selection['products'] as $product_selection) {
            $product_lines[] = sprintf('%s (Quantity: %d)', $product_selection['product'], $product_selection['quantity']);
        }
        $service_lines[] = sprintf(
            "Service: %s\nProducts / Assets:\n%s",
            $service_selection['service'],
            implode("\n", $product_lines)
        );
    }
    $inquiry_details = $request_type === 'quote'
        ? "Service selections:\n" . implode("\n\n", $service_lines) . "\n"
        : ($context_service !== '' ? "Related service: %s\n" : '');
    if ($request_type === 'contact' && $context_service !== '') {
        $inquiry_details = sprintf($inquiry_details, $context_service);
    }
    $record_content = sprintf(
        "Name: %s\nCompany: %s\nEmail: %s\nPhone: %s\nInquiry type: %s\n%sConsent: yes\n\nMessage:\n%s",
        $name,
        $company,
        $email,
        $phone,
        ucfirst($request_type),
        $inquiry_details,
        $message
    );
    $request_summary = array();
    foreach ($services as $service_selection) {
        $products = array();
        foreach ($service_selection['products'] as $product_selection) {
            $products[] = sprintf('%s (Quantity: %d)', $product_selection['product'], $product_selection['quantity']);
        }
        $request_summary[] = sprintf('%s: %s', $service_selection['service'], implode(', ', $products));
    }
    if (!$request_summary && $context_service !== '') {
        $request_summary[] = $context_service;
    }
    $inquiry_id = wp_insert_post(array(
        'post_type' => 'vanregen_inquiry',
        'post_status' => 'private',
        'post_title' => sprintf(
            __('%1$s request - %2$s - %3$s', 'vanregen-content'),
            $request_type === 'quote' ? __('Quote', 'vanregen-content') : __('Contact', 'vanregen-content'),
            $name,
            $submitted_at
        ),
        'post_content' => $record_content,
    ), true);

    if (is_wp_error($inquiry_id)) {
        delete_transient($rate_key);
        return new WP_Error('save_failed', __('Your inquiry could not be saved. Please try again.', 'vanregen-content'), array('status' => 500));
    }

    update_post_meta($inquiry_id, 'vanregen_inquiry_type', $request_type);
    update_post_meta($inquiry_id, 'vanregen_inquiry_name', $name);
    update_post_meta($inquiry_id, 'vanregen_inquiry_company', $company);
    update_post_meta($inquiry_id, 'vanregen_inquiry_email', $email);
    update_post_meta($inquiry_id, 'vanregen_inquiry_phone', $phone);
    update_post_meta($inquiry_id, 'vanregen_inquiry_context_service', $context_service);
    update_post_meta($inquiry_id, 'vanregen_inquiry_request_summary', implode('; ', $request_summary));

    $smtp_settings = vanregen_get_smtp_settings();
    $recipient = $smtp_settings['notification_email'];
    $subject_context = $request_type === 'quote' ? implode(', ', $service_names) : __('Contact', 'vanregen-content');
    $subject = sprintf(__('Website %1$s inquiry: %2$s', 'vanregen-content'), ucfirst($request_type), $subject_context);
    $admin_inquiry_url = admin_url('post.php?post=' . (int) $inquiry_id . '&action=edit');
    $mail_body = sprintf(
        "%s\n\nSubmitted: %s\n\nView and reply to this inquiry in WordPress:\n%s",
        $record_content,
        $submitted_at,
        $admin_inquiry_url
    );
    $mail_result = vanregen_send_mail($recipient, $subject, $mail_body, $email);
    $email_sent = (bool) $mail_result['sent'];
    update_post_meta($inquiry_id, 'vanregen_inquiry_email_status', $email_sent
        ? 'sent'
        : ($smtp_settings['enabled'] ? 'failed' : 'not_configured'));
    update_post_meta($inquiry_id, 'vanregen_inquiry_email_error', $mail_result['error']);
    if (!$email_sent) {
        error_log(sprintf('Van Regen: inquiry %d was saved but email delivery failed: %s', $inquiry_id, $mail_result['error']));
    }

    return new WP_REST_Response(array(
        'message' => $email_sent
            ? __('Your inquiry was received. The team will follow up soon.', 'vanregen-content')
            : __('Your inquiry was saved in WordPress, but the email notification could not be delivered.', 'vanregen-content'),
        'emailSent' => (bool) $email_sent,
    ), 201);
}
