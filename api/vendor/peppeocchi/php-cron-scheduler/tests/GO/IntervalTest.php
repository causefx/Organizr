<?php namespace GO\Job\Tests;

use GO\Job;
use PHPUnit\Framework\TestCase;

class IntervalTest extends TestCase
{
    public function testShouldRunEveryMinute()
    {
        $job = new Job('ls');

        $this->assertTrue($job->everyMinute()->isDue(\DateTime::createFromFormat('H:i', '00:00')));
    }

    public function testShouldRunHourly()
    {
        $job = new Job('ls');

        // Default run is at minute 00 every hour
        $this->assertTrue($job->hourly()->isDue(\DateTime::createFromFormat('H:i', '10:00')));
        $this->assertFalse($job->hourly()->isDue(\DateTime::createFromFormat('H:i', '10:01')));
        $this->assertTrue($job->hourly()->isDue(\DateTime::createFromFormat('H:i', '11:00')));
    }

    public function testShouldRunHourlyWithCustomInput()
    {
        $job = new Job('ls');

        $this->assertTrue($job->hourly(19)->isDue(\DateTime::createFromFormat('H:i', '10:19')));
        $this->assertTrue($job->hourly('07')->isDue(\DateTime::createFromFormat('H:i', '10:07')));
        $this->assertFalse($job->hourly(19)->isDue(\DateTime::createFromFormat('H:i', '10:01')));
        $this->assertTrue($job->hourly(19)->isDue(\DateTime::createFromFormat('H:i', '11:19')));
    }

    public function testShouldThrowExceptionWithInvalidHourlyMinuteInput()
    {
        $this->expectException(\InvalidArgumentException::class);

        $job = new Job('ls');
        $job->hourly('abc');
    }

    public function testShouldRunDaily()
    {
        $job = new Job('ls');

        // Default run is at 00:00 every day
        $this->assertTrue($job->daily()->isDue(\DateTime::createFromFormat('H:i', '00:00')));
    }

    public function testShouldRunDailyWithCustomInput()
    {
        $job = new Job('ls');

        $this->assertTrue($job->daily(19)->isDue(\DateTime::createFromFormat('H:i', '19:00')));
        $this->assertTrue($job->daily(19, 53)->isDue(\DateTime::createFromFormat('H:i', '19:53')));
        $this->assertFalse($job->daily(19)->isDue(\DateTime::createFromFormat('H:i', '18:00')));
        $this->assertFalse($job->daily(19, 53)->isDue(\DateTime::createFromFormat('H:i', '19:52')));

        // A string is also acceptable
        $this->assertTrue($job->daily('19')->isDue(\DateTime::createFromFormat('H:i', '19:00')));
        $this->assertTrue($job->daily('19:53')->isDue(\DateTime::createFromFormat('H:i', '19:53')));
    }

    public function testShouldThrowExceptionWithInvalidDailyHourInput()
    {
        $this->expectException(\InvalidArgumentException::class);

        $job = new Job('ls');
        $job->daily('abc');
    }

    public function testShouldThrowExceptionWithInvalidDailyMinuteInput()
    {
        $this->expectException(\InvalidArgumentException::class);

        $job = new Job('ls');
        $job->daily(2, 'abc');
    }

    public function testShouldRunWeekly()
    {
        $job = new Job('ls');

        // Default run is every Sunday at 00:00
        $this->assertTrue($job->weekly()->isDue(
            new \DateTime('Sunday'))
        );

        $this->assertFalse($job->weekly()->isDue(
            new \DateTime('Tuesday'))
        );
    }

    public function testShouldRunWeeklyOnCustomDay()
    {
        $job = new Job('ls');

        $this->assertTrue($job->weekly(6)->isDue(
            new \DateTime('Saturday'))
        );

        // Testing also the helpers to run weekly on custom day
        $this->assertTrue($job->monday()->isDue(
            new \DateTime('Monday'))
        );
        $this->assertFalse($job->monday()->isDue(
            new \DateTime('Saturday'))
        );

        $this->assertTrue($job->tuesday()->isDue(
            new \DateTime('Tuesday'))
        );
        $this->assertTrue($job->wednesday()->isDue(
            new \DateTime('Wednesday'))
        );
        $this->assertTrue($job->thursday()->isDue(
            new \DateTime('Thursday'))
        );
        $this->assertTrue($job->friday()->isDue(
            new \DateTime('Friday'))
        );
        $this->assertTrue($job->saturday()->isDue(
            new \DateTime('Saturday'))
        );
        $this->assertTrue($job->sunday()->isDue(
            new \DateTime('Sunday'))
        );
    }

