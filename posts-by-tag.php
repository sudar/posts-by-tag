<?php
/**
Plugin Name: Posts By Tag
Plugin Script: posts-by-tag.php
Plugin URI: http://sudarmuthu.com/wordpress/posts-by-tag
Description: Provide sidebar widgets that can be used to display posts from a set of tags in the sidebar
Author: Sudar
Donate Link: http://sudarmuthu.com/if-you-wanna-thank-me
License: GPL
Version: 3.0.4
Author URI: http://sudarmuthu.com/
Text Domain: posts-by-tag
Domain Path: languages/

=== RELEASE NOTES ===
Check readme file for full release notes
*/

/*  Copyright 2009  Sudar Muthu  (email : sudar@sudarmuthu.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

if ( !class_exists( 'TagWidget' ) ) {
    require_once dirname( __FILE__ ) . '/include/class-tagwidget.php';
}

/**
 * The main Plugin class
 *
 * @package Posts_By_Tag
 * @subpackage default
 * @author Sudar
 */
class Posts_By_Tag {

    // boolean fields that needs to be validated
    private $boolean_fields     = array( 
                'tag_from_post',
                'tag_from_post_slug',
                'tag_from_post_custom_field',
                'exclude',
                'exclude_current_post',
                'excerpt',
                'excerpt_filter',
                'content',
                'content_filter',
                'thumbnail',
                'author',
                'date',
                'tag_links'
            );

    // constants
    const VERSION               = '3.0.4';
    const CUSTOM_POST_FIELD_OLD = 'posts_by_tag_page_fields'; // till v 2.7.4
    const CUSTOM_POST_FIELD     = '_posts_by_tag_page_fields';

    // Filters
    const FILTER_PERMALINK      = 'pbt_permalink_filter';
    const FILTER_ONCLICK        = 'pbt_onclick_filter';
    const FILTER_PRO_ANALYTICS  = 'pbt_pro_analytics_filter';

    public static $TEMPLATES    = array( '[TAGS]', '[POST_ID]', '[POST_SLUG]' );

    /**
     * Initalize the plugin by registering the hooks
     */
    function __construct() {

        // Load localization domain
        $this->translations = dirname( plugin_basename( __FILE__ ) ) . '/languages/' ;
        load_plugin_textdomain( 'posts-by-tag', FALSE, $this->translations );

        // Register hooks
        add_action('admin_print_scripts', array(&$this, 'add_script'));
        add_action('admin_head', array(&$this, 'add_script_config'));
        
        /* Use the admin_menu action to define the custom boxes */
        add_action('admin_menu', array(&$this, 'add_custom_box'));

        /* Use the save_post action to do something with the data entered */
        add_action('save_post', array(&$this, 'save_postdata'));

        // Add more links in the plugin listing page
        add_filter( 'plugin_row_meta', array( &$this, 'add_plugin_links' ), 10, 2 );  

        //Short code
        add_shortcode('posts-by-tag', array(&$this, 'shortcode_handler'));
    }

    /**
     * Add script to admin page
     */
    function add_script() {
        if ($this->is_on_plugin_page()) {
            // Build in tag auto complete script
            wp_enqueue_script( 'suggest' );
        }
    }

    /**
     * add script to admin page
     */
    function add_script_config() {
        // Add script only to Widgets page
        if ($this->is_on_plugin_page()) {
            //TODO: Move this to a seperate js file
?>

<script type="text/javascript">
    // Function to add auto suggest
    function setSuggest(id) {
        jQuery('#' + id).suggest("<?php echo get_bloginfo('wpurl'); ?>/wp-admin/admin-ajax.php?action=ajax-tag-search&tax=post_tag", {multiple:true, multipleSep: ","});
    }

    function thumbnailChanged(id, size_id) {
        if (jQuery('#' + id).is(':checked')) {
            jQuery('#'  + size_id).parents('p').show();
            thumbnailSizeChanged(size_id);
        } else {
            jQuery('#' + size_id).parents('p').hide();
        }
    }

    function thumbnailSizeChanged(id) {
        if (jQuery('#' + id).val() === 'custom') {
            jQuery('#' + id + '-span').show();
        } else {
            jQuery('#' + id + '-span').hide();
        }
    }
</script>
<?php
        }
    }

    /**
     * Adds additional links in the Plugin listing. Based on http://zourbuth.com/archives/751/creating-additional-wordpress-plugin-links-row-meta/
     */
    function add_plugin_links($links, $file) {
        $plugin = plugin_basename(__FILE__);

        if ($file == $plugin) // only for this plugin
            return array_merge( $links, 
            array( '<a href="http://sudarmuthu.com/wordpress/posts-by-tag/pro-addons?utm_source=wpadmin&utm_campaign=PostsByTag&utm_medium=plugin-listing&utm_content=pro-addon" target="_blank">' . __('Buy Addons', 'posts-by-tag') . '</a>' )
        );
        return $links;
    }

