<?php
/**
 * WACDMG Image AI Class
 *
 * Handles saving AI-generated images to the WordPress Media Library.
 *
 * @package WACDMG
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WACDMG_Image_AI {

    /**
     * Download a remote image and save it to the WordPress Media Library.
     *
     * @param string $image_url The URL of the AI-generated image.
     * @param string $alt_text  Alt text / title to use for the attachment.
     * @param int    $post_id   Optional: attach the image to this post.
     * @return int|WP_Error The attachment ID on success, WP_Error on failure.
     */
    public function wacdmg_save_image_to_library( $image_url, $alt_text = '', $post_id = 0 ) {
        // Require the media functions if not already loaded.
        if ( ! function_exists( 'media_sideload_image' ) ) {
            require_once ABSPATH . 'wp-admin/includes/media.php';
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/image.php';
        }

        // Sanitize inputs.
        $image_url = esc_url_raw( $image_url );
        $alt_text  = sanitize_text_field( $alt_text );
        $post_id   = intval( $post_id );

        // Sideload the image into the Media Library.
        $attachment_id = media_sideload_image( $image_url, $post_id, $alt_text, 'id' );

        if ( is_wp_error( $attachment_id ) ) {
            return $attachment_id;
        }

        // Write alt text to the attachment meta.
        if ( ! empty( $alt_text ) ) {
            update_post_meta( $attachment_id, '_wp_attachment_image_alt', $alt_text );
        }

        // Store the AI-generated origin URL as meta for reference.
        update_post_meta( $attachment_id, '_wacdmg_ai_source_url', $image_url );
        update_post_meta( $attachment_id, '_wacdmg_ai_generated', true );

        return $attachment_id;
    }

    /**
     * Get all AI-generated images from the Media Library.
     *
     * @param int $limit Max number of images to return.
     * @return array Array of attachment data.
     */
    public function wacdmg_get_ai_images( $limit = 20 ) {
        $args = array(
            'post_type'      => 'attachment',
            'post_mime_type' => 'image',
            'post_status'    => 'inherit',
            'posts_per_page' => $limit,
            'meta_key'       => '_wacdmg_ai_generated',
            'meta_value'     => '1',
            'orderby'        => 'date',
            'order'          => 'DESC',
        );

        $attachments = get_posts( $args );
        $result      = array();

        foreach ( $attachments as $attachment ) {
            $result[] = array(
                'id'         => $attachment->ID,
                'url'        => wp_get_attachment_url( $attachment->ID ),
                'thumbnail'  => wp_get_attachment_image_url( $attachment->ID, 'medium' ),
                'title'      => $attachment->post_title,
                'alt'        => get_post_meta( $attachment->ID, '_wp_attachment_image_alt', true ),
                'source_url' => get_post_meta( $attachment->ID, '_wacdmg_ai_source_url', true ),
                'date'       => $attachment->post_date,
            );
        }

        return $result;
    }
}
