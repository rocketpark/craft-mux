<?php

namespace rocketpark\mux\constants;

class UploadExtensions
{
    const VIDEO_EXTENSIONS = ['MP4', 'MOV', 'MKV', 'WEBM', 'M4V'];

    const AUDIO_EXTENSIONS = ['MP3', 'M4A', 'WAV', 'FLAC', 'AAC', 'OGG', 'OPUS'];

    /**
     * The default comma-separated extension list accepted by CP uploads (video + audio).
     * Kept as a literal constant (rather than joining VIDEO_EXTENSIONS/AUDIO_EXTENSIONS at
     * runtime) so it can be used directly as a property default value in Settings.php.
     */
    const DEFAULT_EXTENSIONS_STRING = 'MP4,MOV,MKV,WEBM,M4V,MP3,M4A,WAV,FLAC,AAC,OGG,OPUS';
}
