<?php

namespace Voyage\Events\Models;

use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DataList;
use Voyage\Events\Pages\EventsPage;
use Voyage\Events\Helpers\sfDate;

class RecurringDayOfWeek extends DataObject
{
    private static $table_name = "RecurringDayOfWeek";

    /**
     * @var array
     */
    private static $db = array(
        'Value' => 'Int'
    );

    private static $default_sort = "Value ASC";

    /**
     * @var array
     */
    private static $belongs_many_many = array(
        'Events' => EventsPage::class
    );

    public static function create_default_records()
    {
        for ($i = 0; $i <= 6; $i++) {
            $record = new RecurringDayOfWeek();
            $record->Value = $i;
            $record->write();
        }
    }
    public function requireDefaultRecords()
    {
        parent::requireDefaultRecords();
        $records = DataList::create(RecurringDayOfWeek::class);
        if (!$records->exists()) {
            self::create_default_records();
        } elseif ($records->count() != 7) {
            foreach ($records as $record) {
                $record->delete();
            }
            self::create_default_records();
        }
    }

    public function getTitle()
    {
        return strftime("%a", sfDate::getInstance()->nextDay($this->Value)->get());
    }
}
