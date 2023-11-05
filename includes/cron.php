<?php

final class RY_WT_Cron
{
    public static function add_action()
    {
        add_action('ry_check_ntp_time', [__CLASS__, 'check_ntp_time']);
    }

    public static function check_ntp_time()
    {
        if (!function_exists('stream_socket_client')) {
            wp_clear_scheduled_hook('ry_check_ntp_time');
            return;
        }

        $ntp_time = 0;
        $ntp_server_url = apply_filters('ry_ntp_server_url', 'udp://time.google.com:123');
        $socket = stream_socket_client($ntp_server_url, $errno, $errstr);
        if ($socket) {
            fwrite($socket, chr(0x1B) . str_repeat(chr(0x00), 47));
            $response = fread($socket, 48);
            fclose($socket);
            if (!empty($response)) {
                $data = @unpack('N12', $response);
                if (is_array($data) && isset($data[9])) {
                    $ntp_time = sprintf('%u', $data[9]) - 2208988800;
                }
            }
        }

        if ($ntp_time > 0) {
            $time_diff = current_time('timestamp', true) - $ntp_time;
            RY_WT::update_option('ntp_time_error', abs($time_diff) > MINUTE_IN_SECONDS);
            return $time_diff;
        }
    }
}
