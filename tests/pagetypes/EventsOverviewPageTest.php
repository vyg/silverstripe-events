<?php
namespace Voyage\Events\Tests\PageTypes;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\Versioned\Versioned;
use Voyage\Events\Models\RecurringException;
use Voyage\Events\Pages\EventsPage;
use Voyage\Events\Pages\EventsOverviewPage;

class EventsOverviewPageTest extends SapphireTest
{
    protected static $fixture_file = 'fixtures/EventsOverviewPageTest.yml';

    public function testShouldReturnDailyRecurringDates()
    {
        $event = $this->objFromFixture(EventsPage::class, 'daily');
        $event->copyVersionToStage(Versioned::DRAFT, Versioned::LIVE);
        $overview = $this->objFromFixture(EventsOverviewPage::class, 'daily_overview');
        $eventDates = $overview->getEventList('2018-11-01', $end = '2018-12-31');
        $this->assertEquals(5, count($eventDates));
        $this->assertEquals('2018-11-07', $eventDates[1]->StartDate);
    }

    public function testShouldReturnWeeklyRecurringDates()
    {
        $event = $this->objFromFixture(EventsPage::class, 'weekly');
        $event->copyVersionToStage(Versioned::DRAFT, Versioned::LIVE);
        $overview = $this->objFromFixture(EventsOverviewPage::class, 'weekly_overview');
        $eventDates = $overview->getEventList('2018-11-01', $end = '2018-12-31');
        $this->assertEquals(5, count($eventDates));
        $this->assertEquals('2018-11-02', $eventDates[0]->StartDate);
    }

    public function testShouldReturnMonthlyRecurringDates()
    {
        $event = $this->objFromFixture(EventsPage::class, 'monthly');
        $event->copyVersionToStage(Versioned::DRAFT, Versioned::LIVE);
        $overview = $this->objFromFixture(EventsOverviewPage::class, 'monthly_overview');
        $eventDates = $overview->getEventList('2018-11-01', $end = '2018-12-31');
        $this->assertEquals(4, count($eventDates));
        $this->assertEquals('2018-11-10', $eventDates[0]->StartDate);
    }

    public function testShouldExcludeExceptionsForDailyEvents()
    {
        $event = $this->objFromFixture(EventsPage::class, 'daily');
        $exception = $this->objFromFixture(RecurringException::class, 'fordaily1');
        $exception->EventsPageID = $event->ID;
        $exception->write();
        $exception = $this->objFromFixture(RecurringException::class, 'fordaily2');
        $exception->EventsPageID = $event->ID;
        $exception->write();
        $event->copyVersionToStage(Versioned::DRAFT, Versioned::LIVE);
        $overview = $this->objFromFixture(EventsOverviewPage::class, 'daily_overview');
        $eventDates = $overview->getEventList('2018-11-01', $end = '2018-12-31');
        $this->assertEquals(3, count($eventDates));
        $eventDateStrings = array_map(function($ev) { return $ev->StartDate; }, $eventDates->toArray());
        $this->assertNotContains('2018-11-13', $eventDateStrings);
        $this->assertNotContains('2018-11-25', $eventDateStrings);
    }

    public function testShouldExcludeExceptionsForWeeklyEvents()
    {
        $event = $this->objFromFixture(EventsPage::class, 'weekly');
        $exception = $this->objFromFixture(RecurringException::class, 'forweekly');
        $exception->EventsPageID = $event->ID;
        $exception->write();
        $event->copyVersionToStage(Versioned::DRAFT, Versioned::LIVE);
        $overview = $this->objFromFixture(EventsOverviewPage::class, 'weekly_overview');
        $eventDates = $overview->getEventList('2018-11-01', $end = '2018-12-31');
        $this->assertEquals(4, count($eventDates));
        $eventDateStrings = array_map(function($ev) { return $ev->StartDate; }, $eventDates->toArray());
        $this->assertNotContains('2018-11-16', $eventDateStrings);
    }

    public function testShouldExcludeExceptionsForMonthlyEvents()
    {
        $event = $this->objFromFixture(EventsPage::class, 'monthly');
        $exception = $this->objFromFixture(RecurringException::class, 'formonthly');
        $exception->EventsPageID = $event->ID;
        $exception->write();
        $event->copyVersionToStage(Versioned::DRAFT, Versioned::LIVE);
        $overview = $this->objFromFixture(EventsOverviewPage::class, 'monthly_overview');
        $eventDates = $overview->getEventList('2018-11-01', $end = '2018-12-31');
        $this->assertEquals(3, count($eventDates));
        $eventDateStrings = array_map(function($ev) { return $ev->StartDate; }, $eventDates->toArray());
        $this->assertNotContains('2018-12-10', $eventDateStrings);
    }
}
