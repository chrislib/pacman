# pacman

Pretty Awesome Content Manager

The simple idea behind it is to extend HTML with a few helpful tags to get content from the database into an xml template.

Command reference so far:

<for tbl="..." where="...">   // tbl takes a comma separated list of database tables, the where here is obviousle the joining condition
</for>

<if var/tbl="..." (key/row="...") val/min/max/set="...">  // var looks for a php variable (if key is set, we take an array)
(<else>)                                                  // tbl + row goes to the database
</if>                                                     // and val/min/max/set describes our condition

<var src="..." (key="...") />   // gets us the value of some php variable

<db tbl="..." col="..." />      // fetch the data, please

