<?php
/**
Plugin Name: Posts By Tag
Plugin URI: http://sudarmuthu.com/wordpress/posts-by-tag
Description: Provide sidebar widgets that can be used to display posts from a set of tags in the sidebar.
Author: Sudar
Version: 0.5
Author URI: http://sudarmuthu.com/
Text Domain: posts-by-tag

=== RELEASE NOTES ===
2009-07-26 - v0.1 - Initial Release
2009-08-02 - v0.2 - Added Template function and swedish translation.
2009-08-14 - v0.3 - Better caching and Turkish translation.
2009-09-16 - v0.4 - Added support for sorting the posts (Thanks to Michael http://mfields.org/).
2010-01-03 - v0.5 - Removed JavaScript from unwanted admin pages and added Belorussian translation.
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
        $thumbnail = (bool) $instance['thumbnail'];
        $excerpt = (bool) $instance['excerpt'];
        $num = $instance['num']; // Number of posts to show.
        $title = $instance['title'];
		$order = $instance['order'];

        echo $before_widget;
        echo $before_title;
        echo $title;
        echo $after_title;
        posts_by_tag($tags, $num, $excerpt, $thumbnail, $order, $widget_id, $order);
        echo $after_widget;
    }

    /** @see WP_Widget::update */
    function update($new_instance, $old_instance) {
		$instance = $old_instance;
        // validate data
        $instance['title'] = strip_tags($new_instance['title']);
        $instance['tags'] = strip_tags($new_instance['tags']);
        $instance['number'] = intval($new_instance['number']);
        $instance['thumbnail'] = (bool)$new_instance['thumbnail'];
        $instance['excerpt'] = (bool)$new_instance['excerpt'];
		$instance['order'] = ($new_instance['order'] === 'asc') ? 'asc' : 'desc';

        return $instance;
    }

    /** @see WP_Widget::form */
    function form($instance) {

		/* Set up some default widget settings. */
		$defaults = array( 'title' => '', 'tags' => '', 'number' => '5', 'thumbnail' => false, 'excerpt' => false );
		$instance = wp_parse_args( (array) $instance, $defaults );

        $title = esc_attr($instance['title']);
        $tags = $instance['tags'];
        $number = intval($instance['number']);
        $thumbnail = (bool) $instance['thumbnail'];
        $excerpt = (bool) $instance['excerpt'];
		$order = ( strtolower( $instance['order'] ) === 'asc' ) ? 'asc' : 'desc'; 
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
            <?php _e('Seperate multiple tags by comma', 'posts-by-tag');?>
		</p>
        
        <p>
            <label for="<?php echo $this->get_field_id('number'); ?>">
				<?php _e('Number of posts to show:', 'posts-by-tag'); ?>
            <input style="width: 25px; text-align: center;" id="<?php echo $this->get_field_id('number'); ?>" name="<?php echo $this->get_field_name('number'); ?>" type="text" value="<?php echo $number; ?>" /></label>
        </p>

        <p>
            <label for="<?php echo $this->get_field_id('thumbnail'); ?>">
            <input type ="checkbox" class ="checkbox" id="<?php echo $this->get_field_id('thumbnail'); ?>" name="<?php echo $this->get_field_name('thumbnail'); ?>" value ="true" <?php checked($thumbnail, true); ?> /></label>
            <?php _e( 'Show Post thumbnails' , 'posts-by-tag'); ?>
        </p>

        <p>
            <label for="<?php echo $this->get_field_id('excerpt'); ?>">
            <input type ="checkbox" class ="checkbox" id="<?php echo $this->get_field_id('excerpt'); ?>" name="<?php echo $this->get_field_name('excerpt'); ?>" value ="true" <?php checked($excerpt, true); ?> /></label>
				<?php _e( 'Show post excerpt' , 'posts-by-tag'); ?>
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
 * @param <string> $tags
 * @param <number> $num
 * @param <bool> $excerpt
 * @param <bool> $thumbnail
 * @param <string> $widget_id
 * @param <set> $order (asc,desc) defaults to 'desc'
 */
function posts_by_tag($tags, $num, $excerpt = false, $thumbnail = false, $order = 'desc', $widget_id = "0" ) {
    // first look in cache
    $tag_posts_output = wp_cache_get($widget_id);
    if ($tag_posts_output === false || $widget_id == "0") {
        // Not present in cache so load it

        // Get array of post info.
        $tag_array = explode(",", $tags);
        $tag_id_array = array();

        foreach ($tag_array as $tag) {
            $tag_id_array[] = get_tag_ID(trim($tag));
        }
		
        $tag_posts = get_posts( array( 'numberposts'=>$num, 'tag__in' => $tag_id_array, 'order' => $order ) ); 
        $output = '<ul>';
        foreach($tag_posts as $post) {
            setup_postdata($post);
            $output .= '<li class="posts-by-tag-item-' . $post->ID . '">';
            if ($thumbnail) {
                $output .=  '<a class="thumb" href="' . get_permalink($post) . '" title="' . get_the_title($post->ID) . '"><img src="' . esc_url(get_post_meta($post->ID, 'post_thumbnail', true)) . '" alt="' . get_the_title($post->ID) . '" /></a>';
            }
            $output .=  '<a href="' . get_permalink($post) . '">' . $post->post_title . '</a><small>' . __('Posted by: ', 'posts-by-tag');
            $output .=  get_the_author($post) . '</small>';
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

        echo $output;

        // if it is not called from theme, save the output to cache
        if ($widget_id != "0") {
            wp_cache_set($widget_id, $output);
        }
    }
}

/**
 * get tag id from tag name
 * @param <type> $tag_name
 * @return <type>
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