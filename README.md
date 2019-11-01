General Information:
--------------------

This is a very simple URL shortener and redirector/dereferrer script previously used on my website. Due to regular abuse and blacklisting of my domain in several anti-malware solutions, this service is no longer available. Think twice before you add this script to a publicly available website.

Installation Instructions:
--------------------------

Create a mysql database, user and add the table for storing link information
```
mysql
CREATE USER 'links'@'%' IDENTIFIED BY 'MY_PASSWORD';
CREATE DATABASE links;
use links;
CREATE TABLE `link` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `url` varchar(2000) COLLATE latin1_bin NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_bin;
FLUSH PRIVILEGES;
quit
```

Upload the script to your web server and modify the constants at the top to match your setup.

That's it, enjoy your new URL shortener :)
