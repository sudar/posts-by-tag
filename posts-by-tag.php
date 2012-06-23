<?php
/**
Plugin Name: Posts By Tag
Plugin URI: http://sudarmuthu.com/wordpress/posts-by-tag
Description: Provide sidebar widgets that can be used to display posts from a set of tags in the sidebar.
Author: Sudar
Donate Link: http://sudarmuthu.com/if-you-wanna-thank-me
License: GPL
Version: 2.6
Author URI: http://sudarmuthu.com/
Text Domain: posts-by-tag

=== RELEASE NOTES ===
2009-07-26 - v0.1 - Initial Release
2009-08-02 - v0.2 - Added Template function and swedish translation.
2009-08-14 - v0.3 - Better caching and Turkish translation.
2009-09-16 - v0.4 - Added support for sorting the posts (Thanks to Michael http://mfields.org/).
2010-01-03 - v0.5 - Removed JavaScript from unwanted admin pages and added Belorussian translation.
2010-03-18 - v0.6 - Added option to hide author links.
2010-04-16 - v0.7 - Fixed an issue in showing the number of posts.
2010-05-08 - v0.8 - Added support for shortcode and sorting by title.
2010-06-18 - v0.9 - Fixed an issue with the order by option.
2010-06-19 - v1.0 - Fixed issue with shortcode.
2010-06-23 - v1.1 - Fixed issue with shortcode, which was not fixed properly in 1.0.
2010-06-25 - v1.2 - Fixed issue with shortcode, which was not fixed properly in 1.0 and 1.1.
2010-07-12 - v1.3 - Fixed some inconsistency in documentation and code.
2010-08-02 - v1.4 - Added German translations.
2010-08-26 - v1.5 - Added Dutch translations and fixed typos.
2011-02-17 - v1.6 - Fixed an issue in handling boolean in shortcode.
2011-05-11 - v1.7 - Added support for displaying dates and fixed a bug which was corrupting the loop.
2011-09-07 - v1.8 - Added support for displaying content (Thanks rjune).
2011-11-13 - v1.9 - Added Spanish and Hebrew translations.
2011-11-20 - v2.0 - Added option to exclude tags.
                  - Fixed bug in displaying author name
                  - Added support for post thumbnails
                  - Don't display widget title if posts are not found
                  - Added Tag links
                  - Added the option to take tags from the current post
                  - Added the option to take tags from the custom fields of current page
2011-11-22 - v2.1 - Added option to include tag links from shortcode and template function.
2011-12-31 - v2.1.1 - Fixed undefined notices for nouncename while creating new posts
2012-01-31 - v2.2 - Fixed issues with order by option. Added Bulgarian translations
2012-04-08 - v2.3 - (Dev time: 3 hours)
                  - Added filter to the get_the_content() call
                  - Moved caching logic to widget
                  - Added the option to exclude current post/page
                  - Added Lithuanian translations
2012-04-15 - v2.4 - (Dev time: 0.5 hours)
                  - Added otpion to disable cache if needed
2012-04-30 - v2.5 - (Dev time: 0.5 hours)
                  - Fixed the sorting by title issue (http://wordpress.org/support/topic/plugin-posts-by-tag-order_by-not-working)
2012-05-31 - v2.6 - (Dev time: 2 hours)
                  - Added support for specifying link targets
                  - Changed the argument list for the posts_by_tag template functions
2012-06-23 - v2.7 - (Dev time: 1 hour)
                  - Added support for custom fields to all post types
                  - Added autocomplete for tag fields

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
 * @package PostsByTag
 * @subpackage default
 * @author Sudar
 */
class PostsByTag {

    // boolean fields that needs to be validated
    private $boolean_fields = array( 'exclude', 'exclude_current_post', 'excerpt', 'content', 'thumbnail', 'author', 'date', 'tag_links');

