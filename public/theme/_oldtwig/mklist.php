<?php
/**
 * copyright            : (C) 2001-2010 Advanced Internet Designs Inc.
 * email                : forum@prohost.org
 * $Id: mklist.php.t 4898 2010-01-25 21:30:30Z naudefj $
 *
 * This program is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the
 * Free Software Foundation; version 2 of the License.
 **/

define('plain_form', 1);

if (_uid === '_uid') {
    exit('Sorry, you can not access this page.');
}

if (!empty($_GET['tp']) && $_GET['tp'] == 'OL:1') {
    $def_list_type = '1';
} else {
    $def_list_type = 'square';
}

F()->response->defListType = $def_list_type;