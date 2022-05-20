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

	public function tryDownloadZIP( AcceptanceTester $I ) {
		$I->loginAsAdmin();

		$I->amOnAdminPage( 'admin.php?page=gf_entries&id=1' );
		$download = $I->click( '//tr[contains(@class, "entry_row")]//span[@class="bulk-download"]//a' );
		$I->seeElement( '//div[@id="bulk_download"]//a[@class="button"]' );
		var_dump($download);

	}
}
