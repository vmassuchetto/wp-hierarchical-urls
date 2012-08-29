=== Hierarchical URLs ===

Contributors: viniciusmassuchetto
Tags: rewrite rules, templates
Requires at least: 3.0
Tested up to: 3.4
Stable tag: 0.01
License: GPLv2 or later

Will generate hierarchical URLs like
`category-name/subcategory-name/subsubcategory-name/post-slug` for all post
types and taxonomies.

== Description ==

Enables hierarchical URLs, making posts follow categories and parent categories
to define their permalink. Assume a blog with the following category structure
for posts:

* Music
  - Rock
  - Pop
* Food
  - Indian
  - Italian

By default, their URLs will be `category/%category-name%`. Activating this
plugin will end up in URLs like:

* Music: `music`
  - Rock: `music/rock`
  - Pop: `music/pop`
* Food `food`
  - Indian: `food/indian`
  - Italian: `food/italian`

For posts inside the Italian food category, for example, the URL will be
`food/italian/%postname%`.

Posts and categories won't have any URL prefix like the default
`category/$category%` default permalink structure, but other post types and
taxonomies will have it. If there's a "company" post type, the URLs will be
`company` for archive pages and `company/%postname%` for a single post.

== Installation ==

Upload the plugin to your `wp-content/plugins` and activate it.

== TODO ==

* Flush rules on term creating

== Changelog ==

= 0.01 =

* First version with some functional code.
