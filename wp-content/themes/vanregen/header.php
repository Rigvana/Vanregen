<!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#153b32">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="screen-reader-text" href="#vanregen-root"><?php esc_html_e('Skip to content', 'vanregen'); ?></a>
<div id="vanregen-root" tabindex="-1">
    <div class="seo-fallback">
        <header>
            <a href="<?php echo esc_url(home_url('/')); ?>" rel="home"><?php bloginfo('name'); ?></a>
            <?php wp_nav_menu(array('theme_location' => 'primary', 'fallback_cb' => false)); ?>
        </header>
        <main>
            <?php
            $queried_post = get_queried_object();
            if ($queried_post instanceof WP_Post) {
                echo '<h1>' . esc_html(get_the_title($queried_post)) . '</h1>';
                if (!empty($queried_post->post_excerpt)) {
                    echo '<p>' . esc_html(wp_strip_all_tags($queried_post->post_excerpt)) . '</p>';
                }
                echo apply_filters('the_content', $queried_post->post_content);
            } elseif (is_404()) {
                echo '<h1>' . esc_html__('Page not found', 'vanregen') . '</h1>';
            }
            ?>
        </main>
    </div>
