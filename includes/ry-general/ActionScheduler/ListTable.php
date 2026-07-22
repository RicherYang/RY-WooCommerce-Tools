<?php

namespace RY\General\ActionScheduler;

defined('ABSPATH') or exit;

final class ListTable extends \ActionScheduler_ListTable
{
    protected \DateTimeZone $timezone;

    public function __construct(\ActionScheduler_Store $store, \ActionScheduler_Logger $logger, \ActionScheduler_QueueRunner $runner)
    {
        $this->timezone = wp_timezone();
        parent::__construct($store, $logger, $runner);
    }

    protected function get_table_classes()
    {
        $classes = parent::get_table_classes();
        unset($classes[array_search('fixed', $classes)]);

        return $classes;
    }

    protected function get_schedule_display_string(\ActionScheduler_Schedule $schedule)
    {
        $schedule_display_string = '';

        if (is_a($schedule, 'ActionScheduler_NullSchedule')) {
            return __('async', 'ry-woocommerce-tools');
        }

        if (! method_exists($schedule, 'get_date') || ! $schedule->get_date()) {
            return '0000-00-00 00:00:00';
        }

        $schedule->get_date()->setTimezone($this->timezone);
        $next_timestamp = $schedule->get_date()->getTimestamp();

        $schedule_display_string .= $schedule->get_date()->format('Y-m-d H:i:s O');
        $schedule_display_string .= '<br/>';

        if (gmdate('U') > $next_timestamp) {
            /* translators: %s: date interval */
            $schedule_display_string .= sprintf(__(' (%s ago)', 'ry-woocommerce-tools'), human_time_diff(gmdate('U'), $next_timestamp));
        } else {
            /* translators: %s: date interval */
            $schedule_display_string .= sprintf(__(' (%s)', 'ry-woocommerce-tools'), human_time_diff($next_timestamp, gmdate('U')));
        }

        return $schedule_display_string;
    }

    public function column_log_entries(array $row)
    {
        $log_entries_html = '<ol>';

        foreach ($row['log_entries'] as $log_entry) {
            $log_entries_html .= $this->get_log_entry_html($log_entry, $this->timezone);
        }

        $log_entries_html .= '</ol>';

        return $log_entries_html;
    }
}
