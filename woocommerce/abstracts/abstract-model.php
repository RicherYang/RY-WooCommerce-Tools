<?php

abstract class RY_WT_WC_Model
{
    public $testmode;

    protected $log_source = 'ry_woocommerce_tools';
    protected $log_enabled = false;

    private $log;

    public function log($message, $level = 'info')
    {
        if ($this->log_enabled || 'error' === $level) {
            if (empty($this->log)) {
                $this->log = wc_get_logger();
            }

            $this->log->log($level, $message, [
                'source' => $this->log_source,
                '_legacy' => true
            ]);
        }
    }
}
