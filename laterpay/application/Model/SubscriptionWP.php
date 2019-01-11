<?php

/**
 * LaterPay subscription model.
 */
class LaterPay_Model_SubscriptionWP {

    /**
     * instance of the LaterPay_Model_SubscriptionWP
     *
     * @var object of LaterPay_Model_SubscriptionWP
     *
     * @access private
     */
    private static $_instance;

    /**
     * Store hash of query and data for duplicate queries.
     *
     * @var array Internal Cache for Duplicate Queries.
     *
     * @access private
     */
    private static $term_data_store = [];

    /**
     * Constructor for class LaterPay_Model_SubscriptionWP,
     * Kept private as class is singleton.
     */
    private function __construct() { }


    /**
     * Returns Laterpay model instance. Returns LaterPay_Model_Subscription instance if
     * current environment is not VIP. Otherwise returns instance of itself(on VIP platforms).
     * This method is needed to make class singleton
     *
     * @param boolean Force return object of current class
     *
     * @return LaterPay_Compatibility_Subscription|LaterPay_Model_SubscriptionWP
     */
    public static function get_instance( $force = false ) {

        if( laterpay_check_is_vip() || laterpay_is_migration_complete() || $force ) {
            if ( ! self::$_instance ) {
                self::$_instance = new self();
            }
            return self::$_instance;
        }
        else {
            return LaterPay_Compatibility_Subscription::get_instance();
        }
    }

    /**
     * Get subscription data.
     *
     * @param int  $id subscription id
     * @param bool $ignore_deleted ignore deleted subscriptions
     *
     * @return array $subscription array of subscriptions data
     */
    public function get_subscription( $id, $ignore_deleted = false ) {

        $query_args = array(
            // Meta query is required for post id.
            'meta_key'       => '_lp_id', // phpcs:ignore
            'meta_value'     => $id, // phpcs:ignore
            'meta_compare'   => '=',
            'posts_per_page' => 1,
            'post_type'      => 'lp_subscription',
            'no_found_rows'  => true,
            'fields'         => 'ids',
        );

        $query = new WP_Query( $query_args );

        $current_posts = $query->posts;

        $id = ( isset( $current_posts[0] ) ) ? $current_posts[0] : '';

        $subscription = array();

        if ( ! empty( $id ) ) {

            LaterPay_Hooks::get_instance()->remove_wp_query_hooks();

            $query_args = [
                'post_type'      => 'lp_subscription',
                'post_status'    => [ 'draft', 'publish' ],
                'posts_per_page' => 1,
                'no_found_rows'  => true,
                'p'              => $id
            ];

            if ( $ignore_deleted ) {
                $query_args['post_status'] = 'publish';
            }

            $get_subscription_query = new WP_Query( $query_args );

            $posts = $get_subscription_query->get_posts();

            if ( isset( $posts[0] ) ) {
                $subscription = $this->transform_post_to_subscription( $posts[0] );
            }

            LaterPay_Hooks::get_instance()->add_wp_query_hooks();

        }

        return $subscription;
    }

