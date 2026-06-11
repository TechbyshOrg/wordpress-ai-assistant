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
     * Write SEO meta data to the active SEO plugin
     *
     * @param int   $post_id Post ID to update.
     * @param array $meta    Array with keys: seo_title, meta_description, focus_keywords.
     * @return bool True if at least one field was written.
     */
    public function wacdmg_write_seo_meta( $post_id, $meta ) {
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return false;
        }

        $written = false;

        $seo_title   = isset( $meta['seo_title'] )       ? sanitize_text_field( $meta['seo_title'] ) : '';
        $meta_desc   = isset( $meta['meta_description'] ) ? sanitize_textarea_field( $meta['meta_description'] ) : '';
        $focus_kw    = isset( $meta['focus_keywords'] )   ? sanitize_text_field( $meta['focus_keywords'] ) : '';

        // --- Yoast SEO ---
        if ( defined( 'WPSEO_VERSION' ) ) {
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
        if ( defined( 'RANK_MATH_VERSION' ) ) {
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
        if ( class_exists( 'AIOSEO\Plugin\AIOSEO' ) ) {
            if ( ! empty( $seo_title ) ) {
                update_post_meta( $post_id, '_aioseo_title', $seo_title );
                $written = true;
            }
            if ( ! empty( $meta_desc ) ) {
                update_post_meta( $post_id, '_aioseo_description', $meta_desc );
                $written = true;
            }
        }

        // --- Generic fallback (custom meta) ---
        if ( ! $written ) {
            if ( ! empty( $seo_title ) ) {
                update_post_meta( $post_id, '_wacdmg_seo_title', $seo_title );
            }
            if ( ! empty( $meta_desc ) ) {
                update_post_meta( $post_id, '_wacdmg_meta_description', $meta_desc );
            }
            if ( ! empty( $focus_kw ) ) {
                update_post_meta( $post_id, '_wacdmg_focus_keywords', $focus_kw );
            }
            $written = true;
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

        if ( defined( 'WPSEO_VERSION' ) ) {
            $meta['seo_title']        = get_post_meta( $post_id, '_yoast_wpseo_title', true );
            $meta['meta_description'] = get_post_meta( $post_id, '_yoast_wpseo_metadesc', true );
            $meta['focus_keywords']   = get_post_meta( $post_id, '_yoast_wpseo_focuskw', true );
            $meta['plugin']           = 'yoast';
        } elseif ( defined( 'RANK_MATH_VERSION' ) ) {
            $meta['seo_title']        = get_post_meta( $post_id, 'rank_math_title', true );
            $meta['meta_description'] = get_post_meta( $post_id, 'rank_math_description', true );
            $meta['focus_keywords']   = get_post_meta( $post_id, 'rank_math_focus_keyword', true );
            $meta['plugin']           = 'rankmath';
        } elseif ( class_exists( 'AIOSEO\Plugin\AIOSEO' ) ) {
            $meta['seo_title']        = get_post_meta( $post_id, '_aioseo_title', true );
            $meta['meta_description'] = get_post_meta( $post_id, '_aioseo_description', true );
            $meta['plugin']           = 'aioseo';
        }

        return $meta;
    }
}
