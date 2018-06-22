<?php

/**
 * LaterPay user helper.
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
class LaterPay_Helper_User
{

    /**
     * @var mixed Does user want to preview post as visitor or not?
     */
    protected static $_preview_post_as_visitor;

    /**
     * @var
     */
    protected static $_hide_preview_mode_pane;

    /**
     * Check, if the current user has a given capability.
     *
     * @param string           $capability
     * @param WP_Post|int|null $post
     * @param boolean          $strict
     *
     * @return bool
     */
    public static function can( $capability, $post = null, $strict = true ) {
        $allowed = false;

        // try to get WP_Post object, if post id was passed instead of post object
        if ( ! $post instanceof WP_Post ) {
            $post = get_post( $post );
        }

        if ( ! function_exists( 'wp_get_current_user' ) ) {
            include_once( ABSPATH . 'wp-includes/pluggable.php' );
        }

        if ( self::current_user_can( $capability, $post ) ) {
            if ( ! $strict ) {
                // if $strict = false, it's sufficient that a capability is added to the role of the current user
                $allowed = true;
            } else {
                switch ( $capability ) {
                    case 'laterpay_edit_teaser_content':
                        if ( ! empty( $post ) && current_user_can( 'edit_post', $post ) ) {
                            // use edit_post capability as proxy:
                            // - super admins, admins, and editors can edit all posts
                            // - authors and contributors can edit their own posts
                            $allowed = true;
                        }
                        break;

                    case 'laterpay_edit_individual_price':
                        if ( ! empty( $post ) && current_user_can( 'publish_post', $post ) ) {
                            // use publish_post capability as proxy:
                            // - super admins, admins, and editors can publish all posts
                            // - authors can publish their own posts
                            // - contributors can not publish posts
                            $allowed = true;
                        }
                        break;

                    case 'laterpay_has_full_access_to_content':
                        if ( ! empty( $post ) ) {
                            $allowed = true;
                        }
                        break;

                    default:
                        $allowed = true;
                        break;
                }
            }
        }

        return $allowed;
    }

    /**
     * Check, if user has a given capability.
     *
     * @param string       $capability capability
     * @param WP_Post|null $post       post object
     *
     * @return bool
     */
    public static function current_user_can( $capability, $post = null ) {
        if ( current_user_can( $capability ) ) {
            return true;
        }

        $unlimited_access = get_option( 'laterpay_unlimited_access' );
        if ( ! $unlimited_access ) {
            return false;
        }

        // check, if user has a role that has the given capability
        $user = wp_get_current_user();
        if ( ! $user instanceof WP_User || ! $user->roles ) {
            return false;
        }

        if ( ! $post ) {
            return false;
        }

        $has_cap = false;

        foreach ( $user->roles as $role ) {
            if ( ! isset( $unlimited_access[ $role ] ) || false !== array_search( 'none', $unlimited_access[ $role ], true ) ) {
                continue;
            }

            $categories       = array( 'all' );
            // get post categories and their parents
            $post_categories  = wp_get_post_categories( $post->ID );
            foreach ( $post_categories as $post_category_id ) {
                $categories[] = $post_category_id;
                $parents      = LaterPay_Helper_Pricing::get_category_parents( $post_category_id );
                $categories   = array_merge( $categories, $parents );
            }

            if ( array_intersect( $categories, $unlimited_access[ $role ] ) ) {
                $has_cap = true;
                break;
            }
        }

        return $has_cap;
    }

    /**
     * Remove custom capabilities.
     *
     * @return void
     */
    public static function remove_custom_capabilities() {
        global $wp_roles;

        // array of capabilities (capability => option)
        $capabilities = array(
            'laterpay_edit_teaser_content',
            'laterpay_edit_individual_price',
            'laterpay_has_full_access_to_content',
        );

        foreach ( $capabilities as $cap_name ) {
            // loop through roles
            if ( $wp_roles instanceof WP_Roles ) {
                foreach ( array_keys( $wp_roles->roles ) as $role ) {
                    // get role
                    $role = get_role( $role );
                    // remove capability from role
                    $role->remove_cap( $cap_name );
                }
            }
        }
    }

    /**
     * Check, if a given user has a given role.
     *
     * @param string $role    role name
     * @param int    $user_id (optional) ID of a user. Defaults to the current user.
     *
     * @return bool
     */
    public static function user_has_role( $role, $user_id = null ) {

        if ( is_numeric( $user_id ) ) {
            $user = get_userdata( $user_id );
        } else {
            $user = wp_get_current_user();
        }

        if ( empty( $user ) ) {
            return false;
        }

        return in_array( $role, (array) $user->roles, true );
    }

    /**
     * Check, if the current user wants to preview the post as it renders for an admin or as it renders for a visitor.
     *
     * @param null|WP_Post $post
     *
     * @return bool
     */
    public static function preview_post_as_visitor() {
        if ( null === static::$_preview_post_as_visitor ) {
            $preview_post_as_visitor = 0;
            $current_user            = wp_get_current_user();
            if ( $current_user instanceof WP_User ) {
                $preview_post_as_visitor = self::get_user_meta( $current_user->ID, 'laterpay_preview_post_as_visitor' );
                $preview_post_as_visitor = $preview_post_as_visitor ? $preview_post_as_visitor : 0;
                if ( ! empty( $preview_post_as_visitor ) ) {
                    $preview_post_as_visitor = $preview_post_as_visitor[0];
                }
            }
            static::$_preview_post_as_visitor = $preview_post_as_visitor;
        }

        return static::$_preview_post_as_visitor;
    }

    /**
     * Check, if the current user has hidden the post preview mode pane.
     *
     * @return bool
     */
    public static function preview_mode_pane_is_hidden() {
        if (null === static::$_hide_preview_mode_pane) {
            static::$_hide_preview_mode_pane = false;
            $current_user = wp_get_current_user();

            if ( $current_user instanceof WP_User &&
                true === (bool) self::get_user_meta( $current_user->ID, 'laterpay_hide_preview_mode_pane', true )
            ) {
                static::$_hide_preview_mode_pane = true;
            }
        }

        return static::$_hide_preview_mode_pane;
    }

    /**
     * Get user unique id.
     *
     * @return null|string user id
     */
    public static function get_user_unique_id( ) {
        if ( isset( $_COOKIE['laterpay_tracking_code'] ) ) {
            list( $uniqueId, $control_code ) = explode( '.', sanitize_text_field( $_COOKIE['laterpay_tracking_code'] ) );

            // make sure only authorized information is recorded
            if ( $control_code !== md5( $uniqueId . AUTH_SALT ) ) {
                return null;
            }

            return $uniqueId;
        }

        return null;
    }

    /**
     * Remove cookie by name
     *
     * @param $name
     *
     * @return void
     */
    public static function remove_cookie_by_name( $name ) {
        unset( $_COOKIE[ $name ] );
        setcookie(
            $name,
            null,
            time() - 60,
            '/'
        );
    }

    /*
     * Retrieves user_meta based on VIP and Non-VIP environments
     *
     * @param int    $user_id  User ID.
     * @param string $meta_key Metadata key.
     * @param bool   $single   Whether to return a single value.
     *
     * @return mixed Will be an array if $single is false. Will be value of meta data field if $single is true.
     */
    public static function get_user_meta( $user_id, $meta_key, $single = false ) {

        if ( laterpay_check_is_vip_classic() ) {
            $meta_value = get_user_attribute( $user_id, $meta_key );
        } else {
            $meta_value = get_user_meta( $user_id, $meta_key, $single ); // phpcs:ignore
        }

        return $meta_value;
    }

    /*
     * Updates user meta field based on user ID.
     *
     * @param int    $user_id    User ID.
     * @param string $meta_key   Metadata key.
     * @param mixed  $meta_value Metadata value.
     *
     * @return int|bool Meta ID if the key didn't exist, true on successful update, false on failure.
     */
    public static function update_user_meta( $user_id, $meta_key, $meta_value ) {

        if ( laterpay_check_is_vip_classic() ) {
            $result = update_user_attribute( $user_id, $meta_key, $meta_value );
        } else {
            $result = update_user_meta( $user_id, $meta_key, $meta_value ); // phpcs:ignore
        }

        return $result;
    }

}
