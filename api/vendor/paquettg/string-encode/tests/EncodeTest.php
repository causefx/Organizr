<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use stringEncode\Encode;

class ContentTest extends TestCase {

	public function testTo()
	{
		$encode = new Encode;
		$encode->to('ISO-8859-1');
		$this->assertEquals('ISO-8859-1', $encode->charset()['to']);
	}

	public function testFrom()
	{
		$encode = new Encode;
		$encode->from('ISO-8859-1');
		$this->assertEquals('ISO-8859-1', $encode->charset()['from']);
	}

	public function testDetect()
	{
		$encode = new Encode;
		$encode->detect('Calendrier de l\'avent façon Necta!');
		$this->assertEquals('UTF-8', $encode->charset()['from']);
	}

}
