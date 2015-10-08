<?php

/**
 * Erp Settings page main class
 */
class ERP_Settings_Page {

    protected $id    = '';
    protected $label = '';
    protected $single_option = false;

    /**
     * Get id
     *
     * @return string
     */
    public function get_id() {
        return $this->id;
    }

    /**
     * Get saved option id
     *
     * @return string
     */
    public function get_option_id() {
        return 'erp_settings_' . $this->id;
    }

    /**
     * Get label
     *
     * @return string
     */
    public function get_label() {
        return $this->label;
    }

    /**
     * Get settings array
     *
     * @return array
     */
    public function get_settings() {
        return array();
    }

    public function save() {
        global $current_class;

        if ( isset( $_POST['_wpnonce']) && wp_verify_nonce( $_POST['_wpnonce'], 'erp-settings-nonce' ) ) {
            $options = $this->get_settings();

            // Options to update will be stored here
            $update_options = array();

            // Loop options and get values to save
            foreach ( $options as $value ) {

                if ( ! isset( $value['id'] ) )
                    continue;

                $type = isset( $value['type'] ) ? sanitize_title( $value['type'] ) : '';

                // Get the option name
                $option_value = null;

                switch ( $type ) {

                    // Standard types
                    case "checkbox" :

                        if ( isset( $_POST[ $value['id'] ] ) ) {
                            $option_value = 'yes';
                        } else {
                            $option_value = 'no';
                        }

                        break;

                    case "textarea" :

                        if ( isset( $_POST[$value['id']] ) ) {
                            $option_value = wp_kses_post( trim( stripslashes( $_POST[ $value['id'] ] ) ) );
                        } else {
                            $option_value = '';
                        }

                        break;

                    case 'multicheck':

                        if ( isset( $_POST[$value['id']] ) ) {
                            $option_value = array_map( 'sanitize_text_field', array_map( 'stripslashes', (array) $_POST[ $value['id'] ] ) );
                        } else {
                            $option_value = array();
                        }

                        break;

                    case "text" :
                    case 'email':
                    case 'number':
                    case "select" :
                    case "color" :
                    case 'password' :
                    case "single_select_page" :
                    case "image" :
                    case 'radio' :

                       if ( isset( $_POST[$value['id']] ) ) {
                            $option_value = sanitize_text_field( stripslashes( $_POST[ $value['id'] ] ) );
                        } else {
                            $option_value = '';
                        }

                        break;

                    // Special types
                    case "multiselect" :

                        // Get countries array
                        if ( isset( $_POST[ $value['id'] ] ) )
                            $selected_countries = array_map( 'sanitize_text_field', array_map( 'stripslashes', (array) $_POST[ $value['id'] ] ) );
                        else
                            $selected_countries = array();

                        $option_value = $selected_countries;

                        break;

                    // Custom handling
                    default :

                        do_action( 'erp_update_option_' . $type, $value );

                        break;

                }

                if ( ! is_null( $option_value ) ) {
                    // Check if option is an array
                    if ( strstr( $value['id'], '[' ) ) {

                        parse_str( $value['id'], $option_array );

                        // Option name is first key
                        $option_name = current( array_keys( $option_array ) );

                        // Get old option value
                        if ( ! isset( $update_options[ $option_name ] ) )
                             $update_options[ $option_name ] = get_option( $option_name, array() );

                        if ( ! is_array( $update_options[ $option_name ] ) )
                            $update_options[ $option_name ] = array();

                        // Set keys and value
                        $key = key( $option_array[ $option_name ] );

                        $update_options[ $option_name ][ $key ] = $option_value;

                    // Single value
                    } else {
                        $update_options[ $value['id'] ] = $option_value;
                    }
                }

                // Custom handling
                do_action( 'erp_update_option', $value );
            }

            // finally, update the option
            if ( $update_options ) {

                if ( $this->single_option ) {

                    foreach ( $update_options as $name => $value ) {
                        update_option( $name, $value );
                    }

                } else {
                    update_option( $this->get_option_id(), $update_options );
                }

            }
        }
    }

