<?php

namespace Helper;

// here you can define custom actions
// all public methods declared in helper class will be available in $I

class Acceptance extends \Codeception\Module {
	public function seeFileInZipDownload( $file_name ) {
		$response = $this->getModule('WPBrowser')->grabPageSource();
		// Create a temporary file for the ZIP archive content.
		$temp = tempnam( sys_get_temp_dir(), 'bdfgf-' ) . '.zip';
		file_put_contents( $temp, $response );
		// Open the ZIP archive.
		$zip = new \ZipArchive();
		$zip->open( $temp );
		// Search for files in that ZIP archive.
		$file = $zip->statName($file_name);
		$this->assertEquals($file['name'], $file_name);
		$zip->close();
		// Delete the temp file.
		unlink( $temp );
	}
}
