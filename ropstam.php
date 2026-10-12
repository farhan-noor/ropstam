<?php

/* 
 * Plugin Name: Ropstam Test
 * Author: Farhan Noor
 * Author URI: https://linkedin.com/in/farhan-noor
 * Version: 1.0.0
 * Description: Technical task for Senior CMS Developer position.
 */

new admin();

class admin{
    
    function __construct(){        
        //Register post type
        add_action( 'init', [$this, 'register_services_post_type'] );

        //Register meta fields
        add_action( 'init', [$this, 'register_services_meta_fields'] );
        
        //Services Shortcode to use int the admin page block.
        add_shortcode( 'services', [$this, 'recent_services_shortcode'] );
        
        //Service post content.
        add_filter( 'the_content', [$this, 'services_content_append'] );
        
        //Add scripts
        add_action( 'wp_enqueue_scripts', [$this, 'enqueue_scripts'] );
    }
    
    function enqueue_scripts(){
        wp_enqueue_script(
            'services-js',
            plugin_dir_url( __FILE__ ) . 'script.js',
            [],
            '1.0.0',
            true
        );
        
        wp_enqueue_style('services-cs', plugin_dir_url( __FILE__ ) . 'style.css', []);
    }

    /**
     * Register Services custom post type.
     */
    function register_services_post_type() {

        $labels = array(
            'name'               => 'Services',
            'singular_name'      => 'Service',
            'menu_name'          => 'Services',
            'add_new'            => 'Add New Service',
            'add_new_item'       => 'Add New Service',
            'edit_item'          => 'Edit Service',
            'new_item'           => 'New Service',
            'view_item'          => 'View Service',
            'search_items'       => 'Search Services',
            'not_found'          => 'No services found',
            'not_found_in_trash' => 'No services found in Trash',
        );

        register_post_type( 'services', array(
            'labels'        => $labels,
            'public'        => true,
            'show_in_rest'  => true,
            'menu_icon'     => 'dashicons-admin-tools',
            'supports'      => array( 'title', 'editor', 'thumbnail', 'custom-fields' ),
            'has_archive'   => true,
            'rewrite'       => array( 'slug' => 'services' ),
        ) );
    }


    /**
     * Register custom meta fields for Services.
     */
    function register_services_meta_fields() {

        register_post_meta( 'services', 'base_price', array(
            'type'              => 'number',
            'single'            => true,
            'show_in_rest'      => true,
            'sanitize_callback' => 'sanitize_text_field',
            'auth_callback'     => function () {
                return current_user_can( 'edit_posts' );
            },
        ) );

        register_post_meta( 'services', 'delivery_time_days', array(
            'type'              => 'integer',
            'single'            => true,
            'show_in_rest'      => true,
            'sanitize_callback' => 'absint',
            'auth_callback'     => function () {
                return current_user_can( 'edit_posts' );
            },
        ) );
    }
    
    /**
    * Shortcode: [recent_services]
    * Displays the 3 most recently updated services.
    */
   function recent_services_shortcode() {

        $services = new WP_Query( array(
            'post_type'      => 'services',
            'post_status'    => 'publish',
            'posts_per_page' => 3,
            'orderby'        => 'modified',
            'order'          => 'DESC',
            'no_found_rows'  => true,
        ) );

        if ( ! $services->have_posts() ) {
            return '<p>No services found.</p>';
        }

        // Configure optional add-ons and their prices here.
        $addons = array(
            'priority_turnaround' => array(
                'label' => 'Priority Turnaround',
                'price' => 50,
            ),
            'seo_setup' => array(
                'label' => 'SEO Setup',
                'price' => 75,
            ),
            'express_support' => array(
                'label' => 'Express Support',
                'price' => 30,
            ),
        );

        ob_start();

        echo '<div class="recent-services">';

        while ( $services->have_posts() ) {
            $services->the_post();

            $service_id = get_the_ID();

            $base_price = get_post_meta(
                $service_id,
                'base_price',
                true
            );

            $delivery_days = get_post_meta(
                $service_id,
                'delivery_time_days',
                true
            );

            $base_price = is_numeric( $base_price )
                ? max( 0, (float) $base_price )
                : 0;

            $delivery_days = absint( $delivery_days );

            echo '<article class="service-item">';

            echo '<h3>';
            echo '<a href="' . esc_url( get_permalink() ) . '">';
            echo esc_html( get_the_title() );
            echo '</a>';
            echo '</h3>';

            echo '<div class="service-description">';
            echo wp_kses_post( get_the_excerpt() );
            echo '</div>';

            echo '<div class="service-calculator" data-base-price="'
                . esc_attr( $base_price ) . '">';

            echo '<p><strong>Base Price:</strong> ';
            echo esc_html(
                '$' . number_format( $base_price, 2 )
            );
            echo '</p>';

            if ( $delivery_days > 0 ) {
                echo '<p><strong>Delivery Time:</strong> ';
                echo esc_html( $delivery_days );
                echo ' days</p>';
            }

            echo '<fieldset class="service-addons">';
            echo '<legend><strong>Optional Add-ons</strong></legend>';

            foreach ( $addons as $key => $addon ) {
                $input_id = 'service-' . $service_id . '-' . $key;

                echo '<div class="service-addon">';

                echo '<label for="' . esc_attr( $input_id ) . '">';

                echo '<input type="checkbox"'
                    . ' id="' . esc_attr( $input_id ) . '"'
                    . ' class="service-addon-toggle"'
                    . ' data-addon-price="' . esc_attr( $addon['price'] ) . '"> ';

                echo esc_html( $addon['label'] );
                echo ' (+' . esc_html(
                    '$' . number_format( $addon['price'], 2 )
                ) . ')';

                echo '</label>';
                echo '</div>';
            }

            echo '</fieldset>';

            echo '<p class="service-estimate">';
            echo '<strong>Estimated Price: </strong>';
            echo '<span class="service-estimated-price" aria-live="polite">';
            echo esc_html( '$' . number_format( $base_price, 2 ) );
            echo '</span>';
            echo '</p>';

            echo '</div>'; // .service-calculator
            echo '</article>';
        }

        echo '</div>';

        wp_reset_postdata();

        return ob_get_clean();
    }
   
   function services_content_append( $content ){
       //return 'Test';
       if( is_singular('services') AND in_the_loop() AND is_main_query() ){
           ob_start();
           $service_id = get_the_ID();
           $base_price          = get_post_meta( $service_id, 'base_price', true );
           $delivery_time_days  = get_post_meta( $service_id, 'delivery_time_days', true );
           echo '<p>';
           if ( $base_price !== '' ) {
               echo '<strong>Base Price:</strong> ';
               echo esc_html( number_format( (float) $base_price, 2 ) );
               echo ' | ';
           }

           if ( $delivery_time_days !== '' ) {
               echo '<strong>Delivery Time:</strong> ';
               echo esc_html( $delivery_time_days );
               echo ' days';
           }
           echo '</p>';
           return ob_get_clean().$content;
       }
       
       //Return default content
       return $content;
   }
}
