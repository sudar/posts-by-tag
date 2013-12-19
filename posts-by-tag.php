<?php
/**
Plugin Name: Posts By Tag
Plugin Script: posts-by-tag.php
Plugin URI: http://sudarmuthu.com/wordpress/posts-by-tag
Description: Provide sidebar widgets that can be used to display posts from a set of tags in the sidebar.
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

/**
 * The main Plugin class
 *
 * @package Posts_By_Tag
 * @subpackage default
 * @author Sudar
 */
class Posts_By_Tag {

    // boolean fields that needs to be validated
    private $boolean_fields = array( 'exclude', 'exclude_current_post', 'excerpt', 'excerpt_filter', 'content', 'content_filter', 'thumbnail', 'author', 'date', 'tag_links');

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
            "tags"                  => '',   // comma Separated list of tags
            "number"                => 5,
            "exclude"               => FALSE,
            "exclude_current_post"  => FALSE,
            "excerpt"               => FALSE,
            "excerpt_filter"        => TRUE,
            "content"               => FALSE,
            "content_filter"        => TRUE,
            'thumbnail'             => FALSE,
            'thumbnail_size'        => 'thumbnail',
            'thumbnail_size_width'  => 100,
            'thumbnail_size_height' => 100,
            'order_by'              => 'date',
            'order'                 => 'desc',
            'author'                => FALSE,
            'date'                  => FALSE,
            'tag_links'             => FALSE,
            'link_target'           => ''
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
 * TagWidget Class - Wrapper for the widget
 *
 * @package Posts_By_Tag
 * @subpackage Widgets
 * @author Sudar
 */
class TagWidget extends WP_Widget {
    /** constructor */
    function TagWidget() {
		/* Widget settings. */
		$widget_ops = array( 'classname' => 'TagWidget', 'description' => __('Widget that shows posts from a set of tags', 'posts-by-tag'));

		/* Widget control settings. */
		$control_ops = array('id_base' => 'tag-widget' );

		/* Create the widget. */
		parent::WP_Widget( 'tag-widget', __('Posts By Tag', 'posts-by-tag'), $widget_ops, $control_ops );
    }

    /** @see WP_Widget::widget */
    function widget($args, $instance) {
        global $post;

        extract( $args );

        $tags                 = $instance['tags'];
        $current_tags         = (bool) $instance['current_tags'];
        $current_page_tags    = (bool) $instance['current_page_tags'];

        $tag_links            = (bool) $instance['tag_links'];
        $disable_cache        = (bool) $instance['disable_cache'];
        $link_target          = $instance['link_target'];

        $title                = $instance['title'];
        $post_id              = $post->ID;

        if ($current_tags) {
            // if current tags is enabled then set tags to empty
            $tags = '';
        }

        if ($current_page_tags) {
            $tags = ''; // reset the tags
            // get tags and title from page custom fields

            if ($post_id > 0) {
                Posts_By_Tag::update_postmeta_key( $post_id );
                $posts_by_tag_page_fields = get_post_meta( $post_id, Posts_By_Tag::CUSTOM_POST_FIELD, TRUE );

                if (isset($posts_by_tag_page_fields) && is_array($posts_by_tag_page_fields)) {
                    if ($posts_by_tag_page_fields['widget_title'] != '') {
                        $title = $posts_by_tag_page_fields['widget_title'];
                    }
                    if ($posts_by_tag_page_fields['widget_tags'] != '') {
                        $tags = $posts_by_tag_page_fields['widget_tags'];
                    }
                }
            }
        }

        if (($current_tags || $current_page_tags) && !is_singular()) {
            // either current post tags or page custom tags is enabled and it is not a singular page
            $widget_content = '';
        } else {
            if ($current_page_tags && $tags == '') {
                $widget_content = '';
            } else {
                if (($current_tags || $current_page_tags) && is_singular() && $post_id > 0) {
                    $key = "posts-by-tag-$widget_id-$post_id";
                } else {
                    $key = "posts-by-tag-$widget_id";
                }

                if ($disable_cache || (false === ( $widget_content = get_transient( $key ) ) )) {

                    $widget_content = get_posts_by_tag( $tags, $instance );

                    if (!$disable_cache) {
                        // store in cache
                        set_transient($key, $widget_content, 86400); // 60*60*24 - 1 Day
                    }
                }
            }
        }

        if ($widget_content != '') {
            echo $before_widget;
            echo $before_title;
            echo $title;
            echo $after_title;

            echo $widget_content;
            if ($tag_links && !$exclude) {
                echo pbt_get_tag_more_links($tags);
            }

            echo $after_widget;
        }
    }

