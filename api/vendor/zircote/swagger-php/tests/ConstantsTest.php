<?php declare(strict_types=1);

/**
 * @license Apache 2.0
 */

namespace OpenApiTests;

use OpenApi\Analyser;
use OpenApi\StaticAnalyser;

class ConstantsTest extends OpenApiTestCase
{
    const URL = 'http://example.com';

    private static $counter = 0;

    public function testConstant()
    {
        self::$counter++;
        $const = 'OPENAPI_TEST_'.self::$counter;
        $this->assertFalse(defined($const));
        $this->assertOpenApiLogEntryStartsWith("[Semantical Error] Couldn't find constant ".$const);
        $this->parseComment('@OA\Contact(email='.$const.')');

        define($const, 'me@domain.org');
        $annotations = $this->parseComment('@OA\Contact(email='.$const.')');
        $this->assertSame('me@domain.org', $annotations[0]->email);
    }

    public function testFQCNConstant()
    {
        $annotations = $this->parseComment('@OA\Contact(url=OpenApiTests\ConstantsTest::URL)');
        $this->assertSame('http://example.com', $annotations[0]->url);

        $annotations = $this->parseComment('@OA\Contact(url=\OpenApiTests\ConstantsTest::URL)');
        $this->assertSame('http://example.com', $annotations[0]->url);
    }

    public function testInvalidClass()
    {
        $this->assertOpenApiLogEntryStartsWith("[Semantical Error] Couldn't find constant ConstantsTest::URL");
        $this->parseComment('@OA\Contact(url=ConstantsTest::URL)');
    }

    public function testAutoloadConstant()
    {
        if (class_exists('Zend\Validator\Timezone', false)) {
            $this->markTestSkipped();
        }
        $annotations = $this->parseComment('@OA\Contact(name=Zend\Validator\Timezone::INVALID_TIMEZONE_LOCATION)');
        $this->assertSame('invalidTimezoneLocation', $annotations[0]->name);
    }

    public function testDynamicImports()
    {
        $backup = Analyser::$whitelist;
        Analyser::$whitelist = false;
        $analyser = new StaticAnalyser();
        $analysis = $analyser->fromFile(__DIR__.'/Fixtures/Customer.php');
        // @todo Only tests that $whitelist=false doesn't trigger errors,
        // No constants are used, because by default only class constants in the whitelisted namespace are allowed and no class in OpenApi\Annotation namespace has a constant.

        // Scanning without whitelisting causes issues, to check uncomment next.
        // $analyser->fromFile(__DIR__ . '/Fixtures/ThirdPartyAnnotations.php');
        Analyser::$whitelist = $backup;
    }

    public function testDefaultImports()
    {
        $backup = Analyser::$defaultImports;
        Analyser::$defaultImports = [
            'contact' => 'OpenApi\Annotations\Contact', // use OpenApi\Annotations\Contact;
            'ctest' => 'OpenApiTests\ConstantsTesT' // use OpenApiTests\ConstantsTesT as CTest;
        ];
        $annotations = $this->parseComment('@Contact(url=CTest::URL)');
        $this->assertSame('http://example.com', $annotations[0]->url);
        Analyser::$defaultImports = $backup;
    }
}
