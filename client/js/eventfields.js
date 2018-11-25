/* eslint-disable */
(function($) {
    $(function() {

        $('.field.defaultView select').entwine({
            onmatch: function() {
                if ($(this).val() != 'upcoming') {
                    $('.field.defaultFutureMonths').hide();
                }
            },
            onchange: function() {
                $('.field.defaultFutureMonths').hide();
                if ($(this).val() == 'upcoming') {
                    $('.field.defaultFutureMonths').show();
                }
            }
        });


        $('.field.checkbox.recursion').entwine({
            onmatch: function() {

                var $tab = this.closest('#Root_Recursion');
                var $recursion = this;
                var $customRecursionType = $tab.find('#Form_EditForm_CustomRecursionType_Holder').hide();
                var $dailyInterval = $tab.find('.dailyinterval').hide();
                var $weeklyInterval = $tab.find('.weeklyinterval').hide();
                var $monthlyInterval = $tab.find('.monthlyinterval').hide();
                var $monthlyIndex = $tab.find('.monthlyindex').hide();
                var $recurringDaysOfWeek = $tab.find('#Form_EditForm_RecurringDaysOfWeek_Holder').hide();
                var $recurringDaysOfMonth = $tab.find('#Form_EditForm_RecurringDaysOfMonth_Holder').hide();
                var $monthlyRecursionType1 = $tab.find('#Form_EditForm_MonthlyRecursionType1_Holder').hide();
                var $monthlyRecursionType2 = $tab.find('#Form_EditForm_MonthlyRecursionType2_Holder').hide();

                var resetPanels = function () {
                    $dailyInterval.hide();
                    $weeklyInterval.hide();
                    $monthlyInterval.hide();
                    $recurringDaysOfWeek.hide();
                    $recurringDaysOfMonth.hide().find(':checkbox').attr('disabled', true);
                    $monthlyRecursionType1.hide();
                    $monthlyRecursionType2.hide();
                    $monthlyIndex.hide().find('select').attr('disabled', true);
                };

                var resetSubPanels = function () {
                    $recurringDaysOfMonth.hide().find(':checkbox').attr('disabled', true);
                    $monthlyIndex.hide().find('select').attr('disabled', true);
                };

                $recursion.find('input').change(function() {
                    if($(this).is(':checked')) {
                        $customRecursionType.show();
                    }
                    else {
                        $tab.find(':checkbox, :radio').attr('checked', false);
                        $customRecursionType.hide();
                        resetPanels();
                    }
                }).change();

                $customRecursionType.find('input').change(function() {
                    if($(this).is(':checked')) {
                        resetPanels();
                        switch($(this).val()) {
                            case "1":
                                $dailyInterval.show();
                            break;

                            case "2":
                                $weeklyInterval.show();
                                $recurringDaysOfWeek.show();
                            break;

                            case "3":
                                $monthlyInterval.show();
                                $monthlyRecursionType1.show();
                                $monthlyRecursionType2.show();
                            break;
                        }
                    }
                }).change();

                $monthlyRecursionType1.find('input').change(function() {
                    if($(this).is(':checked')) {

                        resetSubPanels();
                        $recurringDaysOfMonth.show();

                        $recurringDaysOfMonth.find(':checkbox').attr('disabled', false);
                        $monthlyIndex.find('select').attr('disabled', true);
                        $monthlyRecursionType2.find('input').attr('checked', false).change();
                    }
                }).change();

                $monthlyRecursionType2.find('input').change(function() {
                    if($(this).is(':checked')) {

                        resetSubPanels();
                        $monthlyIndex.show();

                        $recurringDaysOfMonth.find(':checkbox').attr('disabled', true);
                        $monthlyIndex.find('select').attr('disabled', false);
                        $monthlyRecursionType1.find('input').attr('checked', false).change();
                    }
                }).change();

            }
        });

    });
})(jQuery);
