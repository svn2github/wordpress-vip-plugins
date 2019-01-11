<?php

/**
 * LaterPay time pass model.
 */
class LaterPay_Model_TimePassWP {
    /**
     * instance of the LaterPay_Model_TimePassWP
     *
     * @var object of LaterPay_Model_TimePassWP
     *
     * @access private
     */
    private static $instance = null;

    /**
     * laterpay timepasses CPT name.
     *
     * @var string
     *
     * @access public
     */
    public static $timepass_post_type = 'lp_passes';

    /**
     * Store hash of query and data for duplicate queries.
     *
     * @var array Internal Cache for Duplicate Queries.
     *
     * @access private
     */
    private static $term_data_store = [];

    /**
     * Blank constructor to avoid creation of new instances.
     *
     * LaterPay_Model_TimePassWP constructor.
     */
    protected function __construct() {}

    /**
     * Singleton to get only one instance
     *
     * @param boolean Force return object of current class
     *
     * @return LaterPay_Model_TimePassWP|LaterPay_Compatibility_TimePass
     */
    public static function get_instance( $force = false ) {

        if ( laterpay_check_is_vip() || laterpay_is_migration_complete() || $force ) {

            if ( ! isset( self::$instance ) ) {
                self::$instance = new self();
            }

            return self::$instance;
        } else {
            return LaterPay_Compatibility_TimePass::get_instance();
        }
    }

    /**
     * Get time pass data.
     *
     * @param int  $time_pass_id time pass id
     * @param bool $ignore_deleted ignore deleted time passes
     *
     * @return array $time_pass array of time pass data
     */
    public function get_pass_data( $time_pass_id, $ignore_deleted = false ) {

        $query_args = array(
            // Meta query is required for post id.
            'meta_key'       => '_lp_id', // phpcs:ignore
            'meta_value'     => $time_pass_id, // phpcs:ignore
            'meta_compare'   => '=',
            'posts_per_page' => 1,
            'post_type'      => self::$timepass_post_type,
            'no_found_rows'  => true,
            'fields'         => 'ids',
        );

        $query = new WP_Query( $query_args );

        $current_posts = $query->posts;

        $id = ( isset( $current_posts[0] ) ) ? $current_posts[0] : '';

        $result = array();

        if ( ! empty( $id ) ) {

            $post = get_post( $id );

            if ( ! empty( $post ) && ! ( $ignore_deleted && $post->post_status !== 'publish' ) ) {
                $result = $this->get_formatted_results( array( $post ), true );
            }
        }

        return $result;
    }

    /**
     * Get all active time passes.
     *
     * @return array of time passes
     */
    public function get_active_time_passes() {
        return $this->get_all_time_passes( true );
    }

    /**
     * Get all time passes.
     *
     * @param bool $ignore_deleted ignore deleted time passes
     *
     * @return array $time_passes list of time passes
     */
    public function get_all_time_passes( $ignore_deleted = false ) {
        // Remove hooks to avoid loop.
        LaterPay_Hooks::get_instance()->remove_wp_query_hooks();

        // @TODO: Change logic posts_per_page for better performance.
        $args = array(
            'post_type'      => self::$timepass_post_type,
            'posts_per_page' => 100,
            'no_found_rows'  => true,
        );

        if ( $ignore_deleted ) {
            $args['post_status'] = 'publish';
        } else {
            $args['post_status'] = array( 'publish', 'draft' );
        }

        $query = new WP_Query( $args );

        $result = $this->get_formatted_results( $query->get_posts() );

        // Add removed WP_Query hooks.
        LaterPay_Hooks::get_instance()->add_wp_query_hooks();

        return $result;
    }

    /**
     * Send Result into expected associated array.
     *
     * @param array $query  array of passes posts.
     * @param bool  $single Single pass.
     *
     * @return mixed
     */
    private function get_formatted_results( $query, $single = false ) {
        $result = array();

        foreach ( $query as $post ) {

            $id = $post->ID;
            $row = array();
            $post_meta = get_post_meta( $id );

            $row['pass_id']         = ( isset( $post_meta['_lp_id'][0] ) ) ? $post_meta['_lp_id'][0] : '';
            $row['title']           = $post->post_title;
            $row['description']     = $post->post_content;
            $row['duration']        = ( isset( $post_meta['_lp_duration'][0] ) ) ? $post_meta['_lp_duration'][0] : 0;
            $row['period']          = ( isset( $post_meta['_lp_period'][0] ) ) ? $post_meta['_lp_period'][0] : '';
            $row['price']           = ( isset( $post_meta['_lp_price'][0] ) ) ? $post_meta['_lp_price'][0] : 0;
            $row['revenue_model']   = ( isset( $post_meta['_lp_revenue_model'][0] ) ) ? $post_meta['_lp_revenue_model'][0] : '';

            if ( isset( $post_meta['_lp_access_to_all'][0] ) ) {

                $row['access_to']       = $post_meta['_lp_access_to_all'][0];
                $row['access_category'] = 0;

            } elseif ( isset( $post_meta['_lp_access_to_include'] ) ) {

                $row['access_to']       = 2;
                $row['access_category'] = $post_meta['_lp_access_to_include'];

            } elseif ( isset( $post_meta['_lp_access_to_except'] ) ) {

                $row['access_to']       = 1;
                $row['access_category'] = $post_meta['_lp_access_to_except'];

            }

            if ( $post->post_status === 'publish' ) {
                $row['is_deleted'] = '0';
            } else {
                $row['is_deleted'] = '1';
            }

            if ( $single ) {
                $result = $row;
                break;
            }

            $result[] = $row;
        }

        return $result;
    }

