<?php
/**
 * copyright            : (C) 2001-2013 Advanced Internet Designs Inc.
 * email                : forum@prohost.org
 * $Id: coppa.php.t 5737 2013-11-10 18:14:16Z naudefj $
 *
 * This program is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the
 * Free Software Foundation; version 2 of the License.
 **/

if (_uid === '_uid') {
    exit('Sorry, you can not access this page.');
}
$TITLE_EXTRA = ': COPPA Confirmation';

// Change this line if you want to increase the minimum age.
$coppa = strtotime('-13 years');

F()->response->coppa_date = strftime('%B %d, %Y', $coppa);
