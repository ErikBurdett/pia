(function (wp) {
    const { registerBlockType } = wp.blocks;
    const { InspectorControls } = wp.blockEditor;
    const { PanelBody, TextControl, ToggleControl } = wp.components;
    const { createElement, useEffect } = wp.element;

    registerBlockType('addevent-calendar/block', {
        title: 'AddEvent Calendar',
        icon: 'calendar',
        category: 'widgets',
        attributes: {
            calendarID: { type: 'string', default: 'ih348885' },
            showTitle: { type: 'boolean', default: true },
            showToday: { type: 'boolean', default: true },
            showDateNav: { type: 'boolean', default: true },
            showDate: { type: 'boolean', default: true },
            showMonthWeekToggle: { type: 'boolean', default: true },
            showSubscribeButton: { type: 'boolean', default: true },
            showPrint: { type: 'boolean', default: true },
            showTimeZone: { type: 'boolean', default: true },
            showMonth: { type: 'boolean', default: true },
            showWeek: { type: 'boolean', default: true },
            showSchedule: { type: 'boolean', default: true },
            defaultView: { type: 'string', default: 'month' },
        },

        edit: function (props) {
            const { attributes, setAttributes } = props;
            const {
                calendarID,
                showTitle,
                showToday,
                showDateNav,
                showDate,
                showMonthWeekToggle,
                showSubscribeButton,
                showPrint,
                showTimeZone,
                showMonth,
                showWeek,
                showSchedule,
                defaultView,
            } = attributes;
        
            useEffect(() => {
                // Load the AddEvent script if it's not already loaded
                if (!window.AddEvent) {
                    const script = document.createElement('script');
                    script.src = 'https://cdn.addevent.com/libs/cal/js/cal.embed.t1.init.js';
                    script.async = true;
                    document.head.appendChild(script);
                }
        
                // Trigger the AddEvent calendar to load in the editor
                const loadCalendarPreview = () => {
                    if (window.AddEvent && calendarID) {
                        window.AddEvent.init();
                    }
                };
        
                loadCalendarPreview();  // Ensure the calendar is loaded in the editor
            }, [calendarID, showTitle, showToday, showDateNav, showDate, showMonthWeekToggle, showSubscribeButton, showPrint, showTimeZone, showMonth, showWeek, showSchedule, defaultView]);  // Re-run when any of the settings change
        
            return createElement(
                'div',
                null,
                createElement(
                    InspectorControls,
                    null,
                    createElement(
                        PanelBody,
                        { title: 'Calendar Settings' },
                        createElement(TextControl, {
                            label: 'Calendar ID',
                            value: calendarID,
                            onChange: (value) => setAttributes({ calendarID: value }),
                            help: createElement('a', { href: 'https://help.addevent.com/docs/how-to-find-your-calendar-id', target: '_blank' }, 'How to find the CalendarID')
                        }),
                        createElement(ToggleControl, {
                            label: 'Title of calender',
                            checked: showTitle,
                            onChange: (value) => setAttributes({ showTitle: value })
                        }),
                        createElement(ToggleControl, {
                            label: 'Today button',
                            checked: showToday,
                            onChange: (value) => setAttributes({ showToday: value })
                        }),
                        createElement(ToggleControl, {
                            label: 'Prev/Next buttons',
                            checked: showDateNav,
                            onChange: (value) => setAttributes({ showDateNav: value })
                        }),
                        createElement(ToggleControl, {
                            label: 'Date',
                            checked: showDate,
                            onChange: (value) => setAttributes({ showDate: value })
                        }),
                        createElement(ToggleControl, {
                            label: 'Month selector',
                            checked: showMonthWeekToggle,
                            onChange: (value) => setAttributes({ showMonthWeekToggle: value })
                        }),
                        createElement(ToggleControl, {
                            label: 'Subscribe button',
                            checked: showSubscribeButton,
                            onChange: (value) => setAttributes({ showSubscribeButton: value })
                        }),
                        createElement(ToggleControl, {
                            label: 'Print',
                            checked: showPrint,
                            onChange: (value) => setAttributes({ showPrint: value })
                        }),
                        createElement(ToggleControl, {
                            label: 'Timezone select',
                            checked: showTimeZone,
                            onChange: (value) => setAttributes({ showTimeZone: value })
                        }),
                        createElement(ToggleControl, {
                            label: 'Month View',
                            checked: showMonth,
                            onChange: (value) => setAttributes({ showMonth: value })
                        }),
                        createElement(ToggleControl, {
                            label: 'Week View',
                            checked: showWeek,
                            onChange: (value) => setAttributes({ showWeek: value })
                        }),
                        createElement(ToggleControl, {
                            label: 'Schedule View',
                            checked: showSchedule,
                            onChange: (value) => setAttributes({ showSchedule: value })
                        }),
                        createElement(ToggleControl, {
                            label: 'Default View (Month)',
                            checked: defaultView === 'month',
                            onChange: (value) => setAttributes({ defaultView: value ? 'month' : 'week' }),
                        })
                    )
                ),
                createElement(
                    'div',
                    null,
                    createElement('h3', null, 'Calendar Preview'),
                    calendarID
                        ? createElement('div', {
                            className: 'ae-emd-cal',
                            'data-calendar': calendarID,
                            'data-configure': 'true',
                            'data-title-show': showTitle ? 'true' : 'false',
                            'data-today': showToday ? 'true' : 'false',
                            'data-datenav': showDateNav ? 'true' : 'false',
                            'data-date': showDate ? 'true' : 'false',
                            'data-monthweektoggle': showMonthWeekToggle ? 'true' : 'false',
                            'data-subscribebtn': showSubscribeButton ? 'true' : 'false',
                            'data-print': showPrint ? 'true' : 'false',
                            'data-timezone': showTimeZone ? 'true' : 'false',
                            'data-swimonth': showMonth ? 'true' : 'false',
                            'data-swiweek': showWeek ? 'true' : 'false',
                            'data-swischedule': showSchedule ? 'true' : 'false',
                            'data-defaultview': defaultView,
                        })
                        : createElement('p', null, 'Please enter a Calendar ID to preview the calendar.')
                )
            );
        },
        

        save: function (props) {
            const { attributes } = props;
            const {
                calendarID,
                showTitle,
                showToday,
                showDateNav,
                showDate,
                showMonthWeekToggle,
                showSubscribeButton,
                showPrint,
                showTimeZone,
                showMonth,
                showWeek,
                showSchedule,
                defaultView,
            } = attributes;

            return createElement(
                'div',
                null,
                calendarID
                    ? createElement('div', {
                          className: 'ae-emd-cal',
                          'data-calendar': calendarID,
                          'data-configure': 'true',
                          'data-title-show': showTitle ? 'true' : 'false',
                          'data-today': showToday ? 'true' : 'false',
                          'data-datenav': showDateNav ? 'true' : 'false',
                          'data-date': showDate ? 'true' : 'false',
                          'data-monthweektoggle': showMonthWeekToggle ? 'true' : 'false',
                          'data-subscribebtn': showSubscribeButton ? 'true' : 'false',
                          'data-print': showPrint ? 'true' : 'false',
                          'data-timezone': showTimeZone ? 'true' : 'false',
                          'data-swimonth': showMonth ? 'true' : 'false',
                          'data-swiweek': showWeek ? 'true' : 'false',
                          'data-swischedule': showSchedule ? 'true' : 'false',
                          'data-defaultview': defaultView,
                      })
                    : createElement('p', null, 'No Calendar ID provided.')
            );
        },
    });
})(window.wp);



