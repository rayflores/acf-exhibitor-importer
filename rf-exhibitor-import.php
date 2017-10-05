<?php
/**
 * Plugin Name: Exhibitor Importer
 * Plugin URI: http://rayflores.com
 * Description: Importer for the Page - Exhibitor List - built with ACF, that overwrites current data with an uploaded csv file.
 * Author: Ray Flores / Stoke Interactive
 * Author URI: http://rayflores.com
 * Version: 0.1
 * Requires at least: 4.0
 * Tested up to: 4.4.2
 *
 */

// if shipment tracking plugin is active
add_action( 'admin_init', 'rf_exhibitor_register_importers');

/**
 * Add menu items
 */
function rf_exhibitor_register_importers() {
    register_importer( 'rf_acf_exhibitor_importer', __( 'Import Exhibitor Data (CSV)', 'rf-exhibitor-importer' ), __( 'Import Exhibitor List to your site via a csv file.', 'rf-exhibitor-importer'), 'rf_exhibitor_importer' );
}

/**
 * Add menu item
 */
function rf_exhibitor_importer() {
    // Load Importer API
    require_once ABSPATH . 'wp-admin/includes/import.php';

    if ( ! class_exists( 'WP_Importer' ) ) {
        $class_wp_importer = ABSPATH . 'wp-admin/includes/class-wp-importer.php';
        if ( file_exists( $class_wp_importer ) )
            require $class_wp_importer;
    }

    // includes
    require 'importers/class-rf-exhibitor-importer.php';

    // Dispatch
    $importer = new RF_Exhibitor_Importer();
    $importer->dispatch();
}
