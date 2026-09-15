<?php
/**
 * WACDMG SEO Integration Class
 *
 * Handles writing AI-generated SEO meta to popular SEO plugins.
 * Supports Yoast SEO, Rank Math, and AIOSEO with automatic detection.
 *
 * @package WACDMG
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WACDMG_SEO {

    /**
     * Resolve the configured SEO integration target.
     *
     * @return string auto|yoast|rankmath|aioseo|generic
     */
    public function wacdmg_get_integration_target() {
        $creds  = get_option( 'wacdmg_ai_creds', array() );
        $target = isset( $creds['seo_integration'] ) ? sanitize_key( $creds['seo_integration'] ) : 'auto';
        $allowed = array( 'auto', 'yoast', 'rankmath', 'aioseo', 'generic' );
        return in_array( $target, $allowed, true ) ? $target : 'auto';
    }

    /**
     * Write SEO meta data to the active SEO plugin
     *
     * @param int   $post_id Post ID to update.
     * @param array $meta    Array with keys: seo_title, meta_description, focus_keywords.
     * @param string|null $target Optional override for seo_integration.
     * @return bool True if at least one field was written.
     */
    public function wacdmg_write_seo_meta( $post_id, $meta, $target = null ) {
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return false;
        }

        $written = false;

        $seo_title = isset( $meta['seo_title'] ) ? sanitize_text_field( $meta['seo_title'] ) : '';
        $meta_desc = isset( $meta['meta_description'] ) ? sanitize_textarea_field( $meta['meta_description'] ) : '';
        $focus_kw  = isset( $meta['focus_keywords'] ) ? sanitize_text_field( $meta['focus_keywords'] ) : '';

        if ( null === $target ) {
            $target = $this->wacdmg_get_integration_target();
        }

        $write_yoast    = ( 'yoast' === $target ) || ( 'auto' === $target && defined( 'WPSEO_VERSION' ) );
        $write_rankmath = ( 'rankmath' === $target ) || ( 'auto' === $target && defined( 'RANK_MATH_VERSION' ) );
        $write_aioseo   = ( 'aioseo' === $target ) || ( 'auto' === $target && class_exists( 'AIOSEO\Plugin\AIOSEO' ) );
        $write_generic  = ( 'generic' === $target );

        // --- Yoast SEO ---
        if ( $write_yoast ) {
            if ( ! empty( $seo_title ) ) {
                update_post_meta( $post_id, '_yoast_wpseo_title', $seo_title );
                $written = true;
            }
            if ( ! empty( $meta_desc ) ) {
                update_post_meta( $post_id, '_yoast_wpseo_metadesc', $meta_desc );
                $written = true;
            }
            if ( ! empty( $focus_kw ) ) {
                update_post_meta( $post_id, '_yoast_wpseo_focuskw', $focus_kw );
                $written = true;
            }
        }

        // --- Rank Math ---
        if ( $write_rankmath ) {
            if ( ! empty( $seo_title ) ) {
                update_post_meta( $post_id, 'rank_math_title', $seo_title );
                $written = true;
            }
            if ( ! empty( $meta_desc ) ) {
                update_post_meta( $post_id, 'rank_math_description', $meta_desc );
                $written = true;
            }
            if ( ! empty( $focus_kw ) ) {
                update_post_meta( $post_id, 'rank_math_focus_keyword', $focus_kw );
                $written = true;
            }
        }

        // --- AIOSEO ---
        if ( $write_aioseo ) {
            if ( ! empty( $seo_title ) ) {
                update_post_meta( $post_id, '_aioseo_title', $seo_title );
                $written = true;
            }
            if ( ! empty( $meta_desc ) ) {
                update_post_meta( $post_id, '_aioseo_description', $meta_desc );
                $written = true;
            }
            if ( ! empty( $focus_kw ) ) {
                update_post_meta( $post_id, '_aioseo_keywords', $focus_kw );
                $written = true;
            }
        }

        // --- Generic fallback (custom meta) ---
        if ( $write_generic || ! $written ) {
            if ( ! empty( $seo_title ) ) {
                update_post_meta( $post_id, '_wacdmg_seo_title', $seo_title );
                $written = true;
            }
            if ( ! empty( $meta_desc ) ) {
                update_post_meta( $post_id, '_wacdmg_meta_description', $meta_desc );
                $written = true;
            }
            if ( ! empty( $focus_kw ) ) {
                update_post_meta( $post_id, '_wacdmg_focus_keywords', $focus_kw );
                $written = true;
            }
        }

        return $written;
    }

    /**
     * Detect which SEO plugin is active
     *
     * @return array List of active SEO plugin slugs.
     */
    public function wacdmg_detect_seo_plugins() {
        $plugins = array();

        if ( defined( 'WPSEO_VERSION' ) ) {
            $plugins[] = 'yoast';
        }
        if ( defined( 'RANK_MATH_VERSION' ) ) {
            $plugins[] = 'rankmath';
        }
        if ( class_exists( 'AIOSEO\Plugin\AIOSEO' ) ) {
            $plugins[] = 'aioseo';
        }

        return $plugins;
    }

    /**
     * Get existing SEO meta for a post
     *
     * @param int $post_id Post ID.
     * @return array Existing meta values.
     */
    public function wacdmg_get_existing_seo_meta( $post_id ) {
        $meta = array(
            'seo_title'        => '',
            'meta_description' => '',
            'focus_keywords'   => '',
            'plugin'           => 'none',
        );

        $target = $this->wacdmg_get_integration_target();

        if ( 'yoast' === $target || ( 'auto' === $target && defined( 'WPSEO_VERSION' ) ) ) {
            $meta['seo_title']        = get_post_meta( $post_id, '_yoast_wpseo_title', true );
            $meta['meta_description'] = get_post_meta( $post_id, '_yoast_wpseo_metadesc', true );
            $meta['focus_keywords']   = get_post_meta( $post_id, '_yoast_wpseo_focuskw', true );
            $meta['plugin']           = 'yoast';
        } elseif ( 'rankmath' === $target || ( 'auto' === $target && defined( 'RANK_MATH_VERSION' ) ) ) {
            $meta['seo_title']        = get_post_meta( $post_id, 'rank_math_title', true );
            $meta['meta_description'] = get_post_meta( $post_id, 'rank_math_description', true );
            $meta['focus_keywords']   = get_post_meta( $post_id, 'rank_math_focus_keyword', true );
            $meta['plugin']           = 'rankmath';
        } elseif ( 'aioseo' === $target || ( 'auto' === $target && class_exists( 'AIOSEO\Plugin\AIOSEO' ) ) ) {
            $meta['seo_title']        = get_post_meta( $post_id, '_aioseo_title', true );
            $meta['meta_description'] = get_post_meta( $post_id, '_aioseo_description', true );
            $meta['focus_keywords']   = get_post_meta( $post_id, '_aioseo_keywords', true );
            $meta['plugin']           = 'aioseo';
        } else {
            $meta['seo_title']        = get_post_meta( $post_id, '_wacdmg_seo_title', true );
            $meta['meta_description'] = get_post_meta( $post_id, '_wacdmg_meta_description', true );
            $meta['focus_keywords']   = get_post_meta( $post_id, '_wacdmg_focus_keywords', true );
            $meta['plugin']           = 'generic';
        }

        return $meta;
    }
}
