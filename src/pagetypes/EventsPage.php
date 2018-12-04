<?php

namespace Voyage\Events\Pages;

use Page;
use Voyage\Events\Helpers\sfDate;
use Voyage\Events\Models\EventDateTime;
use Voyage\Events\Models\RecurringException;
use SilverStripe\ORM\DataList;
use Voyage\Events\Helpers\RecursionReader;
use SilverStripe\Forms\FieldGroup;
use SilverStripe\Forms\LabelField;
use Voyage\Events\Models\RecurringDayOfWeek;
use SilverStripe\View\Requirements;
use Voyage\Events\Models\RecurringDayOfMonth;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\OptionsetField;
use SilverStripe\Forms\CheckboxSetField;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldAddNewButton;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;

class EventsPage extends Page
{
    private static $singular_name = 'Event Page';

    private static $plural_name = 'Event Pages';

    private static $table_name = "EventsPage";

    /**
    * @var array
    */
    private static $db = [
        'Location' => 'Varchar',
        'Description' => 'HTMLText',
        'Recursion' => 'Boolean',
        'CustomRecursionType' => 'Int',
        'DailyInterval' => 'Int',
        'WeeklyInterval' => 'Int',
        'MonthlyInterval' => 'Int',
        'MonthlyRecursionType1' => 'Int',
        'MonthlyRecursionType2' => 'Int',
        'MonthlyIndex' => 'Int',
        'MonthlyDayOfWeek' => 'Int'
    ];

    /**
    * @var array
    */
    private static $has_many = [
        'DateTimes'  => EventDateTime::class,
        'Exceptions' => RecurringException::class,
    ];

    /**
    * @var array
    */
    private static $many_many = [
        'RecurringDaysOfWeek' => RecurringDayOfWeek::class,
        'RecurringDaysOfMonth' => RecurringDayOfMonth::class
    ];

    /**
     * @return fieldList
     */
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        Requirements::javascript('voyage/events: client/js/eventfields.js');
        Requirements::css('voyage/events: client/css/eventfields.css');

        $dateTimeConfig = GridFieldConfig_RecordEditor::create();

        if ($this->DateTimes()->count() >= 1) {
            $dateTimeConfig->removeComponentsByType(GridFieldAddNewButton::class);
        }

        $fields->addFieldsToTab(
            'Root.DatesAndTimes',
            [
                GridField::create(
                    "DateTimes",
                    'Date and Time',
                    $this->DateTimes(),
                    $dateTimeConfig
                )->setDescription('One item limit. If there is no item and no add button please refresh the page.')
            ]
        );

        $fields->addFieldsToTab(
            'Root.Recursion',
            [
                CheckboxField::create('Recursion', _t('EventsPage.REPEATEVENT', 'Repeat this event'))->addExtraClass('recursion'),
                OptionsetField::create(
                    'CustomRecursionType',
                    _t('EventsPage.DESCRIBEINTERVAL', 'Describe the interval at which this event recurs.'),
                    array(
                        '1' => _t('EventsPage.DAILY', 'Daily'),
                        '2' => _t('EventsPage.WEEKLY', 'Weekly'),
                        '3' => _t('EventsPage.MONTHLY', 'Monthly')
                    )
                )->setHasEmptyDefault(true),
                $dailyInterval = FieldGroup::create(
                      LabelField::create($name = "every1", $title = _t("EventsPage.EVERY", "Every ")),
                      DropdownField::create('DailyInterval', '', array_combine(range(1, 10), range(1, 10))),
                      LabelField::create($name = "days", $title = _t("EventsPage.DAYS", " day(s)"))
                ),
                 $weeklyInterval = FieldGroup::create(
                      LabelField::create($name = "every2", $title = _t("EventsPage.EVERY", "Every ")),
                      DropdownField::create('WeeklyInterval', '', array_combine(range(1, 10), range(1, 10))),
                      LabelField::create($name = "weeks", $title = _t("EventsPage.WEEKS", " weeks"))
                  ),
                CheckboxSetField::create(
                    'RecurringDaysOfWeek',
                    _t('EventsPage.ONFOLLOWINGDAYS', 'On the following day(s)...'),
                    DataList::create(RecurringDayOfWeek::class)->map("ID", "Title")
                ),
                $monthlyInterval = FieldGroup::create(
                    LabelField::create($name="every3", $title = _t("EventsPage.EVERY", "Every ")),
                    DropdownField::create('MonthlyInterval', '', array_combine(range(1, 10), range(1, 10))),
                    LabelField::create($name = "months", $title = _t("EventsPage.MONTHS", " month(s)"))
                ),
                OptionsetField::create('MonthlyRecursionType1', '', array('1' => _t('EventsPage.ONTHESEDATES', 'On these date(s)...')))->setHasEmptyDefault(true),
                CheckboxSetField::create('RecurringDaysOfMonth', '', DataList::create(RecurringDayOfMonth::class)->map("ID", "Value")),
                OptionsetField::create('MonthlyRecursionType2', '', array('1' => _t('EventsPage.ONTHE', 'On the...')))->setHasEmptyDefault(true),
                $monthlyIndex = FieldGroup::create(
                    DropdownField::create('MonthlyIndex', '', array(
                        '1' => _t('EventsPage.FIRST', 'First'),
                        '2' => _t('EventsPage.SECOND', 'Second'),
                        '3' => _t('EventsPage.THIRD', 'Third'),
                        '4' => _t('EventsPage.FOURTH', 'Fourth'),
                        '5' => _t('EventsPage.LAST', 'Last')
                    ))->setHasEmptyDefault(true),
                    DropdownField::create('MonthlyDayOfWeek', '', DataList::create(RecurringDayOfWeek::class)->map('Value', 'Title'))->setHasEmptyDefault(true),
                    LabelField::create($name = "ofthemonth", $title = _t("EventsPage.OFTHEMONTH", " of the month."))
                ),
                GridField::create(
                    'Exceptions',
                    'Any exceptions to this pattern? Add the dates below.',
                    $this->Exceptions(),
                    GridFieldConfig_RecordEditor::create()
                ),
            ]
        );

