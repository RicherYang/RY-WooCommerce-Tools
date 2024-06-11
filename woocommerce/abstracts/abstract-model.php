<?php

abstract class RY_WT_WC_Model
{
    protected $model_type = 'woocommerce_tools';
    protected $log_enabled = null;

    private $log;

    public function is_testmode(): bool
    {
        return 'yes' === RY_WT::get_option($this->model_type . '_testmode', 'no');
    }

    public function log($message, $level = WC_Log_Levels::INFO, $context = [])
    {
        if(null === $this->log_enabled) {
            $this->log_enabled = 'yes' === RY_WT::get_option($this->model_type . '_log', 'no');
        }

        if ($this->log_enabled || 'error' === $level) {
            if (empty($this->log)) {
                $this->log = wc_get_logger();
            }

            if(version_compare(WC_VERSION, '8.6', '<')) {
                $message .= ' CONTEXT: ' . wp_json_encode($context);
                $this->log->log($level, $message, [
                    'source' => 'ry_' . $this->model_type,
                ]);
            } else {
                $context['source'] = 'ry_' . $this->model_type;
                $this->log->log($level, $message, $context);
            }
        }
    }
}
