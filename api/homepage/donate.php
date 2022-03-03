<?php

trait DonateHomepageItem
{
	public function donateSettingsArray($infoOnly = false)
	{
		$homepageInformation = [
			'name' => 'Donate',
			'enabled' => strpos('personal', $this->config['license']) !== false,
			'image' => 'plugins/images/tabs/donate.png',
			'category' => 'Requests',
			'settingsArray' => __FUNCTION__
		];
		if ($infoOnly) {
			return $homepageInformation;
		}
		$homepageSettings = [
			'debug' => true,

			'settings' => [
				'About' => [
					$this->settingsOption('about', 'Donations', ['about' => 'This item allows you to use Stripe to accept donations on the homepage']),
				],
				'Setup' => [
					$this->settingsOption('html', null, ['label' => 'Instructions', 'override' => 12, 'html' => '
					<div class="panel panel-default">
						<div class="panel-heading">
							<a href="https://dashboard.stripe.com//" target="_blank"><span class="label label-info m-l-5">Visit Stripe Site</span></a>
						</div>
						<div class="panel-wrapper collapse in">
							<div class="panel-body">
								<ul class="list-icons">
									<li lang="en"><i class="fa fa-caret-right text-info"></i> Create or Login if you already have an account</li>
									<li lang="en"><i class="fa fa-caret-right text-info"></i> Goto products and click [Add Product]</li>
									<li lang="en"><i class="fa fa-caret-right text-info"></i> Name the product anything you like</li>
									<li lang="en"><i class="fa fa-caret-right text-info"></i> Make sure the product has standard pricing</li>
									<li lang="en"><i class="fa fa-caret-right text-info"></i> Also make sure that the product is set to <code>One Time</code></li>
									<li lang="en"><i class="fa fa-caret-right text-info"></i> Set the pricing to the minimum price i.e. 1 USD</li>
									<li lang="en"><i class="fa fa-caret-right text-info"></i> Click <code>Save Product</code></li>
									<li lang="en"><i class="fa fa-caret-right text-info"></i> Click The Product you just created</li>
									<li lang="en"><i class="fa fa-caret-right text-info"></i> Copy <code>ID</code> value</li>
									<li lang="en"><i class="fa fa-caret-right text-info"></i> Click <code>Developers</code></li>
									<li lang="en"><i class="fa fa-caret-right text-info"></i> Click <code>API Keys</code></li>
									<li lang="en"><i class="fa fa-caret-right text-info"></i> Copy both <code>Publishable key</code> and <code>Secret key</code></li>
								</ul>
							</div>
						</div>
					</div>
					']
					),
				],
				'Enable' => [
					$this->settingsOption('enable', 'homepageDonateEnabled'),
					$this->settingsOption('auth', 'homepageDonateAuth'),
				],
				'Connection' => [
					$this->settingsOption('input', 'homepageDonatePublicToken', ['label' => 'Public Token']),
					$this->settingsOption('token', 'homepageDonateSecretToken', ['label' => 'Secret Token']),
					$this->settingsOption('input', 'homepageDonateProductID', ['label' => 'Product ID']),

				],
				'Customize' => [
					$this->settingsOption('input', 'homepageDonateCustomizeHeading', ['label' => 'Heading']),
					$this->settingsOption('code-editor', 'homepageDonateCustomizeDescription', ['label' => 'Description', 'mode' => 'html']),
					$this->settingsOption('select', 'homepageDonateMinimum',
						['label' => 'Minimum',
							'options' => [
								['name' => '1 USD', 'value' => '100'],
								['name' => '2 USD', 'value' => '200'],
								['name' => '3 USD', 'value' => '300'],
								['name' => '4 USD', 'value' => '400'],
								['name' => '5 USD', 'value' => '500'],
								['name' => '10 USD', 'value' => '1000'],
								['name' => '20 USD', 'value' => '2000'],
								['name' => '25 USD', 'value' => '2500'],
								['name' => '50 USD', 'value' => '5000'],
								['name' => '75 USD', 'value' => '7500'],
								['name' => '100 USD', 'value' => '10000'],
							]
						]),
				]
			]
		];
		return array_merge($homepageInformation, $homepageSettings);
	}


	public function donateHomepagePermissions($key = null)
	{
		$permissions = [
			'main' => [
				'enabled' => [
					'homepageDonateEnabled'
				],
				'auth' => [
					'homepageDonateAuth'
				],
				'not_empty' => [
					'homepageDonateMinimum',
					'homepageDonatePublicToken',
					'homepageDonateSecretToken',
					'homepageDonateProductID',
				]
			]
		];
		return $this->homepageCheckKeyPermissions($key, $permissions);
	}

	public function homepageDonateCreateSession($amount = null)
	{
		$amount = $amount ? $amount * 100 : $this->config['homepageDonateMinimum'];
		if ($this->config['homepageDonatePublicToken'] == '' || $this->config['homepageDonateSecretToken'] == '' || $this->config['homepageDonateProductID'] == '') {
			$this->setResponse(409, 'Donation Tokens are not setup');
			return false;
		}
		try {
			$stripe = new \Stripe\StripeClient(
				trim($this->config['homepageDonateSecretToken'])
			);
			$sessionInfo = [
				'payment_method_types' => ['card'],
				'line_items' => [[
					'price_data' => [
						'product' => $this->config['homepageDonateProductID'],
						'unit_amount' => $amount,
						'currency' => 'usd',
					],
					'quantity' => 1,
				]],
				'mode' => 'payment',
				'success_url' => $this->getServerPath() . 'api/v2/homepage/donate/success',
				'cancel_url' => $this->getServerPath() . 'api/v2/homepage/donate/error',
			];
			if ($this->user['email'] && stripos($this->user['email'], 'placeholder') == false) {
				$sessionInfo = array_merge($sessionInfo, ['customer_email' => $this->user['email']]);
			}
			$session = $stripe->checkout->sessions->create($sessionInfo);
			header('HTTP/1.1 303 See Other');
			header('Location: ' . $session->url);
		} catch (\Stripe\Exception\ApiErrorException $e) {
			die($this->showHTML('Error', $e->getMessage()));
		}
	}

	public function homepageOrderDonate()
	{
		if ($this->homepageItemPermissions($this->donateHomepagePermissions('main'))) {
			$minimum = $this->config['homepageDonateMinimum'] / 100;
			return '
			<script>
				$(document).on("keyup", "#custom-donation-amount", function () {
					$("#homepage-donation-form").attr("action", "api/v2/homepage/donate?amount=" + $(this).val());
				});
			</script>
				<div id="' . __FUNCTION__ . '">
					<div class="panel panel-primary" style="position: static; zoom: 1;">
						<div class="panel-heading"> ' . $this->config['homepageDonateCustomizeHeading'] . '</div>
						<div class="panel-wrapper collapse in" aria-expanded="true">
							<div class="panel-body">
								<p>' . $this->config['homepageDonateCustomizeDescription'] . '</p>
								<script src="https://polyfill.io/v3/polyfill.min.js?version=3.52.1&features=fetch"></script>
								<script src="https://js.stripe.com/v3/"></script>
								<form id="homepage-donation-form" action="api/v2/homepage/donate?amount=' . $minimum . '" method="POST" target="_blank">
									<div class="input-group m-b-30">
										<span class="input-group-addon">$</span>
										<input type="number" class="form-control" name="amount" id="custom-donation-amount" placeholder="' . $minimum . '" min="' . $minimum . '"/>
										<span class="input-group-btn"> 
											<button class="btn btn-info" type="submit" id="checkout-button" lang="en">Donate</button> 
										</span>
									</div>
								</form>
							</div>
						</div>
					</div>
				</div>
				';
		}
	}
}