    /**
     * Update or create new time pass.
     *
     * @param array $data payment data
     *
     * @return array $data array of saved/updated time pass data
     */
    public function update_time_pass( $data ) {
        if ( ! empty( $data['counter_id'] ) ) {
            $counter_id = $data['counter_id'];
        }
        // leave only the required keys
        $data = array_intersect_key( $data, LaterPay_Helper_TimePass::get_default_options() );

        // fill values that weren't set from defaults
        $data = array_merge( LaterPay_Helper_TimePass::get_default_options(), $data );

        // pass_id is a primary key, set by autoincrement
        $time_pass_id = $data['pass_id'];
        unset( $data['pass_id'] );

        $args = array(
            'post_title'   => $data['title'],
            'post_content' => $data['description'],
            'post_status'  => 'publish',
            'post_type'    => self::$timepass_post_type,
        );

        if ( empty( $time_pass_id ) ) {

            if ( isset( $counter_id ) ) {
                $timepass_counter = $counter_id;
            } else {
                $timepass_counter = get_option( 'lp_pass_count', 0 );
                $timepass_counter = $timepass_counter + 1;
                update_option( 'lp_pass_count', $timepass_counter );
            }
            $args['meta_input']['_lp_id'] = $timepass_counter;
            $data['tp_id']   = wp_insert_post( $args );
            $data['pass_id'] = $timepass_counter;
        } else {

            $query_args = array(
                // Meta query is required for post id.
                'meta_key'       => '_lp_id', // phpcs:ignore
                'meta_value'     => $time_pass_id, // phpcs:ignore
                'meta_compare'   => '=',
                'posts_per_page' => 1,
                'post_type'      => self::$timepass_post_type,
                'no_found_rows'  => true,
                'fields'         => 'ids',
            );

            $query = new WP_Query( $query_args );

            $current_posts = $query->posts;

            $data['tp_id'] = isset( $current_posts[0] ) ? $current_posts[0] : '';

            if ( ! empty( $data['tp_id'] ) ) {

                $args['ID'] = $data['tp_id'];
                wp_update_post( $args );

            }

            $data['pass_id'] = $time_pass_id;

        }


        if ( ! empty( $data['tp_id'] ) ) {

            $access_data = intval( $data['access_to'] );
            $categories  = explode( ',', $data['access_category'] );

            if ( 0 === $access_data ) {

                delete_post_meta( $data['tp_id'], '_lp_access_to_except' );
                delete_post_meta( $data['tp_id'], '_lp_access_to_include' );
                update_post_meta( $data['tp_id'], '_lp_access_to_all', $data['access_to'] );

            } elseif ( 1 === $access_data ) {

                delete_post_meta( $data['tp_id'], '_lp_access_to_all' );
                delete_post_meta( $data['tp_id'], '_lp_access_to_include' );
                delete_post_meta( $data['tp_id'], '_lp_access_to_except' );

                foreach ( $categories as $category_id ) {
                    if ( 0 !== absint( $category_id ) ) {
                        add_post_meta( $data['tp_id'], '_lp_access_to_except', $category_id );
                    }
                }

            } else {

                delete_post_meta( $data['tp_id'], '_lp_access_to_except' );
                delete_post_meta( $data['tp_id'], '_lp_access_to_all' );
                delete_post_meta( $data['tp_id'], '_lp_access_to_include' );

                foreach ( $categories as $category_id ) {
                    if ( 0 !== absint( $category_id ) ) {
                        add_post_meta( $data['tp_id'], '_lp_access_to_include', $category_id );
                    }
                }

            }

            update_post_meta( $data['tp_id'], '_lp_revenue_model', $data['revenue_model'] );
            update_post_meta( $data['tp_id'], '_lp_price', $data['price'] );
            update_post_meta( $data['tp_id'], '_lp_period', $data['period'] );
            update_post_meta( $data['tp_id'], '_lp_duration', $data['duration'] );

        }

        // purge cache
        LaterPay_Helper_Cache::purge_cache();

        return $data;
    }

