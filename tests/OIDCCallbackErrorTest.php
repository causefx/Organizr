<?php
/**
 * Test for outputOIDCCallbackError function with Organizr theming & branding
 */

class MockOrganizrOIDC {
    public $config = [
        'title' => 'AciDFliX',
        'favIcon' => '<link rel="shortcut icon" href="https://organizr.local/favicon.ico">'
    ];
    public $root = '/tmp';

    public function getServerPath() {
        return 'https://organizr.local/';
    }

    public function favIcons($rootPath = '') {
        return $this->config['favIcon'];
    }

    public function outputOIDCCallbackError($error) {
        $redirectUrl = $this->getServerPath();
        if (empty($redirectUrl) || $redirectUrl === 'http://' || $redirectUrl === 'https://') {
            $redirectUrl = '/';
        }
        $appTitle = !empty($this->config['title']) ? $this->config['title'] : 'Organizr';
        $isStateError = (stripos((string)$error, 'state') !== false);
        $heading = $isStateError ? 'Login Session Expired' : 'Login Failed';
        $userMessage = $isStateError
            ? 'Your login session has expired, was cancelled, or you may have switched browsers (for example, clicking a password reset link in an email client). Don\'t worry, any credential or password updates were saved.'
            : htmlspecialchars((string)$error);

        $mvpCssPath = ($this->root ?? dirname(__DIR__, 2)) . DIRECTORY_SEPARATOR . 'css' . DIRECTORY_SEPARATOR . 'mvp.css';
        $cssTag = file_exists($mvpCssPath)
            ? '<style>' . file_get_contents($mvpCssPath) . '</style>'
            : '<link rel="stylesheet" href="' . htmlspecialchars(rtrim($redirectUrl, '/') . '/css/mvp.css') . '">';

        $favIconHtml = method_exists($this, 'favIcons') ? $this->favIcons($redirectUrl) : '';

        ob_start();
        echo '<!DOCTYPE html>
<html lang="en">
<head>
	' . $cssTag . '
	' . $favIconHtml . '
	<meta charset="utf-8">
	<meta name="description" content="' . htmlspecialchars($heading . ' - ' . $appTitle) . '">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>' . htmlspecialchars($heading . ' - ' . $appTitle) . '</title>
	<style>
		body {
			display: flex;
			align-items: center;
			justify-content: center;
			min-height: 100vh;
			margin: 0;
			padding: 1rem;
			box-sizing: border-box;
		}
		main {
			width: 100%;
			max-width: 520px;
			margin: 0 auto;
			padding: 0;
		}
		aside {
			text-align: center;
			border-radius: 8px;
			padding: 2rem;
			box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
		}
		aside h2 {
			margin-top: 0;
			font-size: 1.5rem;
		}
		aside h3 {
			margin-top: 0.5rem;
			font-size: 1.25rem;
		}
		aside p {
			margin: 1rem 0;
			line-height: 1.6;
		}
		aside a b {
			display: inline-block;
			padding: 0.75rem 1.5rem;
			border-radius: 5px;
			text-decoration: none;
		}
		details {
			margin-top: 1.5rem;
			text-align: left;
			background: var(--color-accent);
			padding: 0.5rem 1rem;
			border-radius: 5px;
		}
		details summary {
			cursor: pointer;
			font-weight: bold;
		}
	</style>
</head>
<body>
	<main>
		<section>
			<aside>
				<h2>' . htmlspecialchars($appTitle) . '</h2>
				<h3>' . htmlspecialchars($heading) . '</h3>
				<p>' . $userMessage . '</p>
				<p><a href="' . htmlspecialchars($redirectUrl) . '"><b>Return to Login Page</b></a></p>
				' . ($isStateError ? '<details><summary><small>Technical Details</small></summary><p><small><code>' . htmlspecialchars((string)$error) . '</code></small></p></details>' : '') . '
			</aside>
		</section>
	</main>
</body>
</html>';
        return ob_get_clean();
    }
}

// Run assertions
$test = new MockOrganizrOIDC();

// Test 1: State error with branding
$htmlState = $test->outputOIDCCallbackError('Invalid or expired state parameter');
assert(strpos($htmlState, '<title>Login Session Expired - AciDFliX</title>') !== false, 'Test 1 Failed: Title should include heading and appTitle');
assert(strpos($htmlState, '<h2>AciDFliX</h2>') !== false, 'Test 1 Failed: Card should display app title');
assert(strpos($htmlState, 'Login Session Expired') !== false, 'Test 1 Failed: Heading should indicate session expired');
assert(strpos($htmlState, 'switched browsers') !== false, 'Test 1 Failed: Should explain browser switch');
assert(strpos($htmlState, 'https://organizr.local/') !== false, 'Test 1 Failed: Should include redirect URL');
assert(strpos($htmlState, 'Return to Login Page') !== false, 'Test 1 Failed: Should have return button');
assert(strpos($htmlState, 'Close this window') === false, 'Test 1 Failed: Should not have non-functional close window link');
assert(strpos($htmlState, 'favicon.ico') !== false, 'Test 1 Failed: Should include favicon');
assert(strpos($htmlState, 'min-height: 100vh') !== false, 'Test 1 Failed: Should have centered card styling');
echo "PASS: Test 1 (State error with Organizr branding & theming)\n";

// Test 2: Generic error with fallback title
$testGeneric = new MockOrganizrOIDC();
$testGeneric->config['title'] = '';
$htmlGeneric = $testGeneric->outputOIDCCallbackError('Access denied by identity provider');
assert(strpos($htmlGeneric, '<title>Login Failed - Organizr</title>') !== false, 'Test 2 Failed: Should fallback to Organizr in title');
assert(strpos($htmlGeneric, '<h2>Organizr</h2>') !== false, 'Test 2 Failed: Card should fallback to Organizr');
assert(strpos($htmlGeneric, 'Access denied by identity provider') !== false, 'Test 2 Failed: Should show generic error');
echo "PASS: Test 2 (Generic error with default Organizr branding)\n";

// Test 3: XSS protection in title and error
$testXSS = new MockOrganizrOIDC();
$testXSS->config['title'] = '<script>alert("xss")</script>';
$htmlXSS = $testXSS->outputOIDCCallbackError('<img src=x onerror=alert(1)>');
assert(strpos($htmlXSS, '<script>') === false, 'Test 3 Failed: Title must be escaped');
assert(strpos($htmlXSS, '&lt;script&gt;') !== false, 'Test 3 Failed: Title should be HTML entities');
assert(strpos($htmlXSS, '<img src=x') === false, 'Test 3 Failed: Error must be escaped');
assert(strpos($htmlXSS, '&lt;img src=x') !== false, 'Test 3 Failed: Error should be HTML entities');
echo "PASS: Test 3 (XSS protection for all injected values)\n";

echo "ALL TESTS PASSED SUCCESSFULLY!\n";
