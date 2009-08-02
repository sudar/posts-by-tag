<?php
/**
Plugin Name: Posts By Tag
Plugin URI: http://sudarmuthu.com/wordpress/posts-by-tag
Description: Provide sidebar widgets that cna be used to display posts from a set of tags in the sidebar.
Author: Sudar
Version: 0.2
Author URI: http://sudarmuthu.com/
Text Domain: posts-by-tag

=== RELEASE NOTES ===
2009-07-26 – v0.1 – Initial Release
2009-08-02 – v0.1 – Added Template function and swedish translation.
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
    ?>

    <script type="text/javascript">
    // Function to add auto suggest
    function setSuggest(id) {
        jQuery('#' + id).suggest("<?php echo get_bloginfo('wpurl'); ?>/wp-admin/admin-ajax.php?action=ajax-tag-search&tax=post_tag", {multiple:true, multipleSep: ","});
    }
    </script>
    <?php
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

        echo $before_widget;
        echo $before_title;
        echo $title;
        echo $after_title;

        posts_by_Tag($tags, $num, $excerpt, $thumbnail, $widget_id);

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
<?php
    }
} // class TagWidget

/**
 * Template function to display posts by tags
 * @param <string> $tags
 * @param <number> $num
 * @param <bool> $excerpt
 * @param <bool> $thumbnail
 * @param <number> $widget_id 
 */
function posts_by_tag($tags, $num, $excerpt = false, $thumbnail = false, $widget_id = 0) {

    // first look in cache
    $tag_posts = wp_cache_get($widget_id);
    if ($tag_posts === false) {
        // Not present in cahce so load it

        // Get array of post info.
        $tag_array = explode(",", $tags);
        $tag_id_array = array();

        foreach ($tag_array as $tag) {
            $tag_id_array[] = get_tag_ID(trim($tag));
        }

        $tag_posts = get_posts(array('numberposts'=>$num, 'tag__in' => $tag_id_array));
        wp_cache_set($widget_id, $tag_posts);
    }

    echo '<ul>';
    foreach($tag_posts as $post) {
        setup_postdata($post);
        echo '<li class="posts-by-tag-item-' . $post->ID . '">';
        if ($thumbnail) {
            echo '<a class="thumb" href="' . get_permalink($post) . '" title="' . $post->post_title . '"><img src="' . get_post_meta($post->ID, 'post_thumbnail', true) . '" alt="' . $post->post_title . '" /></a>';
        }
        echo '<a href="' . get_permalink($post) . '">' . $post->post_title . '</a><small>';
        _e('Posted by: ', 'posts-by-tag');
        echo get_the_author($post) . '</small>';
        if( $excerpt ) {
            echo '<br />';
            if ($post->post_excerpt!=NULL)
                echo $post->post_excerpt;
            else
                the_excerpt();
        }
        echo '</li>';
    }
    echo '</ul>';

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