<?php
class MaB_Core_Activator {
    public static function activate() {
        if ( ! get_option( 'mab_hosters' ) ) {
            $defaults = [
                [
                    'name' => 'nitroflare.com',
                    'dead_messages' => 'Sorry, this file is no longer available,has been removed',
                    'bg_color' => '#006ca2',
                    'text_color' => '#ffffff',
                ],
                [
                    'name' => 'rapidgator.net',
                    'dead_messages' => 'This file has been removed,File not found',
                    'bg_color' => '#ff801a',
                    'text_color' => '#ffffff',
                ],
                [
                    'name' => 'turbobit.net',
                    'dead_messages' => 'File has been removed due to copyright,File not found',
                    'bg_color' => '#f8631c',
                    'text_color' => '#ffffff',
                ],
                [
                    'name' => 'ddownload.com',
                    'dead_messages' => 'File has been deleted',
                    'bg_color' => '#153fa6',
                    'text_color' => '#ffffff',
                ],
                [
                    'name' => 'uploadgig.com',
                    'dead_messages' => 'file not found,has been removed,file has been deleted',
                    'bg_color' => '#4284A4',
                    'text_color' => '#ffffff',
                ],
            ];
            update_option( 'mab_hosters', $defaults );
        }
    }
}