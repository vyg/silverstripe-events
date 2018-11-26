<?php
/**
 * An exception date in a run of dates for a recurring event.
 */
namespace Voyage\Events\Models;

use SilverStripe\Forms\DateField;
use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\DataObject;

class RecurringException extends DataObject {
    private static $table_name = 'RecurringException';
    private static $singular_name = 'RecurringException';
    private static $plural_name = 'RecurringExceptions';

    private static $db = [
        'ExceptionDate' => 'Date',
    ];

    private static $has_one = [
        'EventsPage' => EventsPage::class,
    ];

    private static $default_sort = "ExceptionDate ASC";

    public function getCMSFields() {
        $fields = parent::getCMSFields();

        DateField::set_default_config('showcalendar', true);

        $fields->addFieldToTab(
            'Root.Main',
            new DateField('ExceptionDate', _t('CalendarDateTime.EXCEPTIONDATE','Exception Date'))
        );

        return $fields;
    }

    public function summaryFields() {
        return [
            'FormattedExceptionDate' => _t('Calendar.EXCEPTIONDATE','Exception date'),
        ];
    }

    public function getFormattedExceptionDate() {
       if(!$this->ExceptionDate) return "--";
       return $this->obj('ExceptionDate')->Format('dd MMMM YYYY');
    }
}
