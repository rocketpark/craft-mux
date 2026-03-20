<?php

namespace rocketpark\mux\constants;

class StaticRenditions
{
    /**
     * Resolutions that are incompatible with "highest".
     * Used for mutual exclusivity enforcement in UI and controller.
     */
    const SPECIFIC_RESOLUTIONS = [
        '2160p', '1440p', '1080p', '720p', '540p', '480p', '360p', '270p',
    ];

    /**
     * Nominal max height in pixels per fixed resolution tier (upscale validation vs source video).
     * Keys match {@see SPECIFIC_RESOLUTIONS}. Excludes "highest" and "audio-only".
     */
    const RESOLUTION_MAX_HEIGHTS = [
        '2160p' => 2160,
        '1440p' => 1440,
        '1080p' => 1080,
        '720p'  => 720,
        '540p'  => 540,
        '480p'  => 480,
        '360p'  => 360,
        '270p'  => 270,
    ];

    /**
     * All rendition options: resolution value => display label.
     * Ordered for display (Highest first, audio-only last).
     */
    const ALL_RENDITION_OPTIONS = [
        'highest'    => 'Highest',
        '2160p'      => '2160p',
        '1440p'      => '1440p',
        '1080p'      => '1080p',
        '720p'       => '720p',
        '540p'       => '540p',
        '480p'       => '480p',
        '360p'       => '360p',
        '270p'       => '270p',
        'audio-only' => 'Audio Only',
    ];

    /**
     * Returns options formatted for Craft's checkboxGroupField macro:
     * [['value' => 'highest', 'label' => 'Highest'], ...]
     */
    public static function getCheckboxOptions(): array
    {
        $options = [];
        foreach (self::ALL_RENDITION_OPTIONS as $value => $label) {
            $options[] = ['value' => $value, 'label' => $label];
        }
        return $options;
    }
}
