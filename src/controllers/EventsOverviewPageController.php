<?php

namespace Voyage\Events\Pages;

use PageController;
use SilverStripe\View\ArrayData;
use SilverStripe\Control\HTTPRequest;
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
     * Map lengths of date parameters to view types
     */
    protected $viewDateLengthMap = [
        '7' => 'month',
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
     * Default view of number of configured months from the current date
     */
    protected function setDefaultView($_ = null)
    {
        $this->startDate = sfDate::getInstance();
        $this->endDate = sfDate::getInstance()->addMonth($this->DefaultFutureMonths);
    }

    /**
     * Set a custom view based on date
     *
     * @param HTTPRequest
     */
    protected function setCustomView(HTTPRequest $request)
    {
        $date = $request->param('ID');
        $this->view = $this->getViewType(strlen($date), 'default');
        $method = 'set' . ucfirst($this->view) . 'View';
        return $this->$method($date);
    }

    /**
     * Get type of view from date length map
     *
     * @param  int    $index     Index into map
     * @param  string $default   Deault value to return if $index does note exist
     *
     * @return string
     */
    protected function getViewType($index, $default) {
        return (isset($this->viewDateLengthMap[$index]))
            ? $this->viewDateLengthMap[$index]
            : $default;
    }

    /**
     * Set the view for the month of the given date
     *
     * @param string $date  Month in YYY-MM format
     */
    protected function setMonthView($date)
    {
        $startOfMonth = "{$date}-01";
        $this->startDate = sfDate::getInstance($startOfMonth);
        $this->endDate = sfDate::getInstance($startOfMonth)->finalDayOfMonth();
        $this->view = 'month';
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
}
