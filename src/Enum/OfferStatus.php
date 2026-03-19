<?php
declare(strict_types=1);

namespace App\Enum;

enum OfferStatus: string
{
    case CALL = '1';
    case CALLWORK = '2';
    case PREOFFER = '3';
    case ON_SIDE_VIEW = '4';
    case OFFER = '5';
    case WAIT_O_YES = '6';
    case PRE_INVOICE = '7';
    case WAIT_PI_YES = '8';
    case APPOINTMENT = '9';
    case PROJECT_WORK = '10';
    case CHECK = '11';
    case INVOICE = '12';
    case WAIT_I_YES = '13';
    case FINISHED = '14';
    case ARCHIVED = '15';
}