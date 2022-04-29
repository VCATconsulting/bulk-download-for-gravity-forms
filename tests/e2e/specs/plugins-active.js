// Load utilities from the e2e-test-utils package.
import { visitAdminPage } from '@wordpress/e2e-test-utils';

// Name of the test suite.
describe( 'Plugins activated', () => {

	// Flow being tested.
	// Ideally each flow is independent and can be run separately.
	it( 'Should load properly', async () => {
		// Navigate the admin and performs tasks
		// Use Puppeteer APIs to interacte with mouse, keyboard...
		await visitAdminPage( '/plugins.php' );

		// Assertions
		// Check if all plugins are activated
		const bdfgfNodes = await page.$x(
			'//tr[@data-slug="bulk-download-for-gravity-forms"]//span[@class="deactivate"]'
		);
		expect( bdfgfNodes.length ).not.toEqual( 0 );
		const gravityformsNodes = await page.$x(
			'//tr[@data-slug="gravityforms"]//span[@class="deactivate"]'
		);
		expect( gravityformsNodes.length ).not.toEqual( 0 );
		const gravityformsCliNodes = await page.$x(
			'//tr[@data-slug="gravityformscli"]//span[@class="deactivate"]'
		);
		expect( gravityformsCliNodes.length ).not.toEqual( 0 );
	} );
} );
