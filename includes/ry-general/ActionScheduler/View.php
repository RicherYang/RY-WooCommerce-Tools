<?php

namespace RY\General\ActionScheduler;

defined('ABSPATH') or exit;

use RY\General\ActionScheduler\ListTable;

final class View extends \ActionScheduler_AdminView
{
    protected function get_list_table()
    {
        if (null === $this->list_table) {
            $this->list_table = new ListTable(\ActionScheduler::store(), \ActionScheduler::logger(), \ActionScheduler::runner());
            $this->list_table->process_actions();
        }

        return $this->list_table;
    }
}
