=== Plugin Name ===
Contributors: Antonio V Mendes De Araujo
Donate link: 
Tags: subcribe, newsletter, feed, subscribers, epicwin
Requires at least: 2.9
Tested up to: 3.04
Stable tag: trunk

== Description ==
This plugin allows your blog visitors to subscribe to your blog via email and receive notifications whenever you create a new post. You can control everything from the Wordpress admin.

Use the `<?php get_epicwin_box(); ?>` template tag to add a subscribe box anywhere on your Wordpress site.

Please remember to rate if you like or dislike the plugin, your feedback keeps me motivated and always improving the plugin.

**** IMPORTANT  *****

1. Since version 1.2 the plugin has switched the mail system from mail() to sendmail. Please update your plugin immediately if you are using a version older than 1.2.

2. Always remember to back up your data before upgrading the plugin as the re-installation of the plugin will wipe out all subscribers from the database.

3. Back up your subscribers by using the export options and then use the generated file to import them after the upgrade has been finished.

== Installation ==
1. Upload the 'epicwin-subscribers' folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Enjoy and remember to report bugs. :)

== Frequently Asked Questions ==
There are no Frequently Asked Questions. Please feel free to ask any questions at antonio@epicwindesigns.com

== Screenshots ==
1. Here is a picture of the back-end of the plugin.

== Changelog ==
= 1.4 =
* Removed the SwiftMaler library and used wp_mail as the default function for sending emails, since  alot of people were getting error messages related to Swift Mailer.

= 1.3 =
* Fix a bug that would send an email notification even when updating a post. Now emails are sent only when new posts are added. More Updates will come shorlty.

= 1.2 =
* The plugin now uses the Swiftmailer library to send emails. This is a major improvement from the last version since the last one was using the mail function mulitple times to send emails.
* Swiftmailer uses sendmail to send email so if your server does not support sendmail this plugin will not send emails when a new post is created.
* Created a section for email settings so now admins can change the subject and message fields on the email.
* Fixed a few gramatical errors as well as a few HTML syntax errors.

= 1.1 =
* Changed the code structure so it is more readable and allow for internationalization.
* Created a template tag so that it is possible to add the subscribe box anywhere in wordpress.
* This version fixes core functions that weren't working due to different settings between production and testing environments.

== Upgrade Notice ==
* It is very important that you update the plugin to the latest version because the old way the plugin was sending emails could really put a strain on a server.
* This version fixes core functions that weren't working due to diferente settigns between production and testing environments.
