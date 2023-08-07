const { test, expect } = require( '@wordpress/e2e-test-utils-playwright' );


test.describe( 'Check, if all mandatory plugins are active', () => {
	test('Plugins activated', async ({admin, page}) => {
		await admin.visitAdminPage( 'plugins.php?plugin_status=active' );

		// Check if all plugins are activated
		await expect(
			page.locator( '[id="deactivate-bulk-download-for-gravity-forms"]' )
		).toBeVisible();
		await expect(
			page.locator( '[id="deactivate-gravityforms"]' )
		).toBeVisible();
		await expect(
			page.locator( '[id="deactivate-gravityformscli"]' )
		).toBeVisible();
	});
});