    /**
     * Adds the custom section in the edit screens for all post types
     */
    function add_custom_box() {
		$post_types = get_post_types( array(), 'objects' );
		foreach ( $post_types as $post_type ) {
			if ( $post_type->show_ui ) {
                add_meta_box( 'posts_by_tag_page_box', __( 'Posts By Tag Page Fields', 'posts-by-tag' ),
                    array(&$this, 'inner_custom_box'), $post_type->name, 'side' );
			}
        }
    }

    /**
     * Prints the inner fields for the custom post/page section
     */
    function inner_custom_box() {
        global $post;
        $post_id = $post->ID;

        $widget_title = '';
        $widget_tags = '';

        if ($post_id > 0) {
            Posts_By_Tag::update_postmeta_key( $post_id );
            $posts_by_tag_page_fields = get_post_meta( $post_id, self::CUSTOM_POST_FIELD, TRUE );

            if (isset($posts_by_tag_page_fields) && is_array($posts_by_tag_page_fields)) {
                $widget_title = $posts_by_tag_page_fields['widget_title'];
                $widget_tags = $posts_by_tag_page_fields['widget_tags'];
            }
        }
        // Use nonce for verification
?>
        <input type="hidden" name="posts_by_tag_noncename" id="posts_by_tag_noncename" value="<?php echo wp_create_nonce( plugin_basename(__FILE__) );?>" />
        <p>
            <label> <?php _e('Widget Title', 'posts-by-tag'); ?> <input type="text" name="widget_title" value ="<?php echo $widget_title; ?>"></label><br>
            <label> <?php _e('Widget Tags', 'posts-by-tag'); ?> <input type="text" name="widget_tags" id = "widget_tags" value ="<?php echo $widget_tags; ?>" onfocus ="setSuggest('widget_tags');"></label>
        </p>
<?php
    }

    /**
     * When the post is saved, saves our custom data
     * @param string $post_id
     * @return string return post id if nothing is saved
     */
    function save_postdata( $post_id ) {

        // Don't do anything during Autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return $post_id;
        }

        // verify this came from the our screen and with proper authorization,
        // because save_post can be triggered at other times

		if ( !array_key_exists('posts_by_tag_noncename', $_POST)) {
			return $post_id;
		}

        if ( !wp_verify_nonce( $_POST['posts_by_tag_noncename'], plugin_basename(__FILE__) )) {
            return $post_id;
        }

        if ( 'page' == $_POST['post_type'] ) {
            if ( !current_user_can( 'edit_page', $post_id )) {
                return $post_id;
            }
        } elseif (!current_user_can('edit_post', $post_id)) { 
            return $post_id;
        }

        // OK, we're authenticated: we need to find and save the data

        $fields = array();

        if (isset($_POST['widget_title'])) {
            $fields['widget_title'] = $_POST['widget_title'];
        } else {
            $fields['widget_title'] = '';
        }

        if (isset($_POST['widget_tags'])) {
            $fields['widget_tags'] = $_POST['widget_tags'];
        } else {
            $fields['widget_tags'] = '';
        }

