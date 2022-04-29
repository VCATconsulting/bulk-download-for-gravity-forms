<?php

class HasDownloadLinksCest {
	public function tryToFindDownloadLinks( AcceptanceTester $I ) {
		$I->loginAsAdmin();

		$I->amOnAdminPage( 'admin.php?page=gf_entries&id=1' );
		$I->seeElement( '//tr[@id="entry_row_1"]//span[@class="bulk-download"]//a' );
		$I->seeElement( '//select[@id="bulk-action-selector-top"]/option[@value="gf_bulk_download"]' );

		$I->amOnAdminPage( 'admin.php?page=gf_entries&view=entry&id=1&lid=1' );
		$I->seeElement( '//div[@id="bulk_download"]//a[@class="button"]' );
	}
}
