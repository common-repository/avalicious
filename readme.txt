=== Avalicious! ===
Contributors: alisdee
Tags: dreamwidth, livejournal, tumblr, social, users, avatars, comments
Requires at least: 2.7.1
Tested up to: 5.0.3
Stable tag: 1.3.3

A WordPress plugin that integrates LiveJournal, Dreamwidth, and Tumblr user avatars in WordPress comments.

== Description ==

**Avalicious!** is a WordPress plugin that integrates LiveJournal, Dreamwidth, and Tumblr user avatars in WordPress comments. It is a functional re-write of [Also LJ Avatar](http://alltrees.org/Wordpress/#ALA "Alltrees' Also LJ Avatar") (itself a re-write of some even older plugins), with the following differences:

* user avatars are downloaded via the cURL library, hopefully avoiding issues with hosts that disable remote URL includes
* the regexps for extracting avatars have been improved
* the user’s journal URL is extracted from a comment’s URL (not the name)
* the user’s name is not re-written.

= Version 1.3.3 = 

* Minor fixes. Should not have better compatibility with Dreamwidth icons.

= Version 1.3.2 = 

* Should now work with both HTTP *and* HTTPS Tumblrs. Magic!

= Version 1.3.1 = 

* Small regexp fixes.

= Version 1.3 =

* Added support for Tumblr icons.
* Old icons are now cleaned up automatically every month.
* Small bugfixes.


== Installation ==

1. Upload the `avalicious` folder to the `/wp-content/plugins/` directory.
1. If required, make the `avalicious/danga-icons` directory writable (though the plugin should work without it).
1. Activate the plugin through the 'Plugins' menu in WordPress.

== Credits ==

**Avalicious!** is based off the original [Also LJ Avatar](http://alltrees.org/Wordpress/#ALA "Alltrees' Also LJ Avatar") by Ravenwood and Irwin. No disrespect is intended towards the original authors; without their great work, this plugin wouldn't have been possible (or at least would've taken a hell of a lot longer to write).