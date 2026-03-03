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
	private $dryRun = false;

	public function __construct($backup_folder) {
		$now = new DateTime;
		$this->_BACKUP_FOLDER = rtrim($backup_folder, '/');
		$this->_CURRENT_DATE_TIME = $now->format('d-m-Y_H-i');
	}

	public function run($database, $debug = false, $dryRun = false) {
		$this->debug = ($debug === true);
		$this->dryRun = ($dryRun === true);
		try {
			$this->current_dump_path = $this->_BACKUP_FOLDER . "/" . $database . "_" . $this->_CURRENT_DATE_TIME;
			$this->database = $database;

			if ($this->dryRun) {
				$this->echo_if_debug("<p><strong>[DRY RUN] Would backup '" . $database . "' to '" . $this->current_dump_path . "'</strong></p>");
			} else {
				$this->echo_if_debug("<p><strong>Backing up '" . $database . "' to '" . $this->current_dump_path . "'</strong></p>");
			}

			$this->echo_if_debug("<ol>");
			if ($this->dryRun) {
				$this->echo_if_debug("<li>[DRY RUN] Previewing mongodump...</li>");
			} else {
				$this->echo_if_debug("<li>Executing mongodump...</li>");
			}
			$this->mongodump();

			if ($this->dryRun) {
				$this->echo_if_debug("<li>[DRY RUN] Previewing zip creation...</li>");
			} else {
				$this->echo_if_debug("<li>Zipping files...</li>");
			}
			$this->zip_files();

			if ($this->dryRun) {
				$this->echo_if_debug("<li>[DRY RUN] Previewing cleanup...</li>");
			} else {
				$this->echo_if_debug("<li>Deleting dump folder...</li>");
			}
			$this->delete_dump_folder();

			if ($this->dryRun) {
				$this->echo_if_debug("<li>[DRY RUN] Complete! (no changes made)</li>");
			} else {
				$this->echo_if_debug("<li>Complete!</li>");
			}
			$this->echo_if_debug("</ol>");
			return;
		}
		catch (Exception $ex) {
			return false;
		}
	}

	private function echo_if_debug($string) {
		if ($this->debug) {
			echo $string;
		}
	}

	private function mongodump() {
		$command = "mongodump --db " . $this->database . " --out " . $this->current_dump_path;
		if ($this->dryRun) {
			$this->echo_if_debug("<ul><li>[DRY RUN] Would execute: " . $command . "</li></ul>");
		} else {
			$results = shell_exec($command);
			$this->echo_if_debug("<ul><li>" . $command . "</li><li>".$results."</li></ul>");
		}
	}

	private function zip_files() {
		$database_dump_folder = $this->current_dump_path . "/" . $this->database;
		$zip_path = $this->current_dump_path . '.zip';

		if ($this->dryRun) {
			// Count files that would be included
			$file_count = 0;
			if (is_dir($database_dump_folder)) {
				$files = new RecursiveIteratorIterator(
					new RecursiveDirectoryIterator($database_dump_folder),
					RecursiveIteratorIterator::LEAVES_ONLY
				);
				foreach ($files as $file) {
					$file_count++;
				}
			}
			$this->echo_if_debug("<li>[DRY RUN] Would create zip with " . $file_count . " files at: " . $zip_path . "</li>");
		} else {
			// Initialize archive object
			$zip = new ZipArchive;
			$zip->open($zip_path, ZipArchive::CREATE);

			// Create recursive directory iterator
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
			}

			$zip->close();
		}
	}

	private function delete_dump_folder() {
		if ($this->dryRun) {
			$this->echo_if_debug("<li>[DRY RUN] Would delete dump folder: " . $this->current_dump_path . "</li>");
		} else {
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
}