    /**
     * Get sections
     *
     * @return array
     */
    public function get_sections() {
        return array();
    }

    public function output() {
        $fields = $this->get_settings();

        $defaults = array(
            'id'                => '',
            'title'             => '',
            'class'             => '',
            'css'               => '',
            'default'           => '',
            'desc'              => '',
            'tooltip'           => false,
            'custom_attributes' => array()
        );

        if ( $fields ) {
            foreach ($fields as $field) {

                if ( ! isset( $field['type'] ) ) {
                    continue;
                }

                $value = wp_parse_args( $field, $defaults );

                // Custom attribute handling
                $custom_attributes = array();

                if ( ! empty( $value['custom_attributes'] ) && is_array( $value['custom_attributes'] ) ) {
                    foreach ( $value['custom_attributes'] as $attribute => $attribute_value ) {
                        $custom_attributes[] = esc_attr( $attribute ) . '="' . esc_attr( $attribute_value ) . '"';
                    }
                }

                // Description handling
                if ( $value['tooltip'] === true ) {
                    $description = '';
                    $tip = $value['desc'];
                } elseif ( ! empty( $value['tooltip'] ) ) {
                    $description = $value['desc'];
                    $tip = $value['tooltip'];
                } elseif ( ! empty( $value['desc'] ) ) {
                    $description = $value['desc'];
                    $tip = '';
                } else {
                    $description = $tip = '';
                }

                if ( $description && in_array( $value['type'], array( 'textarea', 'radio' ) ) ) {
                    $description = '<p class="description">' . wp_kses_post( $description ) . '</p>';
                } elseif ( $description && in_array( $value['type'], array( 'checkbox' ) ) ) {
                    $description =  wp_kses_post( $description );
                } elseif ( $description ) {
                    $description = '<p class="description">' . wp_kses_post( $description ) . '</p>';
                }

                if ( $tip && in_array( $value['type'], array( 'checkbox' ) ) ) {

                    $tip = '<p class="description">' . $tip . '</p>';

                } elseif ( $tip ) {

                    $tip = '<img class="help_tip" data-tip="' . esc_attr( $tip ) . '" src="' . WPERP_ASSETS . '/images/help.png" height="16" width="16" />';

                }

                // Switch based on type
                switch( $value['type'] ) {

                    // Section Titles
                    case 'title':
                        if ( ! empty( $value['title'] ) ) {
                            echo '<h3>' . esc_html( $value['title'] ) . '</h3>';
                        }
                        if ( ! empty( $value['desc'] ) ) {
                            echo wpautop( wptexturize( wp_kses_post( $value['desc'] ) ) );
                        }
                        echo '<table class="form-table">'. "\n\n";
                        if ( ! empty( $value['id'] ) ) {
                            do_action( 'erp_settings_' . sanitize_title( $value['id'] ) );
                        }
                    break;

                    // Section Ends
                    case 'sectionend':
                        if ( ! empty( $value['id'] ) ) {
                            do_action( 'erp_settings_' . sanitize_title( $value['id'] ) . '_end' );
                        }
                        echo '</table>';

                        if ( ! empty( $value['id'] ) ) {
                            do_action( 'erp_settings_' . sanitize_title( $value['id'] ) . '_after' );
                        }
                    break;

                    // Standard text inputs and subtypes like 'number'
                    case 'text':
                    case 'email':
                    case 'number':
                    case 'color' :
                    case 'password' :

                        $type           = $value['type'];
                        $class          = '';
                        $option_value   = $this->get_option( $value['id'], $value['default'] );

                        if ( empty( $value['class'] ) ) {
                            $value['class'] = 'regular-text';
                        }

                        if ( $value['type'] == 'color' ) {
                            $type = 'text';
                            $value['class'] .= 'colorpick';
                        }

                        ?><tr valign="top">
                            <th scope="row" class="titledesc">
                                <label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?></label>
                                <?php echo $tip; ?>
                            </th>
                            <td class="forminp forminp-<?php echo sanitize_title( $value['type'] ) ?>">
                                <input
                                    name="<?php echo esc_attr( $value['id'] ); ?>"
                                    id="<?php echo esc_attr( $value['id'] ); ?>"
                                    type="<?php echo esc_attr( $type ); ?>"
                                    style="<?php echo esc_attr( $value['css'] ); ?>"
                                    value="<?php echo esc_attr( $option_value ); ?>"
                                    class="<?php echo esc_attr( $value['class'] ); ?>"
                                    <?php echo implode( ' ', $custom_attributes ); ?>
                                    /> <?php echo $description; ?>
                            </td>
                        </tr><?php
                    break;

                    case 'image' :

                        $option_value   = (int) $this->get_option( $value['id'], 0 );
                        $image_url = $option_value ? wp_get_attachment_url( $option_value ) : '';

                        ?><tr valign="top">
                            <th scope="row" class="titledesc">
                                <label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?></label>
                                <?php echo $tip; ?>
                            </th>

                            <td>
                                <div class="image-wrap<?php echo $option_value ? '' : ' erp-hide'; ?>">
                                    <input type="hidden" class="erp-file-field" name="<?php echo esc_attr( $value['id'] ); ?>" value="<?php echo esc_attr( $option_value ); ?>">
                                    <img class="erp-option-image" src="<?php echo esc_url( $image_url ); ?>">

                                    <a class="erp-remove-image" title="<?php _e( 'Delete this image?', 'erp' ); ?>">&times;</a>
                                </div>

                                <div class="button-area<?php echo $option_value ? ' erp-hide' : ''; ?>">
                                    <a href="#" class="erp-image-upload button"><?php _e( 'Upload Image', 'erp' ); ?></a>
                                    <?php echo $description; ?>
                                </div>

                            </td>


                        </tr><?php
                    break;

                    // Textarea
                    case 'textarea':

                        $option_value   = $this->get_option( $value['id'], $value['default'] );

                        ?><tr valign="top">
                            <th scope="row" class="titledesc">
                                <label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?></label>
                                <?php echo $tip; ?>
                            </th>
                            <td class="forminp forminp-<?php echo sanitize_title( $value['type'] ) ?>">

                                <textarea
                                    name="<?php echo esc_attr( $value['id'] ); ?>"
                                    id="<?php echo esc_attr( $value['id'] ); ?>"
                                    style="<?php echo esc_attr( $value['css'] ); ?>"
                                    class="<?php echo esc_attr( $value['class'] ); ?>"
                                    <?php echo implode( ' ', $custom_attributes ); ?>
                                    ><?php echo esc_textarea( $option_value );  ?></textarea>

                                    <?php echo $description; ?>
                            </td>
                        </tr><?php
                    break;

                    // Select boxes
                    case 'select' :
                    case 'multiselect' :

                        $option_value   = $this->get_option( $value['id'], $value['default'] );

                        ?><tr valign="top">
                            <th scope="row" class="titledesc">
                                <label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?></label>
                                <?php echo $tip; ?>
                            </th>
                            <td class="forminp forminp-<?php echo sanitize_title( $value['type'] ) ?>">
                                <select
                                    name="<?php echo esc_attr( $value['id'] ); ?><?php if ( $value['type'] == 'multiselect' ) echo '[]'; ?>"
                                    id="<?php echo esc_attr( $value['id'] ); ?>"
                                    style="<?php echo esc_attr( $value['css'] ); ?>"
                                    class="<?php echo esc_attr( $value['class'] ); ?>"
                                    <?php echo implode( ' ', $custom_attributes ); ?>
                                    <?php if ( $value['type'] == 'multiselect' ) echo 'multiple="multiple"'; ?>
                                    >
                                    <?php
                                        foreach ( $value['options'] as $key => $val ) {
                                            ?>
                                            <option value="<?php echo esc_attr( $key ); ?>" <?php

                                                if ( is_array( $option_value ) )
                                                    selected( in_array( $key, $option_value ), true );
                                                else
                                                    selected( $option_value, $key );

                                            ?>><?php echo $val ?></option>
                                            <?php
                                        }
                                    ?>
                               </select> <?php echo $description; ?>
                            </td>
                        </tr><?php
                    break;

                    // Radio inputs
                    case 'radio' :

                        $option_value   = $this->get_option( $value['id'], $value['default'] );

                        ?><tr valign="top">
                            <th scope="row" class="titledesc">
                                <label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?></label>
                                <?php echo $tip; ?>
                            </th>
                            <td class="forminp forminp-<?php echo sanitize_title( $value['type'] ) ?>">
                                <fieldset>
                                    <?php echo $description; ?>
                                    <ul>
                                    <?php
                                        foreach ( $value['options'] as $key => $val ) {
                                            ?>
                                            <li>
                                                <label><input
                                                    name="<?php echo esc_attr( $value['id'] ); ?>"
                                                    value="<?php echo $key; ?>"
                                                    type="radio"
                                                    style="<?php echo esc_attr( $value['css'] ); ?>"
                                                    class="<?php echo esc_attr( $value['class'] ); ?>"
                                                    <?php echo implode( ' ', $custom_attributes ); ?>
                                                    <?php checked( $key, $option_value ); ?>
                                                    /> <?php echo $val ?></label>
                                            </li>
                                            <?php
                                        }
                                    ?>
                                    </ul>
                                </fieldset>
                            </td>
                        </tr><?php
                    break;


                    // multi check
                    case 'multicheck' :

                        $default = is_array( $value['default'] ) ? $value['default'] : array();
                        $option_value   = $this->get_option( $value['id'], $default );

                        ?><tr valign="top">
                            <th scope="row" class="titledesc">
                                <label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?></label>
                                <?php echo $tip; ?>
                            </th>
                            <td class="forminp forminp-<?php echo sanitize_title( $value['type'] ) ?>">
                                <fieldset>
                                    <ul>
                                    <?php
                                        foreach ( $value['options'] as $key => $val ) {
                                            ?>
                                            <li>
                                                <label><input
                                                    name="<?php echo esc_attr( $value['id'] ); ?>[<?php echo esc_attr( $key ); ?>]"
                                                    value="<?php echo $key; ?>"
                                                    type="checkbox"
                                                    style="<?php echo esc_attr( $value['css'] ); ?>"
                                                    class="<?php echo esc_attr( $value['class'] ); ?>"
                                                    <?php echo implode( ' ', $custom_attributes ); ?>
                                                    <?php checked( in_array( $key, $option_value ) ); ?>
                                                    /> <?php echo $val ?></label>
                                            </li>
                                            <?php
                                        }
                                    ?>
                                    </ul>
                                </fieldset>
                                <?php echo $description; ?>
                            </td>
                        </tr><?php
                    break;

                    // Checkbox input
                    case 'checkbox' :

                        $option_value    = $this->get_option( $value['id'], $value['default'] );
                        $visbility_class = array();

                        if ( ! isset( $value['hide_if_checked'] ) ) {
                            $value['hide_if_checked'] = false;
                        }
                        if ( ! isset( $value['show_if_checked'] ) ) {
                            $value['show_if_checked'] = false;
                        }
                        if ( $value['hide_if_checked'] == 'yes' || $value['show_if_checked'] == 'yes' ) {
                            $visbility_class[] = 'hidden_option';
                        }
                        if ( $value['hide_if_checked'] == 'option' ) {
                            $visbility_class[] = 'hide_options_if_checked';
                        }
                        if ( $value['show_if_checked'] == 'option' ) {
                            $visbility_class[] = 'show_options_if_checked';
                        }

                        if ( ! isset( $value['checkboxgroup'] ) || 'start' == $value['checkboxgroup'] ) {
                            ?>
                                <tr valign="top" class="<?php echo esc_attr( implode( ' ', $visbility_class ) ); ?>">
                                    <th scope="row" class="titledesc"><?php echo esc_html( $value['title'] ) ?></th>
                                    <td class="forminp forminp-checkbox">
                                        <fieldset>
                            <?php
                        } else {
                            ?>
                                <fieldset class="<?php echo esc_attr( implode( ' ', $visbility_class ) ); ?>">
                            <?php
                        }

                        if ( ! empty( $value['title'] ) ) {
                            ?>
                                <legend class="screen-reader-text"><span><?php echo esc_html( $value['title'] ) ?></span></legend>
                            <?php
                        }

                        ?>
                            <label for="<?php echo $value['id'] ?>">
                                <input
                                    name="<?php echo esc_attr( $value['id'] ); ?>"
                                    id="<?php echo esc_attr( $value['id'] ); ?>"
                                    type="checkbox"
                                    value="1"
                                    <?php checked( $option_value, 'yes'); ?>
                                    <?php echo implode( ' ', $custom_attributes ); ?>
                                /> <?php echo $description ?>
                            </label> <?php echo $tip; ?>
                        <?php

                        if ( ! isset( $value['checkboxgroup'] ) || 'end' == $value['checkboxgroup'] ) {
                                        ?>
                                        </fieldset>
                                    </td>
                                </tr>
                            <?php
                        } else {
                            ?>
                                </fieldset>
                            <?php
                        }
                    break;

                    // Image width settings
                    case 'image_width' :

                        $width  = $this->get_option( $value['id'] . '[width]', $value['default']['width'] );
                        $height = $this->get_option( $value['id'] . '[height]', $value['default']['height'] );
                        $crop   = checked( 1, $this->get_option( $value['id'] . '[crop]', $value['default']['crop'] ), false );

                        ?><tr valign="top">
                            <th scope="row" class="titledesc"><?php echo esc_html( $value['title'] ) ?> <?php echo $tip; ?></th>
                            <td class="forminp image_width_settings">

                                <input name="<?php echo esc_attr( $value['id'] ); ?>[width]" id="<?php echo esc_attr( $value['id'] ); ?>-width" type="text" size="3" value="<?php echo $width; ?>" /> &times; <input name="<?php echo esc_attr( $value['id'] ); ?>[height]" id="<?php echo esc_attr( $value['id'] ); ?>-height" type="text" size="3" value="<?php echo $height; ?>" />px

                                <label><input name="<?php echo esc_attr( $value['id'] ); ?>[crop]" id="<?php echo esc_attr( $value['id'] ); ?>-crop" type="checkbox" <?php echo $crop; ?> /> <?php _e( 'Hard Crop?', 'erp' ); ?></label>

                                </td>
                        </tr><?php
                    break;

                    // Single page selects
                    case 'single_select_page' :

                        $args = array(
                            'name'             => $value['id'],
                            'id'               => $value['id'],
                            'sort_column'      => 'menu_order',
                            'sort_order'       => 'ASC',
                            'show_option_none' => ' ',
                            'class'            => $value['class'],
                            'echo'             => false,
                            'selected'         => absint( $this->get_option( $value['id'] ) )
                       );

                        if ( isset( $value['args'] ) ) {
                            $args = wp_parse_args( $value['args'], $args );
                        }

                        ?><tr valign="top" class="single_select_page">
                            <th scope="row" class="titledesc"><?php echo esc_html( $value['title'] ) ?> <?php echo $tip; ?></th>
                            <td class="forminp">
                                <?php echo str_replace(' id=', " data-placeholder='" . __( 'Select a page&hellip;', 'erp' ) .  "' style='" . $value['css'] . "' class='" . $value['class'] . "' id=", wp_dropdown_pages( $args ) ); ?> <?php echo $description; ?>
                            </td>
                        </tr><?php
                    break;

                    // Default: run an action
                    default:
                        do_action( 'erp_admin_field_' . $value['type'], $value );
                    break;
                }
            }
        }
    }

    /**
     * Get a setting from the settings API.
     *
     * @param mixed $option
     * @return string
     */
    public function get_option( $option_name, $default = '' ) {

        if ( $this->single_option ) {

            $option_value = get_option( $option_name, $default );

        } else {

            $options = get_option( $this->get_option_id(), array() );
            $option_value = isset( $options[$option_name] ) ? $options[$option_name] : $default;
        }


        if ( is_array( $option_value ) ) {
            $option_value = array_map( 'stripslashes', $option_value );
        } elseif ( ! is_null( $option_value ) ) {
            $option_value = stripslashes( $option_value );
        }

        return $option_value;
    }
}