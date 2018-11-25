<?php

namespace Voyage\Events\Models;

use Voyage\Events\Helpers\sfDate;
use Voyage\Events\Pages\EventsPage;
use SilverStripe\ORM\DataObject;
use SilverStripe\Forms\DateField;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\TimeField;
use SilverStripe\Control\Controller;

class EventDateTime extends DataObject
{
    const MAX_DISPLAY_TIME = '18 months'; // Use strtotime() compatible strings

    private static $table_name = "EventDateTime";

    /**
    * @var array
    */
    private static $db = [
        'StartDate' => 'Date',
        'EventDuration' => 'Int',
        'StartTime' => 'Time',
        'EndTime' => 'Time',
        'LastStartDate' => 'Date',
    ];

    /**
    * @var array
    */
    private static $defaults = [
        'EventDuration' => 1
    ];

    /**
    * @var array
    */
    private static $has_one = [
        'Event' => EventsPage::class
    ];

    private static $casting = [
        'FinishDate' => 'Date'
    ];

    private static $date_format_override;
    private static $time_format_override;

    private static $offset = "+00:00";

    /**
     * @return fieldList
     */
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $fields->removeByName(array(
            'EventID',
        ));

        $fields->addFieldsToTab('Root.Main', [
            DateField::create('StartDate', _t('EventDateTime.STARTDATE', 'First Event Starts')),
            TextField::create('EventDuration', _t('EventDateTime.DayDuration', 'Event Duration'))
            ->setRightTitle('Default 1 day'),
            TimeField::create('StartTime', _t('EventDateTime.STARTTIME', 'Start time')),
            TimeField::create('EndTime', _t('EventDateTime.ENDTIME', 'End Time')),
            DateField::create('LastStartDate', 'Last Event Starts')
                ->setDescription('If this is a recurring event and this is empty then this event will display for '.EventDateTime::MAX_DISPLAY_TIME.' from the current date'),
        ]);

        return $fields;
    }

    /**
     * @return array
     */
    public function summaryFields()
    {
        return array(
            'FormattedStartDate' => _t('Event.STARTDATE', 'First Event Starts'),
            'FormattedLastStartDate' => 'Last Event Starts',
            'EventDuration' => 'Event Duration (days)',
            'FormattedStartTime' => _t('Event.STARTTIME', 'Start time'),
            'FormattedEndTime' => _t('Event.ENDTIME', 'End time')
        );
    }

    /**
     * @return string
     */
    public function Link()
    {
        return Controller::join_links($this->Event()->Link(), "?date=".$this->StartDate);
    }

    /**
     * The date the event will finish on
     *
     * @param Date $startDate
     * @param int $duration
     * @return string
     */
    public function FinishDate($startDate = null)
    {
        $startDate = $startDate ? $startDate : $this->StartDate;

        $finishDate = '';

        if ($this->EventDuration > 1) {
            $endTS = strtotime(sprintf("%s +%s days", $startDate, ($this->EventDuration - 1)));
            $finishDate = sfDate::getInstance($endTS)->date();
        }

        return $finishDate;
    }

    /**
     * Check to see if this event has finished
     *
     * @return boolean
     */
    public function Finished()
    {
        if (empty($this->FinishDate())) {
            $finished = strtotime($this->StartDate) < time();
        } else {
            $finished = strtotime($this->FinishDate()) < time();
        }

        return $finished;
    }

    /**
     * @return string
     */
    public function getFormattedStartDate()
    {
        return $this->obj('StartDate')->Format('dd MMMM YYYY');
    }

    /**
     * @return string
     */
    public function getFormattedLastStartDate()
    {
        return $this->obj('LastStartDate')->Format('dd MMMM YYYY');
    }

    /**
     * @return string
     */
    public function getFormattedStartTime()
    {
        return $this->obj('StartTime')->Format('h:mm a');
    }

    /**
     * @return string
     */
    public function getFormattedEndTime()
    {
        return $this->obj('EndTime')->Format('h:mm a');
    }
}
