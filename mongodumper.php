<?php
/**
 * This class allows you to dump any local mongoDB database, utilizing shell command
 * to do so. If there is an error, please verify that the backup folder has the 
 * correct permissions and this script has execute permissions.
 * 
 * Example:
 *	$dumper = new MongoDumper("/var/www/html/db-backups");
 *  $dumper->run("mydb", true); // 'true' shows debug info
 *  $dumper->run("mydb2", true); // 'true' shows debug info
 *  $dumper->run("mydb3");
 */

class MongoDumper {
	private $_BACKUP_FOLDER = "";
	private $_CURRENT_DATE_TIME = "";
	private $current_dump_path = "";
	private $database = "";
	private $files_to_delete = array();
	private $debug = false;
	private $progress_callback = null;
	private $current_stage = "";
	private $total_files = 0;
	private $processed_files = 0;

	public function __construct($backup_folder) {
		$now = new DateTime;
		$this->_BACKUP_FOLDER = rtrim($backup_folder, '/');
		$this->_CURRENT_DATE_TIME = $now->format('d-m-Y_H-i');
	}

	public function run($database, $debug = false) {
		$this->debug = ($debug === true);
		try {
			$this->current_dump_path = $this->_BACKUP_FOLDER . "/" . $database . "_" . $this->_CURRENT_DATE_TIME;
			$this->database = $database;

			$this->echo_if_debug("<p><strong>Backing up '" . $database . "' to '" . $this->current_dump_path . "'</strong></p>");

			$this->echo_if_debug("<ol>");
			$this->echo_if_debug("<li>Executing mongodump...</li>");
			$this->report_progress('mongodump', 0, 'Starting mongodump...');
			$this->mongodump();
			$this->report_progress('mongodump', 100, 'mongodump complete');

			$this->echo_if_debug("<li>Zipping files...</li>");
			$this->report_progress('zip', 0, 'Starting zip operation...');
			$this->zip_files();
			$this->report_progress('zip', 100, 'Zip complete');

			$this->echo_if_debug("<li>Deleting dump folder...</li>");
			$this->report_progress('cleanup', 0, 'Starting cleanup...');
			$this->delete_dump_folder();
			$this->report_progress('cleanup', 100, 'Cleanup complete');

			$this->echo_if_debug("<li>Complete!</li>");
			$this->echo_if_debug("</ol>");
			$this->report_progress('complete', 100, 'Backup operation complete');
			return true;
		}
		catch (Exception $ex) {
			$this->report_progress('error', null, 'Error: ' . $ex->getMessage());
			return false;
		}
	}

	private function echo_if_debug($string) {
		if ($this->debug) {
			echo $string;
		}
	}

	public function set_progress_callback($callback) {
		if (is_callable($callback)) {
			$this->progress_callback = $callback;
		}
	}

	private function report_progress($stage, $progress = null, $message = null) {
		$this->current_stage = $stage;
		if ($this->progress_callback !== null) {
			call_user_func($this->progress_callback, array(
				'stage' => $stage,
				'progress' => $progress,
				'message' => $message,
				'database' => $this->database
			));
		}
	}

	private function mongodump() {
		$command = "mongodump --db " . $this->database . " --out " . $this->current_dump_path;
	    $results = shell_exec($command);
	    $this->echo_if_debug("<ul><li>" . $command . "</li><li>".$results."</li></ul>");
	}

	private function zip_files() {
		$database_dump_folder = $this->current_dump_path . "/" . $this->database;

		// Initialize archive object
		$zip = new ZipArchive;
		$zip->open($this->current_dump_path . '.zip', ZipArchive::CREATE);

		// Create recursive directory iterator
		$files = new RecursiveIteratorIterator(
		    new RecursiveDirectoryIterator($database_dump_folder),
		    RecursiveIteratorIterator::LEAVES_ONLY
		);

		// Count total files first
		$this->total_files = 0;
		foreach ($files as $file) {
			if ($file->isFile()) {
				$this->total_files++;
			}
		}

		$this->processed_files = 0;
		$this->report_progress('zip', 0, 'Found ' . $this->total_files . ' files to zip');

		// Reset iterator and process files
		$files = new RecursiveIteratorIterator(
		    new RecursiveDirectoryIterator($database_dump_folder),
		    RecursiveIteratorIterator::LEAVES_ONLY
		);

		foreach ($files as $name => $file) {
		    // Get real path for current file
		    $filePath = $file->getRealPath();

		    // Add current file to archive
		    $zip->addFile($filePath);

		    // add file to delete queue
		    $this->files_to_delete[] = $filePath;

		    // Report progress
		    $this->processed_files++;
		    $progress = $this->total_files > 0 ? round(($this->processed_files / $this->total_files) * 100) : 0;
		    $this->report_progress('zip', $progress, 'Zipped file ' . $this->processed_files . ' of ' . $this->total_files);
		}

		$zip->close();
	}

	private function delete_dump_folder() {
		$files = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($this->current_dump_path, FilesystemIterator::SKIP_DOTS), 
			RecursiveIteratorIterator::CHILD_FIRST
		);

		foreach ( $files as $file ) {
		    $file->isDir() ? rmdir($file) : unlink($file);
		}

		rmdir($this->current_dump_path);
	}
}
