=== Admin Instant Search ===
Contributors: polyplugins
Donate link: 
Tags: instant order search, woocommerce, wp, instant search, admin instant search
Requires at least: 6.5
Tested up to: 6.7
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Search WooCommerce orders fast without having to wait for the page to load between searches.


== Description ==

When you frequently search various areas of the WordPress admin, you know the page load times between searches can be frustrating. Admin Instant Search makes finding what you're looking for easier and faster by building an index and keeping it updated as new data comes in. This index makes it much faster to search through by only requiring one database call and using JavaScript to filter through the index on the fly. Currently it only has support for WooCommerce orders, but we are working to add support to search customers, posts, pages, users and more.

Currently Supports:

* Instantly Searching WooCommerce Orders

Features:

* Search through all orders instantly without requiring multiple page loads
* Builds an index of all orders and stores them in your database
* Only requires one initial query to the database in order to fetch the index, which means no loading times between searches
* Ability to adjust the batch size for the initial index so smaller servers don't get overloaded

Road Map:

* Add Caching
* Add support for instantly searching WordPress Posts
* Add support for instantly searching WordPress Pages
* Add support for instantly searching WordPress Users
* Add support for instantly searching WooCommerce Customers
* Add support for instantly searching WooCommerce Customers
* Add support for instantly searching Settings


== Installation ==

1. Backup WordPress
1. Upload the plugin files to the `/wp-content/plugins/` directory, or install the plugin through the WordPress plugins screen directly.
1. Activate the plugin through the 'Plugins' screen in WordPress
1. Use the WooCommerce->Admin Instant Search page to configure the plugin
1. After order indexing is complete navigate to WooCommerce->Orders and you'll see the "Instant Order Search" button next to the order search button.


== Frequently Asked Questions ==

= Why should I use this? =

If you find yourself auditing orders, looking up orders for customer inquiries, or just overall searching orders frequently, this will save you from multiple page loads between search queries.

= How long will it take to index? =

By default it will index 100 orders per minute.


== Screenshots ==

1. Instant Order Search Button
2. Instant Order Search

