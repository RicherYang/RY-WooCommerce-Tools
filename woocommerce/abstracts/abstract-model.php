<?php

abstract class RY_WT_WC_Model
{
    public $testmode;

    protected $log_source = 'ry_woocommerce_tools';
    protected $log_enabled = false;

    private $log;

    public function log($message, $level = WC_Log_Levels::INFO, $context = [])
    {
        if ($this->log_enabled || 'error' === $level) {
            if (empty($this->log)) {
                $this->log = wc_get_logger();
            }

            if(version_compare(WC_VERSION, '8.6', '<')) {
                $message .= ' CONTEXT: ' . wp_json_encode($context);
                $this->log->log($level, $message, [
                    'source' => $this->log_source
                ]);
            } else {
                $context['source'] = $this->log_source;
                $this->log->log($level, $message, $context);
            }
        }
    }
}
