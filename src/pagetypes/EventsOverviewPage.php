<?php

namespace Voyage\Events\Pages;

use Page;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\ArrayList;
use SilverStripe\Forms\TextField;
use Voyage\Events\Helpers\sfDate;
use Voyage\Events\Pages\EventsPage;
use SilverStripe\Forms\NumericField;
use Voyage\Events\Models\EventDateTime;

class EventsOverviewPage extends Page
{
    /**
     * @var string
     */
    private static $table_name = 'EventsOverviewPage';

    private static $reccurring_event_index = 0;

    private static $db = [
        'DefaultFutureMonths' => 'Int',
        'DefaultHeader'       => 'Varchar(150)',
    ];

    private static $defaults = [
        'DefaultFutureMonths' => '3',
        'DefaultHeader'       => 'Upcoming Events',
    ];

    /**
     * @var array
     */
    private static $allowed_children = [
        EventsPage::class
    ];

    /**
     * @return fieldList
     */
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $configuration = _t('EventsOverviewPage.CONFIGURATION', 'Configuration');
        $fields->addFieldsToTab("Root.$configuration", [
            NumericField::create('DefaultFutureMonths', _t('EventsOverviewPage.DEFAULTFUTUREMONTHS', 'Number number of future months to show in default view'))->addExtraClass('defaultFutureMonths'),
            TextField::create('DefaultHeader', _t('EventsOverviewPage.DEFAULTHEADER', 'Default header (displays when no date range has been selected)')),
        ]);

