<!--
# media module
# privacy for embedded content
#
# Part of »Zugzwang Project«
# https://www.zugzwang.org/modules/activities
#
# @author Gustaf Mossakowski <gustaf@koenige.org>
# @copyright Copyright © 2025 Gustaf Mossakowski
# @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
#
-->

# Privacy for Embedded Content

Embedded content from a third party server 


# Data protection for embedded content 

Embedded content from third-party servers, e.g. videos, is not displayed
directly to the visitor to the website. Instead, consent is asked as to
whether this content should be displayed directly on the website.
Alternatively, it is possible to view this content directly on the other
website via a link.

The consent is stored in a cookie. From then on, further embedded
content is displayed directly.

In order to be able to revoke consent at a later date, an option to
change your own settings should be built in (e.g. in the privacy
policy). This works via this block:

	%%% request embedprivacy %%%

The section could read as follows:

	## Embedded content
	
	If you want, you can have content from other
	websites displayed directly on our website. Data is transmitted to these
	websites. The data protection guidelines of these websites apply. You
	can decide here whether you want to see this data directly on our
	website:
	
	%%% request embedprivacy %%%
