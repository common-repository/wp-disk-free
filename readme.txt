=== WP Disk Free ===
Contributors: davide.airaghi
Tags: disk free, disk usage, check quota
Requires at least: 4.0
Tested up to: 6.3.2
Stable tag: 0.2.3
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Plugin used to check how much free space is available on the disk/partition used to host a Wordpress installation.

== Description ==
This plugin checks how much free space is available on the disk/partition used to 
host your Wordpress installation and send an email when it is less than a specified
minimum level configured by the administrator of the website.

The configuration page can be found under "Settings - Disk Free" menu.


== Installation ==
1. Create the directory wp-disk-free in your '/wp-content/plugins/' directory
2. Upload all the plugin's file to the newly created directory
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Configure the plugin using page "Settings - Disk Free"

== Changelog ==

= 0.2.3 =
* added icons
* data validation optimized

= 0.2.2 =
* adapted to WP 5.8.2
* better input management

= 0.2.1 =
* fixed translations
* fixed textual info

= 0.2 =
* added notification email for free disk space back to normal

= 0.1 =
* first internal release