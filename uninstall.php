<?php
/**
 * Uninstall cleanup for AI Product Descriptions & SEO Meta for WooCommerce.
 *
 * Runs only when the plugin is deleted from the Plugins screen (not on deactivation).
 *
 * - Saved API keys and the usage log are always removed.
 * - Other settings and custom templates are removed unless the site chose
 *   "Keep settings and custom templates when the plugin is deleted".
 * - Queued auto-SEO jobs are cancelled.
 * - Post content and SEO meta written to posts, products, and terms are never removed:
 *   that is site content, not plugin configuration.
 *
 * @package WACDMG
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

/**
 * Clean up plugin data for the current site.
 */
function wacdmg_uninstall_site() {
    $creds = get_option( 'wacdmg_ai_creds', array() );
    $keep  = is_array( $creds ) && ! empty( $creds['keep_data_on_uninstall'] );

    if ( $keep && is_array( $creds ) ) {
        // Keep preferences, but never leave credentials behind.
        foreach ( array( 'chatgpt_key', 'apiKey', 'groq_key', 'gemini_key', 'claude_key', 'mistral_key', 'openrouter_key', 'together_key' ) as $key ) {
            unset( $creds[ $key ] );
        }
        update_option( 'wacdmg_ai_creds', $creds );
    } else {
        delete_option( 'wacdmg_ai_creds' );
        delete_option( 'wacdmg_templates' );
    }

    delete_option( 'wacdmg_usage' );

    // Cancel queued background jobs.
    if ( function_exists( 'as_unschedule_all_actions' ) ) {
        as_unschedule_all_actions( 'wacdmg_auto_seo_job', null, 'wacdmg' );
    }
    wp_unschedule_hook( 'wacdmg_auto_seo_job' );
}

if ( is_multisite() ) {
    $wacdmg_site_ids = get_sites(
        array(
            'fields' => 'ids',
            'number' => 0,
        )
    );
    foreach ( $wacdmg_site_ids as $wacdmg_site_id ) {
        switch_to_blog( $wacdmg_site_id );
        wacdmg_uninstall_site();
        restore_current_blog();
    }
} else {
    wacdmg_uninstall_site();
}