    /** @see WP_Widget::update */
    function update($new_instance, $old_instance) {
        $instance = $old_instance;
        
        // validate data
        $instance['title']                 = strip_tags($new_instance['title']);
        $instance['tags']                  = strip_tags($new_instance['tags']);
        $instance['current_tags']          = (bool)$new_instance['current_tags'];
        $instance['current_page_tags']     = (bool)$new_instance['current_page_tags'];
        $instance['number']                = intval($new_instance['number']);
        $instance['exclude']               = (bool)$new_instance['exclude'];
        $instance['exclude_current_post']  = (bool)$new_instance['exclude_current_post'];
        $instance['thumbnail']             = (bool)$new_instance['thumbnail'];
        $instance['thumbnail_size']        = strip_tags( $new_instance['thumbnail_size'] );
        $instance['thumbnail_size_width']  = intval( $new_instance['thumbnail_size_width'] );
        $instance['thumbnail_size_height'] = intval( $new_instance['thumbnail_size_height'] );
        $instance['author']                = (bool)$new_instance['author'];
        $instance['date']                  = (bool)$new_instance['date'];
        $instance['excerpt']               = (bool)$new_instance['excerpt'];
        $instance['content']               = (bool)$new_instance['content'];
        $instance['order']                 = ($new_instance['order'] === 'asc') ? 'asc' : 'desc';
        $instance['order_by']              = strip_tags( $new_instance['order_by'] );

        if ( $instance['order_by'] == '' ) {
            $instance['order_by'] = 'date';
        }

        $instance['campaign']              = strip_tags( $new_instance['campaign'] );
        $instance['event']                 = strip_tags( $new_instance['event'] );

        $instance['tag_links']             = (bool)$new_instance['tag_links'];
        $instance['link_target']           = $new_instance['link_target'];
        $instance['disable_cache']         = (bool)$new_instance['disable_cache'];
        
        return $instance;
    }

