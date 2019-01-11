<?php

/**
 * LaterPay category price model.
 */
class LaterPay_Model_CategoryPriceWP
{

    /**
     * instance of the LaterPay_Model_CategoryPriceWP
     *
     * @var LaterPay_Model_CategoryPriceWP $instance
     *
     * @access private
     */
    private static $instance;

    /**
     * function for sigleton object.
     *
     * @param boolean Force return object of current class
     *
     * @return object LaterPay_Model_CategoryPriceWP
     */
    public static function get_instance( $force = false )
    {
        if ( laterpay_check_is_vip() || laterpay_is_migration_complete() || $force ) {
            if ( ! isset( self::$instance ) ) {
                self::$instance = new self();
            }

            return self::$instance;
        } else {
            return LaterPay_Compatibility_CategoryPrice::get_instance();
        }
    }

    /**
     * LaterPay_Model_CategoryPriceWP constructor.
     */
    protected function __construct() {}


    /**
     * Gets count of categories with defined category.
     *
     * @param array $ids
     *
     * @return array category_price_data
     */
    public function get_categories_with_defined_price_count() {

        $query = array(
            'taxonomy'   => 'category',
            'hide_empty' => false,
            'meta_key'   => '_lp_price',
            'count'      => true,
            'fields'    => 'ids',
        );

        $categories = new WP_Term_Query( $query );

        return $categories->terms === null ? 0 : count( $categories->terms );
    }


    /**
     * Get all categories with a defined category default price.
     *
     * @return array categories
     */
    public function get_categories_with_defined_price() {

        $query = array(
            'taxonomy'   => 'category',
            'hide_empty' => false,
            'meta_key'   => '_lp_price',
        );

        $categories = new WP_Term_Query( $query );
        $result     = $this->format_categories_with_defined_price( $categories->terms );

        return $result;
    }

    /**
     * Convert WP_Term_Query result into stdClass object
     *
     * @param array $terms array of WP_Term objects
     *
     * @return array of stdClass objects
     */
    public function format_categories_with_defined_price( $terms ) {

        $result = array();

        if ( empty( $terms ) ) {
            return $result;
        }

        foreach ( $terms as $key => $term ) {
            $category                 = new stdClass();
            $category->id             = $term->term_id;
            $category->category_name  = $term->name;
            $category->category_id    = $term->term_id;
            $category->category_price = get_term_meta( $term->term_id, '_lp_price', true );
            $category->revenue_model  = get_term_meta( $term->term_id, '_lp_revenue_model', true );
            $category->identifier     = get_term_meta( $term->term_id, '_lp_identifier', true );

            // If identifier is empty add identifier to term meta for backward compatibility.
            if ( empty( $category->identifier ) ) {
                update_term_meta( $term->term_id, '_lp_identifier', $term->term_id );
                $category->identifier = $term->term_id;
            }

            $result[$key]             = $category;
        }

        return $result;
    }

    /**
     * Get categories with defined category default prices by list of category ids.
     *
     * @param array $ids array of category_id
     *
     * @return array category_price_data
     */
    public function get_category_price_data_by_category_ids( $ids ) {

        $query = array(
            'taxonomy'   => 'category',
            'hide_empty' => false,
            'include'    => $ids,
            'meta_key'   => '_lp_price',
        );

        $categories = new WP_Term_Query( $query );
        $result     = $this->format_categories_with_defined_price( $categories->terms );

        return $result;
    }


    /**
     * Get category.
     *
     * @param integer $id id category
     *
     * @return integer id category
     */
    public function get_price_id_by_category_id( $id ) {
        return $id;
    }

    /**
     * Get categories without defined category default prices by search term.
     *
     * @param array $args       query args for get_categories
     *
     * @return array $categories
     */
    public function get_categories_without_price_by_term( $args ) {

        $query = array(
            'taxonomy'     => 'category',
            'hide_empty'   => false,
            'meta_key'     => '_lp_price',
            'meta_compare' => 'NOT EXISTS'
        );

        $args = wp_parse_args(
            $args,
            $query
        );

        $category_with_price = new WP_Term_Query( $args );

        return $category_with_price->terms;

    }

    /**
     * Convert array of term object to array of term id.
     *
     * @param $terms array of term objects.
     *
     * @return array of term id.
     */
    public function get_category_ids( $terms ) {

        $result = array();

        if ( empty( $terms ) ) {
            return $result;
        }

        foreach ( $terms as $key => $term ) {
            $result[$key] = (string) $term->term_id;
        }

        return $result;
    }


    /**
     * Get categories by search term.
     *
     * @param string $term  term string to find categories
     * @param int    $limit limit categories
     *
     * @deprecated please use get_terms( 'category', array( 'name__like' => '$term', 'number' => $limit, 'fields' => 'id=>name' ) );
     *
     * @return array categories
     */
    public function get_categories_by_term( $term, $limit ) {

        global $wpdb;

        $term = $wpdb->esc_like( $term );

        $query = array(
            'taxonomy'   => 'category',
            'hide_empty' => false,
            'name__like' => $term,
            'number'     => $limit,
        );

        $categories = new WP_Term_Query( $query );
        $result     = $this->format_categories_by_term( $categories->terms );

        return $result;
    }

