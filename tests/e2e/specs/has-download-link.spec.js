const { test, expect } = require( '@wordpress/e2e-test-utils-playwright' );

test.describe( 'Find download buttons', () => {
	test('Has download links', async ({admin, page}) => {
		await admin.visitAdminPage( '/admin.php', 'page=gf_entries&id=1' );

		// Navigate the admin and performs tasks
		await expect(
			page.locator( '.row-actions .bulk-download' )
		).toBeVisible();
		await expect(
			page.locator( '#bulk-action-selector-top [value="gf_bulk_download"]' )
		).toHaveAttribute('value', 'gf_bulk_download');

		// Find download button on single entry.
		const firstEntryLink = await page.locator('.entry_row .row-actions a[href*="view=entry"]').getAttribute('href');
		const firstEntryParams = firstEntryLink.split('?')[1];
		await admin.visitAdminPage( '/admin.php', firstEntryParams );
		await expect(
			await page.locator('#bulk_download .button')
		).toBeVisible()
	});
});
