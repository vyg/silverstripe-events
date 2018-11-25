<?php

namespace Voyage\Events\Pages;

use PageController;
use SilverStripe\View\ArrayData;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\ORM\FieldType\DBDate;

/**
 * Events Page Controller
 */
class EventsPageController extends PageController
{

    /**
     * @param HTTPRequest $request
     * @return html
     */
    public function index(HTTPRequest $request)
    {
        $date = $request->getVar('date');

        return $this->customise(new ArrayData([
            'EventStartDate' => $date ? DBDate::create('EventStartDate')->setValue($date) : null
        ]));
    }
}