        update_post_meta($post_id, self::CUSTOM_POST_FIELD, $fields);

    }

    /**
     * Expand the shortcode
     *
     * @param <array> $attributes
     */
    function shortcode_handler($attributes) {
        $options = shortcode_atts(array(
            'tags'                       => '',   // comma Separated list of tags
            'number'                     => 5,
            'tag_from_post'              => FALSE,
            'tag_from_post_slug'         => FALSE,
            'tag_from_post_custom_field' => FALSE,
            'exclude'                    => FALSE,
            'exclude_current_post'       => FALSE,
            'excerpt'                    => FALSE,
            'excerpt_filter'             => TRUE,
            'content'                    => FALSE,
            'content_filter'             => TRUE,
            'thumbnail'                  => FALSE,
            'thumbnail_size'             => 'thumbnail',
            'thumbnail_size_width'       => 100,
            'thumbnail_size_height'      => 100,
            'order_by'                   => 'date',
            'order'                      => 'desc',
            'author'                     => FALSE,
            'date'                       => FALSE,
            'tag_links'                  => FALSE,
            'link_target'                => ''
        ), $attributes);

        $options = pbt_validate_boolean_options($options, $this->boolean_fields);
        $tags = $options['tags'];

        // call the template function
        $output = get_posts_by_tag($tags, $options);

        if ($options['tag_links'] && !$options['exclude']) {
            $output .= pbt_get_tag_more_links($tags);
        }

        return $output;
    }

    /**
     * Check whether you are on a Plugin page
     *
     * @return boolean
     * @author Sudar
     */
    private function is_on_plugin_page() {
        if ( strstr( $_SERVER['REQUEST_URI'], 'wp-admin/post-new.php' ) || 
                strstr( $_SERVER['REQUEST_URI'], 'wp-admin/post.php' ) ||
                strstr( $_SERVER['REQUEST_URI'], 'wp-admin/edit.php' ) ||
                $this->is_widget_page() ) {
            return TRUE; 
        } else {
            return FALSE;
        }
    }

    /**
     * Check whether you are on the widget page
     */
    private function is_widget_page() {
        if ( strstr( $_SERVER['REQUEST_URI'], 'wp-admin/widgets.php' ) ) {
            return TRUE;
        } else {
            return False;
        }
    }

    /**
     * Update postmeta key.
     *
     * Uptill v2.7.4 the Plugin was using a old postmeta key without '_'. 
     * This function updates the postmeta key. Eventually this function will be removed.
     *
     * @return void
     */
    public static function update_postmeta_key( $post_id ) {
        $old_value = get_post_meta($post_id, self::CUSTOM_POST_FIELD_OLD, TRUE);

        if (isset($old_value) && is_array($old_value)) {
            update_post_meta($post_id, self::CUSTOM_POST_FIELD, $old_value);
            delete_post_meta($post_id, self::CUSTOM_POST_FIELD_OLD);
        }
    }
}

// Start this plugin once all other plugins are fully loaded
add_action( 'init', 'Posts_By_Tag_Init' ); function Posts_By_Tag_Init() { global $Posts_By_Tag; $Posts_By_Tag = new Posts_By_Tag(); }

// register TagWidget widget
add_action('widgets_init', create_function('', 'return register_widget("TagWidget");'));

/**
 * Template function to display posts by tags
 *
 * @param string $tags List of tags from where the posts should be retrieved.
 *          If you want the plugin to automatically pick up tags from the current post,
 *          then set either one of the following options to true and leave tags empty
 *          - tag_from_post
 *          - tag_from_post_slug
 *          - tag_from_post_custom_field
 * @param array $options An array which has the following values
 *       - int number Number of posts to show
 *       - bool tag_from_post Whether to get tags from current post's tags. Default is FALSE
 *       - bool tag_from_post_slug Whether to get tags from current post's slug. Default is FALSE
 *       - bool tag_from_post_custom_field Whether to get tags from current post's custom field. Default is FALSE
 *       - bool exclude Whether to exclude the tags specified. Default is FALSE
 *       - bool excerpt - Whether to display excerpts or not
 *       - bool excerpt_filter - Whether to enable or disable excerpt filter
 *       - bool thumbnail - Whether to display thumbnail or not
 *       - string|array thumbnail_size - Size of the thumbnail image. Refer to http://codex.wordpress.org/Function_Reference/get_the_post_thumbnail#Thumbnail_Sizes
 *       - set order_by (title, date, rand) defaults to 'date'
 *       - set order (asc, desc) defaults to 'desc'
 *       - bool author - Whether to show the author name or not
 *       - bool date - Whether to show the post date or not
 *       - bool content - Whether to display content or not
 *       - bool content_filter - Whether to enable or disable content filter
 *       - bool exclude_current_post Whether to exclude the current post/page. Default is FALSE
 *       - bool tag_links Whether to display tag links at the end
 *       - string link_target the value to the target attribute of each links that needs to be added
 *       
 * @return string $output The posts HTML content 
 */
function posts_by_tag( $tags = '', $options = array() ) {
    $output = get_posts_by_tag($tags, $options);

    if ($options['tag_links'] && !$option['exclude']) {
        $output .= pbt_get_tag_more_links($tags);
    }

    echo $output;
}

/**
 * Helper function for @link posts_by_tag
 * 
 * @link posts_by_tag for information about parameters
 * @see posts_by_tag
 *
 */
