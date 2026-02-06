<?php

function theme_enqueue_styles() {
    wp_enqueue_style( 'child-style', get_stylesheet_directory_uri() . '/style.css', [] );
}
add_action( 'wp_enqueue_scripts', 'theme_enqueue_styles', 20 );

function avada_lang_setup() {
	$lang = get_stylesheet_directory() . '/languages';
	load_child_theme_textdomain( 'Avada', $lang );
}
add_action( 'after_setup_theme', 'avada_lang_setup' );

add_action( 'wp_enqueue_scripts', function() {
    if ( is_admin() ) return;

    $upload_dir = wp_upload_dir();

    wp_enqueue_style(
        'iconpia-icons',
        $upload_dir['baseurl'] . '/fusion-icons/iconpia-v1.0/style.css',
        [],
        null
    );
}, 20 );

add_action( 'admin_enqueue_scripts', function() {
    $upload_dir = wp_upload_dir();

    wp_enqueue_style(
        'iconpia-icons-admin',
        $upload_dir['baseurl'] . '/fusion-icons/iconpia-v1.0/style.css',
        [],
        null
    );
});