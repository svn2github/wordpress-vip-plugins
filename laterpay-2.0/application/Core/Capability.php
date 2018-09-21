<?php

/**
 * LaterPay core capabilities.
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
class LaterPay_Core_Capability
{
    protected $allowed_capabilities = array(
        'laterpay_edit_individual_price',
        'laterpay_edit_teaser_content',
        'laterpay_has_full_access_to_content',
    );

    /**
     * Create LaterPay roles for the various plugin versions.
     *
     * @return void
     */
    public function populate_roles() {
        $this->populate_roles_0951();
    }

    /**
     * Update LaterPay roles.
     *
     * @param array $roles
     *
     * @return void
     */
    public function update_roles( array $roles ) {
        foreach ( $roles as $role => $capabilities ) {
            $role = get_role( $role );
            if ( empty( $role ) ) {
                continue;
            }
            if ( is_array( $capabilities ) && isset( $capabilities['add'] ) ) {
                $collection = (array) $capabilities['add'];
                foreach ( $collection as $capability ) {
                    if ( ! $role->has_cap( $capability ) && in_array( $capability, $this->allowed_capabilities, true ) ) {
                        $role->add_cap( $capability );
                    }
                }
            }
            if ( is_array( $capabilities ) && isset( $capabilities['remove'] ) ) {
                $collection = (array) $capabilities['remove'];
                foreach ( $collection as $capability ) {
                    if ( $role->has_cap( $capability ) && in_array( $capability, $this->allowed_capabilities, true ) ) {
                        $role->remove_cap( $capability );
                    }
                }
            }
        }
    }

    /**
    * Create and modify LaterPay roles.
    *
    * @return void
    */
    protected function populate_roles_0951() {
        $roles = array( 'administrator', 'editor' );
        foreach ( $roles as $role ) {
            $role = get_role( $role );
            if ( empty( $role ) ) {
                continue;
            }

            $role->add_cap( 'laterpay_edit_individual_price' );
            $role->add_cap( 'laterpay_edit_teaser_content' );
            $role->add_cap( 'laterpay_has_full_access_to_content' );
        }

        $roles = array( 'author', 'contributor' );
        foreach ( $roles as $role ) {
            $role = get_role( $role );
            if ( empty( $role ) ) {
                continue;
            }

            $role->add_cap( 'laterpay_edit_teaser_content' );
        }

        $role = get_role( 'author' );
        if ( ! empty( $role ) ) {
            $role->add_cap( 'laterpay_edit_individual_price' );
        }
    }
}