    /**
     * Convert WP_Term_Qury result into stdClass object.
     *
     * @param array $terms array of WP_Term objects
     *
     * @return array of stdClass objects
     */
    public function format_categories_by_term($terms){

        $result = array();

        if ( empty( $terms ) ) {
            return $result;
        }

        foreach ( $terms as $key => $value ) {
            $term       = new stdClass();
            $term->id   = (string) $value->term_id;
            $term->text = $value->name;

            $result[$key] = $term;
        }

        return $result;
    }

    /**
     * Set category default price.
     *
     * @param integer $id_category      id category
     * @param float   $price            price for category
     * @param string  $revenue_model    revenue model of category
     * @param integer $id               id price for category
     *
     * @return int|false number of rows affected / selected or false on error
     */
    public function set_category_price( $id_category, $price = 0, $revenue_model = 'ppu', $id = 0, $identifier = '' ) {

        // if category is changed then remove old category it.
        if ( ! empty( $id ) && intval( $id_category ) !== intval( $id ) ) {
            delete_term_meta( $id, '_lp_price' );
            delete_term_meta( $id, '_lp_revenue_model' );
        }

        $laterpay_price         = update_term_meta( $id_category, '_lp_price', $price );
        $laterpay_revenue_model = update_term_meta( $id_category, '_lp_revenue_model', $revenue_model );

        if ( ! empty( $identifier ) ) {
            update_term_meta( $id_category, '_lp_identifier', $identifier );
        }

        if ( $laterpay_price && $laterpay_revenue_model ) {
            $success = true;
        } else {
            $success = false;
        }

        LaterPay_Helper_Cache::purge_cache();

        return $success;
    }

    /**
     * Get price by category id.
     *
     * @param integer $id category id
     *
     * @return float|null price category
     */
    public function get_price_by_category_id( $id ) {

        $price = get_term_meta( $id, '_lp_price', true );

        if (  $price === false || $price === "" ) {
            return null;
        }

        return $price;
    }

    /**
     * Get revenue model by category id.
     *
     * @param integer $id  category id
     *
     * @return string|null category renevue model
     */
    public function get_revenue_model_by_category_id( $id ) {

        $revenue_model = get_term_meta( $id, '_lp_revenue_model', true );

        if ( empty( $revenue_model ) ) {
            return null;
        }

        return $revenue_model;
    }

    /**
     * Check, if category exists by getting the category id by category name.
     *
     * @param string $name name category
     *
     * @return integer category_id
     */
    public function check_existence_of_category_by_name( $name ) {

        if ( function_exists( 'wpcom_vip_term_exists' ) ) {
            $category = wpcom_vip_term_exists( $name, 'category' );
        } else {
            $category = term_exists( $name, 'category' ); // phpcs:ignore
        }

        if ( empty( $category ) ) {
            return null;
        }
        return $category['term_id'];
    }

    /**
     * Delete price by category id.
     *
     * @param integer $id category id
     *
     * @return int|false the number of rows updated, or false on error
     */
    public function delete_prices_by_category_id( $id ) {
        if ( empty( $id ) ) {
            return false;
        }

        $term_id = intval( $id );

        $laterpay_price         = delete_term_meta( $term_id, '_lp_price' );
        $laterpay_revenue_model = delete_term_meta( $term_id, '_lp_revenue_model' );
        $laterpay_identifier    = delete_term_meta( $term_id, '_lp_identifier' );

        if ( $laterpay_price && $laterpay_revenue_model && $laterpay_identifier ) {
            $success = true;
        } else {
            $success = false;
        }

        LaterPay_Helper_Cache::purge_cache();

        return $success;
    }

    /**
     * Delete all category prices from table.
     *
     * @return int|false the number of rows updated, or false on error
     */
    public function delete_all_category_prices() {

        $query = array(
            'taxonomy'   => 'category',
            'hide_empty' => false,
            'meta_key'   => '_lp_price',
        );

        $categories = new WP_Term_Query( $query );

        if ( empty( $categories->terms ) ) {
            return true;
        }

        $error_on_delete = false;
        foreach ( $categories->terms as $category ) {

            $category_price      = delete_term_meta( $category->term_id, '_lp_price' );
            $category_model      = delete_term_meta( $category->term_id, '_lp_revenue_model' );
            $category_identifier = delete_term_meta( $category->term_id, '_lp_identifier' );

            if ( ! $category_price || ! $category_model || ! $category_identifier ) {
                $error_on_delete = true;
            }
        }

        return ( ! $error_on_delete );
    }
}
