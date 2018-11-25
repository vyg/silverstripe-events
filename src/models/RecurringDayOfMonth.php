<?php

namespace Voyage\Events\Models;

use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DataList;
use Voyage\Events\Pages\EventsPage;

class RecurringDayOfMonth extends DataObject
{
    private static $table_name = "RecurringDayOfMonth";

    /**
     * @var array
     */
    private static $db = array(
        'Value' => 'Int'
    );

    /**
     * @var array
     */
    private static $belongs_many_many = array(
        'Events' => EventsPage::class
    );

    private static $default_sort = "Value ASC";

    public static function create_default_records()
    {
        for ($i = 1; $i <= 31; $i++) {
            $record = RecurringDayOfMonth::create([
                'Value' => $i
            ]);
            $record->write();
        }
    }

    public function requireDefaultRecords()
    {
        parent::requireDefaultRecords();
        $records = DataList::create(RecurringDayOfMonth::class);
        if (!$records->exists()) {
            self::create_default_records();
        } elseif ($records->count() != 31) {
            foreach ($records as $record) {
                $record->delete();
            }
            self::create_default_records();
        }
    }
}
