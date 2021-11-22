# pacman

Pretty Awesome Content Manager

The simple idea behind it is to extend HTML with a few helpful tags to get content from the database into an xml template.

Command reference so far:

&lt;for tbl="..." where="..."&gt;   // tbl takes a comma separated list of database tables, the where here is obviousle the joining condition
<> </for>

&lt;if var/tbl="..." (key/row="...") val/min/max/set="..."&gt;  // var looks for a php variable (if key is set, we take an array)
(&lt;else&gt;)                                                  // tbl + row goes to the database
&lt;/if&gt;                                                     // and val/min/max/set describes our condition

&lt;var src="..." (key="...") /&gt;   // gets us the value of some php variable

&lt;db tbl="..." col="..." /&gt;      // fetch the data, please

