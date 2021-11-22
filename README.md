# pacman

**P**retty **A**wesome **C**ontent **Man**ager

The simple idea behind it is to extend HTML by a few helpful tags to get content from the database into an xml template.
I got the code down to 400 lines of php - but at the moment it only does a very basic job - updates coming soon!

Command reference so far:

&lt;for tbl="..." where="..."&gt;
&lt;/for&gt;
- takes a comma separated list of database tables (tbl) and a joining condition (where)
- and loops over the contents (thus replicating everything inside the for tag)


&lt;if var/tbl="..." (key/row="...") val/min/max/set="..."&gt;  
(&lt;else&gt;)
&lt;/if&gt;
- var looks for a php variable (if key is set, we take an array or object)
- tbl + row goes to the database
- and val/min/max/set describes our condition

&lt;var src="..." (key="...") /&gt;
- gets us the value of some php variable

&lt;db tbl="..." col="..." /&gt;
- fetch the data, please ! ;o)


&lt;def var="..." val="..." /&gt;
- define the data, if you're too lazy for setting up a db table :)
- var ensures it's got a proper name, val can be a comma separated list of e.g. images or text

%abc
- will get replaced anywhere in the code by either database content ( like %tbl_col )
- or variables from SESSION or POST/GET ( such that you cannot override the first with the latter xD