        $dailyInterval->addExtraClass('dailyinterval');
        $weeklyInterval->addExtraClass('weeklyinterval');
        $monthlyInterval->addExtraClass('monthlyinterval');
        $monthlyIndex->addExtraClass('monthlyindex');


        return $fields;
    }

    /**
     * @return RecursionReader
     */
    public function getRecursionReader()
    {
        return new RecursionReader($this);
    }

    /**
     * Expired
    *
    * @return boolean
    */
    public function Expired()
    {
        $eventDateTime = $this->DateTimes()->first();
        if ($eventDateTime) {
            return strtotime($eventDateTime->LastStartDate) < time();
        }

        return false;
    }

    /**
     * Get the next upcoming event.
     *
     * @return EventDateTime
     */
    public function LatestEventDateTime()
    {
        $reader = $this->getRecursionReader();
        $eventDateTime = $this->DateTimes()->first();

        if ($eventDateTime) {
            $date_counter = sfDate::getInstance(time()); // Now
            $end = sfDate::getInstance($eventDateTime->LastStartDate);

            if (!$this->Recursion) {
                return $eventDateTime;
            }

            while ($date_counter->get() <= $end->get()) {
                // check the end date
                if ($eventDateTime->LastStartDate) {
                    $end_stamp = strtotime($eventDateTime->LastStartDate);
                    if ($end_stamp > 0 && $end_stamp < $date_counter->get()) {
                        break;
                    }
                }

                if ($reader->recursionHappensOn($date_counter->get())) {
                    $e = singleton(EventsOverviewPage::class)->newRecursionDateTime($eventDateTime, $date_counter->date());
                    return $e;
                }

                $date_counter->tomorrow();
            }

            return $eventDateTime;
        }

        return false;
    }

    /**
     * Get the DateTime. If no start time is specified or there is no
     * event on this date return the first DateTime on this event
     *
     * @param string $start_date
     * @return EventDateTime|boolean
     */
    public function DateTimeAt($start_date = null)
    {
        $reader = $this->getRecursionReader();
        $eventDateTime = $this->DateTimes()->first();
        $date_counter = sfDate::getInstance($start_date); // Now

        if (!$eventDateTime) {
            return false;
        }

        if (!$this->Recursion) {
            return $eventDateTime;
        }

        // check the end date
        if ($eventDateTime->LastStartDate) {
            $end_stamp = strtotime($eventDateTime->LastStartDate);
            if ($end_stamp > 0 && $end_stamp < $date_counter->get()) {
                return $eventDateTime;
            }
        }

        if ($reader->recursionHappensOn($date_counter->get())) {
            $e = singleton(EventsOverviewPage::class)->newRecursionDateTime($eventDateTime, $date_counter->date());
            return $e;
        }

        return $eventDateTime;
    }
}
