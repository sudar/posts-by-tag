=== Posts By Tag ===
Contributors: sudar 
Tags: posts, sidebar, widget, tag, cache
Requires at least: 2.9
Donate Link: http://sudarmuthu.com/if-you-wanna-thank-me
Tested up to: 3.4
Stable tag: 2.7
	
Provide sidebar widgets that can be used to display posts from a set of tags in the sidebar.

== Description ==

Posts By Tag WordPress Plugin, provides sidebar widgets which can be used to display posts from a specific set of tags in the sidebar.

These tags can be specified in the widget or the Plugin can automatically retrieve them from the current post. You can also specify the tags using custom fields in the edit post of page screen.

You can also use shortcode or template function to display the posts.

The Plugin caches the posts of each widget separately, and issues database queries only when needed. This will reduce the amount of database queries involved for each page load and will therefore be light on your server. If this clashes with other Plugins, you also have an option to disable it.

### Features

#### Sidebar Widget

Posts By Tag Plugin provides a sidebar widget which can be configured to display posts from a set of tags in the sidebar. You can have multiple widgets with different set of tags configured for each one of them.

Each widget allows you to choose

*   The set of tags from where posts should be selected (or excluded)
*   The number of posts to be displayed. 
*   Option to enable post excerpts to be displayed with post titles. 
*   Option to display post thumbnail if present.
*   Option to display post author.
*   Option to display post date.
*   Option to display post content.
*   Choose the order in which the posts should be displayed.
*   Option to exclude current post/page.
*   Option to specify the target attribute for links
*   Option to display links to tag archive pages.
*   Option to disable the cache if needed.

#### Template function

In addition to using the widget, you can also use the following template function to display posts from a set of tags, anywhere in the theme

posts_by_tag($tags, $options);

$number, $exclude = FALSE, $excerpt = FALSE, $thumbnail = FALSE, $order_by = "date", $order = "desc", author = FALSE, date = FALSE, $content = FALSE, $exclude_current_post = FALSE, $tag_links = FALSE);

- $tags (string) - set of comma separated tags. If you leave this empty, then the tags from the current post will be used.
- $options (array) - set of options. The following are the fields that are allowed
  - $number (number) - default 5 - number of posts to display
  - $exclude (bool) - default FALSE - Where to include the tags or exclude the tags
  - $excerpt (bool)  - default FALSE - To display post excerpts or not
  - $thumbnail (bool) - default FALSE  - To display post thumbnails or not
  - $order_by (date,title) - default title - Whether to order by date or by title.
  - $order (asc,desc) - default desc - To change the order in which the posts are displayed.
  - $author (bool) - default FALSE - To display author name or not.
  - $date (bool) - default FALSE - To display post date or not.
  - $content (bool) - default FALSE - To display post content or not.
  - $exclude_current_post (bool) - default FALSE - To exclude current post/page.
  - $tag_links (bool) - default FALSE - To display link to tag archive page or not.
  - $link_target (string) - default empty - target attribute for the permalink links.

#### Shortcode

You can also include the shortcode, to display the posts from the set of tags

[posts-by-tag tags = "tag1, tag2"]

All the parameters that are accepted by the template tag can also be used in the shortcode

#### Custom field

You can also specify the tags for each post or page as a custom field also.

#### Caching

Note that the Plugin caches the db queries only when it is used as a widget. If you are going to use the template tag or use shortcode, then you have to cache it yourself. 
Even while using the widget, you have the option of disabling the cache if needed.

#### Styling using CSS

The Plugin adds the following CSS classes. If you want to customize the look of the widget then can change it by adding custom styles to these CSS classes and ids.

*   The UL tag has the class posts-by-tag-list
*   Every LI tag has the class posts-by-tag-item
*   Each LI tag also has the id posts-by-tag-item-{id}, where id is the post id.

### Translation

