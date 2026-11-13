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
     * Maximum accepted image size in bytes.
     *
     * @var int
     */
    const MAX_IMAGE_BYTES = 26214400; // 25 MB.

    /**
     * Download a remote image and save it to the WordPress Media Library.
     *
     * @param string $image_url The URL of the AI-generated image.
     * @param string $alt_text  Alt text / title to use for the attachment.
     * @param int    $post_id   Optional: attach the image to this post.
     * @return int|WP_Error The attachment ID on success, WP_Error on failure.
     */
    public function wacdmg_save_image_to_library( $image_url, $alt_text = '', $post_id = 0 ) {
        $image_url = esc_url_raw( (string) $image_url );
        if ( '' === $image_url ) {
            return new WP_Error( 'wacdmg_image_url', __( 'The image provider did not return an image.', 'wacdmg-ai-content-assistant' ) );
        }

        $response = wp_safe_remote_get( $image_url, array(
            'timeout'             => 60,
            'limit_response_size' => self::MAX_IMAGE_BYTES,
        ) );
        if ( is_wp_error( $response ) ) {
            return $response;
        }
        if ( 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
            return new WP_Error( 'wacdmg_image_download', __( 'Could not download the generated image.', 'wacdmg-ai-content-assistant' ) );
        }

        $attachment_id = $this->wacdmg_save_image_data( wp_remote_retrieve_body( $response ), $alt_text, $post_id );
        if ( ! is_wp_error( $attachment_id ) ) {
            update_post_meta( $attachment_id, '_wacdmg_ai_source_url', $image_url );
        }
        return $attachment_id;
    }

    /**
     * Save raw image bytes to the WordPress Media Library.
     *
     * The file type is detected from the bytes, not trusted from the provider.
     *
     * @param string $binary   Raw image data.
     * @param string $alt_text Alt text / title for the attachment (usually the prompt).
     * @param int    $post_id  Optional parent post ID.
     * @return int|WP_Error Attachment ID on success.
     */
    public function wacdmg_save_image_data( $binary, $alt_text = '', $post_id = 0 ) {
        if ( ! is_string( $binary ) || '' === $binary ) {
            return new WP_Error( 'wacdmg_image_empty', __( 'The image provider returned an empty image.', 'wacdmg-ai-content-assistant' ) );
        }
        if ( strlen( $binary ) > self::MAX_IMAGE_BYTES ) {
            return new WP_Error( 'wacdmg_image_size', __( 'The generated image is too large to save.', 'wacdmg-ai-content-assistant' ) );
        }

        if ( ! function_exists( 'media_handle_sideload' ) ) {
            require_once ABSPATH . 'wp-admin/includes/media.php';
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/image.php';
        }

        $tmp = wp_tempnam( 'wacdmg-ai-image' );
        if ( ! $tmp || false === file_put_contents( $tmp, $binary ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
            return new WP_Error( 'wacdmg_image_tmp', __( 'Could not write a temporary file for the image.', 'wacdmg-ai-content-assistant' ) );
        }

        $extensions = array(
            'image/png'  => 'png',
            'image/jpeg' => 'jpg',
            'image/webp' => 'webp',
        );
        $mime = wp_get_image_mime( $tmp );
        if ( ! $mime || ! isset( $extensions[ $mime ] ) ) {
            wp_delete_file( $tmp );
            return new WP_Error( 'wacdmg_image_type', __( 'The generated file is not a supported image type.', 'wacdmg-ai-content-assistant' ) );
        }

        $alt_text = sanitize_text_field( (string) $alt_text );
        $slug     = $alt_text ? sanitize_title( wp_trim_words( $alt_text, 8, '' ) ) : '';
        $filename = 'ai-' . ( $slug ? $slug : 'image' ) . '-' . gmdate( 'YmdHis' ) . '.' . $extensions[ $mime ];

        $file_array = array(
            'name'     => sanitize_file_name( $filename ),
            'tmp_name' => $tmp,
        );

        $title = $alt_text ? wp_html_excerpt( $alt_text, 100, '…' ) : __( 'AI generated image', 'wacdmg-ai-content-assistant' );

        $attachment_id = media_handle_sideload( $file_array, intval( $post_id ), $title );
        if ( is_wp_error( $attachment_id ) ) {
            if ( file_exists( $tmp ) ) {
                wp_delete_file( $tmp );
            }
            return $attachment_id;
        }

        if ( '' !== $alt_text ) {
            update_post_meta( $attachment_id, '_wp_attachment_image_alt', wp_html_excerpt( $alt_text, 250, '' ) );
        }
        update_post_meta( $attachment_id, '_wacdmg_ai_generated', true );

        return (int) $attachment_id;
    }

    /**
     * Longest edge, in pixels, of the image sent to vision models.
     *
     * @var int
     */
    const VISION_MAX_EDGE = 1024;

    /**
     * Largest image payload sent to vision models, in bytes.
     *
     * @var int
     */
    const VISION_MAX_BYTES = 4194304; // 4 MB.

    /**
     * Load an attachment as a base64 image suitable for vision models.
     *
     * Uses a web-sized copy (768px "medium_large" when available). Files that are not stored
     * locally (offloaded media) are downloaded from their URL. Anything that is not JPEG, PNG,
     * WebP, or GIF, or that is too large, is converted to a resized JPEG.
     *
     * @param int $attachment_id Attachment ID.
     * @return array|WP_Error array( 'mime' => string, 'data' => base64 string ) or an error.
     */
    public function wacdmg_get_image_for_vision( $attachment_id ) {
        $attachment_id = intval( $attachment_id );
        if ( ! $attachment_id || ! wp_attachment_is_image( $attachment_id ) ) {
            return new WP_Error( 'wacdmg_not_image', __( 'Choose an image from the Media Library first. AI alt text needs to see the image itself.', 'wacdmg-ai-content-assistant' ) );
        }

        $bytes = '';
        $tmp   = '';

        // 1. A local web-sized copy, if one exists on disk.
        $path = $this->wacdmg_get_local_image_path( $attachment_id );
        if ( $path ) {
            $bytes = file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
        }

        // 2. Otherwise download it (e.g. media offloaded to cloud storage).
        if ( ! $bytes ) {
            $url = wp_get_attachment_image_url( $attachment_id, 'medium_large' );
            if ( ! $url ) {
                $url = wp_get_attachment_url( $attachment_id );
            }
            if ( $url ) {
                $response = wp_safe_remote_get( $url, array(
                    'timeout'             => 30,
                    'limit_response_size' => self::MAX_IMAGE_BYTES,
                ) );
                if ( ! is_wp_error( $response ) && 200 === (int) wp_remote_retrieve_response_code( $response ) ) {
                    $bytes = wp_remote_retrieve_body( $response );
                }
            }
        }

        if ( ! $bytes ) {
            return new WP_Error( 'wacdmg_image_unreadable', __( 'Could not read this image file.', 'wacdmg-ai-content-assistant' ) );
        }

        $tmp = wp_tempnam( 'wacdmg-vision' );
        if ( ! $tmp || false === file_put_contents( $tmp, $bytes ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
            return new WP_Error( 'wacdmg_image_tmp', __( 'Could not write a temporary file for the image.', 'wacdmg-ai-content-assistant' ) );
        }

        $mime      = wp_get_image_mime( $tmp );
        $supported = array( 'image/jpeg', 'image/png', 'image/webp', 'image/gif' );
        $size      = function_exists( 'wp_getimagesize' ) ? wp_getimagesize( $tmp ) : false;
        $too_big   = strlen( $bytes ) > self::VISION_MAX_BYTES
            || ( $size && max( (int) $size[0], (int) $size[1] ) > self::VISION_MAX_EDGE * 2 );

        if ( ! in_array( $mime, $supported, true ) || $too_big ) {
            $converted = $this->wacdmg_convert_to_jpeg( $tmp );
            wp_delete_file( $tmp );
            return $converted;
        }

        wp_delete_file( $tmp );
        return array(
            'mime' => $mime,
            'data' => base64_encode( $bytes ),
        );
    }

    /**
     * Path to a local web-sized copy of an attachment, or the original when no smaller size exists.
     *
     * @param int $attachment_id Attachment ID.
     * @return string Empty string when no local file exists.
     */
    private function wacdmg_get_local_image_path( $attachment_id ) {
        $original = get_attached_file( $attachment_id );
        if ( ! $original ) {
            return '';
        }

        $meta = wp_get_attachment_metadata( $attachment_id );
        if ( is_array( $meta ) && ! empty( $meta['sizes'] ) ) {
            foreach ( array( 'medium_large', 'large', 'medium' ) as $size ) {
                if ( ! empty( $meta['sizes'][ $size ]['file'] ) ) {
                    $candidate = path_join( dirname( $original ), $meta['sizes'][ $size ]['file'] );
                    if ( file_exists( $candidate ) ) {
                        return $candidate;
                    }
                }
            }
        }

        return file_exists( $original ) ? $original : '';
    }

    /**
     * Convert an image file to a resized JPEG and return it base64-encoded.
     *
     * @param string $path Source file path.
     * @return array|WP_Error
     */
    private function wacdmg_convert_to_jpeg( $path ) {
        $editor = wp_get_image_editor( $path );
        if ( is_wp_error( $editor ) ) {
            return new WP_Error( 'wacdmg_image_convert', __( 'This image format cannot be sent to the AI provider.', 'wacdmg-ai-content-assistant' ) );
        }
        $editor->resize( self::VISION_MAX_EDGE, self::VISION_MAX_EDGE, false );

        $base  = wp_tempnam( 'wacdmg-vision-jpg' );
        $saved = $editor->save( $base . '.jpg', 'image/jpeg' );
        wp_delete_file( $base );
        if ( is_wp_error( $saved ) || empty( $saved['path'] ) || ! file_exists( $saved['path'] ) ) {
            return new WP_Error( 'wacdmg_image_convert', __( 'This image format cannot be sent to the AI provider.', 'wacdmg-ai-content-assistant' ) );
        }

        $bytes = file_get_contents( $saved['path'] ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
        wp_delete_file( $saved['path'] );

        return array(
            'mime' => 'image/jpeg',
            'data' => base64_encode( $bytes ),
        );
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
