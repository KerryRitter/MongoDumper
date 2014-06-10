#MongoDumper
This class allows you to dump any local mongoDB database, utilizing shell command
to do so. If there is an error, please verify that the backup folder has the 
correct permissions and this script has execute permissions.

##Examples
$dumper = new MongoDumper("/var/www/html/db-backups");
$dumper->run("mydb", true); // 'true' shows debug info
$dumper->run("mydb2", true); // 'true' shows debug info
$dumper->run("mydb3");
