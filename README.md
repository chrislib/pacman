# pacman

**P**retty **A**wesome **C**ontent **Man**ager

The simple idea behind it is to extend HTML by a few helpful tags to get content from the database into an xml template.

Command reference so far:

&lt;for tbl="..." where="..."&gt;
&lt;/for&gt;
- takes a comma separated list of database tables (tbl) and a joining condition (where)


&lt;if var/tbl="..." (key/row="...") val/min/max/set="..."&gt;  
(&lt;else&gt;)
&lt;/if&gt;
- var looks for a php variable (if key is set, we take an array)
- tbl + row goes to the database
- and val/min/max/set describes our condition

&lt;var src="..." (key="...") /&gt;
- gets us the value of some php variable

&lt;db tbl="..." col="..." /&gt;
- fetch the data, please ! ;o)

%abc
- will get replaced anywhere in the code by either database content ( like %tbl_col )
- or variables from SESSION or GET ( such that you cannot override the first with the latter xD
