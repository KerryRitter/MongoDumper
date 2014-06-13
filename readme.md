#MongoDumper
This class allows you to dump any local mongoDB database, utilizing shell command to do so. If there is an error, please verify that the backup folder has the correct permissions and this script has execute permissions. The script executes mongodump and then zips the dump contents and deletes the original dump folder to conserve disk space.

##Examples
$dumper = new MongoDumper("/var/www/html/db-backups");  
$dumper->run("mydb", true); // 'true' shows debug info  
$dumper->run("mydb2", true); // 'true' shows debug info  
$dumper->run("mydb3");

##Disclaimer
Use at your own risk. This script uses shell_exec and rmdir. Shell_exec prevents security risks that you must handle in your own environment. Rmdir can cause damage to your file system if the backup path is not entered correctly. While I use this script myself without issue, **neither I nor Acumen Consulting is responsible for any damage done by this script.**