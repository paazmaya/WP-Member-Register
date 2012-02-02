=== Member Register ===
Contributors: paazmaya
Donate link: http://paazmaya.com/
Tags: members, organisation, forum, conversation, users, register
Requires at least: 3.0.0
Tested up to: 3.2.1
Stable tag: master

Member register management related to personal information, payments and what is common for martial arts: belt grades.

== Description ==

This is the long description.  No limit, and you can use Markdown (as well as in the following sections).

For backwards compatibility, if this section is missing, the full length of the short description will be used, and
Markdown parsed.


== Installation ==

1. Upload folder `member-register` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress

== Frequently Asked Questions ==

= A question that someone might have =

An answer to that question.

= What about foo bar? =

Answer to foo bar dilemma.

== Screenshots ==

1. This screen shot description corresponds to screenshot-1.(png|jpg|jpeg|gif). Note that the screenshot is taken from
the directory of the stable readme.txt, so in this case, `/tags/4.3/screenshot-1.png` (or jpg, jpeg, gif)
2. This is the second screen shot

== Changelog ==

= 0.7.0 =
* Released 3 February 2012
* User access level determined by bit wise comparison, in order to add flexibility
* Removed access levels from forum topics. Everyone can see everything if can access forum
* File upload for adding to members only list
* Using "Chosen" jQuery plugin to make select elements more usable

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
* Fine tuning and possible bitfails fixed

= 0.4.0 =
* Released 10 September 2011
* Payments can be deleted
* jQuery Tablesorter for sorting tables
* Grade type added, karate or kobujutsu
* Datepicker from jQuery UI, which is not included by default in Wordpress. Strange.

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
* Initial Member Registery available
* List and add members
