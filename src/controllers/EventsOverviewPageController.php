<?php

namespace Voyage\Events\Pages;

use PageController;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\GroupedDropdownField;
use SilverStripe\ORM\FieldType\DBDate;
use SilverStripe\View\ArrayData;
use Voyage\Events\Helpers\sfDate;

/**
 * Events Page Controller
 */
class EventsOverviewPageController extends PageController
{
    private static $allowed_actions = [
        'index',
        'show',
        'MonthJumpForm',
    ];

    /**
     * Start date for event range
     * @var Date
     */
    protected $startDate;

    /**
     * End date for event range
     * @var Date
     */
    protected $endDate;

    /**
     * Set view mode. Used for switching header.
     *
     * @var string
     */
    protected $view = 'default';

    /**
     * Map date parameters to view types
     *
     * @var array
     */
    protected $viewTypeMap = [
        'today'      => 'today',
        'week'       => 'week',
        'month'      => 'week',
        'xxxx-xx'    => 'month',
        'xxxx-xx-xx' => 'day',
    ];

    /**
     * @param HTTPRequest $request
     * @return html
     */
    public function index(HTTPRequest $request)
    {
        $this->setDefaultView();

        return $this->respond();
    }

    /**
     * @param HTTPRequest $request
     * @return html
     */
    public function show(HTTPRequest $request)
    {
        $this->setCustomView($request);

        return $this->respond();
    }

    /**
     * Get the list of events for the configured dates and filters
     *
     * @return DataList
     */
    public function Events()
    {
        return $this->data()->getEventList($this->getStartDate(), $this->getEndDate());
    }

    /**
     * Get the header text for the current view
     *
     * @return string
     */
    public function EventsHeader()
    {
        $method = 'get' . ucfirst($this->view) . 'EventsHeader';
        return $this->$method();
    }

    /**
     * Form for jumping to a particular month
     *
     * @return Form
     */
    public function MonthJumpForm() {
        $this->setCustomView($this->getRequest());
        $monthOptions = $this->buildMonthOptions();
        $form = new Form(
            $this,
            "MonthJumpForm",
            new FieldList (
                $month = new GroupedDropdownField(_t('EventsOverviewPage.MONTH', 'Month'), '', $monthOptions)
            ),
            new FieldList (
                new FormAction('doMonthJump', _t('EventsOverviewPage.JUMP', 'Go'))
            )
        );

        if($this->startDate) {
            $month->setValue($this->startDate->format('Y-m'));
        }
        else {
            $month->setValue(date('Y-m'));
        }
        return $form;
    }

    /**
     * Perform the action to jump to a month
     *
     * @return HTTPResponse
     */
    public function doMonthJump($data, $form) {
        return $this->redirect($this->Link('show').'/' . $data['Month']);
    }

    public function getView()
    {
        return $this->view;
    }

    /**
     * Build and render jump links for current period if applicable
     *
     * @return string
     */
    public function getEventJumpLinks()
    {
        if ($this->HasJumpLinks()) {
            $method = 'get' . ucfirst($this->view) . 'JumpLinks';
            list($prevLink, $prevTitle, $nextLink, $nextTitle) = $this->$method();
            $linkData = new ArrayData([
                'PrevLink'  => $prevLink,
                'PrevTitle' => $prevTitle,
                'NextLink'  => $nextLink,
                'NextTitle' => $nextTitle,
            ]);
            return $linkData->renderWith('EventsJumpLinks');
        }
    }

    /**
     * Get start of date range as a string
     *
     * @return string|null
     */
    protected function getStartDate()
    {
        return ($this->startDate) ? $this->startDate->date() : null;
    }

    /**
     * Get end of date range as a string
     *
     * @return string|null
     */
    protected function getEndDate()
    {
        return ($this->endDate) ? $this->endDate->date() : null;
    }

    /**
     * Default response, no customisation
     *
     * @return array
     */
    protected function respond() {
        return[];
    }

    /**
     * Set a custom view based on date
     *
     * @param HTTPRequest
     */
    protected function setCustomView(HTTPRequest $request)
    {
        $date = $request->param('ID');
        $otherDate = $request->param('OtherID');
		if($otherDate && $this->validateFullDate($otherDate)) {
			return $this->setRangeView($date, $otherDate);
		}
        $this->view = $this->getViewType($date, 'default');
        $method = 'set' . ucfirst($this->view) . 'View';
        return $this->$method($date);
    }

