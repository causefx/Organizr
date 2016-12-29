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

Once the cookie expires you will need to login again

If you aren't logged in you or any guest will only see your guest enabled tabs

## Images

Main View
![Desktop screenshot](http://i.imgur.com/sHKNeBM.png)

Login View
![Desktop screenshot](http://i.imgur.com/tq9IgWZ.png)

Settings View
![Desktop screenshot](http://i.imgur.com/qtoZkX6.png)

Theme Distributed with Extended License from Creative Market
