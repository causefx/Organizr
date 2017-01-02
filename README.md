# Organizr
HTPC/Homelab Services Organizer - Written in PHP

## Setup
**Requirements:** 

- A webserver (nginx, Apache, IIS or any other webserver) 
- PHP 5.5+
- SQLite (uncomment the php_sqlite extension in php.ini file and point it to the right directory)

## Downloading
- To set it up, clone this repository:
`` git clone https://github.com/causefx/Organizr `` or download the ZIP-file.
- Place all files on a publically accessible webserver, either directly in the root, or inside a directory called ``Dashboard`` or whatever you want it to be called.  Please set the correct user permissions on the directory and it's files.

## Instructions
Upload all contents of zip or git pull the zip into the folder you want to have this on your webserver.  

Set the correct file permission on the files.

Open up the index.php file in your browser once everything is uploaded.  The script take you through to create an admin account and then you will sign in.

The Settings page will load automatically so now you can create tabs to whatever you like in whatever order.

When creating Tabs, type in the name of the tab and hit ``Enter`` this will drop it down to the list where you can fill out the rest of the data.

Name = The Name of Your Tab

URL = URL of The Tab's Location

Icon = Font Awesone icon

Icon URL = If this is filled out, it will use this icon instead of the Font Awesome one

Default = Checking this option will make this tab load first

Active = Checking this option will enable the tab for your page

User = This option enables this tab for Admins and Users but not Guests

Guest = This option enables the tab for Admin, Users and Guests

No iFrame = This option makes it so when you click the tab it will open in a new window (Used for apps that block iFrames)


Once the cookie expires you will need to login again

If you aren't logged in you or any guest will only see your guest enabled tabs

## Desktop Images

Guest View
![Desktop screenshot](http://i.imgur.com/zFlDHYy.png)

Login View
![Desktop screenshot](http://i.imgur.com/QzeErt1.png)

Main View
![Desktop screenshot](http://i.imgur.com/D2vlUtb.png)

Settings View
![Desktop screenshot](http://i.imgur.com/DnDpUSp.png)

Color/Theme View
![Desktop screenshot](http://i.imgur.com/sxnDa9G.png)

User Management View
![Desktop screenshot](http://i.imgur.com/MwNC6Zr.png)

About/Help View
![Desktop screenshot](http://i.imgur.com/HYQPSEu.png)

Side Menu Open View
![Desktop screenshot](http://i.imgur.com/PLVMr0y.png)

Logout View
![Desktop screenshot](http://i.imgur.com/6zfq9UG.png)

## Mobile Images

Main View
<img src="http://i.imgur.com/pfoowtS.png" height="400">

Menu Open View
<img src="http://i.imgur.com/qQc7XKO.png" height="400">

Theme Distributed with Extended License from Creative Market