    /**
     * Delete time pass by id.
     *
     * @param integer $time_pass_id time pass id
     *
     * @return bool true on success or false on error
     */
    public function delete_time_pass_by_id( $time_pass_id ) {

        $query_args = array(
            // Meta query is required for post id.
            'meta_key'       => '_lp_id', // phpcs:ignore
            'meta_value'     => $time_pass_id, // phpcs:ignore
            'meta_compare'   => '=',
            'posts_per_page' => 1,
            'post_type'      => self::$timepass_post_type,
            'no_found_rows'  => true,
            'fields'         => 'ids',
        );

        $query = new WP_Query( $query_args );

        $current_posts = $query->posts;

        $post = null;

        if ( isset( $current_posts[0] ) ) {

            $args = array(
                'ID'          => $current_posts[0],
                'post_status' => 'draft',
            );
            $post = wp_update_post( $args );

        }

        // purge cache
        LaterPay_Helper_Cache::purge_cache();

        return ( is_wp_error( $post ) || empty( $post ) ) ? false : true;
    }

    /**
     * Get count of existing time passes.
     *
     * @param bool $ignore_deleted ignore count of deleted pass.
     *
     * @return int number of defined time passes
     */
    public function get_time_passes_count( $ignore_deleted = false ) {

        $timepass_count = wp_count_posts( self::$timepass_post_type );

        $result = ( ( $ignore_deleted === true ) ? $timepass_count->publish : $timepass_count->publish + $timepass_count->draft );

        return absint( $result );
    }

    /**
     * Get all time passes that apply to a given post by its category ids.
     *
     * @param array|null $term_ids array of category ids
     * @param bool       $exclude  categories to be excluded from the list
     * @param bool       $ignore_deleted ignore deleted time passes
     * @param bool       $include_all include all time passes
     *
     * @return array $time_passes list of time passes
     */
    public function get_time_passes_by_category_ids( $term_ids = null, $exclude = false, $ignore_deleted = false, $include_all = false ) {

        // Remove hooks to avoid loop.
        LaterPay_Hooks::get_instance()->remove_wp_query_hooks();

        // @TODO: Change logic posts_per_page for better performance.
        $query_args = array(
            'post_type'      => self::$timepass_post_type,
            'posts_per_page' => 100,
            'no_found_rows'  => true,
        );

        $meta_query = array(
            'relation' => 'OR',
            array(
                'key'     => '_lp_access_to_all',
                'compare' => 'EXISTS',
            ),
        );

        $access_to_except = array(
            'key'     => '_lp_access_to_except',
            'value'   => $term_ids,
            'compare' => 'NOT IN',
        );

        $access_to_include = array(
            'key'     => '_lp_access_to_include',
            'value'   => $term_ids,
            'compare' => 'IN',
        );

        if ( $include_all ) {
            array_push( $meta_query, $access_to_except, $access_to_include );
        } else {
            if ( $exclude ) {
                $meta_query[] = $access_to_except;
            } else {
                $meta_query[] = $access_to_include;
            }
        }

        if ( $ignore_deleted ) {
            $query_args['post_status'] = 'publish';
        } else {
            $query_args['post_status'] = array( 'publish', 'draft' );
        }

        // Meta query used to get all passes with different cases
        // Case 1: Passes accept in all category
        // Case 2: Passes except specified category
        // Case 3: Passes include specified category
        $query_args['meta_query'] = $meta_query; // phpcs:ignore

        // Create a hash from the query args.
        $args_hash = md5( wp_json_encode( $query_args ) );

        // Check if data already exists for requested query args.
        if ( isset( self::$term_data_store[$args_hash] ) ) {

            // Get data from internal cache for already requested query.
            $timepasses = self::$term_data_store[$args_hash];

        } else {

            // Initialize WP_Query without args.
            $query = new WP_Query();

            // Get posts for requested args.
            $posts = $query->query( $query_args );

            // Get formatted data and store it, in case same query is fired again.
            $timepasses = $this->get_formatted_results( $posts );

            // Unset time pass data if it contains excluded categories.
            foreach ( $timepasses as $key => $timepass ) {
                if ( 1 === $timepass['access_to'] ) {
                    $found_categories = array_intersect( $term_ids, $timepass['access_category'] );

                    if ( ! empty( $found_categories ) ) {
                        unset( $timepasses[$key] );
                    }
                }
            }

            self::$term_data_store[$args_hash] = $timepasses;

        }

        // Add removed hooks.
        LaterPay_Hooks::get_instance()->add_wp_query_hooks();

        return $timepasses;
    }

}
