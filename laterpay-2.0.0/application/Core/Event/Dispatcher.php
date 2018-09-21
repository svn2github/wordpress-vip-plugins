<?php

/**
 * LaterPay core class
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
class LaterPay_Core_Event_Dispatcher implements LaterPay_Core_Event_DispatcherInterface {
    const DEFAULT_PRIORITY = 10;
    /**
     * @var LaterPay_Core_Event_Dispatcher
     */
    private static $dispatcher = null;
    private $listeners = array();
    /**
     * Shared events, that could be called from any place
     * @var array
     */
    private $shared_listeners = array();
    private $sorted = array();

    /**
     * Singleton to get only one event dispatcher
     *
     * @return LaterPay_Core_Event_Dispatcher
     */
    public static function get_dispatcher() {
        if ( ! isset( self::$dispatcher ) ) {
            self::$dispatcher = new self();
        }

        return self::$dispatcher;
    }

    /**
     * Dispatches an event to all registered listeners.
     *
     * @param string $event_name The name of the event to dispatch.
     * @param LaterPay_Core_Event|array|null $args The event to pass to the event handlers/listeners.
     *
     * @throws ReflectionException
     *
     * @return LaterPay_Core_Event
     */
    public function dispatch( $event_name, $args = null ) {
        if ( is_array( $args ) ) {
            $event = new LaterPay_Core_Event( $args );
        } elseif ( $args instanceof LaterPay_Core_Event ) {
            $event = $args;
        } else {
            $event = new LaterPay_Core_Event();
        }
        $event->set_name( $event_name );

        if ( ! isset( $this->listeners[ $event_name ] ) ) {
            return $event;
        }
        $arguments = LaterPay_Hooks::apply_arguments_filters( $event_name, $event->get_arguments() );
        $event->set_arguments( $arguments );

        $this->do_dispatch( $this->get_listeners( $event_name ), $event );
        // apply registered in wordpress filters for the event result
        $result = LaterPay_Hooks::apply_filters( $event_name, $event->get_result() );
        $event->set_result( $result );
        if ( $event->is_echo_enabled() && $event->is_ajax() ) {
            wp_send_json( $event->get_result() );
        }
        return $event;
    }

    /**
     * Triggers the listeners of an event.
     *
     * @param callable[]          $listeners The event listeners.
     * @param LaterPay_Core_Event $event The event object to pass to the event handlers/listeners.
     *
     * @throws ReflectionException
     *
     * @return null
     */
    protected function do_dispatch( $listeners, LaterPay_Core_Event $event ) {
        foreach ( $listeners as $listener ) {
            try {
                $arguments = $this->get_arguments( $listener, $event );
                call_user_func_array( $listener, $arguments );
            } catch ( LaterPay_Core_Exception $e ) {
                unset( $e );
                $event->stop_propagation();
            }

            if ( $event->is_propagation_stopped() ) {
                $event->set_propagations_stopped_by( $listener );
                break;
            }
        }
    }

    /**
     * Processes callback description to get required list of arguments.
     *
     * @param callable|array|object  $callback The event listener.
     * @param LaterPay_Core_Event    $event The event object.
     * @param array                  $attributes The context to get attributes.
     *
     * @throws LaterPay_Core_Exception
     * @throws ReflectionException
     *
     * @return array
     */
    protected function get_arguments( $callback, LaterPay_Core_Event $event, $attributes = array() ) {
        $arguments = array();
        if ( is_array( $callback ) ) {
            if ( ! method_exists( $callback[0], $callback[1] ) && is_callable( $callback ) ) {
                return $arguments;
            } elseif ( method_exists( $callback[0], $callback[1] ) ) {
                $callbackReflection = new ReflectionMethod( $callback[0], $callback[1] );
            } else {
                throw new LaterPay_Core_Exception( sprintf( 'Callback method "%1%s" is not found in "%2%s" Class', $callback[0], $callback[1] ) );
            }
        } elseif ( is_object( $callback ) ) {
            $callbackReflection = new ReflectionObject( $callback );
            $callbackReflection = $callbackReflection->getMethod( '__invoke' );
        } else {
            $callbackReflection = new ReflectionFunction( $callback );
        }

        if ( $callbackReflection->getNumberOfParameters() > 0 ) {
            $parameters = $callbackReflection->getParameters();
            foreach ( $parameters as $param ) {
                if ( array_key_exists( $param->name, $attributes ) ) {
                    $arguments[] = $attributes[ $param->name ];
                } elseif ( $param->getClass() && $param->getClass()->isInstance( $event ) ) {
                    $arguments[] = $event;
                } elseif ( $param->isDefaultValueAvailable() ) {
                    $arguments[] = $param->getDefaultValue();
                } else {
                    $arguments[] = $event;
                }
            }
        }

        return (array) $arguments;
    }

    /**
     * Gets the listeners of a specific event or all listeners.
     *
     * @param string|null $event_name The event name to get listeners or null to get all.
     *
     * @return mixed
     */
    public function get_listeners( $event_name = null ) {
        if ( null !== $event_name ) {
            if ( ! isset( $this->sorted[ $event_name ] ) ) {
                $this->sort_listeners( $event_name );
            }
            return $this->sorted[ $event_name ];
        }

        $listeners = array_keys( $this->listeners );

        foreach ( $listeners as $event_name ) {
            if ( ! isset( $this->sorted[ $event_name ] ) ) {
                $this->sort_listeners( $event_name );
            }
        }

        return array_filter( $this->sorted );
    }

    /**
     * Sorts the internal list of listeners for the given event by priority.
     *
     * @param string $event_name The name of the event.
     *
     * @return null
     */
    private function sort_listeners( $event_name ) {
        $this->sorted[ $event_name ] = array();

        if ( isset( $this->listeners[ $event_name ] ) ) {
            krsort( $this->listeners[ $event_name ] );
            // we should make resulted array unique to avoid duplicated calls.
            // php function `array_unique` works wrong and has bugs working with objects/arrays.
            $temp_array = call_user_func_array( 'array_merge', $this->listeners[ $event_name ] );
            $result = array();
            foreach ( $temp_array as $callback ) {
                if ( ! in_array( $callback, $result, true ) ) {
                    $result[] = $callback;
                }
            }
            $this->sorted[ $event_name ] = $result;
        }
    }

    /**
     * Checks whether an event has any registered listeners.
     *
     * @param string|null $event_name
     *
     * @return mixed
     */
    public function has_listeners( $event_name = null ) {
        return (bool) count( $this->get_listeners( $event_name ) );
    }

    /**
     * Adds an event subscriber.
     *
     * The subscriber is asked for all the events he is
     * interested in and added as a listener for these events.
     *
     * @param LaterPay_Core_Event_SubscriberInterface $subscriber The subscriber.
     */
    public function add_subscriber( LaterPay_Core_Event_SubscriberInterface $subscriber ) {
        foreach ( $subscriber->get_shared_events() as $event_name => $params ) {
            if ( is_string( $params ) ) {
                $this->add_shared_listener( $event_name, array( $subscriber, $params ) );
            } elseif ( is_string( $params[0] ) ) {
                $this->add_shared_listener( $event_name, array( $subscriber, $params[0] ) );
            } else {
                foreach ( $params as $listener ) {
                    $this->add_shared_listener( $event_name, array( $subscriber, $listener[0] ) );
                }
            }
        }

        foreach ( $subscriber->get_subscribed_events() as $event_name => $params ) {
            if ( is_string( $params ) ) {
                $this->add_listener( $event_name, array( $subscriber, $params ) );
            } else {
                foreach ( $params as $listener ) {
                    $callable = $this->get_shared_listener( $listener[0] );
                    if ( method_exists( $subscriber, $listener[0] ) ) {
                        $this->add_listener( $event_name, array( $subscriber, $listener[0] ), isset( $listener[1] ) ? $listener[1] : self::DEFAULT_PRIORITY );
                    } elseif ( $callable !== null ) {
                        $this->add_listener( $event_name, $callable, isset( $listener[1] ) ? $listener[1] : self::DEFAULT_PRIORITY );
                    }
                }
            }
        }
    }

    /**
     * Adds an event listener that listens on the specified events.
     *
     * @param string $event_name The event name to listen on.
     * @param callable $listener The event listener.
     * @param int $priority The higher this value, the earlier an event
     *                            listener will be triggered in the chain (defaults to self::DEFAULT_PRIORITY)
     *
     * @return null
     */
    public function add_listener( $event_name, $listener, $priority = self::DEFAULT_PRIORITY ) {
        LaterPay_Hooks::register_laterpay_action( $event_name );
        $this->listeners[ $event_name ][ $priority ][] = $listener;
        unset( $this->sorted[ $event_name ] );
    }

    /**
     * Adds an shared event listener that listens on the specified events.
     *
     * @param string $event_name The event name to listen on.
     * @param callable $listener The event listener.
     *
     * @return null
     */
    public function add_shared_listener( $event_name, $listener ) {
        $this->shared_listeners[ $event_name ] = $listener;
    }

    /**
     * Returns shared event listener.
     *
     * @param string $event_name The event name.
     *
     * @return callable|null
     */
    public function get_shared_listener( $event_name ) {
        if ( isset( $this->shared_listeners[ $event_name ] ) ) {
            return $this->shared_listeners[ $event_name ];
        }
        return null;
    }

    /**
     * Removes an event subscriber.
     *
     * @param LaterPay_Core_Event_SubscriberInterface $subscriber The subscriber
     */
    public function remove_subscriber( LaterPay_Core_Event_SubscriberInterface $subscriber ) {
        foreach ( $subscriber->get_subscribed_events() as $event_name => $params ) {
            if ( is_array( $params ) && is_array( $params[0] ) ) {
                foreach ( $params as $listener ) {
                    $this->remove_listener( $event_name, array( $subscriber, $listener[0] ) );
                }
            } else {
                $this->remove_listener( $event_name, array( $subscriber, is_string( $params ) ? $params : $params[0] ) );
            }
        }
    }

    /**
     * Removes an event listener from the specified events.
     *
     * @param string $event_name The event name to listen on.
     * @param callable $listener The event listener.
     *
     * @return bool
     */
    public function remove_listener( $event_name, $listener ) {
        if ( ! isset( $this->listeners[ $event_name ] ) ) {
            return false;
        }
        $result = false;
        foreach ( $this->listeners[ $event_name ] as $priority => $listeners ) {
            $key = array_search( $listener, $listeners, true );

            if ( false !== $key ) {
                unset( $this->listeners[ $event_name ][ $priority ][ $key ], $this->sorted[ $event_name ] );
                $result = true;
            }
        }
        return $result;
    }
}
