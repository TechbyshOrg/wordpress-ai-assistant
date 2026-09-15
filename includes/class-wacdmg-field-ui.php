<?php
/**
 * Field UI injections for ACF, Meta Box, and related admin screens.
 *
 * @package WACDMG
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WACDMG_Field_UI {

    /**
     * Hook field injections.
     */
    public function __construct() {
        add_action( 'acf/render_field', array( $this, 'wacdmg_acf_render_field' ), 20 );
        add_filter( 'rwmb_text_html', array( $this, 'wacdmg_metabox_after_html' ), 20, 2 );
        add_filter( 'rwmb_textarea_html', array( $this, 'wacdmg_metabox_after_html' ), 20, 2 );
        add_filter( 'rwmb_wysiwyg_html', array( $this, 'wacdmg_metabox_after_html' ), 20, 2 );
        add_filter( 'rwmb_wysiwyg_after', array( $this, 'wacdmg_metabox_after_html' ), 20, 2 );
    }

    /**
     * Buttons HTML for a third-party field.
     *
     * @param array $args Adapter, key, label.
     * @return string
     */
    public static function wacdmg_buttons_html( $args ) {
        $adapter = isset( $args['adapter'] ) ? sanitize_key( $args['adapter'] ) : 'textarea';
        $key     = isset( $args['key'] ) ? esc_attr( $args['key'] ) : '';
        $label   = isset( $args['label'] ) ? $args['label'] : __( 'field', 'wacdmg-ai-content-assistant' );
        $target  = isset( $args['target'] ) ? esc_attr( $args['target'] ) : '';

        return sprintf(
            '<p class="wacdmg-field-ai-row">'
            . '<button type="button" class="button wacdmg-field-ai" data-action="generate" data-adapter="%1$s" data-field-key="%2$s" data-target="%3$s" data-field-label="%4$s">%5$s</button> '
            . '<button type="button" class="button wacdmg-field-ai" data-action="improve" data-adapter="%1$s" data-field-key="%2$s" data-target="%3$s" data-field-label="%4$s">%6$s</button> '
            . '<button type="button" class="button wacdmg-field-ai" data-action="translate" data-adapter="%1$s" data-field-key="%2$s" data-target="%3$s" data-field-label="%4$s">%7$s</button> '
            . '<span class="wacdmg-field-ai-status"></span>'
            . '</p>',
            esc_attr( $adapter ),
            $key,
            $target,
            esc_attr( wp_strip_all_tags( $label ) ),
            esc_html__( 'Generate with AI', 'wacdmg-ai-content-assistant' ),
            esc_html__( 'Improve', 'wacdmg-ai-content-assistant' ),
            esc_html__( 'Translate', 'wacdmg-ai-content-assistant' )
        );
    }

    /**
     * ACF text / textarea / wysiwyg buttons.
     *
     * @param array $field Field settings.
     */
    public function wacdmg_acf_render_field( $field ) {
        if ( empty( $field['type'] ) || empty( $field['key'] ) ) {
            return;
        }
        $allowed = array( 'text', 'textarea', 'wysiwyg' );
        if ( ! in_array( $field['type'], $allowed, true ) ) {
            return;
        }
        echo self::wacdmg_buttons_html( array(
            'adapter' => 'acf',
            'key'     => $field['key'],
            'label'   => ! empty( $field['label'] ) ? $field['label'] : $field['type'],
        ) );
    }

    /**
     * Meta Box text / textarea / wysiwyg buttons.
     *
     * @param string $html  Existing HTML.
     * @param array  $field Field settings.
     * @return string
     */
    public function wacdmg_metabox_after_html( $html, $field = array() ) {
        if ( ! is_array( $field ) ) {
            return $html;
        }
        $type = isset( $field['type'] ) ? $field['type'] : '';
        if ( ! in_array( $type, array( 'text', 'textarea', 'wysiwyg' ), true ) ) {
            return $html;
        }
        $id = isset( $field['id'] ) ? $field['id'] : '';
        $buttons = self::wacdmg_buttons_html( array(
            'adapter' => ( 'wysiwyg' === $type ) ? 'tinymce' : 'metabox',
            'key'     => $id,
            'target'  => $id ? '#' . $id : '',
            'label'   => ! empty( $field['name'] ) ? $field['name'] : $type,
        ) );
        return $html . $buttons;
    }
}
