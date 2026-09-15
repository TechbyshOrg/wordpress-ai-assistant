<?php
/**
 * WACDMG SEO Integration Class
 *
 * Handles writing AI-generated SEO meta to popular SEO plugins.
 * Supports Yoast SEO, Rank Math, AIOSEO, and SEOPress with automatic detection.
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
     * @return string auto|yoast|rankmath|aioseo|seopress|generic
     */
    public function wacdmg_get_integration_target() {
        $creds  = get_option( 'wacdmg_ai_creds', array() );
        $target = isset( $creds['seo_integration'] ) ? sanitize_key( $creds['seo_integration'] ) : 'auto';
        $allowed = array( 'auto', 'yoast', 'rankmath', 'aioseo', 'seopress', 'generic' );
        return in_array( $target, $allowed, true ) ? $target : 'auto';
    }

    /**
     * Whether SEOPress is active.
     *
     * @return bool
     */
    public function wacdmg_is_seopress_active() {
        return defined( 'SEOPRESS_VERSION' ) || defined( 'SEOPRESS_PRO_VERSION' ) || function_exists( 'seopress_get_toggle_option' );
    }

    /**
     * Write a post meta key only when it is currently empty.
     *
     * @param int    $post_id Post ID.
     * @param string $key     Meta key.
     * @param string $value   Value.
     * @return bool
     */
    private function wacdmg_write_meta_if_empty( $post_id, $key, $value ) {
        if ( $value === '' ) {
            return false;
        }
        $existing = get_post_meta( $post_id, $key, true );
        if ( $existing !== '' && $existing !== null ) {
            return false;
        }
        update_post_meta( $post_id, $key, $value );
        return true;
    }

    /**
     * Copy title/description into Open Graph and Twitter keys when those keys are empty.
     *
     * @param int    $post_id   Post ID.
     * @param string $seo_title Title.
     * @param string $meta_desc Description.
     * @param string $plugin    Active writer slug.
     */
    private function wacdmg_write_social_if_empty( $post_id, $seo_title, $meta_desc, $plugin ) {
        if ( 'yoast' === $plugin ) {
            $this->wacdmg_write_meta_if_empty( $post_id, '_yoast_wpseo_opengraph-title', $seo_title );
            $this->wacdmg_write_meta_if_empty( $post_id, '_yoast_wpseo_opengraph-description', $meta_desc );
            $this->wacdmg_write_meta_if_empty( $post_id, '_yoast_wpseo_twitter-title', $seo_title );
            $this->wacdmg_write_meta_if_empty( $post_id, '_yoast_wpseo_twitter-description', $meta_desc );
        } elseif ( 'rankmath' === $plugin ) {
            $this->wacdmg_write_meta_if_empty( $post_id, 'rank_math_facebook_title', $seo_title );
            $this->wacdmg_write_meta_if_empty( $post_id, 'rank_math_facebook_description', $meta_desc );
            $this->wacdmg_write_meta_if_empty( $post_id, 'rank_math_twitter_title', $seo_title );
            $this->wacdmg_write_meta_if_empty( $post_id, 'rank_math_twitter_description', $meta_desc );
        } elseif ( 'seopress' === $plugin ) {
            $this->wacdmg_write_meta_if_empty( $post_id, '_seopress_social_fb_title', $seo_title );
            $this->wacdmg_write_meta_if_empty( $post_id, '_seopress_social_fb_desc', $meta_desc );
            $this->wacdmg_write_meta_if_empty( $post_id, '_seopress_social_twitter_title', $seo_title );
            $this->wacdmg_write_meta_if_empty( $post_id, '_seopress_social_twitter_desc', $meta_desc );
        }
    }

    /**
     * Write SEO meta data to the active SEO plugin
     *
     * @param int         $post_id Post ID to update.
     * @param array       $meta    Array with keys: seo_title, meta_description, focus_keywords.
     * @param string|null $target  Optional override for seo_integration.
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

        $has_yoast    = defined( 'WPSEO_VERSION' );
        $has_rankmath = defined( 'RANK_MATH_VERSION' );
        $has_aioseo   = class_exists( 'AIOSEO\Plugin\AIOSEO' );
        $has_seopress = $this->wacdmg_is_seopress_active();

        $write_yoast    = ( 'yoast' === $target ) || ( 'auto' === $target && $has_yoast );
        $write_rankmath = ( 'rankmath' === $target ) || ( 'auto' === $target && $has_rankmath );
        $write_aioseo   = ( 'aioseo' === $target ) || ( 'auto' === $target && $has_aioseo );
        $write_seopress = ( 'seopress' === $target ) || ( 'auto' === $target && $has_seopress && ! $has_yoast && ! $has_rankmath && ! $has_aioseo );
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
            $this->wacdmg_write_social_if_empty( $post_id, $seo_title, $meta_desc, 'yoast' );
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
            $this->wacdmg_write_social_if_empty( $post_id, $seo_title, $meta_desc, 'rankmath' );
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

        // --- SEOPress ---
        if ( $write_seopress ) {
            if ( ! empty( $seo_title ) ) {
                update_post_meta( $post_id, '_seopress_titles_title', $seo_title );
                $written = true;
            }
            if ( ! empty( $meta_desc ) ) {
                update_post_meta( $post_id, '_seopress_titles_desc', $meta_desc );
                $written = true;
            }
            if ( ! empty( $focus_kw ) ) {
                update_post_meta( $post_id, '_seopress_analysis_target_kw', $focus_kw );
                $written = true;
            }
            $this->wacdmg_write_social_if_empty( $post_id, $seo_title, $meta_desc, 'seopress' );
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
     * Write SEO meta for a taxonomy term.
     *
     * @param int    $term_id  Term ID.
     * @param array  $meta     seo_title, meta_description, focus_keywords.
     * @param string $taxonomy Taxonomy slug.
     * @return bool
     */
    public function wacdmg_write_term_seo_meta( $term_id, $meta, $taxonomy = '' ) {
        $term_id = intval( $term_id );
        if ( ! $term_id || ! current_user_can( 'edit_term', $term_id ) ) {
            return false;
        }

        $term = get_term( $term_id );
        if ( ! $term || is_wp_error( $term ) ) {
            return false;
        }
        if ( $taxonomy === '' ) {
            $taxonomy = $term->taxonomy;
        }

        $seo_title = isset( $meta['seo_title'] ) ? sanitize_text_field( $meta['seo_title'] ) : '';
        $meta_desc = isset( $meta['meta_description'] ) ? sanitize_textarea_field( $meta['meta_description'] ) : '';
        $focus_kw  = isset( $meta['focus_keywords'] ) ? sanitize_text_field( $meta['focus_keywords'] ) : '';

        $target       = $this->wacdmg_get_integration_target();
        $has_yoast    = defined( 'WPSEO_VERSION' );
        $has_rankmath = defined( 'RANK_MATH_VERSION' );
        $has_aioseo   = class_exists( 'AIOSEO\Plugin\AIOSEO' );
        $has_seopress = $this->wacdmg_is_seopress_active();

        $write_yoast    = ( 'yoast' === $target ) || ( 'auto' === $target && $has_yoast );
        $write_rankmath = ( 'rankmath' === $target ) || ( 'auto' === $target && $has_rankmath );
        $write_aioseo   = ( 'aioseo' === $target ) || ( 'auto' === $target && $has_aioseo );
        $write_seopress = ( 'seopress' === $target ) || ( 'auto' === $target && $has_seopress && ! $has_yoast && ! $has_rankmath && ! $has_aioseo );

        $written = false;

        if ( $write_yoast ) {
            if ( class_exists( 'WPSEO_Taxonomy_Meta' ) && method_exists( 'WPSEO_Taxonomy_Meta', 'set_value' ) ) {
                if ( $seo_title !== '' ) {
                    WPSEO_Taxonomy_Meta::set_value( $term_id, $taxonomy, 'wpseo_title', $seo_title );
                    $written = true;
                }
                if ( $meta_desc !== '' ) {
                    WPSEO_Taxonomy_Meta::set_value( $term_id, $taxonomy, 'wpseo_desc', $meta_desc );
                    $written = true;
                }
                if ( $focus_kw !== '' ) {
                    WPSEO_Taxonomy_Meta::set_value( $term_id, $taxonomy, 'wpseo_focuskw', $focus_kw );
                    $written = true;
                }
            } else {
                if ( $seo_title !== '' ) {
                    update_term_meta( $term_id, 'wpseo_title', $seo_title );
                    $written = true;
                }
                if ( $meta_desc !== '' ) {
                    update_term_meta( $term_id, 'wpseo_desc', $meta_desc );
                    $written = true;
                }
                if ( $focus_kw !== '' ) {
                    update_term_meta( $term_id, 'wpseo_focuskw', $focus_kw );
                    $written = true;
                }
            }
        }

        if ( $write_rankmath ) {
            if ( $seo_title !== '' ) {
                update_term_meta( $term_id, 'rank_math_title', $seo_title );
                $written = true;
            }
            if ( $meta_desc !== '' ) {
                update_term_meta( $term_id, 'rank_math_description', $meta_desc );
                $written = true;
            }
            if ( $focus_kw !== '' ) {
                update_term_meta( $term_id, 'rank_math_focus_keyword', $focus_kw );
                $written = true;
            }
        }

        if ( $write_aioseo ) {
            if ( $seo_title !== '' ) {
                update_term_meta( $term_id, '_aioseo_title', $seo_title );
                $written = true;
            }
            if ( $meta_desc !== '' ) {
                update_term_meta( $term_id, '_aioseo_description', $meta_desc );
                $written = true;
            }
            if ( $focus_kw !== '' ) {
                update_term_meta( $term_id, '_aioseo_keywords', $focus_kw );
                $written = true;
            }
        }

        if ( $write_seopress ) {
            if ( $seo_title !== '' ) {
                update_term_meta( $term_id, '_seopress_titles_title', $seo_title );
                $written = true;
            }
            if ( $meta_desc !== '' ) {
                update_term_meta( $term_id, '_seopress_titles_desc', $meta_desc );
                $written = true;
            }
            if ( $focus_kw !== '' ) {
                update_term_meta( $term_id, '_seopress_analysis_target_kw', $focus_kw );
                $written = true;
            }
        }

        if ( ! $written ) {
            if ( $seo_title !== '' ) {
                update_term_meta( $term_id, '_wacdmg_seo_title', $seo_title );
                $written = true;
            }
            if ( $meta_desc !== '' ) {
                update_term_meta( $term_id, '_wacdmg_meta_description', $meta_desc );
                $written = true;
            }
            if ( $focus_kw !== '' ) {
                update_term_meta( $term_id, '_wacdmg_focus_keywords', $focus_kw );
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
        if ( $this->wacdmg_is_seopress_active() ) {
            $plugins[] = 'seopress';
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
        } elseif ( 'seopress' === $target || ( 'auto' === $target && $this->wacdmg_is_seopress_active() ) ) {
            $meta['seo_title']        = get_post_meta( $post_id, '_seopress_titles_title', true );
            $meta['meta_description'] = get_post_meta( $post_id, '_seopress_titles_desc', true );
            $meta['focus_keywords']   = get_post_meta( $post_id, '_seopress_analysis_target_kw', true );
            $meta['plugin']           = 'seopress';
        } else {
            $meta['seo_title']        = get_post_meta( $post_id, '_wacdmg_seo_title', true );
            $meta['meta_description'] = get_post_meta( $post_id, '_wacdmg_meta_description', true );
            $meta['focus_keywords']   = get_post_meta( $post_id, '_wacdmg_focus_keywords', true );
            $meta['plugin']           = 'generic';
        }

        return $meta;
    }
}