function get_posts_by_tag( $giventags = '', $options = array() ) {
    global $wp_query;
    global $post;

    $current_post_id = $wp_query->post->ID;
    $output          = '';

    $options         = wp_parse_args( $options, array(
                            'number'                     => 5,
                            'tag_from_post'              => FALSE,
                            'tag_from_post_slug'         => FALSE,
                            'tag_from_post_custom_field' => FALSE,
                            'exclude'                    => FALSE,
                            'excerpt'                    => FALSE,
                            'thumbnail'                  => FALSE,
                            'thumbnail_size'             => 'thumbnail',
                            'thumbnail_size_width'       => 100,
                            'thumbnail_size_height'      => 100,
                            'order_by'                   => 'date',
                            'order'                      => 'desc',
                            'author'                     => FALSE,
                            'date'                       => FALSE,
                            'content'                    => FALSE,
                            'content_filter'             => TRUE,
                            'exclude_current_post'       => FALSE,
                            'tag_links'                  => FALSE,
                            'link_target'                => ''
                        )
                    );

    extract( $options, EXTR_OVERWRITE);
    
    $tag_id_array = pbt_get_tag_id_array( $giventags, $options );
    // append the tag ids to options
    $options['tag_ids'] = $tag_id_array;

    if (count($tag_id_array) > 0) {
        // only if we have atleast one tag. get_posts has a bug. If empty array is passed, it returns all posts. That's why we need this condition
        $tag_arg = 'tag__in';
        if ($exclude) {
            $tag_arg = 'tag__not_in';
        }

        // saving the query
        $temp_query = clone $wp_query;
        $temp_post = $post;

        $tag_posts = get_posts( array( 'numberposts' => $number, $tag_arg => $tag_id_array, 'orderby' => $order_by, 'order' => $order ) );

        if (count($tag_posts) > 0) {
            $output = '<ul class = "posts-by-tag-list">';
            foreach($tag_posts as $tag_post) {
                if ($exclude_current_post && $current_post_id == $tag_post->ID) {
                    // exclude currrent post/page
                    continue;
                }

                setup_postdata($tag_post);
                $tag_post_tags_array = wp_get_post_tags( $tag_post->ID );
                $tag_post_tags = array();

                foreach ( $tag_post_tags_array as $tag_post_tag ) {
                    array_push( $tag_post_tags, $tag_post_tag->name );
                }

                $permalink = apply_filters( Posts_By_Tag::FILTER_PERMALINK, get_permalink( $tag_post->ID ), $options, $tag_post );
                $onclick = apply_filters( Posts_By_Tag::FILTER_ONCLICK, '', $options, $tag_post );

                if ( $onclick != '' ) {
                    $onclick_attr = ' onclick = "' . $onclick . '" ';
                } else {
                    $onclick_attr = '';
                }

                $output .= '<li class="posts-by-tag-item ' . implode( ' ', $tag_post_tags ) . '" id="posts-by-tag-item-' . $tag_post->ID . '">';

                if ($thumbnail) {
                    if (has_post_thumbnail($tag_post->ID)) {
                        if ( $thumbnail_size == 'custom' ) {
                            $t_size = array( $thumbnail_size_width, $thumbnail_size_height );
                        } else {
                            $t_size = $thumbnail_size;
                        }
                        $output .=  '<a class="thumb" href="' . $permalink . '" title="' . get_the_title($tag_post->ID) . '" ' . $onclick_attr . ' >' . 
                            get_the_post_thumbnail($tag_post->ID, $t_size) . 
                            '</a>';
                    } else {
                        if (get_post_meta($tag_post->ID, 'post_thumbnail', true) != '') {
                            $output .=  '<a class="thumb" href="' . $permalink . '" title="' . get_the_title($tag_post->ID) . '" ' . $onclick_attr . '>' . 
                                '<img src="' . esc_url(get_post_meta($tag_post->ID, 'post_thumbnail', true)) . '" alt="' . get_the_title($tag_post->ID) . '" >' . 
                            '</a>';
                        }
                    }
                }

                // add permalink
                $output .= '<a class = "posts-by-tag-item-title" href="' . $permalink . '"';
               
                if ($link_target != '') {
                    $output .= ' target = "' . $link_target . '"';
                }

                $output .= $onclick_attr;
                $output .= '>' . $tag_post->post_title . '</a>';

                if($content) {
                    $more_link_text = '(more...)'; $stripteaser = 0; $more_file = '';
                    $post_content = get_the_content($more_link_text, $stripteaser, $more_file);

                    if ($content_filter) {
                        // apply the content filters
                        $post_content = apply_filters('the_content', $post_content);
                    }

                    $post_content = str_replace(']]>', ']]&gt;', $post_content);

                    $output .= $post_content;
                }

                if ($author) {
                    $output .= ' <small>' . __('Posted by: ', 'posts-by-tag');
                    $output .=  get_the_author_meta('display_name', $tag_post->post_author) . '</small>';
                }

                if ($date) {
                    $output .= ' <small>' . __('Posted on: ', 'posts-by-tag');
                    $output .=  mysql2date(get_option('date_format'), $tag_post->post_date) . '</small>';
                }

                if( $excerpt ) {
                    $output .=  '<br />';
                    if ($tag_post->post_excerpt != NULL)
                        if ($excerpt_filter) {
                            $output .= apply_filters('the_excerpt', $tag_post->post_excerpt);
                        } else {
                            $output .= $tag_post->post_excerpt;
                        }
                    else
                        $output .= get_the_excerpt();
                }
                $output .=  '</li>';
            }

            $output .=  '</ul>';
        }

        // restoring the query so it can be later used to display our posts
        $wp_query = clone $temp_query;
        $post = $temp_post;
    }

    // if there were no posts, then don't return any content
    if ( '<ul class = "posts-by-tag-list"></ul>' == $output ) {
        $output = '';
    }
    return $output;
}

