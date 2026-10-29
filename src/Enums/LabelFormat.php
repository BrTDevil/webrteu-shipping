<?php

declare(strict_types=1);

namespace WeBRTeu\Shipping\Enums;

enum LabelFormat: string
{
    case A4Pdf = 'a4_pdf';
    case A6Pdf = 'a6_pdf';
    case Zpl = 'zpl';
}
