<?php

/**
 * @package         Convert Forms
 * @version         5.0.4 Free
 * 
 * @author          Tassos Marinos <info@tassos.gr>
 * @link            https://www.tassos.gr
 * @copyright       Copyright © 2025 Tassos All Rights Reserved
 * @license         GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
*/

namespace ConvertForms\Spam;

defined('_JEXEC') or die('Restricted access');

class Helper
{
    public static function error($error, $thrownBy = '')
    {
        if (empty($error))
        {
            return;
        }

        throw new \ConvertForms\Spam\Exception($error, $thrownBy);
    }
}