    /**
     * Initalize the plugin by registering the hooks
     */
    function __construct() {

        // Load localization domain
        load_plugin_textdomain( 'posts-by-tag', false, dirname(plugin_basename(__FILE__)) .  '/languages' );

        // Register hooks
        add_action('admin_print_scripts', array(&$this, 'add_script'));
        add_action('admin_head', array(&$this, 'add_script_config'));
        
        /* Use the admin_menu action to define the custom boxes */
        add_action('admin_menu', array(&$this, 'add_custom_box'));

        /* Use the save_post action to do something with the data entered */
        add_action('save_post', array(&$this, 'save_postdata'));

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
?>

    <script type="text/javascript">
    // Function to add auto suggest
    function setSuggest(id) {
        jQuery('#' + id).suggest("<?php echo get_bloginfo('wpurl'); ?>/wp-admin/admin-ajax.php?action=ajax-tag-search&tax=post_tag", {multiple:true, multipleSep: ","});
    }
    </script>
<?php
        }
    }

    /**
     * Check whether you are on a Plugin page
     *
     * @return boolean
     * @author Sudar
     */
    private function is_on_plugin_page() {
        if( strstr($_SERVER['REQUEST_URI'], 'wp-admin/post-new.php') || 
                strstr($_SERVER['REQUEST_URI'], 'wp-admin/post.php') ||
                strstr($_SERVER['REQUEST_URI'], 'wp-admin/widgets.php') ||
                strstr($_SERVER['REQUEST_URI'], 'wp-admin/edit.php')) {
            return TRUE; 
        } else {
            return FALSE;
        }
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
            $posts_by_tag_page_fields = get_post_meta($post_id, 'posts_by_tag_page_fields', TRUE);

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

        update_post_meta($post_id, 'posts_by_tag_page_fields', $fields);

    }

    /**
     * Expand the shortcode
     *
     * @param <array> $attributes
     */
    function shortcode_handler($attributes) {
        $options = shortcode_atts(array(
            "tags"      => '',   // comma Separated list of tags
            "number"    => 5,
            "exclude"   => FALSE,
            "exclude_current_post"   => FALSE,
            "excerpt"   => FALSE,
            "content"   => FALSE,
            'thumbnail' => FALSE,
            'order_by'  => 'date',
            'order'     => 'desc',
            'author'    => FALSE,
            'date'      => FALSE,
            'tag_links' => FALSE,
            'link_target' => ''
        ), $attributes);

        $options = validate_boolean_options($options, $this->boolean_fields);
        $tags = $options['tags'];

        // call the template function
        $output = get_posts_by_tag($tags, $options);

        if ($options['tag_links'] && !$options['exclude']) {
            $output .= get_tag_more_links($tags);
        }

        return $output;
    }

    // PHP4 compatibility
    function PostsByTag() {
        $this->__construct();
    }
}

// Start this plugin once all other plugins are fully loaded
add_action( 'init', 'PostsByTag' ); function PostsByTag() { global $PostsByTag; $PostsByTag = new PostsByTag(); }

// register TagWidget widget
add_action('widgets_init', create_function('', 'return register_widget("TagWidget");'));

