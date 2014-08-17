#!/bin/sh


FILE_IN="ext-all-debug.js"
FILE_OUT="ext5aliases.localonly"

rm -f $FILE_OUT


rm -f localonly1[a-z]

grep -P "alias\s*:" $FILE_IN >> localonly1a
grep -P "xtype\s*:" $FILE_IN >> localonly1a
grep -Pzo '(?ms)alias\s*:\s*\[.*?\]' $FILE_IN >> localonly1a


rm -f localonly2[a-z]
cat localonly1a > localonly2a

cat localonly2a | sed -e "s/\[//" > localonly2b
cat localonly2b | sed -e "s/\]//" > localonly2c
cat localonly2c | sed -e "s/^ *alias *: *//" > localonly2d
cat localonly2d | sed -e "s/^ *xtype *: *'/'widget./" > localonly2e
cat localonly2e | sed -e "s/^ *//" > localonly2f
cat localonly2f | sed -e "s/ *\$//" > localonly2g


rm -f localonly3[a-z]*
cat localonly2g > localonly3a

cat localonly3a | grep "xtype:" >> localonly3b-del
cat localonly3a | grep "throw new" >> localonly3b-del
cat localonly3a | grep "function.object" >> localonly3b-del
cat localonly3a | sed -e "/xtype:/d" > localonly3aa
cat localonly3aa | sed -e "/throw new/d" > localonly3ab
cat localonly3ab | sed -e "/function.object/d" > localonly3b

cat localonly3b | sort | uniq > localonly3c
cat localonly3c | sed -e "/^\$/d" > localonly3d
cat localonly3d | sed -e "s/'\$/',/" > localonly3e


echo "<?PHP\n\n\$extAliases = array(" >> $FILE_OUT
cat localonly3e | sed -e "s/^/  /" >> $FILE_OUT
echo "\n);\n\n\n?>\n" >> $FILE_OUT

rm -f localonly[0-9][a-z]


