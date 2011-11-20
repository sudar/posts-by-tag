<?php
/**
Plugin Name: Posts By Tag
Plugin URI: http://sudarmuthu.com/wordpress/posts-by-tag
Description: Provide sidebar widgets that can be used to display posts from a set of tags in the sidebar.
Author: Sudar
Donate Link: http://sudarmuthu.com/if-you-wanna-thank-me
License: GPL
Version: 2.0
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

class PostsByTag {

    /**
     * Initalize the plugin by registering the hooks
     */
    function __construct() {

        // Load localization domain
        load_plugin_textdomain( 'posts-by-tag', false, dirname(plugin_basename(__FILE__)) .  '/languages' );

        // Register hooks
        add_action('admin_print_scripts', array(&$this, 'add_script'));
        add_action('admin_head', array(&$this, 'add_script_config'));

        //Short code
        add_shortcode('posts-by-tag', array(&$this, 'shortcode_handler'));
    }

    /**
     * Add script to admin page
     */
    function add_script() {
        // Build in tag auto complete script
        wp_enqueue_script( 'suggest' );
    }

    /**
     * add script to admin page
     */
    function add_script_config() {
        // Add script only to Widgets page
        if (substr_compare($_SERVER['REQUEST_URI'], 'widgets.php', -11) == 0) {
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
     * Expand the shortcode
     *
     * @param <array> $attributes
     */
    function shortcode_handler($attributes) {
        extract(shortcode_atts(array(
            "tags"      => '',   // comma Separated list of tags
            "number"    => '5',
            "exclude"   => FALSE,
            "excerpt"   => FALSE,
            "content"   => FALSE,
            'thumbnail' => FALSE,
            'order_by'  => 'date',
            'order'     => 'desc',
            'author'    => FALSE,
            'date'      => FALSE
        ), $attributes));

        if (strtolower($exclude) == "false") {
            $exclude = FALSE;
        }

        if (strtolower($excerpt) == "false") {
            $excerpt = FALSE;
        }

        if (strtolower($thumbnail) == "false") {
            $thumbnail = FALSE;
        }
        
        if (strtolower($author) == "false") {
            $author = FALSE;
        }

        if (strtolower($date) == "false") {
            $date = FALSE;
        }

        if (strtolower($content) == "false") {
            $content = FALSE;
        }

        // call the template function
        return get_posts_by_tag($tags, $number, $exclude, $excerpt, $thumbnail, $order_by, $order, $author, $date, $content);
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
 * TagWidget Class
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
        extract( $args );

        $tags = $instance['tags'];
        $number = $instance['number']; // Number of posts to show.
        $exclude = (bool) $instance['exclude'];
        $excerpt = (bool) $instance['excerpt'];
        $content = (bool) $instance['content'];
        $thumbnail = (bool) $instance['thumbnail'];
		$order_by = $instance['order_by'];
		$order = $instance['order'];
        $author = (bool) $instance['author'];
        $date = (bool) $instance['date'];

        $title = $instance['title'];

        echo $before_widget;
        echo $before_title;
        echo $title;
        echo $after_title;
        posts_by_tag($tags, $number, $exclude, $excerpt, $thumbnail, $order_by, $order, $author, $date, $content, $widget_id);
        echo $after_widget;
    }

    /** @see WP_Widget::update */
    function update($new_instance, $old_instance) {
        $instance = $old_instance;
        // validate data
        $instance['title'] = strip_tags($new_instance['title']);
        $instance['tags'] = strip_tags($new_instance['tags']);
        $instance['number'] = intval($new_instance['number']);
        $instance['exclude'] = (bool)$new_instance['exclude'];
        $instance['thumbnail'] = (bool)$new_instance['thumbnail'];
        $instance['author'] = (bool)$new_instance['author'];
        $instance['date'] = (bool)$new_instance['date'];
        $instance['excerpt'] = (bool)$new_instance['excerpt'];
        $instance['content'] = (bool)$new_instance['content'];
        $instance['order'] = ($new_instance['order'] === 'asc') ? 'asc' : 'desc';
        $instance['order_by'] = ($new_instance['order_by'] === 'date') ? 'date' : 'title';

        return $instance;
    }

    /** @see WP_Widget::form */
    function form($instance) {
        
        /* Set up some default widget settings. */
        $defaults = array( 'title' => '', 'tags' => '', 'number' => '5', 'exclude' => FALSE, 'thumbnail' => FALSE, 'author' => FALSE, 'date' => FALSE, 'excerpt' => FALSE, 'content' => FALSE);
        $instance = wp_parse_args( (array) $instance, $defaults );

        $title = esc_attr($instance['title']);
        $tags = $instance['tags'];
        $number = intval($instance['number']);
        $exclude = (bool) $instance['exclude'];
        $thumbnail = (bool) $instance['thumbnail'];
        $author = (bool) $instance['author'];
        $date = (bool) $instance['date'];
        $excerpt = (bool) $instance['excerpt'];
        $content = (bool) $instance['content'];
        $order = ( strtolower( $instance['order'] ) === 'asc' ) ? 'asc' : 'desc'; 
        $order = ( strtolower( $instance['order_by'] ) === 'date' ) ? 'date' : 'title';
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
            <label for="<?php echo $this->get_field_id( 'order' ); ?>">
                <input name="<?php echo $this->get_field_name('order'); ?>" type="radio" value="asc" <?php checked($order, 'asc'); ?> />
				<?php _e( 'Ascending', 'posts-by-tag' ); ?>
            </label>
            <label for="<?php echo $this->get_field_id( 'order' ); ?>">
                <input name="<?php echo $this->get_field_name('order'); ?>" type="radio" value="desc" <?php checked($order, 'desc'); ?> />
				<?php _e( 'Descending', 'posts-by-tag' ); ?>
            </label>
        </p>
<?php
    }
} // class TagWidget

/**
 * Template function to display posts by tags
 *
 * @param <string> $tags
 * @param <int> $number Number of posts to show
 * @param <bool> $exclude Whether to exclude the tags specified. Default is FALSE
 * @param <bool> $excerpt
 * @param <bool> $thumbnail
 * @param <set> $order_by (title, date) defaults to 'date'
 * @param <set> $order (asc, desc) defaults to 'desc'
 * @param <bool> $author - Whether to show the author name or not
 * @param <bool> $date - Whether to show the post date or not
 * @param <bool> $content
 * @param <string> $widget_id - widget id (incase of widgets)
 */
function posts_by_tag($tags, $number, $exclude = FALSE, $excerpt = FALSE, $thumbnail = FALSE, $order_by = 'date', $order = 'desc', $author = FALSE, $date = FALSE, $content = FALSE, $widget_id = "0" ) {
    echo get_posts_by_tag($tags, $number, $exclude, $excerpt, $thumbnail, $order_by, $order, $author, $date, $content, $widget_id);
}

/**
 * Helper function for posts_by_tag
 *
 * @param <string> $tags
 * @param <int> $number Number of posts to show
 * @param <bool> $exclude Whether to exclude the tags specified. Default is FALSE
 * @param <bool> $excerpt
 * @param <bool> $thumbnail
 * @param <set> $order_by (title, date) defaults to 'date'
 * @param <set> $order (asc, desc) defaults to 'desc'
 * @param <bool> $author - Whether to show the author name or not
 * @param <bool> $date - Whether to show the post date or not
 * @param <bool> $content - Whether to show post content or not
 * @param <string> $widget_id - widget id (incase of widgets)
 */
function get_posts_by_tag($tags, $number, $exclude = FALSE, $excerpt = FALSE, $thumbnail = FALSE, $order_by = 'date', $order = 'desc', $author = FALSE, $date = FALSE, $content = FALSE, $widget_id = "0" ) {
    global $wp_query;

    // first look in cache
    $output = wp_cache_get($widget_id, 'posts-by-tag');
    if ($output === FALSE || $widget_id == "0") {
        // Not present in cache so load it

        // Get array of post info.
        $tag_array = explode(",", $tags);
        $tag_id_array = array();

        foreach ($tag_array as $tag) {
            $tag_id_array[] = get_tag_ID(trim($tag));
        }

        $tag_arg = 'tag__in';
        if ($exclude) {
            $tag_arg = 'tag__not_in';
        }

        // saving the query
        $temp_query = clone $wp_query;
        
        // TODO: Need to cache this.
        $tag_posts = get_posts( array( 'numberposts' => $number, $tag_arg => $tag_id_array, 'orderby' => $order_by, 'order' => $order ) );

        // restoring the query so it can be later used to display our posts
        $wp_query = clone $temp_query;

        $output = '<ul class = "posts-by-tag-list">';
        foreach($tag_posts as $post) {
            setup_postdata($post);
            $output .= '<li class="posts-by-tag" id="posts-by-tag-item-' . $post->ID . '">';

            if ($thumbnail) {
                if (has_post_thumbnail($post->ID)) {
                    $output .= get_the_post_thumbnail($post->ID, 'thumbnail');
                } else {
                    if (get_post_meta($post->ID, 'post_thumbnail', true) != '') {
                        $output .=  '<a class="thumb" href="' . get_permalink($post) . '" title="' . get_the_title($post->ID) . '"><img src="' . esc_url(get_post_meta($post->ID, 'post_thumbnail', true)) . '" alt="' . get_the_title($post->ID) . '" ></a>';
                    }
                }
            }

            $output .= '<a href="' . get_permalink($post) . '">' . $post->post_title . '</a>';

            if($content) {
                 $output .= get_the_content();
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

        // if it is not called from theme, save the output to cache
        if ($widget_id != "0") {
            wp_cache_set($widget_id, $output, 'posts-by-tag', 3600);
        }
    }

    return $output;
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
?>