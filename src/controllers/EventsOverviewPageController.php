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
     * @param HTTPRequest $request
     * @return html
     */
    public function index(HTTPRequest $request)
    {
        $date = $this->setDefaultView();

        return $this->customise(new ArrayData([
            'EventStartDate' => $date ? DBDate::create('EventStartDate')->setValue($date) : null
        ]));
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
     * Default view of number of configured months from the current date
     */
    protected function setDefaultView()
    {
		$this->view = "default";
		$this->startDate = sfDate::getInstance();
		$this->endDate = sfDate::getInstance()->addMonth($this->DefaultFutureMonths);
    }
}