    /**
     * Update or create new subscription.
     *
     * @param array $data payment data
     *
     * @return array $data array of saved/updated subscription data
     */
    public function update_subscription( $data ) {

        if ( ! empty( $data['counter_id'] ) ) { // Migration.
            $counter_id = $data['counter_id'];
        }

        // leave only the required keys
        $data = array_intersect_key( $data, LaterPay_Helper_Subscription::get_default_options() );

        // fill values that weren't set from defaults
        $data = array_merge( LaterPay_Helper_Subscription::get_default_options(), $data );

        // subscription_id is a primary key, set by autoincrement
        $id = $data['id'];
        unset( $data['id'] );

        $access_data = intval( $data['access_to'] );
        $categories  = explode( ',', $data['access_category'] );

        if ( empty( $id ) ) {

            if ( isset( $counter_id ) ) {
                $subscription_counter = $counter_id;
            } else {
                $subscription_counter = get_option( 'lp_sub_count', 0 );
                $subscription_counter = $subscription_counter + 1;
                update_option( 'lp_sub_count', $subscription_counter );
            }

            $subscription_post_id = wp_insert_post( [
                'post_content' => $data['description'],
                'post_title'   => $data['title'],
                'post_status'  => 'publish',   // is_deleted
                'post_type'    => 'lp_subscription',
                'meta_input'   => [
                    '_lp_duration'  => $data['duration'],
                    '_lp_period'    => $data['period'],
                    '_lp_price'     => $data['price'],
                    '_lp_id'        => $subscription_counter,
                ],
            ] );

            if ( 0 === $access_data ) {
                $access_key   = '_lp_access_to_all';
                $access_value = $data['access_to'];
                update_post_meta( $subscription_post_id, $access_key, $access_value );
            } elseif ( 1 === $access_data ) {
                foreach ( $categories as $category_id ) {
                    if ( 0 !== absint( $category_id ) ) {
                        add_post_meta( $subscription_post_id, '_lp_access_to_except', $category_id );
                    }
                }
            } else {
                foreach ( $categories as $category_id ) {
                    if ( 0 !== absint( $category_id ) ) {
                        add_post_meta( $subscription_post_id, '_lp_access_to_include', $category_id );
                    }
                }
            }

            $data['id'] = $subscription_counter;

        } else {

            $data['id'] = $id;

            $query_args = array(
                // Meta query is required for post id.
                'meta_key'       => '_lp_id', // phpcs:ignore
                'meta_value'     => $id, // phpcs:ignore
                'meta_compare'   => '=',
                'posts_per_page' => 1,
                'post_type'      => 'lp_subscription',
                'no_found_rows'  => true,
                'fields'         => 'ids',
            );

            $query = new WP_Query( $query_args );

            $current_posts = $query->posts;

            $id = isset( $current_posts[0] ) ? $current_posts[0] : '';

            if ( ! empty( $id ) ) {

                wp_update_post( [
                    'ID'           => $id,
                    'post_content' => $data['description'],
                    'post_title'   => $data['title'],
                ] );

                if ( 0 === $access_data ) {

                    delete_post_meta( $id, '_lp_access_to_except' );
                    delete_post_meta( $id, '_lp_access_to_include' );
                    update_post_meta( $id, '_lp_access_to_all', $data['access_to'] );

                } elseif ( 1 === $access_data ) {

                    delete_post_meta( $id, '_lp_access_to_all' );
                    delete_post_meta( $id, '_lp_access_to_include' );
                    delete_post_meta( $id, '_lp_access_to_except' );

                    foreach ( $categories as $category_id ) {
                        if ( 0 !== absint( $category_id ) ) {
                            add_post_meta( $id, '_lp_access_to_except', $category_id );
                        }
                    }


                } else {

                    delete_post_meta( $id, '_lp_access_to_except' );
                    delete_post_meta( $id, '_lp_access_to_all' );
                    delete_post_meta( $id, '_lp_access_to_include' );

                    foreach ( $categories as $category_id ) {
                        if ( 0 !== absint( $category_id ) ) {
                            add_post_meta( $id, '_lp_access_to_include', $category_id );
                        }
                    }

                }

                update_post_meta( $id, '_lp_duration', $data['duration'] );
                update_post_meta( $id, '_lp_period', $data['period'] );
                update_post_meta( $id, '_lp_price', $data['price'] );
            }

        }

        // purge cache
        LaterPay_Helper_Cache::purge_cache();

        return $data;
    }

    /**
     * Get all active subscriptions.
     *
     * @return array of subscriptions
     */
    public function get_active_subscriptions() {
        return $this->get_all_subscriptions( true );
    }

    /**
     * Get all subscriptions.
     *
     * @param bool $ignore_deleted ignore deleted subscriptions
     *
     * @return array list of subscriptions
     */
    public function get_all_subscriptions( $ignore_deleted = false ) {

        LaterPay_Hooks::get_instance()->remove_wp_query_hooks();

        $query_args = array(
            'post_type'      => 'lp_subscription',
            'post_status'    => [ 'publish', 'draft' ],
            'posts_per_page' => 100,   // TODO: Add pagination and user control over it.
            'no_found_rows'  => true,
        );

        if ( $ignore_deleted ) {
            $query_args['post_status'] = 'publish';
        }

        $get_subscription_query = new WP_Query( $query_args );

        $posts         = $get_subscription_query->get_posts();
        $subscriptions = [];

        foreach ( $posts as $key => $post ) {
            $subscriptions[ $key ] = $this->transform_post_to_subscription( $post );
        }

        LaterPay_Hooks::get_instance()->add_wp_query_hooks();

        return $subscriptions;
    }

    /**
     * Get all subscriptions that apply to a given post by its category ids.
     *
     * @param null $term_ids array of category ids
     * @param bool $exclude  categories to be excluded from the list
     * @param bool $ignore_deleted ignore deleted subscriptions
     * @param bool $include_all include all subscriptions
     *
     * @return array $subscriptions list of subscriptions
     */
    public function get_subscriptions_by_category_ids( $term_ids = null, $exclude = false, $ignore_deleted = false, $include_all = false ) {

        LaterPay_Hooks::get_instance()->remove_wp_query_hooks();

        $query_args = [
            'post_type'      => 'lp_subscription',
            'post_status'    => [ 'publish', 'draft' ],
            'posts_per_page' => 100,
            'no_found_rows'  => true,
        ];

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
        }

        // Meta query used to get all subscriptions with different cases
        // Case 1: Subscriptions accept in all category
        // Case 2: Subscriptions except specified category
        // Case 3: Subscriptions include specified category
        $query_args['meta_query'] = $meta_query; // phpcs:ignore

        // Create a hash from the query args.
        $args_hash = md5( wp_json_encode( $query_args ) );

