<?php

namespace App\Entity;

enum UserGameRole: string
{
    case Host = 'host';
    case Participant = 'participant';
}
