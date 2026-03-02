#MongoDumper
This class allows you to dump any local mongoDB database, utilizing shell command to do so. If there is an error, please verify that the backup folder has the correct permissions and this script has execute permissions. The script executes mongodump and then zips the dump contents and deletes the original dump folder to conserve disk space.

##Examples

###Basic Usage
$dumper = new MongoDumper("/var/www/html/db-backups");  
$dumper->run("mydb", true); // 'true' shows debug info  
$dumper->run("mydb2", true); // 'true' shows debug info  
$dumper->run("mydb3");

###Progress Callback
You can set a progress callback to receive real-time updates during long-running backup operations:

```php
$dumper = new MongoDumper("/var/www/html/db-backups");

$dumper->set_progress_callback(function($data) {
    $stage = $data['stage'];      // 'mongodump', 'zip', 'cleanup', 'complete', 'error'
    $progress = $data['progress']; // 0-100 percentage (null for stages without progress)
    $message = $data['message'];  // Human-readable message
    $database = $data['database']; // Database name being backed up
    
    echo "[$stage] $message\n";
});

$dumper->run("mydb");
```

The callback receives an array with the following keys:
- **stage**: Current operation stage ('mongodump', 'zip', 'cleanup', 'complete', 'error')
- **progress**: Percentage complete (0-100) or null for indeterminate stages
- **message**: Human-readable status message
- **database**: Name of the database being backed up

##Disclaimer
Use at your own risk. This script uses shell_exec and rmdir. Shell_exec prevents security risks that you must handle in your own environment. Rmdir can cause damage to your file system if the backup path is not entered correctly. While I use this script myself without issue, **neither I nor Acumen Consulting is responsible for any damage done by this script.**