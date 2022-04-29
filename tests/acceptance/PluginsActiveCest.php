<?php

class PluginsActiveCest {
	public function tryCheckActivePlugins( AcceptanceTester $I ) {
		$I->loginAsAdmin();

		$I->amOnAdminPage( 'plugins.php' );
		$I->seeElement( '//tr[@data-slug="bulk-download-for-gravity-forms"]//span[@class="deactivate"]' );
		$I->seeElement( '//tr[@data-slug="gravityforms"]//span[@class="deactivate"]' );
		$I->seeElement( '//tr[@data-slug="gravityformscli"]//span[@class="deactivate"]' );
	}
}
