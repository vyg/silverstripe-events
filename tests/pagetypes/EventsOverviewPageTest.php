<?php
namespace Voyage\Events\Tests\PageTypes;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\Versioned\Versioned;
use Voyage\Events\Pages\EventsPage;
use Voyage\Events\Pages\EventsOverviewPage;

class EventsOverviewPageTest extends SapphireTest
{
    protected static $fixture_file = 'fixtures/EventsOverviewPageTest.yml';

    public function testShouldReturnWeeklyRecurringDates()
    {
        $event = $this->objFromFixture(EventsPage::class, 'weekly');
        $event->copyVersionToStage(Versioned::DRAFT, Versioned::LIVE);
        $overview = $this->objFromFixture(EventsOverviewPage::class, 'weekly_overview');
        $eventDates = $overview->getEventList('2018-11-01', $end = '2018-12-31');
        $this->assertEquals(5, count($eventDates));
    }

    public function testShouldReturnMonthlyRecurringDates()
    {
        $event = $this->objFromFixture(EventsPage::class, 'monthly');
        $event->copyVersionToStage(Versioned::DRAFT, Versioned::LIVE);
        $overview = $this->objFromFixture(EventsOverviewPage::class, 'monthly_overview');
        $eventDates = $overview->getEventList('2018-11-01', $end = '2018-12-31');
        $this->assertEquals(4, count($eventDates));
    }
}