    /** @see WP_Widget::form */
    function form($instance) {
        
        /* Set up some default widget settings. */
        $defaults = array( 
            'title'                 => '',
            'tags'                  => '',
            'number'                => '5',
            'current_tags'          => FALSE,
            'current_page_tags'     => FALSE,
            'exclude'               => FALSE,
            'exclude_current_post'  => FALSE,
            'thumbnail'             => FALSE,
            'thumbnail_size'        => 'thumbnail',
            'thumbnail_size_width'  => '100',
            'thumbnail_size_height' => '100',
            'author'                => FALSE,
            'date'                  => FALSE,
            'excerpt'               => FALSE,
            'content'               => FALSE,
            'order'                 => 'desc',
            'order_by'              => 'date',
            'campaign'              => '',
            'event'                 => '',
            'tag_links'             => FALSE,
            'link_target'           => FALSE,
            'disable_cache'         => FALSE
        );

        $instance = wp_parse_args( (array) $instance, $defaults );

        $title                 = esc_attr($instance['title']);
        $tags                  = $instance['tags'];
        $number                = intval($instance['number']);
        $current_tags          = (bool) $instance['current_tags'];
        $current_page_tags     = (bool) $instance['current_page_tags'];
        $exclude               = (bool) $instance['exclude'];
        $exclude_current_post  = (bool) $instance['exclude_current_post'];
        $thumbnail             = (bool) $instance['thumbnail'];
        $thumbnail_size        = esc_attr( $instance['thumbnail_size'] );
        $thumbnail_size_width  = intval( $instance['thumbnail_size_width'] );
        $thumbnail_size_height = intval( $instance['thumbnail_size_height'] );
        $author                = (bool) $instance['author'];
        $date                  = (bool) $instance['date'];
        $excerpt               = (bool) $instance['excerpt'];
        $content               = (bool) $instance['content'];
        $order                 = ( strtolower( $instance['order'] ) === 'asc' ) ? 'asc' : 'desc';
        $order_by              = strtolower( $instance['order_by'] );

        $campaign              = esc_attr( $instance['campaign'] );
        $event                 = esc_attr( $instance['event'] );

        $tag_links             = (bool) $instance['tag_links'];
        $link_target           = $instance['link_target'];
        $disable_cache         = (bool) $instance['disable_cache'];

        // show/hide logic
        if ( $thumbnail ) {
            $thumbnail_size_style = 'block';
            if ( $thumbnail_size == 'custom' ) {
                $thumbnail_size_custom_style = 'block';
            } else {
                $thumbnail_size_custom_style = 'none';
            }
        } else {
            $thumbnail_size_style = 'none';
        }

        $is_analytics = apply_filters( Posts_By_Tag::FILTER_PRO_ANALYTICS, FALSE );

        // TODO: Use JavaScript to disable mutually exclusive fields
?>
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:', 'posts-by-tag'); ?>
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" /></label>
        </p>

        <p>
            <label for="<?php echo $this->get_field_id('tags'); ?>">
            <?php _e( 'Tags:' , 'posts-by-tag'); ?><br />
                    <input class="widefat" id="<?php echo $this->get_field_id('tags'); ?>" name="<?php echo $this->get_field_name('tags'); ?>" type="text" value="<?php echo $tags; ?>" onfocus ="setSuggest('<?php echo $this->get_field_id('tags'); ?>');" />
            </label><br />
            <?php _e('Separate multiple tags by comma', 'posts-by-tag');?>
		</p>
        
        <p>
            <label for="<?php echo $this->get_field_id('exclude'); ?>">
            <input type ="checkbox" class ="checkbox" id="<?php echo $this->get_field_id('exclude'); ?>" name="<?php echo $this->get_field_name('exclude'); ?>" value ="true" <?php checked($exclude, true); ?> /></label>
            <?php _e( 'Exclude these tags' , 'posts-by-tag'); ?>
        </p>

        <p>
            <label for="<?php echo $this->get_field_id('exclude_current_post'); ?>">
            <input type ="checkbox" class ="checkbox" id="<?php echo $this->get_field_id('exclude_current_post'); ?>" name="<?php echo $this->get_field_name('exclude_current_post'); ?>" value ="true" <?php checked($exclude_current_post, true); ?> /></label>
            <?php _e( 'Exclude current post/page' , 'posts-by-tag'); ?>
        </p>

        <p>
            <label for="<?php echo $this->get_field_id('current_tags'); ?>">
            <input type ="checkbox" class ="checkbox" id="<?php echo $this->get_field_id('current_tags'); ?>" name="<?php echo $this->get_field_name('current_tags'); ?>" value ="true" <?php checked($current_tags, true); ?> /></label>
            <?php _e( 'Get tags from current Post' , 'posts-by-tag'); ?>
        </p>

        <p>
            <label for="<?php echo $this->get_field_id('current_page_tags'); ?>">
            <input type ="checkbox" class ="checkbox" id="<?php echo $this->get_field_id('current_page_tags'); ?>" name="<?php echo $this->get_field_name('current_page_tags'); ?>" value ="true" <?php checked($current_page_tags, true); ?> /></label>
            <?php _e( 'Get tags and title from custom fields. You need to set the custom field for each post/page.' , 'posts-by-tag'); ?>
        </p>

        <p>
            <label for="<?php echo $this->get_field_id('number'); ?>">
				<?php _e('Number of posts to show:', 'posts-by-tag'); ?>
            <input style="width: 25px; text-align: center;" id="<?php echo $this->get_field_id('number'); ?>" name="<?php echo $this->get_field_name('number'); ?>" type="text" value="<?php echo $number; ?>" /></label>
        </p>

        <p>
            <label for="<?php echo $this->get_field_id('thumbnail'); ?>">
            <input type ="checkbox" class ="checkbox" id="<?php echo $this->get_field_id('thumbnail'); ?>" name="<?php echo $this->get_field_name('thumbnail'); ?>" value ="true" <?php checked($thumbnail, true); ?> onchange = "thumbnailChanged(<?php echo "'", $this->get_field_id( 'thumbnail' ), "','", $this->get_field_id( 'thumbnail_size' ) , "'" ?>);" ></label>
            <?php _e( 'Show post thumbnails' , 'posts-by-tag'); ?>
        </p>

        <p style = "display: <?php echo $thumbnail_size_style; ?> ;">
            <label for="<?php echo $this->get_field_id('thumbnail_size'); ?>"><?php _e( 'Select thumbnail size' , 'posts-by-tag') ?></label>
            <select id = "<?php echo $this->get_field_id( 'thumbnail_size' ); ?>" name = "<?php echo $this->get_field_name( 'thumbnail_size' ); ?>" onchange = "thumbnailSizeChanged(<?php echo "'", $this->get_field_id( 'thumbnail_size' ), "'"; ?>);">
                <option value = "thumbnail" <?php selected( $thumbnail_size, 'thumbnail' ); ?> ><?php _e( 'Thumbnail', 'posts-by-tag' ); ?></option>
                <option value = "medium" <?php selected( $thumbnail_size, 'medium' ); ?> ><?php _e( 'Medium', 'posts-by-tag' ); ?></option>
                <option value = "large" <?php selected( $thumbnail_size, 'large' ); ?> ><?php _e( 'Large', 'posts-by-tag' ); ?></option>
                <option value = "full" <?php selected( $thumbnail_size, 'full' ); ?> ><?php _e( 'Full', 'posts-by-tag' ); ?></option>
                <option value = "custom" <?php selected( $thumbnail_size, 'custom' ); ?> ><?php _e( 'Custom', 'posts-by-tag' ); ?></option>
            </select>

            <span id = "<?php echo $this->get_field_id( 'thumbnail_size' ); ?>-span" style = "display: <?php echo $thumbnail_size_custom_style; ?> ;">
                <?php _e('Custom size:', 'posts-by-tag'); ?>
                <input style=" text-align: center;" id="<?php echo $this->get_field_id('thumbnail_size_width'); ?>" name="<?php echo $this->get_field_name('thumbnail_size_width'); ?>" size = "4" type="text" value="<?php echo $thumbnail_size_width; ?>" /> x
                <input style=" text-align: center;" id="<?php echo $this->get_field_id('thumbnail_size_height'); ?>" name="<?php echo $this->get_field_name('thumbnail_size_height'); ?>" size = "4" type="text" value="<?php echo $thumbnail_size_height; ?>" />
            </span>
        </p>

        <p>
            <label for="<?php echo $this->get_field_id('author'); ?>">
            <input type ="checkbox" class ="checkbox" id="<?php echo $this->get_field_id('author'); ?>" name="<?php echo $this->get_field_name('author'); ?>" value ="true" <?php checked($author, true); ?> /></label>
            <?php _e( 'Show author name' , 'posts-by-tag'); ?>
        </p>

        <p>
            <label for="<?php echo $this->get_field_id('date'); ?>">
            <input type ="checkbox" class ="checkbox" id="<?php echo $this->get_field_id('date'); ?>" name="<?php echo $this->get_field_name('date'); ?>" value ="true" <?php checked($date, true); ?> /></label>
            <?php _e( 'Show post date' , 'posts-by-tag'); ?>
        </p>

        <p>
            <label for="<?php echo $this->get_field_id('excerpt'); ?>">
            <input type ="checkbox" class ="checkbox" id="<?php echo $this->get_field_id('excerpt'); ?>" name="<?php echo $this->get_field_name('excerpt'); ?>" value ="true" <?php checked($excerpt, true); ?> /></label>
				<?php _e( 'Show post excerpt' , 'posts-by-tag'); ?>
        </p>

        <p>
            <label for="<?php echo $this->get_field_id('content'); ?>">
            <input type ="checkbox" class ="checkbox" id="<?php echo $this->get_field_id('content'); ?>" name="<?php echo $this->get_field_name('content'); ?>" value ="true" <?php checked($content, true); ?> /></label>
				<?php _e( 'Show post content' , 'posts-by-tag'); ?>
        </p>
        
		<p>
            <?php _e('Sort by: ', 'posts-by-tag'); ?>
            <label for="<?php echo $this->get_field_id( 'order_by' ); ?>">
                <input name="<?php echo $this->get_field_name('order_by'); ?>" type="radio" value="date" <?php checked($order_by, 'date'); ?> />
				<?php _e( 'Date', 'posts-by-tag' ); ?>
            </label>
            <label for="<?php echo $this->get_field_id( 'order_by' ); ?>">
                <input name="<?php echo $this->get_field_name('order_by'); ?>" type="radio" value="title" <?php checked($order_by, 'title'); ?> />
				<?php _e( 'Title', 'posts-by-tag' ); ?>
            </label>
            <label for="<?php echo $this->get_field_id( 'order_by' ); ?>">
                <input name="<?php echo $this->get_field_name( 'order_by' ); ?>" type="radio" value="rand" <?php checked( $order_by, 'rand' ); ?> />
				<?php _e( 'Random', 'posts-by-tag' ); ?>
            </label>
        </p>
        
		<p>
            <?php _e('Order by: ', 'posts-by-tag'); ?>
            <label for="<?php echo $this->get_field_id( 'order' ); ?>">
                <input name="<?php echo $this->get_field_name('order'); ?>" type="radio" value="asc" <?php checked($order, 'asc'); ?> />
				<?php _e( 'Ascending', 'posts-by-tag' ); ?>
            </label>
            <label for="<?php echo $this->get_field_id( 'order' ); ?>">
                <input name="<?php echo $this->get_field_name('order'); ?>" type="radio" value="desc" <?php checked($order, 'desc'); ?> />
				<?php _e( 'Descending', 'posts-by-tag' ); ?>
            </label>
        </p>

        <p>
            <label for="<?php echo $this->get_field_id('tag_links'); ?>">
            <input type ="checkbox" class ="checkbox" id="<?php echo $this->get_field_id('tag_links'); ?>" name="<?php echo $this->get_field_name('tag_links'); ?>" value ="true" <?php checked($tag_links, true); ?> /></label>
				<?php _e( 'Show Tag links' , 'posts-by-tag'); ?>
        </p>

        <p>
            <label for="<?php echo $this->get_field_id('link_target'); ?>">
				<?php _e('Target attribute for links', 'posts-by-tag'); ?>
            <input style="width: 75px; text-align: center;" id="<?php echo $this->get_field_id('link_target'); ?>" name="<?php echo $this->get_field_name('link_target'); ?>" type="text" value="<?php echo $link_target; ?>" /></label>
        </p>

        <p>
            <label for="<?php echo $this->get_field_id('disable_cache'); ?>">
            <input type ="checkbox" class ="checkbox" id="<?php echo $this->get_field_id('disable_cache'); ?>" name="<?php echo $this->get_field_name('disable_cache'); ?>" value ="true" <?php checked($disable_cache, true); ?> /></label>
				<?php _e( 'Disable Cache' , 'posts-by-tag'); ?>
        </p>

        <p class = "pbt-analytics">
            <strong><?php _e('Google Analytics Tracking', 'posts-by-tag'); ?></strong><br>
<?php
        if ( ! $is_analytics ) {
            $disable = 'disabled';
?>
            <span class = "pbt-google-analytics-pro" style = "color:red;"><?php _e( 'Only available in Pro addon.' , 'posts-by-tag'); ?><a href = "http://sudarmuthu.com/out/buy-posts-by-tag-google-analytics-addon" target = '_blank'>Buy now</a></span>
<?php
        }
?>
            <label for="<?php echo $this->get_field_id('campaign'); ?>">
				<?php _e('Campaign code', 'posts-by-tag'); ?>
                <input type ="text" <?php echo $disable; ?> id="<?php echo $this->get_field_id('campaign'); ?>" name="<?php echo $this->get_field_name('campaign'); ?>" value ="<?php echo $campaign; ?>" style="width:100%;">
            </label>

            <br>

            <label for="<?php echo $this->get_field_id('event'); ?>">
				<?php _e('Event code', 'posts-by-tag'); ?><br>
                <input type ="text" <?php echo $disable; ?> id="<?php echo $this->get_field_id('event'); ?>" name="<?php echo $this->get_field_name('event'); ?>" value ="<?php echo $event; ?>" style="width: 100%;">
            </label>

            <p> <?php _e( 'You can use the following placeholders' , 'posts-by-tag') ?> </p>
            <p><?php echo implode( ', ', Posts_By_Tag::$TEMPLATES ); ?></p>

<?php
    }
} // class TagWidget

/**
 * Template function to display posts by tags
 *
 * @param <string> $tags
 * @param <array> $options. An array which has the following values
 *         <int> number Number of posts to show
 *         <bool> exclude Whether to exclude the tags specified. Default is FALSE
 *         <bool> excerpt - Whether to display excerpts or not
 *         <bool> excerpt_filter - Whether to enable or disable excerpt filter
 *         <bool> thumbnail - Whether to display thumbnail or not
 *         <string/array> thumbnail_size - Size of the thumbnail image. Refer to http://codex.wordpress.org/Function_Reference/get_the_post_thumbnail#Thumbnail_Sizes
 *         <set> order_by (title, date, rand) defaults to 'date'
 *         <set> order (asc, desc) defaults to 'desc'
 *         <bool> author - Whether to show the author name or not
 *         <bool> date - Whether to show the post date or not
 *         <bool> content - Whether to display content or not
 *         <bool> content_filter - Whether to enable or disable content filter
 *         <bool> exclude_current_post Whether to exclude the current post/page. Default is FALSE
 *         <bool> tag_links Whether to display tag links at the end
 *         <string> link_target the value to the target attribute of each links that needs to be added
 */
function posts_by_tag( $tags = '', $options = array(), $exclude = FALSE, $excerpt = FALSE, $thumbnail = FALSE, $order_by = 'date', $order = 'desc', $author = FALSE, $date = FALSE, $content = FALSE, $exclude_current_post = TRUE, $tag_links = FALSE ) {
    $output = '';

    // compatibility with older versions
    if (!is_array($options)) {
        // build the array
        $number = $options;
        $options = array(
            'number'               => $number,
            'excerpt'              => $excerpt,
            'thumbnail'            => $thumbnail,
            'order_by'             => $order_by,
            'order'                => $order,
            'author'               => $author,
            'date'                 => $date,
            'content'              => $content,
            'exclude_current_post' => $exclude_current_post,
            'tag_links'            => $tag_links,
            'link_target'          => $link_target
        );
    }

    $output = get_posts_by_tag($tags, $options);

    if ($options['tag_links'] && !$option['exclude']) {
        $output .= pbt_get_tag_more_links($tags);
    }

    echo $output;
}

/**
 * Helper function for posts_by_tag
 *
 * @param <string> $tags
 * @param <array> $options. An array which has the following values
 *         <int> number Number of posts to show
 *         <bool> exclude Whether to exclude the tags specified. Default is FALSE
 *         <bool> excerpt - Whether to display excerpts or not
 *         <bool> excerpt_filter - Whether to enable or disable excerpt filter
 *         <bool> thumbnail - Whether to display thumbnail or not
 *         <string/array> thumbnail_size - Size of the thumbnail image. Refer to http://codex.wordpress.org/Function_Reference/get_the_post_thumbnail#Thumbnail_Sizes
 *         <set> order_by (title, date) defaults to 'date'
 *         <set> order (asc, desc) defaults to 'desc'
 *         <bool> author - Whether to show the author name or not
 *         <bool> date - Whether to show the post date or not
 *         <bool> content - Whether to display content or not
 *         <bool> content_filter - Whether to enable or disable content filter
 *         <bool> exclude_current_post Whether to exclude the current post/page. Default is FALSE
 *         <bool> tag_links Whether to display tag links at the end
 *         <string> link_target the value to the target attribute of each links that needs to be added
 */
function get_posts_by_tag( $giventags = '', $options = array(), $exclude = FALSE, $excerpt = FALSE, $thumbnail = FALSE, $order_by = 'date', $order = 'desc', $author = FALSE, $date = FALSE, $content = FALSE, $exclude_current_post = TRUE, $link_target = '' ) {
    global $wp_query;
    global $post;

    $current_post_id = $wp_query->post->ID;
    $output = '';

    if (is_array($options)) {

        $options = wp_parse_args($options, array(
                                'number'                => 5,
                                'exclude'               => FALSE,
                                'excerpt'               => FALSE,
                                'thumbnail'             => FALSE,
                                'thumbnail_size'        => 'thumbnail',
                                'thumbnail_size_width'  => 100,
                                'thumbnail_size_height' => 100,
                                'order_by'              => 'date',
                                'order'                 => 'desc',
                                'author'                => FALSE,
                                'date'                  => FALSE,
                                'content'               => FALSE,
                                'content_filter'        => TRUE,
                                'exclude_current_post'  => FALSE,
                                'tag_links'             => FALSE,
                                'link_target'           => ''
                            )
                     );
        extract( $options, EXTR_OVERWRITE);
    } else {
        $number = $options;
    }

    $tag_id_array = array();
    
    if ( $giventags == '' ) {
        // if tags is empty then take from current posts
        if (is_single()) {
            $tag_array = wp_get_post_tags($current_post_id);
            foreach ($tag_array as $tag) {
                $tag_id_array[] = $tag->term_id;
            }
        }
    } else {
        // Get array of post info.
        $tag_array = explode( ",", $giventags );

        foreach ($tag_array as $tag) {
            $tag_id_array[] = pbt_get_tag_ID(trim($tag));
        }
    }

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

    return $output;
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
 * get tag id from tag name
 *
 * @param <string> $tag_name
 * @return <int> term id. 0 if not found
 */
function pbt_get_tag_ID($tag_name) {
    $tag = get_term_by('name', $tag_name, 'post_tag');
    if ($tag) {
        return $tag->term_id;
    } else {
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
