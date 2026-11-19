<?php

declare(strict_types=1);

namespace MulerTech\FileBundle\Exception;

enum FileRejectionReason: string
{
    case TooLarge = 'too_large';
    case TypeNotAllowed = 'type_not_allowed';
}
