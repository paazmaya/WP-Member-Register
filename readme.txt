=== Member Register ===

Contributors: paazmaya
Donate link: http://paazmaya.fi/
License: MIT
License URI: http://opensource.org/licenses/MIT
Author: Jukka Paasonen
Author URI: http://paazmaya.fi
Tags: members, organisation, forum, conversation, users, register, martial-art, club
Requires at least: 3.5.0
Tested up to: 4.1.0
Stable tag: master

Member register management related to personal information,
payments and what is common for martial arts: belt grades.

== Description ==

Member register offers a way to keep the information of the members organised.

The personal information is stored and if the given member has been registered to use
WordPress via WordPress user, they can change their personal settings.
Also martial art grades and membership payments are stored.

== Installation ==

As any other WordPress plugin:

1. Upload folder `member-register` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress

== Frequently Asked Questions ==

= What do you mean 'member'? =

People who are members of a club or a association are called members, thus a member is managed in a member register

= What is the focus, target or scope of this plugin? =

To address the need for managing a martial arts club which has several member with payment, grading and other information.
Ideally this plugin provides most of the things that might be needed by a martial art club.
As a matter of fact, also clubs can be members, as there are possibilities of having many clubs listed in the register.

== Upgrade Notice ==

Upgrades are somewhat seamless, but not until the release of 1.0.0 will there be separate method for upgrade handling.

== Screenshots ==

1. New payment form, in Finnish
2. New grade form, in Finnish

== Changelog ==

= 0.14.0 =
* Released 18th February 2016
* Following WordPress coding standards (http://codex.wordpress.org/WordPress_Coding_Standards) for PHP, JavaScript and CSS
* Only visible and active members used for any calculations
* Using short syntax for arrays, thus making PHP 5.4 the lowest version supported
* Styles no longer leaking to other parts of the administrating area

= 0.13.0 =
* Released 26th March 2014
* User Interface testing with CasperJS, http://casperjs.org
* Members listed in two lists based on their active state, so that recently registered users will be easier to find
* Fixed showing hidden columns of member list
* Better error handling and checking in file uploads
* Use localStorage to remember hidden columns in member listing
* Filter member list with single input field

= 0.12.0 =
* Released 4th March 2014
* Translations fixed
* Fixed database queries to use WP database class instance
* Added MMA to the available martial arts list
* Added EditorConfig, see http://EditorConfig.org
* Fix listing Clubs that have no members registered to them

= 0.11.4 =
* Released 5th January 2014
* Table styling to match general WP Admin styles
* Using dashicons for whatever icons are needed
* Replaced Tablesorter with Stupid Table Sorter (http://joequery.github.io/Stupid-Table-Plugin/)

= 0.11.3 =
* Released 3rd October 2013
* Removed members not listed anywhere any longer
* Select2 update to 3.4.3 and its style fixes

= 0.11.2 =
* Released 4th July 2013
* Replaced Chosen with Select2

= 0.11.1 =
* Released 28th April 2013
* Few more required fields and types that make client side validation easier
* Fix listing where deleted member was still shown (visible == 0)

= 0.11.0 =
* Released 22th April 2013
* In case public registration is enabled, additional items are shown in the form and hooked in to the members register
* Allow to delete users that do not have payments nor grades
* Localisation fixes, now primarily Finnish but via PO files as originally intended

= 0.10.0 =
* Released 14th August 2012
* Show/hide columns
* Payment can be marked as paid when creating it

= 0.9.1 =
* Released 25 February 2012
* Reordering PHP files

= 0.9.0 =
* Released 14 February 2012
* Group management. Group contains people who can be listed and have rights to files.

= 0.8.2 =
* Released 9 February 2012
* Grade accepts only a year too, but it is transformed to the first day of that year
* Removal of jQuery.cluetip

= 0.8.1 =
* Released 8 February 2012
* All forms are now autocomplete=on
* Datalist for possible directory entries in file upload form
* Tiny fixes that are found from debug.log, like for example that member info could not be updated due to missing space in SQL phrase
* Datalist used also in new grade form for nominator and location, as well as in new payment form

= 0.8.0 =
* Released 7 February 2012
* Using as much as possible WP specific database functions like $wpdb->update
* File management has now access limitations based on club, main art and minimun grade, if needed

= 0.7.3 =
* Released 5 February 2012
* Fixes after testing done by other people
* LIMIT 1 on all SQL UPDATE phrases

= 0.7.2 =
* Released 4 February 2012
* Plenty more of user access rights checking
* Splitting "functions" to "payment" and "grade" PHP files

= 0.7.1 =
* Released 4 February 2012
* SQL query phrase fix in forum topic information

= 0.7.0 =
* Released 3 February 2012
* User access level determined by bit wise comparison, in order to add flexibility
* Removed access levels from forum topics. Everyone can see everything if can access forum
* File upload for adding to members only list
* Using "Chosen" jQuery plugin to make select elements more usable
* Updated jQuery.validate to 1.9.0 and now using it
* WP 3.3 has now jquery-ui-datepicker, thus using that

= 0.6.0 =
* Released 10 November 2011
* Localisation for English and Finnish done properly with PO files
* Remove (set hidden) a member, in same way like discussions, grades and payments
* Club management
* Bug fixing of those which were found rather easily

= 0.5.6 =
* Released 28 September 2011
* Tablefilter at client side for finding the data faster (picnet.table.filter.min.js)
* More efficient use of helper functions
* Nicer and more human readable headings for table columns
* Installation hook up to date, including the country data

= 0.5.5 =
* Released 23 September 2011
* More information of a member via jQuery Cluetip while in the member listing
* Moderate messages by hiding them, posts in level 4 and up, topics in level 5 and up
* Topic visibility level can be set if member has level 5 or higher
* Access levels actually checked before showing Forum forms

= 0.5.4 =
* Released 23 September 2011
* Member access levels used in Forum and shown more clearly in member forms
* Timezone was ignored from the .htaccess file, thus is now set via wp_loaded() hook

= 0.5.3 =
* Released 22 September 2011
* Create new topic on the same page as they are listed
* Create a message to a topic on the same page where other messages are listed
* Several SQL query related fixes for accuracy

= 0.5.2 =
* Released 22 September 2011
* Tablesorter styling to actually show how table is sorted
* Member access levels defined

= 0.5.1 =
* Released 22 September 2011
* Initial version of Forum, listing topics

= 0.5.0 =
* Release 17 September 2011
* Fine tuning and possible bit falls fixed

= 0.4.0 =
* Released 10 September 2011
* Payments can be deleted
* jQuery Tablesorter for sorting tables
* Grade type added, karate or kobujutsu
* Datepicker from jQuery UI, which is not included by default in WordPress. Strange.

= 0.3.0 =
* Released 03 April 2011
* Members can be edited
* List of Nationalities added
* Adding grades for members form
* Payments include the reference number
* Wrote something in Friday before getting totally wasted and now I am not sure what happened. Where is my bicycle?

= 0.2.0 =
* Released 01 April 2011
* Add new members and link to existing WP users
* Adding payments and updating their status

= 0.1.0 =
* Released 29 March 2011
* Initial Member Register available
* List and add members