        return $fields;
    }

    /**
     * @return ArrayList
     */
    public function getEventList($start, $end = null, $keyword = null, $regions = [], $eventTypes = [])
    {
        $eventList = new ArrayList();
        $events = $this->getStandardEvents($start, $end, $keyword, $regions, $eventTypes);
        $eventList->merge($events);

        if ($recurring = $this->getRecurringEvents()) {
            $eventList = $this->addRecurringEvents($start, $end, $recurring, $eventList, $keyword, $regions, $eventTypes);
        }

        $eventList = $eventList->sort([
            "StartDate" => "ASC",
            "StartTime" => "ASC"
        ]);

        return $eventList;
    }

    /**
     * @return DataList
     */
    protected function getStandardEvents($start, $end, $keyword = null, $regionIDs = [], $eventTypeIDs = [])
    {
        $children = $this->AllChildren();
        $ids = $children->column('ID');


        if (empty($ids)) {
            return [];
        }

        $list = EventDateTime::get()
            ->filter(array(
                'EventID' => $ids
            ))
            ->innerJoin("EventsPage", "\"EventsPage\".\"ID\" = \"EventDateTime\".\"EventID\"")
            ->where("\"EventsPage\".\"Recursion\" != 1");

        $list = $this->keywordFilter($list, $keyword);
        $list = $this->taxonomyEventFilter($list, $regionIDs, 'Region');
        $list = $this->taxonomyEventFilter($list, $eventTypeIDs, 'EventType');

        if ($start && $end) {
            $list = $list->where("
					(EventDateTime.StartDate <= '$start' AND EventDateTime.LastStartDate >= '$end') OR
					(EventDateTime.StartDate BETWEEN '$start' AND '$end') OR
					(EventDateTime.LastStartDate BETWEEN '$start' AND '$end')
					");
        } elseif ($start) {
            $list = $list->where("(EventDateTime.StartDate >= '$start' OR EventDateTime.LastStartDate > '$start')");
        } elseif ($end) {
            $list = $list->where("(EventDateTime.LastStartDate <= '$end' OR EventDateTime.StartDate < '$end')");
        }
        return $list;
    }

    /**
     * @return DataList
     */
    protected function getRecurringEvents()
    {
        $events = EventsPage::get()
            ->filter("Recursion", "1")
            ->filter("ParentID", $this->ID);

        return $events;
    }

    /**
     * @return ArrayList
     */
    protected function addRecurringEvents($start_date, $end_date, $recurring_events, $all_events, $keyword = null, $regionIDs = [], $eventTypeIDs = [])
    {
        $date_counter = sfDate::getInstance($start_date);
        $end = sfDate::getInstance($end_date);

        foreach ($recurring_events as $recurring_event) {
            $reader = $recurring_event->getRecursionReader();

            $eventDateTimes = $recurring_event->DateTimes();

            $eventDateTimes = $this->dateFilter($eventDateTimes, $date_counter, $end);
            $eventDateTimes = $this->keywordFilter($eventDateTimes, $keyword);
            $eventDateTimes = $this->taxonomyEventFilter($eventDateTimes, $regionIDs, 'Region');
            $eventDateTimes = $this->taxonomyEventFilter($eventDateTimes, $eventTypeIDs, 'EventType');

            foreach ($eventDateTimes as $recurring_event_datetime) {
                $date_counter = sfDate::getInstance($start_date);
                $start = sfDate::getInstance($recurring_event_datetime->StartDate);

                if ($start->get() > $date_counter->get()) {
                    $date_counter = $start;
                }

                while ($date_counter->get() <= $end->get()) {
                    // check the end date
                    if ($recurring_event_datetime->LastStartDate) {
                        $end_stamp = strtotime($recurring_event_datetime->LastStartDate);
                        if ($end_stamp > 0 && $end_stamp < $date_counter->get()) {
                            break;
                        }
                    }

                    // Check to see if recursion happens on this day. If it does create a new date time
                    if ($reader->recursionHappensOn($date_counter->get())) {
                        $e = $this->newRecursionDateTime($recurring_event_datetime, $date_counter->date());
                        $all_events->push($e);
                    }

                    $date_counter->tomorrow();
                }

                $date_counter->reset();
            }
        }

        return $all_events;
    }

    /**
     * @return EventDateTime
     */
    public function newRecursionDateTime($recurring_event_datetime, $start_date)
    {
        $e = new EventDateTime();
        $relation = 'EventID';

        foreach ($recurring_event_datetime->getQueriedDatabaseFields() as $field => $type) {
            $e->$field = $recurring_event_datetime->$field;
        }

        $e->Title = $recurring_event_datetime->owner->Title;
        $e->DateTimeID = $recurring_event_datetime->ID;
        $e->StartDate = $start_date;
        $e->EventDuration = $recurring_event_datetime->EventDuration;
        $e->LastStartDate = $recurring_event_datetime->LastStartDate;
        $e->$relation = $recurring_event_datetime->$relation;
        $e->ID = "recurring" . self::$reccurring_event_index;
        self::$reccurring_event_index++;

        return $e;
    }

    public function getRecurringEventEndDate($startDate, $duration)
    {
        $endTS = strtotime(sprintf("%s +%s days", $startDate, ($duration - 1)));
        $endDate = sfDate::getInstance($endTS)->date();

        return $endDate;
    }

    /**
     * Get events containing the particular keyword
     *
     * @param DataList $eventDateTimes
     * @param string|null $keyword
     * @return DataList
     */
    public function keywordFilter($eventDateTimes, $keyword = null)
    {
        if ($keyword) {
            $keywords = explode(' ', $keyword);
            $eventDateTimes = $eventDateTimes->filterAny([
                'Event.Title:PartialMatch' => $keywords,
                'Event.Description:PartialMatch' => $keywords
            ]);
        }

        return $eventDateTimes;
    }

    /**
     * Filter events by date
     *
     * @param DataList $eventDateTimes
     * @param sfDate $date_counter
     * @param sfDate $end
     * @return DataList
     */
    public function dateFilter($eventDateTimes, $date_counter, $end)
    {
        $eventDateTimes = $eventDateTimes->filter([
            'StartDate:LessThanOrEqual' => $end->date(),
        ]);

        $eventDateTimes = $eventDateTimes->filterAny([
            'LastStartDate:ExactMatch' => null,
            'LastStartDate:GreaterThanOrEqual' => $date_counter->date(),
        ]);
        return $eventDateTimes;
    }

    /**
     * Filter events by a has_one taxonomy field
     *
     * @param DataList $eventDateTimes
     * @param Array $typeIDs
     * @param string $typeFieldname
     * @return DataList
     */
    public function taxonomyEventFilter($eventDateTimes, $typeIDs, $typeFieldname)
    {
        if (!empty($typeIDs)) {
            $eventDateTimes = $eventDateTimes->filter([
                'Event.'.$typeFieldname.'ID' => $typeIDs
            ]);
        }

        return $eventDateTimes;
    }
}
