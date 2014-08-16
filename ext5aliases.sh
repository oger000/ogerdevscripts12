#!/bin/sh


FILE_IN="ext-all-debug.js"
FILE_OUT="aliases.localonly"

rm -f $FILE_OUT

rm -f localonly1
grep -P "alias\s*:" $FILE_IN >> localonly1
grep -P "xtype\s*:" $FILE_IN >> localonly1

cat localonly1  | sed -e "s/\[//" > localonly2a
cat localonly2a | sed -e "s/\]//" > localonly2b
cat localonly2b | sed -e "s/^ *alias *: *//" > localonly2c
cat localonly2c | sed -e "s/^ *xtype *: *'/'widget./" > localonly2d
cat localonly2d | sed -e "s/^ *xtype *: */'widget./" > localonly2z

cat localonly2z | sort | uniq > localonly3



#rm -f localonly1 localonly2[a-z]