*   Swedish (Thanks Gunnar Lindberg Årneby)
*   Turkish (Thanks Yakup Gövler)
*   Belorussian (Thanks [FatCow][2])
*   German (Thanks [Renate][3])
*   Dutch (Thanks [Rene][4])
*   Hebrew (Thanks [Sagive SEO][6])
*   Spanish (Thanks Brian Flores of [InMotion Hosting][7])
*   Bulgarian (Thanks Nikolay Nikolov of [IQ Test][11])
*   Lithuanian (Thanks  Vincent G , from [http://www.host1free.com][12])
*   Hindi (Thanks Love Chandel)

The pot file is available with the Plugin. If you are willing to do translation for the Plugin, use the pot file to create the .po files for your language and let me know. I will add it to the Plugin after giving credit to you.

### Support

Support for the Plugin is available from the [Plugin's home page][1]. If you have any questions or suggestions, do leave a comment there or contact me in [twitter][5].

### Stay updated

I would be posting updates about this Plugin in my [blog][8] and in [Twitter][5]. If you want to be informed when new version of this Plugin is released, then you can either subscribe to this [blog's RSS feed][9] or [follow me in Twitter][5].

### Links

*   [Plugin home page][1]
*   [Author's Blog][8]
*   [Other Plugins by the author][10]

 [1]: http://sudarmuthu.com/wordpress/posts-by-tag
 [2]: http://www.fatcow.com/
 [3]: http://www.heftrucknederland.nl
 [4]: http://wpwebshop.com/premium-wordpress-plugins/
 [5]: http://twitter.com/sudarmuthu
 [6]: http://www.sagive.co.il
 [7]: http://www.inmotionhosting.com/
 [8]: http://sudarmuthu.com/blog
 [9]: http://sudarmuthu.com/feed
 [10]: http://sudarmuthu.com/wordpress
 [11]: http://umenlisam.com/
 [12]: http://www.host1free.com

== Installation ==

Extract the zip file and just drop the contents in the wp-content/plugins/ directory of your WordPress installation and then activate the Plugin from Plugins page. You should see a new widget called "Tag Posts" in the widgets pages, which you can drag and drop in the sidebar of your theme.

== Screenshots ==

1. Widget settings page

== Changelog ==
### Changelog

#### v0.1 (2009-07-26)  

*   Initial Version

#### v0.2 (2009-08-02)  

*   Added template functions
*   Added Swedish translation (Thanks Gunnar Lindberg Årneby)

#### v0.3 (2009-08-14)  

*   Improved caching performance
*   Added Turkish translation (Thanks Yakup Gövler)

#### v0.4 (2009-09-16)  

*   Added support for sorting the posts (Thanks to Michael http://mfields.org/)

#### v0.5 (2010-01-03)

*   Removed JavaScript from unwanted admin pages and added Belorussian translation.

#### v0.6 (2010-03-18)

*   Added option to hide author links.

#### v0.7 (2010-04-16)

*   Fixed an issue in showing the number of posts.

#### v0.8 (2010-05-08)

 *  Added support for shortcode and sorting by title.

#### v0.9 (2010-06-18)

 *  Fixed an issue with the order by option.

#### v1.0 (2010-06-19)

 *  Fixed issue with shortcode.

#### v1.1 (2010-06-23)

 *  Fixed issue with shortcode, which was not fixed properly in 1.0

#### v1.2 (2010-06-25)

 *  Fixed issue with shortcode, which was not fixed properly in 1.0 and 1.1

#### v1.3 (2010-07-12)

 *  Fixed some inconsistency in documentation and code

#### v1.4 (2010-08-04)

 *  Added German translations

#### v1.5 (2010-08-26)

 *  Added Dutch translations and fixed typos

#### v1.6 (2011-02-17)

 *  Fixed an issue in handling boolean in shortcode

#### v1.7 (2011-05-11)

 *  Added support for displaying post dates.
 *  Fixed a bug which was corrupting the loop.

#### v1.8 (2011-09-07)

 *  Added support for displaying content (Thanks rjune)
 
#### v1.9 (2011-11-13)

 * Added Spanish and Hebrew translations.

#### v2.0 (2011-11-20)

  * Added option to exclude tags.
  * Fixed bug in displaying author name
  * Added support for post thumbnails
  * Don't display widget title if posts are not found
  * Added Tag links
  * Added the option to take tags from the current post
  * Added the option to take tags from the custom fields of current page

#### v2.1 (2011-11-22)

 * Added option to include tag links from shortcode and template function.

#### v2.1.1 (2011-12-31)

 *  Fixed undefined notices for nouncename while creating new posts

#### v2.2 (2012-01-31)

 *  Fixed issues with order by option.
 *  Added Bulgarian translations

#### v2.3 (2012-04-04) (Dev time - 3 hours)
    * Added filter to the get_the_content() call
    * Moved caching logic to widget
    * Added the option to exclude current post/page
    * Added Lithuanian translations

#### v2.4 (2012-04-15) (Dev time - 0.5 hours)
    * Added option to disable cache if needed

#### v2.5 (2012-04-30) (Dev time - 0.5 hours)
    * Fixed the sorting by title issue 

#### v2.6 (2012-05-31) (Dev time: 2 hours)
    - Added support for specifying link targets
    - Changed the argument list for the posts_by_tag template functions

#### v2.7 (2012-06-23) (Dev time: 1 hour)
    - Added support for custom fields to all post types
    - Added autocomplete for tag fields in custom field boxes
    - Added Hindi translations

==Readme Generator== 

This Readme file was generated using <a href = 'http://sudarmuthu.com/wordpress/wp-readme'>wp-readme</a>, which generates readme files for WordPress Plugins.
