=== Advanced Password Security ===
Contributors: trewknowledge, fclaussen
Tags: password, security
Requires at least: 4.0.0
Requires PHP: 5.5
Tested up to: 4.9.1
Stable tag: 1.0.3
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Force your administrators to update their password after a set amount of time.

== Description ==

Force your admins and/or selected roles to update their password to a new one after a set amount of time. Users will also be unable to use a previously used password.

= FORCE SPECIFIC ROLES TO CHANGE THEIR PASSWORDS =

On the settings page, you can select which roles you want to enforce the password reset.

= WHY FORCE ADMINS TO DO THIS? =

If your site has several admins, all it takes is for one of them to have a weak password or to not change it often for someone to figure it out or brute force attack it. With access to only one of your admin accounts, the hacker can take total control of your website.

By forcing admins to change their password often and not allowing them to use previously used password, you add another layer of security to your data.

= PRIVACY POLICY =
This plugin does not share any data from the site or its users. The only data that is stored in the site's database is the previously used encrypted passwords of users so they can’t use those again. That information never leaves the server where the plugin is installed and is never shared.

We do not have access to this or any other information.

== Installation ==

= Automatic installation =

Automatic installation is the easiest option as WordPress handles the file transfers itself and you don’t need to leave your web browser. To
do an automatic install of, log in to your WordPress dashboard, navigate to the Plugins menu and click Add New.

In the search field type “Advanced Password Security” and click Search Plugins. Once you’ve found our plugin you can view details about it such as the point release, rating and description. Most importantly of course, you can install it by simply clicking "Install Now".

= Manual installation =

The manual installation method involves downloading our plugin and uploading it to your web server via your favorite FTP application. The WordPress codex contains [instructions on how to do this here](http://codex.wordpress.org/Managing_Plugins#Manual_Plugin_Installation).

= Updating =

Automatic updates should work like a charm; as always though, ensure you backup your site just in case.

== Frequently Asked Questions ==

= Do my regular users need to change their password too? =

No! By default the plugin asks for Administrators, Editors, Authors and Contributors to change their password.
Your subscribers will not be disturbed.

= How often should I ask admins to change their password? =

How often as you'd like. By default, it is set to once a month. Just remember that the number in the settings is the amount in days.

= Where can I get help? =

For support requests, refer the plugin repository on [github](https://github.com/trewknowledge/Advanced-Password-Security/issues). Open a new issue and someone on our team will be happy to help.

== Changelog ==

= 1.0.3 - 2018-01-01 =
* Fix typo on last update that caused the plugin not to work properly.

= 1.0.2 - 2017-12-29 =
* Fixed an issue where users could update their password to a blank one and get away with it. WP does not stop them from doing this. ( Thanks to Aires Gonçalves for pointing this out. )

= 1.0.0 - 2017-12-27 =
* Initial Release