    public function testShouldRunWeeklyOnCustomDayAndTime()
    {
        $job = new Job('ls');

        $date1 = new \DateTime('Saturday 03:45');
        $date2 = new \DateTime('Saturday 03:46');

        $this->assertTrue($job->weekly(6, 3, 45)->isDue($date1));
        $this->assertTrue($job->weekly(6, '03:45')->isDue($date1));
        $this->assertFalse($job->weekly(6, '03:45')->isDue($date2));
    }

    public function testShouldRunMonthly()
    {
        $job = new Job('ls');

        // Default run is every 1st of the month at 00:00
        $this->assertTrue($job->monthly()->isDue(
            new \DateTime('01 January'))
        );
        $this->assertTrue($job->monthly()->isDue(
            new \DateTime('01 December'))
        );

        $this->assertFalse($job->monthly()->isDue(
            new \DateTime('02 January'))
        );
    }

    public function testShouldRunMonthlyOnCustomMonth()
    {
        $job = new Job('ls');

        $this->assertTrue($job->monthly()->isDue(
            new \DateTime('01 January'))
        );

        // Testing also the helpers to run weekly on custom day
        $this->assertTrue($job->january()->isDue(
            new \DateTime('01 January'))
        );
        $this->assertFalse($job->january()->isDue(
            new \DateTime('01 February'))
        );

        $this->assertTrue($job->february()->isDue(
            new \DateTime('01 February'))
        );

        $this->assertTrue($job->march()->isDue(
            new \DateTime('01 March'))
        );
        $this->assertTrue($job->april()->isDue(
            new \DateTime('01 April'))
        );
        $this->assertTrue($job->may()->isDue(
            new \DateTime('01 May'))
        );
        $this->assertTrue($job->june()->isDue(
            new \DateTime('01 June'))
        );
        $this->assertTrue($job->july()->isDue(
            new \DateTime('01 July'))
        );
        $this->assertTrue($job->august()->isDue(
            new \DateTime('01 August'))
        );
        $this->assertTrue($job->september()->isDue(
            new \DateTime('01 September'))
        );
        $this->assertTrue($job->october()->isDue(
            new \DateTime('01 October'))
        );
        $this->assertTrue($job->november()->isDue(
            new \DateTime('01 November'))
        );
        $this->assertTrue($job->december()->isDue(
            new \DateTime('01 December'))
        );
    }

    public function testShouldRunMonthlyOnCustomDayAndTime()
    {
        $job = new Job('ls');

        $date1 = new \DateTime('May 15 12:21');
        $date2 = new \DateTime('February 15 12:21');
        $date3 = new \DateTime('February 16 12:21');

        $this->assertTrue($job->monthly(5, 15, 12, 21)->isDue($date1));
        $this->assertTrue($job->monthly(5, 15, '12:21')->isDue($date1));
        $this->assertFalse($job->monthly(5, 15, '12:21')->isDue($date2));
        // Every 15th at 12:21
        $this->assertTrue($job->monthly(null, 15, '12:21')->isDue($date1));
        $this->assertTrue($job->monthly(null, 15, '12:21')->isDue($date2));
        $this->assertFalse($job->monthly(null, 15, '12:21')->isDue($date3));
    }

    public function testShouldRunAtSpecificDate()
    {
        $job = new Job('ls');

        $date = '2018-01-01';

        // As instance of datetime
        $this->assertTrue($job->date(new \DateTime($date))->isDue(new \DateTime($date)));
        // As date string
        $this->assertTrue($job->date($date)->isDue(new \DateTime($date)));
        // Fail for different day
        $this->assertFalse($job->date($date)->isDue(new \DateTime('2018-01-02')));
    }

    public function testShouldRunAtSpecificDateTime()
    {
        $job = new Job('ls');

        $date = '2018-01-01 12:20';

        // As instance of datetime
        $this->assertTrue($job->date(new \DateTime($date))->isDue(new \DateTime($date)));
        // As date string
        $this->assertTrue($job->date($date)->isDue(new \DateTime($date)));
        // Fail for different time
        $this->assertFalse($job->date($date)->isDue(new \DateTime('2018-01-01 12:21')));
    }

    public function testShouldFailIfDifferentYear()
    {
        $job = new Job('ls');

        // As instance of datetime
        $this->assertFalse($job->date('2018-01-01')->isDue(new \DateTime('2019-01-01')));
    }

    public function testEveryMinuteWithParameter()
    {
        $job = new Job('ls');

        // Job should run at 10:00, 10:05, 10:10 etc., but not at 10:02
        $this->assertTrue($job->everyMinute(5)->isDue(\DateTime::createFromFormat('H:i', '10:00')));
        $this->assertFalse($job->everyMinute(5)->isDue(\DateTime::createFromFormat('H:i', '10:02')));
        $this->assertTrue($job->everyMinute(5)->isDue(\DateTime::createFromFormat('H:i', '10:05')));
    }
}
