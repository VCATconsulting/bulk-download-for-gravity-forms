<?php

class Step2HasDownloadLinksCest {
	public function tryToFindDownloadLinks( AcceptanceTester $I ) {
		$I->loginAsAdmin();

		$I->amOnAdminPage( 'admin.php?page=gf_entries&id=1' );
		$I->seeElement( '//tr[contains(@class, "entry_row")]//span[@class="bulk-download"]//a' );
		$I->seeElement( '//select[@id="bulk-action-selector-top"]/option[@value="gf_bulk_download"]' );

		// Follow the first link.
		$I->click( '//tr[contains(@class, "entry_row")]//a[text()="View"]' );
		$I->seeElement( '//div[@id="bulk_download"]//a[@class="button"]' );
	}

	public function tryDownloadZipWithResponse( AcceptanceTester $I ) {
		$I->loginAsAdmin();

		$I->amOnAdminPage( 'admin.php?page=gf_entries&id=1' );
		$I->click( '//tr[contains(@class, "entry_row")]//span[@class="bulk-download"]//a' );
		$I->seeFileInZipDownload('bernhard.kau@vcat.de/0e6baf5788de64013a42fae374a83dde2.jpeg');
	}
}
