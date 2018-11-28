<?php

namespace Voyage\Events\Pages;

use PageController;
use SilverStripe\View\ArrayData;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\GroupedDropdownField;
use SilverStripe\ORM\FieldType\DBDate;
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
     * Sedt the view for the given date
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
}
