<?php

namespace App;

/**
 * This enum is used in a simplified permission system, where a higher permission level
 * implies also having the lower levels:
 * 'owner' implies also having 'write' and 'read'
 * 'write' implies also having 'read'.
 *
 * If a more complicated permissions system is required this could be refactored to a RBAC system.
 * With roles that map to on or more permissions.
 */
enum SharePermission
{
    case None;
    case Owner;
    case Write;
    case Read;
}
