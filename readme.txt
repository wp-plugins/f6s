=== f6s ===
Contributors: f6s,emanuellainas
Tags: f6s, data, api, mentors, teams, entrepreneurs, profile, deals
Requires at least: 3.4
Tested up to: 3.4
Stable tag: 0.4.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Integrate your f6s data into a WordPress site

== Description ==

This plugin allows you to integrate your f6s data inside Wordpress posts and pages.

Please direct any questions or bug reports to support@f6s.com

**Data Items Available through the f6s WP Plugin:**

**Valid Properties for Deals**

- id - reference id of the deal
- name - name of the deal
- description - description of the deal
- thumbnail - information about the deal image:
	- thumbnail.url - full URL of the deal thumbnail
	- thumbnail.width / thumbnail.height - size, in pixels of the image
- value - value of the deal
- beta - is this a beta product
- category - internal category of the deal
	- category.id - internal id of category
	- category.name - name of category
- company - publisher company of the deal
	- company.id -  id of company profile
	- company.name - name of company
	- company.url -  company profile url
- url - url where more info about this deal available
- get_deal_url - url to get the deal

**Valid Properties for Mentors**

- id - reference id of the mentor
- name - name of the mentor
- description - mentor profile description
- url - f6s profile url
- current_title - current work title of the mentor (ex: CEO of ExampleCompany) - empty string if no data available
- thumbnail - information about the mentor’s profile image:
	- thumbnail.url - full URL of the mentor thumbnail
	- thumbnail.width / thumbnail.height - size, in pixels of the image
- skills (list) - list of mentor skills, has properties:
	- name - name of the skill
- links (list) - list of profile links, has properties:
	- type - link type, can be: website, facebook, twitter, linkedin, github, iosapp, android, mobile
- value - the actual URL
mentored_orgs (list) - list of all organizations mentored by this person, has properties:
	- name - name of the organization
	- url - f6s URL of the organization
- thumbnail - information about the organization image:
		- thumbnail.url - full URL of the organization thumbnail
		- thumbnail.width / thumbnail.height - size, in pixels of the image
	
**Valid Properties for Teams**

- id - reference id of the team
- name - name of the team
- description - description of the team
- url - f6s profile url
- thumbnail - information about the team’s profile image:
	- thumbnail.url - full URL of the mentor thumbnail
	- thumbnail.width / thumbnail.height - size, in pixels of the image
- links (list) - list of profile links, has properties:
	- type - link type, can be: website, facebook, twitter, linkedin, github, iosapp, android, mobile
- value - the actual URL
- members (list) - list of all team’s members, has properties:
	- profile_id - reference id of the person
	- name - name of the person
	- url - f6s profile url
- thumbnail - information about the organization image:
		- thumbnail.url - full URL of the organization thumbnail
		- thumbnail.width / thumbnail.height - size, in pixels of the image


**Resizing thumbnails:**

You can resize thumbnails according to your specifications using html / css attributes. The plugin served them in a single size.

**Including data from f6s:**

The data can be displayed in Wordpress posts and pages by using shortcodes. You will be making use of the following:

[deal] (self-closing) - single deal object. Has the following attributes:

- program - the ID of your accelerator or event profile
- id - the ID of the deal
- display - property that you want to display. See “Valid Properties for Deals”

[deal-list] (enclosing) -  loop through a list of deals, one by one. Has the following attributes:

- program - the ID of your accelerator or event profile

[mentor] (self-closing OR enclosing) - single deal object. Has the following attributes:

- program - the ID of your accelerator or event profile
- id - the ID of the deal
- display (used with self-closing mentor object) - property that you want to display. See “Valid Properties for Mentors”
- list (used with enclosing mentor object) - use this to loop though the properties marked as list (Valid Properties for Mentors). See examples from ...

[mentors-list] (enclosing) -  loop through a list of deals, one by one. Has the following attributes:

- program - the ID of your accelerator or event profile

[team] (self-closing OR enclosing) - single team object. Has the following attributes:

- program - the ID of your accelerator profile
- id - the ID of the team
- display (used with self-closing mentor object) - property that you want to display. See “Valid Properties for Team”
- list (used with enclosing mentor object) - use this to loop though the properties marked as list (Valid Properties for Mentors). See examples from ...

[team-list] (enclosing) -  loop through a list of teams, one by one. Has the following attributes:

- program - the ID of your accelerator profile

[f6s-data] (enclosing) - put your html inside this shortcode to protect it from alterations when switching between HTML and Visual mode

[list-index] (enclosing) - to be used inside a list structure. Conditions the content in between. 

Has the following properties:

- first (no value) - the enclosing content will only be displayed once, at the beginning of the list;

- notfirst (no value) - opposite of first, the enclosing content will not be displayed at the beginning of the list;

- last (no value) - the enclosing content will only be displayed once, at the end of the list;
- notlast (no value) - opposite of last;

	- odd (no value) - the enclosing content will only be displayed for an odd index when looping through list items

	- even (no value) - the enclosing content will only be displayed for an even index when looping through list items

	- multiple (numeric value) - the enclosing content will only be displayed if index is a 
multiple of the given value, when looping through list items

	- notmultiple (numeric value) - opposite of multiple. Creative use of embedded multiple and notmultiple tags can solve most formatting problems.


**Important!** *It is strongly recommended that you always add these shortcodes in HTML edit mode and not Visual mode.*


== Installation ==

1. Install the f6s plugin either via the WordPress.org directory or by uploading the zip file.
2. Activate the f6s Wordpress Plugin
3. Go to Settings, f6s Plugin, enter your private API key and hit Save Changes
4. That's it. Go edit your content now.

== Frequently Asked Questions ==

= My shortcodes seems to be broken when viewing a post, although I am sure the structure is correct. What should I do? =

1. Check if the f6s plugin is active
2. Add a slash to the end of each self-closing tag (e.g. [deal display=name /])
3. Make sure you're editing the post in HTML mode not Visual Mode

= Switching to Visual Mode breaks my shortcodes. What now? =

Try enclosing all your f6s shotcodes between [f6s-data] and [/f6s-data]

== Changelog ==

= 0.4 =
* Multiple lists on the same page now work properly

= 0.3 =
* Added support for teams

= 0.2 =
* Added support for mentors