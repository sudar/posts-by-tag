# Posts By Tag #
**Contributors:** sudar  
**Tags:** posts, sidebar, widget, tag, cache  
**Requires at least:** 2.9  
**Donate Link:** http://sudarmuthu.com/if-you-wanna-thank-me  
**Tested up to:** 3.8.1  
**Stable tag:** 3.1  
	
Provide sidebar widget, shortcode and template functions that can be used to display posts from a set of tags using various options in the sidebar or anywhere in a post.

## Description ##

Posts By Tag WordPress Plugin, provides sidebar widgets which can be used to display posts from a specific set of tags in the sidebar.

These tags can be specified in the widget or the Plugin can automatically retrieve them from the current post tags, post slug or form custom field. The custom fields can be specified in the edit post or page screen.

You can also use shortcode or template function to display the posts.

The Plugin caches the posts of each widget separately, and issues database queries only when needed. This will reduce the amount of database queries involved for each page load and will therefore be light on your server. If this clashes with other Plugins, you also have an option to disable it.

### Features

#### Sidebar Widget

Posts By Tag Plugin provides a sidebar widget which can be configured to display posts from a set of tags in the sidebar. You can have multiple widgets with different set of tags configured for each one of them.

Each widget allows you to choose

-   The set of tags from where posts should be selected (or excluded)
-   The number of posts to be displayed. 
-   Whether to pick the tags from current post
-   Whether to pick the tags from current post slug
-   Whether to pick the tags from current post's custom field
-   Option to enable post excerpts to be displayed with post titles. 
-   Option to display post thumbnail if present.
-   Option to display post author.
-   Option to display post date.
-   Option to display post content.
-   Choose the order in which the posts should be displayed.
-   Option to exclude current post/page.
-   Option to specify the target attribute for links
-   Option to display links to tag archive pages.
-   Option to disable the cache if needed.
-   Option to enable Google Analytics tracking on the links (Only in [Pro Addon](http://sudarmuthu.com/wordpress/posts-by-tag/pro-addons))

To add the widget, log into your WordPress admin console and go to Appearances -> Widgets. You will find the widget with the title "Posts By Tag". Drag and drop it in the sidebar where you want the widget to be displayed.

#### Template function

In addition to using the widget, you can also use the following template function to display posts from a set of tags, anywhere in the theme.

`posts_by_tag($tags, $options);`

The following options can be passed in the $options array

- `$tags` (string) - set of comma separated tags. If you leave this empty, then the tags from the current post will be used.
- `$options` (array) - set of options. The following are the fields that are allowed
  - `number` (number) - default 5 - number of posts to display
  - `tag_from_post` (bool) - default FALSE - whether to pick up tags from current post's tag
  - `tag_from_post_slug` (bool) - default FALSE - whether to pick up tags from current post's slug
  - `tag_from_post_custom_field` (bool) - default FALSE - whether to pick up tags from current post's custom field
  - `exclude` (bool) - default FALSE - Where to include the tags or exclude the tags
  - `excerpt` (bool)  - default FALSE - To display post excerpts or not
  - `excerpt_filter` (bool) - default TRUE Whether to enable or disable excerpt filter
  - `thumbnail` (bool) - default FALSE  - To display post thumbnails or not
  - `thumbnail_size` (string/array) - default thumbnail  - Size of the thumbnail image. Refer to http://codex.wordpress.org/Function_Reference/get_the_post_thumbnail#Thumbnail_Sizes
  - `order_by` (date,title, random) - default date - Whether to order by date or by title or show them randomly
  - `order` (asc,desc) - default desc - To change the order in which the posts are displayed.
  - `author` (bool) - default FALSE - To display author name or not.
  - `date` (bool) - default FALSE - To display post date or not.
  - `content` (bool) - default FALSE - To display post content or not.
  - `content_filter` (bool) - default TRUE Whether to enable or disable content filter
  - `exclude_current_post` (bool) - default TRUE - To exclude current post/page.
  - `tag_links` (bool) - default FALSE - To display link to tag archive page or not.
  - `link_target` (string) - default empty - target attribute for the permalink links.

In addition to the above options the following options are available in the [Pro addon](sudarmuthu.com/wordpress/posts-by-tag/pro-addons)

- `campaign` (string) - The Google Analytics campaign code that needs to be appended to every link
- `event` (string) - The Google Analytics events code that needs to be appended to every link

You can checkout [some example PHP code](http://sudarmuthu.com/wordpress/posts-by-tag#example-template) that shows how you can call the template function with different options.

#### Shortcode

You can also include the following shortcode in your blog posts or WordPress page, to display the posts from the set of tags.

`posts-by-tag tags = "tag1, tag2"]`

All the parameters that are accepted by the template tag can also be used in the shortcode.

You can checkout [some example shortcodes](http://sudarmuthu.com/wordpress/posts-by-tag#example-shortcode) that shows how you can use the shortcode with different options.

#### Custom field

You can also specify the tags for each post or page and a custom title using custom field. The UI for the custom field is available on the right side of the add/edit post/page screen in WordPress admin console.

#### Styling using CSS

The Plugin adds the following CSS classes. If you want to customize the look of the widget then can change it by adding custom styles to these CSS classes and ids.

- The `UL` tag has the class `posts-by-tag-list`
- Every `LI` tag has the class `posts-by-tag-item`
- Every `LI` tag also has all tags names to which the post belongs as part of the class attribute
- Each `LI` tag also has the id `posts-by-tag-item-{id}`, where id is the post id.
- Each `<a>` tag inside `LI` that contains title has the class `posts-by-tag-item-title`.

If you want to output categories of the post as class names(so that you can style them differently), then you can get the code from this [forum thread](http://wordpress.org/support/topic/plugin-posts-by-tag-display-post-category-classes-in-outputted-code).

#### Caching

If you are using the widget, then the Plugin automatically caches the db queries. This will greatly improve the performance of you page. If this clashes with other Plugins or if you want to manage the cache yourself, then you disable the cache if needed.

However if you are going to use the shortcode or the template directly, then you might have to cache the output yourself.

### Development and Support

The development of the Plugin happens over at [github][13]. If you want to contribute to the Plugin, fork the [project at github][13] and send me a pull request.

If you are not familiar with either git or Github then refer to this [guide to see how fork and send pull request](http://sudarmuthu.com/blog/contributing-to-project-hosted-in-github).

If you are looking for ideas, then you can start with one of the following TODO items :)

### TODO

The following are the features that I am thinking of adding to the Plugin, when I get some free time. If you have any feature request or want to increase the priority of a particular feature, then let me know by adding them to [github issues][7].

- Provide template tags for widget title, that will be dynamically expanded.
- Add support for custom post types
- Ability to sort posts alphabetically
- Ability to [exclude posts by id](http://sudarmuthu.com/wordpress/posts-by-tag#comment-783250)
- Ability to [show comment count](http://sudarmuthu.com/wordpress/posts-by-tag#comment-783248)
- Ability to [retrieve posts by date range](http://sudarmuthu.com/wordpress/posts-by-tag#comment-780935)
- <del>Ability to pull posts randomly.</del> - Added in v3.0

### Support

- If you have found a bug/issue or have a feature request, then post them in [github issues][7]
- If you have a question about usage or need help to troubleshoot, then post in WordPress forums or leave a comment in [Plugins's home page][1]
- If you like the Plugin, then kindly leave a review/feedback at [WordPress repo page][8].
- If you find this Plugin useful or and wanted to say thank you, then there are ways to [make me happy](http://sudarmuthu.com/if-you-wanna-thank-me) :) and I would really appreciate if you can do one of those.
- Checkout other [WordPress Plugins][10] that I have written
- If anything else, then contact me in [twitter][3].

 [1]: http://sudarmuthu.com/wordpress/posts-by-tag
 [3]: http://twitter.com/sudarmuthu
 [7]: http://github.com/sudar/posts-by-tag/issues
 [8]: http://wordpress.org/extend/plugins/posts-by-tag/
 [9]: http://sudarmuthu.com/feed
 [10]: http://sudarmuthu.com/wordpress
 [13]: http://github.com/sudar/posts-by-tag

## Translation ##

*   Swedish (Thanks Gunnar Lindberg Årneby)
*   Turkish (Thanks Yakup Gövler)
*   Belorussian (Thanks FatCow)
*   German (Thanks Renate)
*   Dutch (Thanks Rene)
*   Hebrew (Thanks Sagive SEO)
*   Spanish (Thanks Brian Flores of InMotion Hosting)
*   Bulgarian (Thanks Nikolay Nikolov of [IQ Test)
*   Lithuanian (Thanks  Vincent G)
*   Hindi (Thanks Love Chandel)
*   Gujarati (Thanks Punnet of Resolutions Mart)

The pot file is available with the Plugin. If you are willing to do translation for the Plugin, use the pot file to create the .po files for your language and let me know. I will add it to the Plugin after giving credit to you.

## Installation ##

Extract the zip file and just drop the contents in the wp-content/plugins/ directory of your WordPress installation and then activate the Plugin from Plugins page. You should see a new widget called "Tag Posts" in the widgets pages, which you can drag and drop in the sidebar of your theme.

## Screenshots ##

![](screenshot-1.png)

Widget settings page. This is how the sidebar widget settings page looks like

![](screenshot-2.png)

Custom fields meta box. This is how the custom fields meta box looks like in the add or edit post/page screen

## Readme Generator ##

This Readme file was generated using <a href = 'http://sudarmuthu.com/wordpress/wp-readme'>wp-readme</a>, which generates readme files for WordPress Plugins.
