<?php declare(strict_types=1);

namespace Hihaho\Sdk\Enums;

enum ScrollDirection: string
{
    case Rtl = 'rtl';
    case Ltr = 'ltr';
    case Ttb = 'ttb';
    case Btt = 'btt';
}
