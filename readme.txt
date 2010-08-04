=== Posts By Tag ===
Contributors: sudar 
Tags: posts, sidebar, widget, tag, cache
Requires at least: 2.8
Tested up to: 3.0.1
Stable tag: 1.4
	
Provide sidebar widgets that can be used to display posts from a set of tags in the sidebar.

== Description ==

Posts By Tag WordPress Plugin, provides sidebar widgets which can be used to display posts from a specific set of tags in the sidebar. You can also use shortcode or template function to display the posts.

The Plugin caches the posts of each widget separately, and issues database queries only when needed. This will reduce the amount of database queries involved for each page load and will therefore be light on your server.

### Features

#### Sidebar Widget

Posts By Tag Plugin provides a sidebar widget which can be configured to display posts from a set of tags in the sidebar. You can have multiple widgets with different set of tags configured for each one of them.

Each widget allows you to choose

*   The set of tags whose posts should be displayed 
*   The number of posts to be displayed. 
*   Option to enable post excerpts to be displayed with post titles. 
*   Option to display post thumbnail if present.
*   Choose the order in which the posts should be displayed.

#### Template function

In addition to using the widget, you can also use the following template function to display posts from a set of tags, anywhere in the theme

posts_by_tag($tags, $number, $excerpt = false, $thumbnail = false, $order_by = "date", $order = "desc", author = false);

*   $tags (string) - set of comma seperated tags
*   $number (number) - number of posts to display
*   $excerpt (bool) - To display post excerpts or not
*   $thumbnail (bool) - To display post thumbnails or not
*   $order_by (date,title) - Whether to order by date or by title.
*   $order (asc,desc) - To change the order in which the posts are displayed.
*   $author (bool) - To display author name or not.

#### Shortcode

You can also include the shortcode, to display the posts from the set of tags

[posts-by-tag tags = "tag1, tag2"][/posts-by-tag]

All the parameters that are accepted by the template tag can also be used in the shortcode

### Translation

*   Swedish (Thanks Gunnar Lindberg Årneby)
*   Turkish (Thanks Yakup Gövler)
*   Belorussian (Thanks [FatCow][2])
*   German (Thanks Renate[3])

The pot file is available with the Plugin. If you are willing to do translation for the Plugin, use the pot file to create the .po files for your language and let me know. I will add it to the Plugin after giving credit to you.

### Support

Support for the Plugin is available from the [Plugin's home page][1]. If you have any questions or suggestions, do leave a comment there.

 [1]: http://sudarmuthu.com/wordpress/posts-by-tag
 [2]: http://www.fatcow.com/
 [3]: http://www.heftrucknederland.nl

== Installation ==

Extract the zip file and just drop the contents in the wp-content/plugins/ directory of your WordPress installation and then activate the Plugin from Plugins page. You should see a new widget called "Tag Posts" in the widgets pages, which you can drag and drop in the sidebar of your theme.

== Screenshots ==

1. Widget settings page

== Changelog ==
### Changelog

** v0.1 (2009-07-26)  

*   Initial Version

** v0.2 (2009-08-02)  

*   Added template functions
*   Added Swedish translation (Thanks Gunnar Lindberg Årneby)

** v0.3 (2009-08-14)  

*   Improved caching performance
*   Added Turkish translation (Thanks Yakup Gövler)

** v0.4 (2009-09-16)  

*   Added support for sorting the posts (Thanks to Michael http://mfields.org/)

** v0.5 (2010-01-03)

*   Removed JavaScript from unwanted admin pages and added Belorussian translation.

** v0.6 (2010-03-18)

*   Added option to hide author links.

** v0.7 (2010-04-16)

*   Fixed an issue in showing the number of posts.

** v0.8 (2010-05-08)

 *  Added support for shortcode and sorting by title.

** v0.9 (2010-06-18)

 *  Fixed an issue with the order by option.

** v1.0 (2010-06-19)

 *  Fixed issue with shortcode.

** v1.1 (2010-06-23)

 *  Fixed issue with shortcode, which was not fixed properly in 1.0

** v1.2 (2010-06-25)

 *  Fixed issue with shortcode, which was not fixed properly in 1.0 and 1.1

** v1.3 (2010-07-12)

 *  Fixed some inconsistency in documentation and code

** v1.4 (2010-08-04)

 *  Added German translations

==Readme Generator== 

This Readme file was generated using <a href = 'http://sudarmuthu.com/wordpress/wp-readme'>wp-readme</a>, which generates readme files for WordPress Plugins.