function pbt_get_tag_id_array( $tags, $options ) {
    global $post;

    $tag_id_array = array();

    if ( $options['tag_from_post'] || $options['tag_from_post_slug'] || $options['tag_from_post_custom_field'] ) {
        
        // if tags is empty then try to take it from current posts
        if ( is_singular() && !is_attachment() ) {

            if ( $options['tag_from_post'] ) {
                $tag_array = wp_get_post_tags( $post->ID );
                foreach ($tag_array as $tag) {
                    $tag_id_array[] = $tag->term_id;
                }
                return $tag_id_array;
            }

            if ( $options['tag_from_post_slug'] ) {
                if ($post->ID > 0) {
                    $tags = get_post( $post )->post_name; 
                }
            }

            if ( $options['tag_from_post_custom_field'] ) {
                if ($post->ID > 0) {
                    Posts_By_Tag::update_postmeta_key( $post_id );
                    $posts_by_tag_page_fields = get_post_meta( $post_id, Posts_By_Tag::CUSTOM_POST_FIELD, TRUE );

                    if (isset($posts_by_tag_page_fields) && is_array($posts_by_tag_page_fields)) {
                        if ($posts_by_tag_page_fields['widget_tags'] != '') {
                            $tags = $posts_by_tag_page_fields['widget_tags'];
                        }
                    }
                }
            }
        }
    }

    if ( '' != $tags ) {
        // Get array of post info.
        $tag_array = explode( ",", $tags );

        foreach ($tag_array as $tag) {
            $tag_id_array[] = pbt_get_tag_ID(trim($tag));
        }
    }
    
    return $tag_id_array;
}

/**
 * Get tag more links for a bunch of tags. Exposed as a template function
 *
 * @param <type> $tags
 * @param <type> $prefix
 */
function pbt_get_tag_more_links($tags, $prefix = 'More posts: ') {
    global $wp_query;
    
    $tag_array = array();
    $output = '';
    
    if ($tags == '') {
        // if tags is empty then take from current posts
        if (is_single()) {
            $tag_array = wp_get_post_tags($wp_query->post->ID);
        }
    } else {
        $tag_array = explode(",", $tags);
    }

    if (count($tag_array) > 0) {
        $output = '<p>' . $prefix;
        
        foreach ($tag_array as $tag) {
            $tag_name = $tag;
            if (is_object($tag)) {
                $tag_name = $tag->name;
            }

            $output .= pbt_get_tag_more_link($tag_name);
        }

        $output .= '</p>';
    }

    return $output;
}

/**
 * Get tag more link for a single tag
 *
 * @param <type> $tag
 * @return <type>
 */
function pbt_get_tag_more_link($tag) {
    return '<a href = "' . get_tag_link(pbt_get_tag_ID($tag)) . '">' . $tag . '</a> ';
}

/**
 * get tag id from tag name or slug
 *
 * @param <string> $tag_name - Tag name or slug
 * @return <int> term id. 0 if not found
 */
function pbt_get_tag_ID($tag_name) {
    $tag = get_term_by('name', $tag_name, 'post_tag');
    if ($tag) {
        return $tag->term_id;
    } else {
        $tag = get_term_by( 'slug', $tag_name, 'post_tag' );
        if ( $tag ) {
            return $tag->term_id;
        }
        return 0;
    }
}

/**
 * Validate Boolean options
 *
 * @param <array> - $options to validate
 * @param <array> - List of boolean fields
 *
 * @return <array> - Validated Options
 * @author Sudar
 */
function pbt_validate_boolean_options($options, $fields) {
    $validated_options = array();

    foreach($options as $key => $value) {
        if (in_array($key, $fields)) {
            $validated_options[$key] = filter_var($value, FILTER_VALIDATE_BOOLEAN);
        } else {
            $validated_options[$key] = $value; 
        }
    }

    return $validated_options;
}
?>
