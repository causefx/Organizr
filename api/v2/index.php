<?php
/**
 * @OA\Info(title="Organizr API", description="Organizr - Accept no others", version="2.0")
 * @OA\Server(url=API_HOST,description="This Organizr Install")
 * @OA\Server(url="https://demo.organizr.app",description="Organizr Demo API")
 * @OA\Server(url="{schema}://{hostPath}",description="Custom Organizr API",
 *      @OA\ServerVariable(
 *          serverVariable="schema",
 *          enum={"https", "http"},
 *          default="http"
 *      ),
 *     @OA\ServerVariable(
 *          serverVariable="hostPath",
 *          description="Your Organizr URL",
 *          default="localhost"
 *      )
 * )
 * @OA\SecurityScheme(
 *   securityScheme="api_key",
 *   type="apiKey",
 *   in="header",
 *   name="Token"
 * )
 */
require_once '../functions.php';

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\StreamInterface;
use Slim\Factory\AppFactory;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

class JsonBodyParserMiddleware implements MiddlewareInterface
{
	public function process(Request $request, RequestHandler $handler): Response
	{
		$contentType = $request->getHeaderLine('Content-Type');
		if (strstr($contentType, 'application/json')) {
			$contents = json_decode(file_get_contents('php://input'), true);
			if (json_last_error() === JSON_ERROR_NONE) {
				$request = $request->withParsedBody($contents);
			}
		}
		return $handler->handle($request);
	}
}

class Lowercase implements MiddlewareInterface
{
	/**
	 * @var ResponseFactoryInterface
	 */
	private $responseFactory;
	
	/*
	 * Whether returns a 301 response to the new path.
	 */
	public function redirect(ResponseFactoryInterface $responseFactory): self
	{
		$this->responseFactory = $responseFactory;
		return $this;
	}
	
	/*
	 * Process a request and return a response.
	 */
	public function process(Request $request, RequestHandler $handler): Response
	{
		$uri = $request->getUri();
		$path = strtolower($uri->getPath());
		if ($this->responseFactory && ($uri->getPath() !== $path)) {
			return $this->responseFactory->createResponse(301)
				->withHeader('Location', (string)$uri->withPath($path));
		}
		return $handler->handle($request->withUri($uri->withPath($path)));
	}
}

$GLOBALS['api'] = array(
	'response' => array(
		'result' => 'success',
		'message' => null,
		'data' => null
	)
);
$GLOBALS['bypass'] = array(
	'/api/v2/upgrade',
	'/api/v2/update',
	'/api/v2/force',
	'/api/v2/auth',
	'/api/v2/wizard',
	'/api/v2/login',
	'/api/v2/wizard/path',
	'/api/v2/login/api',
	'/api/v2/plex/register'
);
$GLOBALS['responseCode'] = 200;
function jsonE($json)
{
	return safe_json_encode($json, JSON_HEX_QUOT | JSON_HEX_TAG | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
}

function getBasePath()
{
	$uri = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
	if (stripos($uri, 'api/v2') !== false) {
		return $uri;
	} else {
		return '';
	}
}

function overWriteURI()
{
	$uri = $_SERVER['REQUEST_URI'];
	$query = $_SERVER['QUERY_STRING'];
	if (stripos($uri, 'api/v2') === false && stripos($query, 'group=') !== false) {
		$group = explode('group=', $query);
		$_SERVER['REQUEST_URI'] = 'auth-' . $group[1];
	}
}

overWriteURI();
// Instantiate App
$app = AppFactory::create();
// Add error middleware
$app->addRoutingMiddleware();
$app->addErrorMiddleware(true, true, true);
$app->setBasePath(getBasePath());
$app->add(function ($request, $handler) {
	// add the organizr to your request as [READ-ONLY]
	$Organizr = new Organizr();
	$request = $request->withAttribute('Organizr', $Organizr);
	return $handler->handle($request);
});
//$app->add(new Lowercase());
/*
 * Include all routes
 */
foreach (glob(__DIR__ . DIRECTORY_SEPARATOR . 'routes' . DIRECTORY_SEPARATOR . '*.php') as $filename) {
	require_once $filename;
}
/*
 * Include all Plugin routes
 */
foreach (glob(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . "*.php") as $filename) {
	require_once $filename;
}
/*
 *
 *  This is the last defined api endpoint to catch all undefined endpoints
 *
 */
$app->any('{route:.*}', function ($request, $response) {
	$GLOBALS['api']['response']['data'] = array(
		'endpoint' => $request->getUri()->getPath(),
		'method' => $request->getMethod(),
	);
	$GLOBALS['api']['response']['result'] = 'error';
	$GLOBALS['api']['response']['message'] = 'Endpoint Not Found or Defined';
	$GLOBALS['responseCode'] = 404;
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});
$app->run();