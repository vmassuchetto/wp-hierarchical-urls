<?php
/*
 * Plugin Name: Hierarchical URLs
 * Plugin URI: http://vinicius.soylocoporti.org.br
 * Description: Will generate hierarchical URLs like <code>category-name/subcategory-name/subsubcategory-name/post-slug</code> for all post types and taxonomies.
 * Version: 0.01
 * Author: Vinicius Massuchetto
 * Author URI: http://vinicius.soylocoporti.org.br
 */

class Hierarchical_Urls {

    function Hierarchical_Urls() {

        add_filter( 'post_link', array( $this, 'post_link' ), 10, 3 );
        add_filter( 'term_link', array( $this, 'term_link' ), 10, 3 );

        if ( ! is_admin() ) {
            add_filter( 'rewrite_rules_array', array( $this, 'rewrite_rules_array' ) );
            add_action( 'wp_loaded', array( $this, 'flush_rules' ) );

        }

        //add_action( 'wp_loaded', array( $this, 'debug_rules' ) );

    }

    function debug_rules() {
        global $wp_query, $wp_rewrite;
        print_r( $wp_query );
        print_r( $wp_rewrite->rewrite_rules() );
    }

    function flush_rules() {
        if ( ! $rules = get_option( 'rewrite_rules' ))
            $rules = array();
        $plugin_rules = $this->get_rules() + $rules;
        foreach ( array_keys( $plugin_rules ) as $r ) {
            if ( empty( $rules[$r] ) ) {
                global $wp_rewrite;
                $wp_rewrite->flush_rules();
                break;
            }
        }
    }

    function post_link( $permalink, $post, $leavename ) {

        $categories = array_reverse( get_the_category( $post->ID ) );
        if ( empty( $categories[0] ) )
            return $post_link;

        $category = $categories[0];
        $path = array( $category->slug );

        while ( ( $category = get_category( $category->parent ) )
            && ( ! empty( $category->term_id ) ) ) {
            $path[] = $category->slug;
        }

        $path = array_reverse( $path );
        $path[] = $post->post_name;

        return home_url( implode( '/', $path ) );

    }

    function term_link ( $permalink, $term, $leavename ) {
        $oterm = $term;
        $path = array( $term->slug );
        while ( ( $term = get_term( $term->parent, $term->taxonomy ) )
            && ( ! empty( $term->parent ) ) ) {
            $path[] = $term->slug;
        }

        if ( 'category' != $oterm->taxonomy ) {
            $taxonomy = get_taxonomy( $oterm->taxonomy );
            $path[] = $taxonomy->rewrite['slug'];
        }
        return home_url( implode( '/', array_reverse( $path ) ) );
    }

    function rewrite_rules_array( $rules ) {
        return $this->get_rules() + $rules;
    }

    function get_entities() {
        $entities = array();
        $types = get_post_types( array( 'public' => true ) );
        foreach ( $types as $t ) {
            $post_type = get_post_type_object( $t );
            $entities[] = array(
                'post_type' => $post_type,
                'taxonomies' => get_object_taxonomies( $post_type->name, 'objects' )
            );
        }
        return $entities;
    }

    function get_rules_tree( $args = false ) {

        $defaults = array(
            'post_type' => 'post',
            'taxonomy_name' => 'category',
            'parent' => 0,
            'path' => array()
        );
        $args = wp_parse_args( $args, $defaults );

        $terms = get_terms( $args['taxonomy_name'], array( 'parent' => $args['parent'] ) );
        if ( empty( $terms ) )
            return array();

        $rules = array();
        foreach( $terms as $term ) {

            $term_path = $args['path'];
            $term_path_match = $args['path'];

            $term_path[] = $term->slug;
            $term_path_match[] = '(' . $term->slug . ')';

            $term_path_str = implode( '/', $term_path );
            $term_path_match_str = implode( '/', $term_path_match );

            $feed_sufix = 'feed/?(rss|rss2|atom)?/?$';
            $page_sufix = 'page/?([0-9]{1,})?/?$';

            // Posts
            $rules[$term_path_str . '/([^/]+)/?$'] =
                'index.php?name=$matches[1]';

            // Term
            $rules[$term_path_match_str . '/?$'] =
                'index.php?taxonomy=' . $args['taxonomy_name'] . '&term=$matches[1]';

            // Feeds
            $rules[$term_path_match_str . '/' . $feed_sufix] =
                'index.php?taxonomy=' . $args['taxonomy_name'] . '&term=$matches[1]&feed=atom';

            // Pagination
            $rules[$term_path_match_str . '/' . $page_sufix] =
                'index.php?taxonomy=' . $args['taxonomy_name'] . '&term=$matches[1]&paged=$matches[2]';

            $subrules = $this->get_rules_tree( array(
                'parent' => $term->term_id,
                'path' => $term_path
            ) );

            if ( $subrules )
                $rules = array_merge( $rules, $subrules );

        }
        return $rules;
    }

    function get_rules() {

        $rules = array();

        foreach ( $this->get_entities() as $e ) {

            // Post Type
            $rules[$e['post_type']->name . '/?$'] =
                'index.php?post_type=' . $e['post_type']->name;

            foreach( $e['taxonomies'] as $t ) {
                $args = array(
                    'post_type' => $e['post_type']->name,
                    'taxonomy_name' => $t->name
                );
                $rules = array_merge( $rules, $this->get_rules_tree( $args ) );
            }

        }

        return $rules;
    }

}

function hierarchical_urls_init() {
    new Hierarchical_Urls();
}
add_action( 'plugins_loaded', 'hierarchical_urls_init' );