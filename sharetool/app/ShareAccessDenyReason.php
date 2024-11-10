<?php

namespace App;

enum ShareAccessDenyReason
{
    case MissingCredentials;
    case PublicTokenIncorrect;
    case InvalidSharePassword;
    case MissingPermission;
}