    /**
     * Get type of view from map
     *
     * @param  int    $index     Index into map
     * @param  string $default   Deault value to return if $index does not exist
     *
     * @return string
     */
    public function getViewType($index, $default) {
        $type = $default;

        if (isset($this->viewTypeMap[$index])) {
            $type = $this->viewTypeMap[$index];
        } else {
            $dateMask = $this->dateMask($index);
            if (isset($this->viewTypeMap[$dateMask])) {
                $type = $this->viewTypeMap[$dateMask];
            }
        }

        return $type;
    }

    /**
     * Convert a date format string into a mask.
     *
     * E.g. 2018-11-15 => xxxx-xx-xx
     *
     * @param  string $date
     *
     * @return string
     */
    protected function dateMask($date) {
        return preg_replace('/[0-9]/', 'x', $date);
    }

    /**
     * Validate that a date parameter is in the corrrect format
     *
     * @param string $date
     * @return boolean
     */
    protected function validateFullDate($date)
    {
        return preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $date);
    }

    /**
     * Default view of number of configured months from the current date
     *
     * @param string $_  Not used
     */
    protected function setDefaultView($_ = null)
    {
        $this->startDate = sfDate::getInstance();
        $this->endDate = sfDate::getInstance()->addMonth($this->DefaultFutureMonths);
    }

    /**
     * Set the view for the month of the given date
     *
     * @param string $date  Month in YYY-MM format
     */
    protected function setMonthView($date)
    {
        if ($date == 'month') {
            $this->startDate = sfDate::getInstance()->firstDayOfMonth();
        } else {
            $this->startDate = sfDate::getInstance("{$date}-01");
        }
        $this->endDate = sfDate::getInstance($this->startDate)->finalDayOfMonth();
        $this->view = 'month';
    }

    /**
     * Set the view for this week
     *
     * @param string $_  Not used
     */
    protected function setWeekView($_ = null)
    {
        $this->startDate = sfDate::getInstance()->firstDayOfWeek();
        $this->endDate = sfDate::getInstance()->finalDayOfWeek();
        $this->view = 'week';
    }

    /**
     * Set the view for today
     *
     * @param string $_  Not used
     */
    protected function setTodayView($_ = null)
    {
        $this->startDate = sfDate::getInstance();
        $this->endDate = sfDate::getInstance();
        $this->view = 'today';
    }

    /**
     * Set the view for the given date
     *
     * @param string $date  Start date for event list
     */
    protected function setDayView($date)
    {
        $this->startDate = sfDate::getInstance($date);
        $this->endDate = sfDate::getInstance($date);
        $this->view = 'day';
    }

    /**
     * Sedt the view for a custom range
     *
     * @param string $startDate  Start date for the range
     * @param string $endDate    Start date for the range
     */
    protected function setRangeView($startDate, $endDate)
    {
        $this->startDate = sfDate::getInstance($startDate);
        $this->endDate = sfDate::getInstance($endDate);
        $this->setRangeViewType();
    }

    /**
     * If the range matches a day, week or month we set the view type appropriately
     */
    protected function setRangeViewType()
    {
        if ($this->startDate->date() == $this->endDate->date()) {
            $this->view = 'day';
        } elseif (($this->startDate->date() == sfDate::getInstance($this->startDate)->firstDayOfWeek()->date())
                    && ($this->endDate->date() == sfDate::getInstance($this->startDate)->finalDayOfWeek()->date())) {
            $this->view = 'week';
        } elseif (($this->startDate->date() == sfDate::getInstance($this->startDate)->firstDayOfMonth()->date())
                    && ($this->endDate->date() == sfDate::getInstance($this->startDate)->finalDayOfMonth()->date())) {
            $this->view = 'month';
        } else {
            $this->view = 'range';
        }
    }

    /**
     * Get the default header text
     *
     * @return string
     */
    protected function getDefaultEventsHeader()
    {
        return $this->DefaultHeader;
    }

    /**
     * Get the month view header text
     *
     * @return string
     */
    protected function getMonthEventsHeader()
    {
        return $this->startDate->format('F Y');
    }

    /**
     * Get the week view header text
     *
     * @return string
     */
    protected function getWeekEventsHeader()
    {
        return $this->startDate->format('j F Y') . ' - ' . $this->endDate->format('j F Y');
    }

    /**
     * Get the today view header text
     *
     * @return string
     */
    protected function getTodayEventsHeader()
    {
        return $this->startDate->format('j F Y');
    }

    /**
     * Get the day view header text
     *
     * @return string
     */
    protected function getDayEventsHeader()
    {
        return $this->startDate->format('j F Y');
    }

    /**
     * Get the jump links for previous and next months
     *
     * @return array
     */
    protected function getMonthJumpLinks()
    {
        $prevMonth = sfDate::getInstance($this->startDate)->subtractMonth();
        $nextMonth = sfDate::getInstance($this->startDate)->addMonth();
        return [
            self::join_links($this->Link('show'), $prevMonth->format('Y-m-d'), sfDate::getInstance($prevMonth)->finalDayOfMonth()->format('Y-m-d')),
            _t('EventsOverviewPage.PREVMONTH', 'Previous month'),
            self::join_links($this->Link('show'), $nextMonth->format('Y-m-d'), sfDate::getInstance($nextMonth)->finalDayOfMonth()->format('Y-m-d')),
            _t('EventsOverviewPage.NEXTMONTH', 'Next month'),
        ];
    }

    /**
     * Get the jump links for previous and next weeks
     *
     * @return array
     */
    protected function getWeekJumpLinks()
    {
        $prevWeek = sfDate::getInstance($this->startDate)->subtractWeek();
        $nextWeek = sfDate::getInstance($this->startDate)->addWeek();
        return [
            self::join_links($this->Link('show'), $prevWeek->format('Y-m-d'), sfDate::getInstance($prevWeek)->finalDayOfWeek()->format('Y-m-d')),
            _t('EventsOverviewPage.PREVWEEK', 'Previous week'),
            self::join_links($this->Link('show'), $nextWeek->format('Y-m-d'), sfDate::getInstance($nextWeek)->finalDayOfWeek()->format('Y-m-d')),
            _t('EventsOverviewPage.NEXTWEEK', 'Next week'),
        ];
    }

    /**
     * Get the jump links for previous and next days
     *
     * @return array
     */
    protected function getDayJumpLinks()
    {
        $prevDay = sfDate::getInstance($this->startDate)->subtractDay();
        $nextDay = sfDate::getInstance($this->startDate)->addDay();
        return [
            self::join_links($this->Link('show'), $prevDay->format('Y-m-d')),
            _t('EventsOverviewPage.PREVDAY', 'Previous day'),
            self::join_links($this->Link('show'), $nextDay->format('Y-m-d')),
            _t('EventsOverviewPage.NEXTDAY', 'Next day'),
        ];
    }

    /**
     * Get the jump links for previous and next days from current day
     *
     * @return array
     */
    protected function getTodayJumpLinks()
    {
        return $this->getDayJumpLinks();
    }

    /**
     * Get the custom range view header text
     *
     * @return string
     */
    protected function getRangeEventsHeader()
    {
        return $this->startDate->format('j F Y') . ' - ' . $this->endDate->format('j F Y');
    }

    /**
     * Build an associative array of years and months for use in a
     * GroupedDropDownList
     *
     * @return array
     */
    protected function buildMonthOptions()
    {
        $baseDate = sfDate::getInstance();
        $years = range($baseDate->subtractYear(1)->format('Y'), $baseDate->addYear(3)->format('Y'));
        $monthOptions = [];
        $months = range(1, 12);
        array_walk($years, function($year) use(&$monthOptions, $months) {
            $monthsByYear = [];
            array_walk(
                $months,
                function($month) use($year, &$monthsByYear) {
                    $date = $year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT);
                    $monthsByYear[$date] = sfDate::getInstance($date)->format('F') . ' ' . $year;
                }
            );
            $monthOptions[$year] = $monthsByYear;
        });
        return $monthOptions;
    }

    /**
     * Check if the current view type is one that should have jumplinks
     */
    protected function hasJumpLinks()
    {
        return in_array($this->view, ['day', 'week', 'month', 'today']);
    }
}
