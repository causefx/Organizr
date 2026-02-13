<?php declare(strict_types=1);

namespace OpenApi\Tests\Fixtures\StaticAnalyser;

interface foo
{
}

$o = new class {
    public function foo()
    {
    }
};

$o = new class extends \stdClass {
};

$o = new class implements foo {
};

$o = new class() {
    public function foo()
    {
    }
};

$o = new class() extends \stdClass {
};

$o = new class() implements foo {
};
