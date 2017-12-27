# Advanced-Password-Security
License: GPLv3

License URI: http://www.gnu.org/licenses/gpl-3.0.html

# Description 

Force your admins and/or select roles to update their password to a new one after a set amount of time.

## Force specific roles to change their passwords

On the settings page you can select which roles you want to enforce the password reset.

## Why force admins to do this?

Having multiple administrators are a security issue and even though we can't guarantee that this plugin will keep your data safe, it will definitely help.

Let's say your site has 6 admins.
All it takes is for one of them to have a week password or not change it often for someone to figure it out or brute force it. With access to only one of your 6 admin accounts, the hacker can do whatever it wants with your data.
By forcing admins to change their password from time to time and not allowing them to use a password they've used in the past, you add another layer of security to your data.

## Privacy Policy
This plugin do not share any data of the site or it's users. The only data that is stored on the site database is the previously used encrypted passwords of users so they can't use those again. But that information never leaves the server where the plugin is installed and is never shared.
We have no access to this or any other information.

# Installation

## Automatic installation

Automatic installation is the easiest option as WordPress handles the file transfers itself and you don’t need to leave your web browser. To do an automatic install of, log in to your WordPress dashboard, navigate to the Plugins menu and click Add New.

In the search field type “Advanced Password Security” and click Search Plugins. Once you’ve found our plugin you can view details about it such as the point release, rating and description. Most importantly of course, you can install it by simply clicking "Install Now".

## Manual installation

The manual installation method involves downloading our plugin and uploading it to your web server via your favorite FTP application. The WordPress codex contains [instructions on how to do this here](http://codex.wordpress.org/Managing_Plugins#Manual_Plugin_Installation).

## Updating

Automatic updates should work like a charm; as always though, ensure you backup your site just in case.

# Frequently Asked Questions

## Do my regular users need to change their password to?

No! By default the plugin asks for Administrators, Editors, Authors and Contributors to change their password.
Your subscribers will not be disturbed.

## How often should I ask admins to change their password?

How often as you'd like. By default, it is set to once a month. Just remember that the number in the settings is the amount in days.

## Where can I get help?

For support requests, refer the plugin repository on [github](https://github.com/trewknowledge/Advanced-Password-Security/issues). Open a new issue and someone on our team will be happy to help.

# Changelog

### 1.0.0 - 2017-12-27
* Initial Release
