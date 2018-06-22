<?php

/**
 * LaterPay pricing helper.
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
class LaterPay_Helper_Pricing
{
    /**
     * Types of prices.
     */
    const TYPE_GLOBAL_DEFAULT_PRICE     = 'global default price';
    const TYPE_CATEGORY_DEFAULT_PRICE   = 'category default price';
    const TYPE_INDIVIDUAL_PRICE         = 'individual price';
    const TYPE_INDIVIDUAL_DYNAMIC_PRICE = 'individual price, dynamic';

    /**
     * @const string Status of post at time of publication.
     */
    const STATUS_POST_PUBLISHED         = 'publish';
    const META_KEY                      = 'laterpay_post_prices';

    /**
     * Check, if the current post or a given post is purchasable.
     *
     * @param null|int $post_id
     *
     * @return null|bool true|false (null if post is free)
     */
    public static function is_purchasable( $post_id = null ) {
        if ( $post_id === null ) {
            $post_id = get_the_ID();
            if ( ! $post_id ) {
                return false;
            }
        }

        // check, if the current post price is not 0
        $price = LaterPay_Helper_Pricing::get_post_price( $post_id, true );

        if ( floatval( 0.00 ) === floatval( $price ) || ! in_array( get_post_type( $post_id ), (array) get_option( 'laterpay_enabled_post_types' ), true ) ) {
            // returns null for this case
            return null;
        }

        return true;
    }

    /**
     * Apply a given category default price to a given post.
     *
     * @param int     $post_id     post id to apply price.
     * @param int     $category_id category id.
     * @param boolean $strict      checks, if the given category_id is assigned to the post_id
     *
     * @return boolean true|false
     */
    public static function apply_category_default_price_to_post( $post_id, $category_id, $strict = false ) {

        $post = get_post( $post_id );

        if ( null === $post ) {
            return false;
        }

        // check, if the post has the given category_id
        if ( $strict && ! has_category( $category_id, $post ) ) {
            return false;
        }

        $post_price = array(
            'type'        => LaterPay_Helper_Pricing::TYPE_CATEGORY_DEFAULT_PRICE,
            'category_id' => (int) $category_id,
        );

        return update_post_meta( $post_id, LaterPay_Helper_Pricing::META_KEY, $post_price );
    }

    /**
     * Recalculates Category price based on current category.
     *
     * @param int    $post_id             Post id.
     * @param int    $category_id         Current category price id.
     * @param string $post_price_type     post price type.
     * @param array  $category_price_data details of category prices.
     * @param array  $categories_of_post  current post categories.
     *
     * @return array|bool returns false on no category default price or return array containing category id and revenue model
     */
    public static function recalculate_post_price( $post_id, $category_id, $post_price_type, $category_price_data = array(), $categories_of_post = array() ) {

        $category_default_price_revenue_model = null;

        if ( empty( $categories_of_post ) ) {
            $categories_of_post = wp_get_post_categories( $post_id );
        }

        $LaterPay_Category_Model = LaterPay_Model_CategoryPriceWP::get_instance();

        if ( empty( $category_price_data ) ) {
            $category_price_data = LaterPay_Helper_Pricing::get_category_price_data_by_category_ids( $categories_of_post );
        }

        $recalculate_cat_price   = false;

        // if the post has a category defined, from which to use the category default price, then let's get that price
        if ( in_array( $category_id, $categories_of_post, true ) ) {

            $category_default_price_revenue_model = (string) $LaterPay_Category_Model->get_revenue_model_by_category_id( $category_id );

            // If category default value is blank.
            if ( empty( $category_default_price_revenue_model ) ) {
                $recalculate_cat_price = true;
            }

        } elseif ( count( $categories_of_post ) > 0 ) {

            $recalculate_cat_price = true;
        }

        // Recalculate category default price.
        if ( $recalculate_cat_price && ! in_array( $post_price_type, array( self::TYPE_INDIVIDUAL_PRICE, self::TYPE_INDIVIDUAL_DYNAMIC_PRICE ), true ) ) {

            $new_category  = 0;
            $new_cat_price = 0;

            foreach ( $category_price_data as $cat_data ) {

                $cat_price = floatval( $cat_data['category_price'] );

                if ( ! empty( $cat_price ) && $cat_price > $new_cat_price ) {
                    $new_cat_price = $cat_price;
                    $new_category  = $cat_data['category_id'];
                }

            }

            if ( $new_category !== 0 ) {

                $post_prices = array(
                    'type'        => LaterPay_Helper_Pricing::TYPE_CATEGORY_DEFAULT_PRICE,
                    'category_id' => $new_category,
                );

                $category_id = $new_category;
                update_post_meta( $post_id, LaterPay_Helper_Pricing::META_KEY, $post_prices );

                $category_default_price_revenue_model = (string) $LaterPay_Category_Model->get_revenue_model_by_category_id( $new_category );
            } else {
                delete_post_meta( $post_id, LaterPay_Helper_Pricing::META_KEY );
            }
        }

        if ( ! empty( $category_default_price_revenue_model ) ) {
            return array( 'category_id' => $category_id, 'revenue_model' => $category_default_price_revenue_model );
        }

        return false;
    }

    /**
     * Get post price, depending on price type applied to post.
     *
     * @param int  $post_id     post id.
     * @param bool $recalculate Recalculate category price or not.
     *
     * @return float $price
     */
    public static function get_post_price( $post_id, $recalculate = false ) {
        $global_default_price = get_option( 'laterpay_global_price' );

        $cache_key = 'laterpay_post_price_' . $post_id;

        $cache_price = wp_cache_get( $cache_key, 'laterpay' );

        // checks if the price is in cache and returns it
        if ( isset( $cache_price['price'] ) && ! empty( $cache_price['price'] ) ) {

            // Return price if individual price type.
            if ( isset( $cache_price['type'] ) && self::TYPE_INDIVIDUAL_PRICE === $cache_price['type'] ) {
                return $cache_price['price'];
            }

        }

        $post       = get_post( $post_id );
        $post_price = get_post_meta( $post_id, LaterPay_Helper_Pricing::META_KEY, true );

        if ( ! is_array( $post_price ) ) {
            $post_price = array();
        }

        $post_price_type = array_key_exists( 'type', $post_price )        ? $post_price['type']        : '';
        $category_id     = array_key_exists( 'category_id', $post_price ) ? $post_price['category_id'] : '';

        // Recalculate post price.
        if ( $recalculate && ! in_array( $post_price_type, array( self::TYPE_INDIVIDUAL_PRICE, self::TYPE_INDIVIDUAL_DYNAMIC_PRICE ), true ) ) {

            $categories_of_post  = wp_get_post_categories( $post_id );
            $category_price_data = LaterPay_Helper_Pricing::get_category_price_data_by_category_ids( $categories_of_post );

            if ( isset( $cache_price['type'] ) && $cache_price['type'] === LaterPay_Helper_Pricing::TYPE_CATEGORY_DEFAULT_PRICE ) {

                $category_data_exists = array_search( $category_id, array_column( $category_price_data, 'category_id' ), true );

                // Check if there are no changes in category price.
                if ( $category_data_exists !== false ) {

                    $current_category_val = floatval( $category_price_data[$category_data_exists]['category_price'] );

                    if( $cache_price['price'] === $current_category_val ) {

                        return $cache_price['price'];

                    } else {

                        $cache_data = array(
                            'type'  => $post_price_type,
                            'price' => $current_category_val,
                        );

                        // add the price to the current post cache
                        wp_cache_set( $cache_key, $cache_data, 'laterpay' );

                        return $current_category_val;

                    }
                }

            }

            $new_category_data = self::recalculate_post_price( $post_id, $category_id, $post_price_type, $category_price_data, $categories_of_post );

            if ( false !== $new_category_data ) {

                $category_id     = $new_category_data['category_id'];
                $post_price_type = LaterPay_Helper_Pricing::TYPE_CATEGORY_DEFAULT_PRICE;

            } else {

                $post_price_type = LaterPay_Helper_Pricing::TYPE_GLOBAL_DEFAULT_PRICE;

            }

        }

        switch ( $post_price_type ) {
            case LaterPay_Helper_Pricing::TYPE_INDIVIDUAL_PRICE:
                $price = array_key_exists( 'price', $post_price ) ? $post_price['price'] : '';
                break;

            case LaterPay_Helper_Pricing::TYPE_INDIVIDUAL_DYNAMIC_PRICE:
                $price = LaterPay_Helper_Pricing::get_dynamic_price( $post );
                break;

            case LaterPay_Helper_Pricing::TYPE_CATEGORY_DEFAULT_PRICE:

                $LaterPay_Category_Model = LaterPay_Model_CategoryPriceWP::get_instance();
                $price = $LaterPay_Category_Model->get_price_by_category_id( (int) $category_id );
                break;

            case LaterPay_Helper_Pricing::TYPE_GLOBAL_DEFAULT_PRICE:
                $price = $global_default_price;
                break;

            default:
                if ( $global_default_price > 0 ) {
                    $price = $global_default_price;
                } else {
                    $price = 0;
                }
                break;
        }

        $price = (float) $price;

        $cache_data = array(
            'type'  => $post_price_type,
            'price' => $price,
        );

        // add the price to the current post cache
        wp_cache_set( $cache_key, $cache_data, 'laterpay' );

        return (float) $price;
    }

    /**
     * Get the post price type. Returns global default price or individual price, if no valid type is set.
     *
     * @param int $post_id
     *
     * @return string $post_price_type
     */
    public static function get_post_price_type( $post_id ) {
        $cache_key = 'laterpay_post_price_' . $post_id;

        // get the price from the cache, if it exists
        $post_price_type = wp_cache_get( $cache_key, 'laterpay' );
        if ( isset( $post_price_type['type'] ) ) {
            return $post_price_type['type'];
        }

        $post_price = get_post_meta( $post_id, LaterPay_Helper_Pricing::META_KEY, true );
        if ( ! is_array( $post_price ) ) {
            $post_price = array();
        }
        $post_price_type = array_key_exists( 'type', $post_price ) ? $post_price['type'] : '';

        switch ( $post_price_type ) {
            case LaterPay_Helper_Pricing::TYPE_INDIVIDUAL_PRICE:
            case LaterPay_Helper_Pricing::TYPE_INDIVIDUAL_DYNAMIC_PRICE:
            case LaterPay_Helper_Pricing::TYPE_CATEGORY_DEFAULT_PRICE:
                break;

            default:
                // set a price type as global default price
                $post_price_type = LaterPay_Helper_Pricing::TYPE_GLOBAL_DEFAULT_PRICE;
                break;
        }

        $cache_data = array(
            'type'  => $post_price_type,
            'price' => 0.0,
        );

        // cache the post price type
        wp_cache_set( $cache_key, $cache_data, 'laterpay' );

        return (string) $post_price_type;
    }

    /**
     * Get the current price for a post with dynamic pricing scheme defined.
     *
     * @param WP_Post $post
     *
     * @return float price
     */
    public static function get_dynamic_price( WP_Post $post ) {
        $post_price             = get_post_meta( $post->ID, LaterPay_Helper_Pricing::META_KEY, true );
        $days_since_publication = self::dynamic_price_days_after_publication( $post );
        $price_range_type       = $post_price['price_range_type'];
        $currency               = LaterPay_Helper_Config::get_currency_config();

        if ( $post_price['change_start_price_after_days'] >= $days_since_publication ) {
            $price = $post_price['start_price'];
        } else {
            if ( $post_price['transitional_period_end_after_days'] <= $days_since_publication ||
                 0 === intval( $post_price['transitional_period_end_after_days'] )
                ) {
                $price = $post_price['end_price'];
            } else {    // transitional period between start and end of dynamic price change
                $price = LaterPay_Helper_Pricing::calculate_transitional_price( $post_price, $days_since_publication );
            }
        }

        // detect revenue model by price range
        $rounded_price = round( $price, 2 );

        switch ( $price_range_type ) {
            case 'ppu':
                if ( $rounded_price < $currency['ppu_min'] ) {
                    if ( abs( $currency['ppu_min'] - $rounded_price ) < $rounded_price ) {
                        $rounded_price = $currency['ppu_min'];
                    } else {
                        $rounded_price = 0;
                    }
                } else if ( $rounded_price > $currency['ppu_only_limit'] ) {
                    $rounded_price = $currency['ppu_only_limit'];
                }
                break;
            case 'sis':
                if ( $rounded_price < $currency['sis_only_limit'] ) {
                    if ( abs( $currency['sis_only_limit'] - $rounded_price ) < $rounded_price ) {
                        $rounded_price = $currency['sis_only_limit'];
                    } else {
                        $rounded_price = 0;
                    }
                } else if ( $rounded_price > $currency['sis_max'] ) {
                    $rounded_price = $currency['sis_max'];
                }
                break;
            case 'ppusis':
                if ( $rounded_price > $currency['ppu_max'] ) {
                    $rounded_price = $currency['ppu_max'];
                } else if ( $rounded_price < $currency['sis_min'] ) {
                    if ( abs( $currency['sis_min'] - $rounded_price ) < $rounded_price ) {
                        $rounded_price = $currency['sis_min'];
                    } else {
                        $rounded_price = 0.00;
                    }
                }
                break;
            default:
                break;
        }

        return number_format( $rounded_price, 2 );
    }

    /**
     * Get the current days count since publication.
     *
     * @param WP_Post $post
     *
     * @return int days
     */
    public static function dynamic_price_days_after_publication( WP_Post $post ) {
        $days_since_publication = 0;

        // unpublished posts always have 0 days after publication
        if ( $post->post_status !== LaterPay_Helper_Pricing::STATUS_POST_PUBLISHED ) {
            return $days_since_publication;
        }

        if ( function_exists( 'date_diff' ) ) {
            $date_time = new DateTime( date( 'Y-m-d' ) );
            $days_since_publication = $date_time->diff( new DateTime( date( 'Y-m-d', strtotime( $post->post_date ) ) ) )->format( '%a' );
        } else {
            $d1 = strtotime( date( 'Y-m-d' ) );
            $d2 = strtotime( $post->post_date );
            $diff_secs = abs( $d1 - $d2 );
            $days_since_publication = floor( $diff_secs / ( 3600 * 24 ) );
        }

        return $days_since_publication;
    }

    /**
     * Calculate transitional price between start price and end price based on linear equation.
     *
     * @param array $post_price postmeta see 'laterpay_post_prices'
     * @param int   $days_since_publication
     *
     * @return float
     */
    private static function calculate_transitional_price( $post_price, $days_since_publication ) {
        $end_price          = $post_price['end_price'];
        $start_price        = $post_price['start_price'];
        $days_until_end     = $post_price['transitional_period_end_after_days'];
        $days_until_start   = $post_price['change_start_price_after_days'];

        $coefficient = ( $end_price - $start_price ) / ( $days_until_end - $days_until_start );

        return $start_price + ( $days_since_publication - $days_until_start ) * $coefficient;
    }

    /**
     * Get revenue model of post price (Pay-per-Use or Single Sale).
     *
     * @param int $post_id
     *
     * @return string $revenue_model
     */
    public static function get_post_revenue_model( $post_id ) {
        $post_price = get_post_meta( $post_id, LaterPay_Helper_Pricing::META_KEY, true );

        if ( ! is_array( $post_price ) ) {
            $post_price = array();
        }

        $post_price_type = array_key_exists( 'type', $post_price ) ? $post_price['type'] : '';

        $revenue_model = '';

        // set a price type (global default price or individual price), if the returned post price type is invalid
        switch ( $post_price_type ) {
            // Dynamic Price does currently not support Single Sale as revenue model
            case LaterPay_Helper_Pricing::TYPE_INDIVIDUAL_DYNAMIC_PRICE:
                $revenue_model = 'ppu';
                break;

            case LaterPay_Helper_Pricing::TYPE_INDIVIDUAL_PRICE:
                if ( array_key_exists( 'revenue_model', $post_price ) ) {
                    $revenue_model = $post_price['revenue_model'];
                }
                break;

            case LaterPay_Helper_Pricing::TYPE_CATEGORY_DEFAULT_PRICE:
                if ( array_key_exists( 'category_id', $post_price ) ) {

                    $category_model = LaterPay_Model_CategoryPriceWP::get_instance();
                    $revenue_model  = $category_model->get_revenue_model_by_category_id( $post_price['category_id'] );
                }
                break;

            case LaterPay_Helper_Pricing::TYPE_GLOBAL_DEFAULT_PRICE:
                $revenue_model = get_option( 'laterpay_global_price_revenue_model' );
                break;
        }

        // fallback in case the revenue_model is not correct
        if ( ! in_array( $revenue_model, array( 'ppu', 'sis' ), true ) ) {

            $price    = array_key_exists( 'price', $post_price ) ? $post_price['price'] : get_option( 'laterpay_global_price' );
            $currency = LaterPay_Helper_Config::get_currency_config();

            if ( ( $price >= $currency['ppu_min'] && $price <= $currency['ppu_max'] ) || 0.00 == floatval( $price ) ) { // WPCS: loose comparison ok.
                $revenue_model = 'ppu';
            } else if ( $price >= $currency['sis_only_limit'] && $price <= $currency['sis_max'] ) {
                $revenue_model = 'sis';
            }
        }

        return $revenue_model;
    }

    /**
     * Return the revenue model of the post.
     * Validates and - if required - corrects the given combination of price and revenue model.
     *
     * @param string $revenue_model
     * @param float  $price
     *
     * @return string $revenue_model
     */
    public static function ensure_valid_revenue_model( $revenue_model, $price ) {
        $currency = LaterPay_Helper_Config::get_currency_config();

        if ($revenue_model === 'ppu') {
            if ( 0.00 == floatval( $price ) || ( $price >= $currency['ppu_min'] && $price <= $currency['ppu_max'] ) ) { // WPCS: loose comparison ok.
                return 'ppu';
            }

            return 'sis';
        }

        if ($price >= $currency['sis_min'] && $price <= $currency['sis_max']) {
            return 'sis';
        }

        return 'ppu';
    }

     /**
     * Return data for dynamic prices. Can be values already set or defaults.
     *
     * @param WP_Post $post
     * @param null $price
     *
     * @return array
     */
    public static function get_dynamic_prices( WP_Post $post, $price = null ) {
        if ( ! LaterPay_Helper_User::can( 'laterpay_edit_individual_price', $post ) ) {
            return array( 'success' => false, );
        }

        $currency    = LaterPay_Helper_Config::get_currency_config();
        $post_prices = get_post_meta( $post->ID, 'laterpay_post_prices', true );
        if ( ! is_array( $post_prices ) ) {
            $post_prices = array();
        }

        $post_price = array_key_exists( 'price', $post_prices ) ? (float) $post_prices['price'] : LaterPay_Helper_Pricing::get_post_price( $post->ID, true );
        if ( $price !== null ) {
            $post_price = $price;
        }

        $start_price                        = array_key_exists( 'start_price',      $post_prices ) ? (float) $post_prices['start_price'] : '';
        $end_price                          = array_key_exists( 'end_price',        $post_prices ) ? (float) $post_prices['end_price'] : '';
        $reach_end_price_after_days         = array_key_exists( 'reach_end_price_after_days',           $post_prices ) ? (float) $post_prices['reach_end_price_after_days'] : '';
        $change_start_price_after_days      = array_key_exists( 'change_start_price_after_days',        $post_prices ) ? (float) $post_prices['change_start_price_after_days'] : '';
        $transitional_period_end_after_days = array_key_exists( 'transitional_period_end_after_days',   $post_prices ) ? (float) $post_prices['transitional_period_end_after_days'] : '';

        // return dynamic pricing widget start values
        if ( ( $start_price === '' ) && ( $price !== null ) ) {
            if ( $post_price >= $currency['sis_only_limit'] ) {
                // Single Sale (sis), if price >= 5.01
                $end_price = $currency['sis_only_limit'];
            } elseif ( $post_price >= $currency['sis_min'] ) {
                // Single Sale or Pay-per-Use, if 1.49 >= price <= 5.00
                $end_price = $currency['sis_min'];
            } else {
                // Pay-per-Use (ppu), if price <= 1.48
                $end_price = $currency['ppu_min'];
            }

            $dynamic_pricing_data = array(
                array(
                      'x' => 0,
                      'y' => $post_price,
                ),
                array(
                      'x' => $currency['dynamic_start'],
                      'y' => $post_price,
                ),
                array(
                      'x' => $currency['dynamic_end'],
                      'y' => $end_price,
                ),
                array(
                      'x' => 30,
                      'y' => $end_price,
                ),
            );
        } elseif ( $transitional_period_end_after_days === '' ) {
            $dynamic_pricing_data = array(
                array(
                    'x' => 0,
                    'y' => $start_price,
                ),
                array(
                    'x' => $change_start_price_after_days,
                    'y' => $start_price,
                ),
                array(
                    'x' => $reach_end_price_after_days,
                    'y' => $end_price,
                ),
            );
        } else {
            $dynamic_pricing_data = array(
                array(
                    'x' => 0,
                    'y' => $start_price,
                ),
                array(
                    'x' => $change_start_price_after_days,
                    'y' => $start_price,
                ),
                array(
                    'x' => $transitional_period_end_after_days,
                    'y' => $end_price,
                ),
                array(
                    'x' => $reach_end_price_after_days,
                    'y' => $end_price,
                ),
            );
        }

        // get number of days since publication to render an indicator in the dynamic pricing widget
        $days_after_publication = LaterPay_Helper_Pricing::dynamic_price_days_after_publication( $post );

        $result = array(
            'values' => $dynamic_pricing_data,
            'price'  => array(
                'pubDays'    => $days_after_publication,
                'todayPrice' => $price,
            ),
        );

        return $result;
    }

     /**
     * Return adjusted prices.
     *
     * @param float $start
     * @param float $end
     *
     * @return array
     */
    public static function adjust_dynamic_price_points( $start, $end ) {
        $currency = LaterPay_Helper_Config::get_currency_config();
        $range    = 'ppu';
        $price    = array(
            'start' => $start,
            'end'   => $end,
        );

        if ( $price['start'] >= $currency['sis_only_limit'] || $price['end'] >= $currency['sis_only_limit'] ) {

            foreach ( $price as $key => $value ) {
                if ( 0 !== $value && $value < $currency['sis_only_limit'] ) {
                    $price[ $key ] = $currency['sis_only_limit'];
                }
            }

            $range = 'sis';
        } elseif (
            ( $price['start'] > $currency['ppu_only_limit'] && $price['start'] < $currency['sis_only_limit'] ) ||
                ( $price['end'] > $currency['ppu_only_limit'] && $price['end'] < $currency['sis_only_limit'] )
            ) {

            foreach ( $price as $key => $value ) {
                if ( 0 !== intval( $value ) ) {
                    if ( $value < $currency['ppu_only_limit'] ) {
                        $price[ $key ] = $currency['sis_min'];
                    } elseif ( $value > $currency['sis_only_limit'] ) {
                        $price[ $key ] = $currency['ppu_max'];
                    }
                };
            }

            $range = 'ppusis';
        } else {

            foreach ( $price as $key => $value ) {
                if ( 0 !== intval( $value ) ) {
                    if ( $value < $currency['ppu_min'] ) {
                        $price[ $key ] = $currency['ppu_min'];
                    } elseif ( $value > $currency['ppu_max'] ) {
                        $price[ $key ] = $currency['ppu_max'];
                    }
                };
            }
        }

        // set range
        array_push( $price, $range );

        return array_values( $price );
    }

    /**
     * Select categories from a given list of categories that have a category default price
     * and return an array of their ids.
     *
     * @param array $categories
     *
     * @return array
     */
    public static function get_categories_with_price( $categories ) {
        $categories_with_price = array();
        $ids                   = array();

        if ( is_array( $categories ) ) {
            foreach ( $categories as $category ) {
                $ids[] = $category->term_id;
            }
        }

        if ( $ids ) {
            $laterpay_category_model = LaterPay_Model_CategoryPriceWP::get_instance();
            $categories_with_price   = $laterpay_category_model->get_category_price_data_by_category_ids( $ids );
        }

        return $categories_with_price;
    }

     /**
     * Reset post publication date.
     *
     * @param WP_Post $post
     *
     * @return void
     */
    public static function reset_post_publication_date( WP_Post $post ) {
        $actual_date        = date( 'Y-m-d H:i:s' );
        $actual_date_gmt    = gmdate( 'Y-m-d H:i:s' );
        $post_update_data   = array(
                                    'ID'            => $post->ID,
                                    'post_date'     => $actual_date,
                                    'post_date_gmt' => $actual_date_gmt,
                                );

        wp_update_post( $post_update_data );
    }

    /**
     * Get category price data by category ids.
     *
     * @param $category_ids
     *
     * @return array
     */
    public static function get_category_price_data_by_category_ids( $category_ids ) {
        $result = array();

        if ( is_array( $category_ids ) && count( $category_ids ) > 0 ) {
            // this array will prevent category prices from duplication
            $ids_used                = array();
            $laterpay_category_model = LaterPay_Model_CategoryPriceWP::get_instance();
            $category_price_data     = $laterpay_category_model->get_category_price_data_by_category_ids( $category_ids );
            // add prices data to results array
            foreach ( $category_price_data as $category ) {
                $ids_used[] = absint( $category->category_id );
                $result[]   = (array) $category;
            }

            // loop through each category and check, if it has a category price
            // if not, then try to get the parent category's category price
            foreach ( $category_ids as $category_id ) {
                $has_price = false;
                foreach ( $category_price_data as $category ) {
                    if ( absint( $category->category_id ) === $category_id ) {
                        $has_price = true;
                        break;
                    }
                }

                if ( ! $has_price ) {
                    $parent_id = get_category( $category_id )->parent;
                    while ( $parent_id ) {
                        $parent_data = $laterpay_category_model->get_category_price_data_by_category_ids( $parent_id );
                        if ( ! $parent_data ) {
                            $parent_id = get_category( $parent_id )->parent;
                            continue;
                        }
                        $parent_data = (array) $parent_data[0];
                        if ( ! in_array( absint( $parent_data['category_id'] ), $ids_used, true ) ) {
                            $ids_used[] = $parent_data['category_id'];
                            $result[]   = $parent_data;
                        }
                        break;
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Check if category has parent category with category price set
     *
     * @param $category_id
     *
     * @return bool
     */
    public static function check_if_category_has_parent_with_price( $category_id ) {

        $laterpay_category_model = LaterPay_Model_CategoryPriceWP::get_instance();

        $has_price               = false;

        // get parent id with price
        $parent_id = get_category( $category_id )->parent;
        while ( $parent_id ) {
            $category_price = $laterpay_category_model->get_category_price_data_by_category_ids( $parent_id );
            if ( ! $category_price ) {
                $parent_id = get_category( $parent_id )->parent;
                continue;
            }
            $has_price = $parent_id;
            break;
        }

        return $has_price;
    }

    /**
     * Get category parents
     *
     * @param $category_id
     *
     * @return array of parent categories ids
     */
    public static function get_category_parents( $category_id ) {
        $parents = array();

        $parent_id = get_category( $category_id )->parent;
        while ( $parent_id ) {
            $parents[] = $parent_id;
            $parent_id = get_category( $parent_id )->parent;
        }

        return $parents;
    }

    /**
     * Get revenue label
     *
     * @param $revenue
     *
     * @return mixed
     */
    public static function get_revenue_label( $revenue ) {
        if ( $revenue === 'sis' ) {
            return __( 'Pay Now', 'laterpay' );
        }

        return __( 'Pay Later', 'laterpay' );
    }
}
