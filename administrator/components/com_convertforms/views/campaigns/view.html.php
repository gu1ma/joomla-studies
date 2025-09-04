<?php

/**
 * @package         Convert Forms
 * @version         5.0.4 Free
 * 
 * @author          Tassos Marinos <info@tassos.gr>
 * @link            https://www.tassos.gr
 * @copyright       Copyright © 2024 Tassos All Rights Reserved
 * @license         GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
*/
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\HTML\Helpers\Sidebar;
use Joomla\CMS\Language\Text;

/**
 * Campaigns View
 */
class ConvertFormsViewCampaigns extends HtmlView
{
    /**
     * Items view display method
     * 
     * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
     * 
     * @return  mixed  A string if successful, otherwise a JError object.
     */
    public function display($tpl = null) 
    {
		// Access check.
		ConvertForms\Helper::authorise('convertforms.campaigns.manage', true);

        $this->items         = $this->get('Items');
        $this->state         = $this->get('State');
        $this->pagination    = $this->get('Pagination');
        $this->filterForm    = $this->get('FilterForm');
        $this->activeFilters = $this->get('ActiveFilters');
        $this->config        = \Joomla\CMS\Component\ComponentHelper::getParams('com_convertforms');

        ConvertForms\Helper::addSubmenu('campaigns');
        $this->sidebar = Sidebar::render();

        // Trigger all ConvertForms plugins
        PluginHelper::importPlugin('convertforms');
        Factory::getApplication()->triggerEvent('onConvertFormsServiceName');

        // Deprecation notice
        Factory::getApplication()->enqueueMessage(Text::_('COM_CONVERTFORMS_CAMPAIGNS_LIST_DEPRECATION_MSG'), 'warning');
        
        // Check for errors.
        if (!is_null($this->get('Errors')) && count($errors = $this->get('Errors')))
        {
            Factory::getApplication()->enqueueMessage(implode("\n", $errors), 'error');
            return false;
        }

        // Set the toolbar
        $this->addToolBar();

        // Display the template
        parent::display($tpl);
    }

    /**
     *  Add Toolbar to layout
     */
    protected function addToolBar() 
    {
        $canDo = ConvertForms\Helper::getActions();

        $title = Text::_('COM_CONVERTFORMS') . ": " . Text::_('COM_CONVERTFORMS_CAMPAIGNS');
        Factory::getDocument()->setTitle($title);
        ToolbarHelper::title($title);

        $toolbar = Toolbar::getInstance('toolbar');

        if ($canDo->get('core.create'))
        {
            $toolbar->addNew('campaign.add');
        }

        $dropdown = $toolbar->dropdownButton('status-group')
            ->text('JTOOLBAR_CHANGE_STATUS')
            ->toggleSplit(false)
            ->icon('fas fa-ellipsis-h')
            ->buttonClass('btn btn-action')
            ->listCheck(true);

        $childBar = $dropdown->getChildToolbar();
        
        if ($canDo->get('core.edit.state'))
        {
            $childBar->publish('campaigns.publish')->listCheck(true);
            $childBar->unpublish('campaigns.unpublish')->listCheck(true);
            $childBar->standardButton('copy')->text('JTOOLBAR_DUPLICATE')->task('campaigns.duplicate')->listCheck(true);
            $childBar->trash('campaigns.trash')->listCheck(true);
        }

        if ($this->state->get('filter.state') == -2)
        {
            $toolbar->delete('campaigns.delete')
                ->text('JTOOLBAR_EMPTY_TRASH')
                ->message('JGLOBAL_CONFIRM_DELETE')
                ->listCheck(true);
        }

        if ($canDo->get('core.admin'))
        {
            $toolbar->preferences('com_convertforms');
        }

        $toolbar->help('JHELP', false, 'http://www.tassos.gr/joomla-extensions/convert-forms/docs');
    }
}