/**
 * TagWidget Class - Wrapper for the widget
 *
 * @package PostsByTag
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
        $number               = $instance['number']; // Number of posts to show.
        $exclude              = (bool) $instance['exclude'];
        $exclude_current_post = (bool) $instance['exclude_current_post'];
        $excerpt              = (bool) $instance['excerpt'];
        $content              = (bool) $instance['content'];
        $thumbnail            = (bool) $instance['thumbnail'];
        $order_by             = $instance['order_by'];
        $order                = $instance['order'];
        $author               = (bool) $instance['author'];
        $date                 = (bool) $instance['date'];

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
                $posts_by_tag_page_fields = get_post_meta($post_id, 'posts_by_tag_page_fields', TRUE);

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

                    $widget_content = get_posts_by_tag($tags, $number, $exclude, $excerpt, $thumbnail, $order_by, $order, $author, $date, $content, $exclude_current_post, $link_target);

                    if (!disable_cache) {
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
                echo get_tag_more_links($tags);
            }

            echo $after_widget;
        }
    }

    /** @see WP_Widget::update */
    function update($new_instance, $old_instance) {
        $instance = $old_instance;
        
        // validate data
        $instance['title']                = strip_tags($new_instance['title']);
        $instance['tags']                 = strip_tags($new_instance['tags']);
        $instance['current_tags']         = (bool)$new_instance['current_tags'];
        $instance['current_page_tags']    = (bool)$new_instance['current_page_tags'];
        $instance['number']               = intval($new_instance['number']);
        $instance['exclude']              = (bool)$new_instance['exclude'];
        $instance['exclude_current_post'] = (bool)$new_instance['exclude_current_post'];
        $instance['thumbnail']            = (bool)$new_instance['thumbnail'];
        $instance['author']               = (bool)$new_instance['author'];
        $instance['date']                 = (bool)$new_instance['date'];
        $instance['excerpt']              = (bool)$new_instance['excerpt'];
        $instance['content']              = (bool)$new_instance['content'];
        $instance['order']                = ($new_instance['order'] === 'asc') ? 'asc' : 'desc';
        $instance['order_by']             = ($new_instance['order_by'] === 'date') ? 'date' : 'title';

        $instance['tag_links']            = (bool)$new_instance['tag_links'];
        $instance['link_target']          = $new_instance['link_target'];
        $instance['disable_cache']        = (bool)$new_instance['disable_cache'];
        
        return $instance;
    }

    /** @see WP_Widget::form */
    function form($instance) {
        
        /* Set up some default widget settings. */
        $defaults = array( 'title' => '', 'tags' => '', 'current_tags' => FALSE, 'number' => '5', 'exclude' => FALSE, 'exclude_current_post' => FALSE, 'thumbnail' => FALSE, 'author' => FALSE, 'date' => FALSE, 'excerpt' => FALSE, 'content' => FALSE);
        $instance = wp_parse_args( (array) $instance, $defaults );

        $title                = esc_attr($instance['title']);
        $tags                 = $instance['tags'];
        $number               = intval($instance['number']);
        $current_tags         = (bool) $instance['current_tags'];
        $current_page_tags    = (bool) $instance['current_page_tags'];
        $exclude              = (bool) $instance['exclude'];
        $exclude_current_post = (bool) $instance['exclude_current_post'];
        $thumbnail            = (bool) $instance['thumbnail'];
        $author               = (bool) $instance['author'];
        $date                 = (bool) $instance['date'];
        $excerpt              = (bool) $instance['excerpt'];
        $content              = (bool) $instance['content'];
        $order                = ( strtolower( $instance['order'] ) === 'asc' ) ? 'asc' : 'desc';
        $order_by             = ( strtolower( $instance['order_by'] ) === 'date' ) ? 'date' : 'title';

        $tag_links            = (bool) $instance['tag_links'];
        $link_target          = $instance['link_target'];
        $disable_cache        = (bool) $instance['disable_cache'];
?>

<?php
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
            <input type ="checkbox" class ="checkbox" id="<?php echo $this->get_field_id('thumbnail'); ?>" name="<?php echo $this->get_field_name('thumbnail'); ?>" value ="true" <?php checked($thumbnail, true); ?> /></label>
            <?php _e( 'Show post thumbnails' , 'posts-by-tag'); ?>
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
 *         <bool> excerpt
 *         <bool> thumbnail
 *         <set> order_by (title, date) defaults to 'date'
 *         <set> order (asc, desc) defaults to 'desc'
 *         <bool> author - Whether to show the author name or not
 *         <bool> date - Whether to show the post date or not
 *         <bool> content
 *         <bool> exclude_current_post Whether to exclude the current post/page. Default is FALSE
 *         <bool> tag_links Whether to display tag links at the end
 *         <string> link_target the value to the target attribute of each links that needs to be added
 */
function posts_by_tag($tags, $options, $exclude = FALSE, $excerpt = FALSE, $thumbnail = FALSE, $order_by = 'date', $order = 'desc', $author = FALSE, $date = FALSE, $content = FALSE, $exclude_current_post = FALSE, $tag_links = FALSE) {
    $output = '';

    // compatibility with older versions
    if (!is_array($options)) {
        // build the array
        $number = $options;
        $options = array(
            'number' => $number,
            'excerpt' => $excerpt, 
            'thumbnail' => $thumbnail, 
            'order_by' => $order_by, 
            'order' => $order,
            'author' => $author, 
            'date' => $date, 
            'content' => $content, 
            'exclude_current_post' => $exclude_current_post, 
            'tag_links' => $tag_links,
            'link_target' => $link_target
        );
    }

    $output = get_posts_by_tag($tags, $options);

    if ($options['tag_links'] && !$option['exclude']) {
        $output .= get_tag_more_links($tags);
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
 *         <bool> excerpt
 *         <bool> thumbnail
 *         <set> order_by (title, date) defaults to 'date'
 *         <set> order (asc, desc) defaults to 'desc'
 *         <bool> author - Whether to show the author name or not
 *         <bool> date - Whether to show the post date or not
 *         <bool> content
 *         <bool> exclude_current_post Whether to exclude the current post/page. Default is FALSE
 *         <string> link_target the value to the target attribute of each links that needs to be added
 */
function get_posts_by_tag($tags, $options, $exclude = FALSE, $excerpt = FALSE, $thumbnail = FALSE, $order_by = 'date', $order = 'desc', $author = FALSE, $date = FALSE, $content = FALSE, $exclude_current_post = FALSE, $link_target = '') {
    global $wp_query;
    $current_post_id = $wp_query->post->ID;
    $output = '';

    if (is_array($options)) {

        wp_parse_args($options, array(
                                'number' => 5,
                                'exclude' => FALSE,
                                'excerpt' => FALSE, 
                                'thumbnail' => FALSE, 
                                'order_by' => 'date', 
                                'order' => 'desc', 
                                'author' => FALSE, 
                                'date' => FALSE, 
                                'content' => FALSE, 
                                'exclude_current_post' => FALSE, 
                                'tag_links' => FALSE,
                                'link_target' => '' 
                            )
                     );
        extract( $options, EXTR_OVERWRITE);
    } else {
        $number = $options;
    }

    $tag_id_array = array();
    
    if ($tags == '') {
        // if tags is empty then take from current posts
        if (is_single()) {
            $tag_array = wp_get_post_tags($current_post_id);
            foreach ($tag_array as $tag) {
                $tag_id_array[] = $tag->term_id;
            }
        }
    } else {
        // Get array of post info.
        $tag_array = explode(",", $tags);

        foreach ($tag_array as $tag) {
            $tag_id_array[] = get_tag_ID(trim($tag));
        }
    }

    if (count($tag_id_array) > 0) {
        // only if we have atleast one tag. get_posts has a bug. If empty array is passed, it returns all posts. That's why we need this condition
        $tag_arg = 'tag__in';
        if ($exclude) {
            $tag_arg = 'tag__not_in';
        }

        // saving the query
        $temp_query = clone $wp_query;

        $tag_posts = get_posts( array( 'numberposts' => $number, $tag_arg => $tag_id_array, 'orderby' => $order_by, 'order' => $order ) );

        // restoring the query so it can be later used to display our posts
        $wp_query = clone $temp_query;

        if (count($tag_posts) > 0) {
            $output = '<ul class = "posts-by-tag-list">';
            foreach($tag_posts as $post) {
                if ($exclude_current_post && $current_post_id == $post->ID) {
                    // exclude currrent post/page
                    continue;
                }

                setup_postdata($post);
                $output .= '<li class="posts-by-tag-item" id="posts-by-tag-item-' . $post->ID . '">';

                if ($thumbnail) {
                    if (has_post_thumbnail($post->ID)) {
                        $output .= get_the_post_thumbnail($post->ID, 'thumbnail');
                    } else {
                        if (get_post_meta($post->ID, 'post_thumbnail', true) != '') {
                            $output .=  '<a class="thumb" href="' . get_permalink($post) . '" title="' . get_the_title($post->ID) . '"><img src="' . esc_url(get_post_meta($post->ID, 'post_thumbnail', true)) . '" alt="' . get_the_title($post->ID) . '" ></a>';
                        }
                    }
                }

                // add permalink
                $output .= '<a href="' . get_permalink($post) . '"';
               
                if ($link_target != '') {
                    $output .= ' target = "' . $link_target . '"';
                }

                $output .= '>' . $post->post_title . '</a>';

                if($content) {
                        $output .= get_the_content_with_formatting();
                }

                if ($author) {
                    $output .= ' <small>' . __('Posted by: ', 'posts-by-tag');
                    $output .=  get_the_author_meta('display_name', $post->post_author) . '</small>';
                }

                if ($date) {
                    $output .= ' <small>' . __('Posted on: ', 'posts-by-tag');
                    $output .=  mysql2date(get_option('date_format'), $post->post_date) . '</small>';
                }

                if( $excerpt ) {
                    $output .=  '<br />';
                    if ($post->post_excerpt!=NULL)
                        $output .= apply_filters('the_excerpt', $post->post_excerpt);
                    else
                        $output .= get_the_excerpt();
                }
                $output .=  '</li>';
            }
            $output .=  '</ul>';
        }
    }

    return $output;
}

/**
 * Get tag more links for a bunch of tags. Exposed as a template function
 *
 * @param <type> $tags
 * @param <type> $prefix
 */
function get_tag_more_links($tags, $prefix = 'More posts: ') {
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

            $output .= get_tag_more_link($tag_name);
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
function get_tag_more_link($tag) {
    return '<a href = "' . get_tag_link(get_tag_ID($tag)) . '">' . $tag . '</a> ';
}

/**
 * get tag id from tag name
 *
 * @param <string> $tag_name
 * @return <int> term id. 0 if not found
 */
if (!function_exists("get_tag_ID")) {
    function get_tag_ID($tag_name) {
        $tag = get_term_by('name', $tag_name, 'post_tag');
        if ($tag) {
            return $tag->term_id;
        } else {
            return 0;
        }
    }
}

/**
 * Get the content of a post with formatting.
 * Calls the filter before returning the content
 *
 */
function get_the_content_with_formatting ($more_link_text = '(more...)', $stripteaser = 0, $more_file = '') {
	$content = get_the_content($more_link_text, $stripteaser, $more_file);
	$content = apply_filters('the_content', $content);
	$content = str_replace(']]>', ']]&gt;', $content);
	return $content;
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
function validate_boolean_options($options, $fields) {
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
