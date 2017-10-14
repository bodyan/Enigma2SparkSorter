# Enigma2SparkSorter
Enigma2SparkSorter - little tool, for creating Spark channel list/favourites according Enigma2 bouquets. 
If you have old channel list, you need manually to check every working service. 
On Enigma2 while scaning os delete all not active service. Also Dreamboxeditor is pretty good editor.
So how it's work:
1. Update actual satellites.xml on enigma2 and scan satellites. Create/edit/save bouquets and copy them into enigma2 folder.
2. Update sat.xml(generate new with Satellites Update) on spark os, delete all services and scan satellites. After scanning, save files and copy them into spark folder.
3. Run this script, after that you will be have new tv_prog.xml, tv_fav.xml in sprk folder. 
  Copy and update receiver with new channel list.
  




