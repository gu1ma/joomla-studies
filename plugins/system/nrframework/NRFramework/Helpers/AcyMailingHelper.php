<?php
/**
 * @author          Tassos Marinos <info@tassos.gr>
 * @link            https://www.tassos.gr
 * @copyright       Copyright © 2024 Tassos All Rights Reserved
 * @license         GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

namespace Tassos\Framework\Helpers;

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;

class AcyMailingHelper
{
    /**
     * Load AcyMailing Helper.
     * 
     * @return  void
     */
    public static function load()
    {
        $ds = DIRECTORY_SEPARATOR;

		$path = implode($ds, [JPATH_ADMINISTRATOR, 'components', 'com_acym', 'helpers', 'helper.php']);
		if (!file_exists($path))
		{
			$path = implode($ds, [JPATH_ADMINISTRATOR, 'components', 'com_acymailing', 'helpers', 'helper.php']);
		}
		
		if (!@include_once($path))
		{
				throw new \Exception(Text::_('NR_ACYMAILING_HELPER_CLASS_ERROR'));
		}
    }

    /**
	 * Subscribe method for AcyMailing v6+
	 *
	 * @param   string  $email
	 * @param   array   $params
	 * @param   array   $lists
	 * @param   bool 	$doubleOptin
	 * @param   bool 	$triggerAcymNotifications   This triggers the Acym Configuration > Subscription > Advanced Configuration notifications
	 *
	 * @return  void
	 */
	public static function subscribe($email, $params, $lists, $doubleOptin = true, $triggerAcymNotifications = false)
	{
        self::load();

		// Create user object
		$user = new \stdClass();
		$user->email 	 = $email;
		$user->confirmed = $doubleOptin ? 0 : 1;

		$user_fields = array_change_key_case($params);

		$user->name = isset($user_fields['name']) ? $user_fields['name'] : '';
		$user->language = isset($user_fields['language']) ? $user_fields['language'] : '';

		// Load User Class
        $userClass = new \AcyMailing\Classes\UserClass();

		// Check if exists
		$existing_user = $userClass->getOneByEmail($email);

		if ($existing_user)
		{
			$user->id = $existing_user->id;
		} else
		{
			// Save user to database only if it's a new user.
			if (!$user->id = $userClass->save($user))
			{
				throw new \Exception(Text::_('PLG_CONVERTFORMS_ACYMAILING_CANT_CREATE_USER'));
			}
		}

		// Save Custom Fields
        $fieldClass = new \AcyMailing\Classes\FieldClass();

		// getAllfields was removed in 7.7.4 and we must use getAll moving forward.
		$acy_fields_method = method_exists($fieldClass, 'getAllfields') ? 'getAllfields' : 'getAll';
		$acy_fields = $fieldClass->$acy_fields_method();
		
		unset($user_fields['name']); // Name is already used during user creation.

		$fields_to_store = [];

		foreach ($user_fields as $paramKey => $paramValue)
		{
			// Check if paramKey it's a custom field
			$field_found = array_filter($acy_fields, function($field) use($paramKey) {
				return (strtolower($field->name) == $paramKey || $field->id == $paramKey);
			});

			if ($field_found)
			{
				// Get the 1st occurence
				$field = array_shift($field_found);

				// AcyMailing 6 needs field's ID to recognize a field.
				$fields_to_store[$field->id] = $paramValue;

				// $paramValue output: array(1) { [0]=> string(2) "gr" }
				// AcyMailing will get the key as the value instead of "gr"
				// We combine to remove the keys in order to keep the values
				if (is_array($paramValue))
				{
					$fields_to_store[$field->id] = array_combine($fields_to_store[$field->id], $fields_to_store[$field->id]);
				}
			}
		}

		$fields_to_store = array_filter($fields_to_store);

		if ($fields_to_store)
		{
			$fieldClass->store($user->id, $fields_to_store);
		}

		// Determine whether the user is already subscribed to the list.
		$userSubscriptions = $userClass->getUserSubscriptionById($user->id);

		if (is_scalar($lists))
		{
			$lists = [$lists];
		}

		// Check if the user is new
		$userIsNew = false;
		if (is_array($lists) && count($lists))
		{
			foreach ($lists as $list)
			{
				if ($userIsNew)
				{
					break;
				}
	
				$userIsNew = !(array_key_exists($list, $userSubscriptions) && $userSubscriptions[$list]->status == 1);
			}
		}

		// Subscribe user to AcyMailing lists
		$subscribed = $userClass->subscribe($user->id, $lists);

		if ($triggerAcymNotifications)
		{
			// Trigger "When a user submits the subscription form" notification
			$userClass->sendNotification($user->id, 'acy_notification_subform');

			/**
			 * If the user is new, it triggers:
			 * 
			 * - "When a new user is created, send an e-mail to" notification
			 * 
			 * If the user is existing, it triggers:
			 * 
			 * - "When a user changes their profile" notification
			 */
			$userClass->sendNotification(
				$user->id,
				!$existing_user ? 'acy_notification_create' : 'acy_notification_profile'
			);
		}

		$data = array_merge((array) $userClass->getOneByEmail($email), [
			'isNew' => $userIsNew,
		]);

		return $data;
    }
    
    /**
	 * Subscribe method for AcyMailing v5
	 *
	 * @param  array $lists
	 *
	 * @return void
	 */
	public static function subscribe_v5($email, $params, $lists, $doubleOptin = true)
	{
        self::load();

		// Create user object
		$user = new \stdClass();
		$user->email 	 = $email;
		$user->confirmed = $doubleOptin ? false : true;

		// Get Custrom Fields
    	$db = Factory::getDbo();

        $customFields = $db->setQuery(
            $db->getQuery(true)
                ->select($db->quoteName('namekey'))
                ->from($db->quoteName('#__acymailing_fields'))
        )->loadColumn();

		if (is_array($customFields) && count($customFields))
		{
			foreach ($params as $key => $param)
			{
				if (in_array($key, $customFields))
				{
					$user->$key = $param;
				}
			}
		}
		
		$acymailing = acymailing_get('class.subscriber');
		$userid = $acymailing->subid($email);

		// AcyMailing sends account confirmation e-mails even if the user exists, so we need
		// to run save() method only if the user actually is new.
		if (is_null($userid)) 
		{
			// Save user to database
			if (!$userid = $acymailing->save($user))
			{
				throw new \Exception(Text::_('PLG_CONVERTFORMS_ACYMAILING_CANT_CREATE_USER'));
			}
		}

		// Subscribe user to AcyMailing lists
		$lead = [];
		foreach($lists as $listId)
		{
			$lead[$listId] = ['status' => 1];
		}

		return $acymailing->saveSubscription($userid, $lead);
	}

    /**
     * Get the total number of subscribers of a specific list.
     * 
     * @param   int  $list  The list ID.
     * 
     * @return  int
     */
    public static function getListTotalSubscribers($list = null)
    {
        if (!$list)
        {
            return;
        }
        
        self::load();

        $listClass = new \AcyMailing\Classes\ListClass();

        return $listClass->getSubscribersCountByListId($list);
    }

    /**
     * Get all AcyMailing lists.
     * 
     * @return  array
     */
    public static function getAllLists()
    {
        self::load();
        
        $listClass = new \AcyMailing\Classes\ListClass();

        return $listClass->getAll();
    }
}