        // Check if data already exists for requested query args.
        if ( isset( self::$term_data_store[$args_hash] ) ) {

            // Get data from internal cache for already requested query.
            $subscriptions = self::$term_data_store[$args_hash];

        } else {

            // Initialize WP_Query without args.
            $get_subscriptions_in_category_query = new WP_Query();

            // Get posts for requested args.
            $posts         = $get_subscriptions_in_category_query->query( $query_args );
            $subscriptions = [];

            foreach ( $posts as $key => $post ) {
                $subscriptions[ $key ] = $this->transform_post_to_subscription( $post );
            }

            // Unset subscription data if it contains excluded categories.
            foreach ( $subscriptions as $key => $subscription ) {
                if ( 1 === $subscription['access_to'] ) {
                    $found_categories = array_intersect( $term_ids, $subscription['access_category'] );

                    if ( ! empty( $found_categories ) ) {
                        unset( $subscriptions[$key] );
                    }
                }
            }

            // Store formatted data, in case same query is fired again.
            self::$term_data_store[$args_hash] = $subscriptions;

        }

        LaterPay_Hooks::get_instance()->add_wp_query_hooks();

        return $subscriptions;
    }

    /**
     * Delete subscription by id.
     *
     * @param integer $id subscription id
     *
     * @return bool true on success or false on error
     */
    public function delete_subscription_by_id( $id ) {

        $query_args = array(
            // Meta query is required for post id.
            'meta_key'       => '_lp_id', // phpcs:ignore
            'meta_value'     => $id, // phpcs:ignore
            'meta_compare'   => '=',
            'posts_per_page' => 1,
            'post_type'      => 'lp_subscription',
            'no_found_rows'  => true,
            'fields'         => 'ids',
        );

        $query = new WP_Query( $query_args );

        $current_posts = $query->posts;

        $post = null;

        if ( isset( $current_posts[0] ) ) {

            $args = [
                'ID'          => $current_posts[0],
                'post_status' => 'draft',
            ];
            $post = wp_update_post( $args );

        }

        // purge cache
        LaterPay_Helper_Cache::purge_cache();

        return ( is_wp_error( $post ) || empty( $post ) ) ? false : true;

    }

    /**
     * Get count of existing subscriptions.
     *
     * @param bool $ignore_deleted to get count of deleted subscription or not.
     *
     * @return int number of defined subscriptions
     */
    public function get_subscriptions_count( $ignore_deleted = false ) {

        $subscriptions_count = wp_count_posts( 'lp_subscription' );

        $result = ( ( $ignore_deleted === true ) ? $subscriptions_count->publish : $subscriptions_count->publish + $subscriptions_count->draft );

        return absint( $result );
    }

    /**
     * Returns relevant fields for subscription of given WP_Post
     *
     * @param WP_Post $post Post to transform
     *
     * @return array Subscription instace as array
     */
    private function transform_post_to_subscription( $post ) {

        $post_meta     = get_post_meta( $post->ID );
        $is_deleted    = ( $post->post_status === 'draft' ) ? 1 : 0 ;

        $post_meta = $this->ensure_post_meta_present( $post_meta );


        $subscription                    = [];
        $subscription['id']              = $post_meta['lp_id'];
        $subscription['title']           = $post->post_title;
        $subscription['description']     = $post->post_content;
        $subscription['is_deleted']      = $is_deleted;
        $subscription['duration']        = $post_meta['duration'];
        $subscription['period']          = $post_meta['period'];
        $subscription['access_to']       = $post_meta['access_to'];
        $subscription['access_category'] = $post_meta['access_category'];
        $subscription['price']           = $post_meta['price'];

        return $subscription;
    }

    /**
     * Check post meta has a values.
     *
     * @param array $post_meta Post meta values fetched form database
     *
     * @return array
     */
    private function ensure_post_meta_present( $post_meta ) {

        $post_meta_new   = [];
        $default_options = LaterPay_Helper_Subscription::get_default_options();

        $post_meta_new['duration'] = ( isset( $post_meta['_lp_duration'][0] ) ) ? $post_meta['_lp_duration'][0] : $default_options['duration'];
        $post_meta_new['period']   = ( isset( $post_meta['_lp_period'][0] ) ) ? $post_meta['_lp_period'][0] : $default_options['period'];
        $post_meta_new['price']    = ( isset( $post_meta['_lp_price'][0] ) ) ? $post_meta['_lp_price'][0] : $default_options['price'];
        $post_meta_new['lp_id']    = ( isset( $post_meta['_lp_id'][0] ) ) ? $post_meta['_lp_id'][0] : $default_options['lp_id'];

        $post_meta_new['access_to']       = 0;
        $post_meta_new['access_category'] = 0;

        if ( ! empty( $post_meta['_lp_access_to_include'] ) ) {

            $post_meta_new['access_to']       = 2;
            $post_meta_new['access_category'] = $post_meta['_lp_access_to_include'];

        } elseif ( ! empty( $post_meta['_lp_access_to_except'] ) ) {

            $post_meta_new['access_to']       = 1;
            $post_meta_new['access_category'] = $post_meta['_lp_access_to_except'];

        }

        return $post_meta_new;
    }
}
