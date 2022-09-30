// Load utilities from the e2e-test-utils package.
import { visitAdminPage } from '@wordpress/e2e-test-utils';

// Name of the test suite.
describe( 'Find download buttons', () => {

	// Flow being tested.
	// Ideally each flow is independent and can be run separately.
	it( 'Should load properly', async () => {
		// Navigate the admin and performs tasks
		// Use Puppeteer APIs to interacte with mouse, keyboard...
		await visitAdminPage( '/admin.php', 'page=gf_entries&id=1' );

		const bulkDownloadLink = await page.$x(
			'//tr[@id="entry_row_1"]//span[@class="bulk-download"]//a'
		);
		expect( bulkDownloadLink.length ).not.toEqual( 0 );

		const bulkDownloadAction = await page.$x(
			'//select[@id="bulk-action-selector-top"]/option[@value="gf_bulk_download"]'
		);
		expect( bulkDownloadAction.length ).not.toEqual( 0 );

		// Find download button on single entry.
		const firstEntryLink = await page.$eval( '.entry_row .row-actions .edit a', ( el ) => el.href );
		if ( firstEntryLink ) {
			const firstEntryParams = firstEntryLink.split('?')[1];
			await visitAdminPage( '/admin.php', firstEntryParams );

			const singleEntryBulkDownloadLink = await page.$x(
				'//div[@id="bulk_download"]//a[@class="button"]'
			);
			expect( singleEntryBulkDownloadLink.length ).not.toEqual( 0 );
		} else {
			throw new Exception( 'Link not found' );
		}
	} );
} );
