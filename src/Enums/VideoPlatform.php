<?php declare(strict_types=1);

namespace Hihaho\Sdk\Enums;

enum VideoPlatform: string
{
    case BlueBillywig = 'blue_billywig';
    case ExternalUrl = 'external_url';
    case JwPlayer = 'jw_player';
    case Kaltura = 'kaltura';
    case Kinescope = 'kinescope';
    case Mediasite = 'mediasite';
    case Mux = 'mux';
    case Panopto = 'panopto';
    case Qumu = 'qumu';
    case UbiCast = 'ubi_cast';
    case Vimeo = 'vimeo';
    case Youtube = 'youtube';
}
