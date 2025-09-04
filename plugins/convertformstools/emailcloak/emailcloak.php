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

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\HTML\HTMLHelper;

/**
 * Email Cloak Protection Plugin for Convert Forms
 * 
 * This plugin addresses conflicts with Joomla's Email Cloak plugin which normally 
 * processes and cloaks all email addresses in content. When Email Cloak runs on 
 * Convert Forms content, it can break form layouts and functionality.
 * 
 * How it works:
 * 1. Temporarily replaces email addresses in form elements with unique hashes
 * 2. Allows Email Cloak plugin to process the content
 * 3. Restores the original email addresses after processing
 * 
 * Key features:
 * - Preserves email addresses in form inputs (value, placeholder attributes)
 * - Allows proper email cloaking in HTML content fields
 * - Maintains form functionality while keeping email protection
 * - Prevents layout breaking caused by unwanted email address cloaking
 */
class PlgConvertFormsToolsEmailCloak extends CMSPlugin
{
    /**
     * Prefix used when generating unique hashes for email addresses.
     * This makes the hashes easily identifiable in the HTML output.
     */
    private const EMAIL_HASH_PREFIX = 'cf_email_';

    /**
     * Special marker used to disable Joomla's Email Cloak plugin processing.
     * This is appended to form content to prevent unwanted email cloaking.
     */
    private const EMAIL_CLOAK_OFF = '{emailcloak=off}';

    /**
     * Stores email addresses and their corresponding hashes.
     * Used to temporarily replace email addresses with hashes and restore them later.
     *
     * @var    array
     */
    private static $emailHashes = [];

    /**
     * Application object
     *
     * @var    \Joomla\CMS\Application\CMSApplication
     */
    protected $app;

    /**
     * Protects email addresses found in the form HTML by replacing them with hashes.
     * This prevents the core emailcloak plugin from processing these emails.
     *
     * @param   string  &$html  The form HTML content
     * 
     * @return  void
     */
    public function onConvertFormsFormAfterRender(&$html)
    {
        if (!$this->shouldIrun())
        {
            return;
        }

        // Let's protect the emails in the form HTML
        // This is the first pass, where we protect the emails before any content plugins are applied.
        $html = $this->protectEmails($html);

        // After this event, Convert Forms triggers Joomla's onContentPrepare event, which activates content plugins, including EmailCloak.
        // If a content plugin runs before EmailCloak and introduces additional email addresses into the form, those emails will be cloaked.
        // To prevent this, we append {emailcloak=off} to disable the EmailCloak plugin for the form content.
        $html .= self::EMAIL_CLOAK_OFF;
    }

    /**
     * Additional protection pass after content plugins have run.
     * This is needed because content plugins might introduce new email addresses.
     *
     * @param   string  &$html  The form HTML content
     * 
     * @return  void
     */
    public function onConvertFormsFormAfterContentPrepare(&$html)
    {
        if (!$this->shouldIrun())
        {
            return;
        }

        $html = $this->protectEmails($html);

        // Finally, give the chance to Email Cloak plugin to cloak remaining (unprotected) email addresses.
        // We skip the Joomla Articles view, as the Content Prepare event is triggered by default.
        $dontRun = $this->app->input->get('option') == 'com_content' && $this->app->input->get('view') == 'article';

        if ($dontRun)
        {
            return;
        }

        $html = HTMLHelper::_('content.prepare', $html, null, 'convertforms-emailcloak');
    }

    /**
     * Restores the protected email addresses back to their original form
     * after all plugins have finished processing the page.
     *
     * @return  void
     */
    public function onAfterRender()
    {
        if (!$this->shouldIrun())
        {
            return;
        }

        $buffer = $this->app->getBody();

        $buffer = $this->revert($buffer);

        $this->app->setBody($buffer);
    }

    /**
     * Checks if the plugin should run based on various conditions:
     * - Must be in frontend
     * - Email cloak plugin must be enabled
     * - Convert Forms component must be installed
     *
     * @return  boolean
     */
    private function shouldIrun()
    {
        if (!$this->app->isClient('site'))
        {
            return;
        }

        if (!PluginHelper::isEnabled('content', 'emailcloak'))
        {
            return;
        }

        // Initialize Convert Forms Library
        if (!@include_once(JPATH_ADMINISTRATOR . '/components/com_convertforms/autoload.php'))
        {
            return;
        }

        return true;
    }

    /**
     * Protects email addresses by replacing them with unique hashes.
     * Only processes emails found in specific form elements and their attributes.
     * Uses DOM parsing for reliable HTML manipulation.
     *
     * @param   string  $html  The HTML content to process
     * 
     * @return  string  The processed HTML with protected email addresses
     */
    private function protectEmails($html)
    {
        // Quick check for @ character before expensive DOM parsing
        if (strpos($html, '@') === false)
        {
            return $html;
        }

        try
        {
            // Use DOMDocument to parse HTML
            $dom = new \DOMDocument();

            $html = iconv('UTF-8', 'UTF-8', $html);
            $html = mb_encode_numericentity($html, [0x80, 0x10FFFF, 0, 0x1FFFFF], 'UTF-8');

            // Prevent HTML5 errors
            libxml_use_internal_errors(true);
            $dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
            libxml_clear_errors();

            // Define elements to process
            $elements = [
                'input',
                'textarea',
                'option',
            ];

            foreach ($elements as $tag)
            {
                $nodes = $dom->getElementsByTagName($tag);
                
                foreach ($nodes as $node)
                {
                    // Process all attributes dynamically
                    if ($node->hasAttributes()) 
                    {
                        foreach ($node->attributes as $attribute) 
                        {
                            $value = $attribute->value;
                            $newValue = $this->replaceEmails($value);

                            if ($value !== $newValue)
                            {
                                $attribute->value = $newValue;
                            }
                        }
                    }

                    // Process text content for textarea and option elements
                    if (in_array($tag, ['textarea', 'option']))
                    {
                        $value = $node->nodeValue;
                        $newValue = $this->replaceEmails($value);

                        if ($value !== $newValue)
                        {
                            $node->nodeValue = $newValue;
                        }
                    }
                }
            }

            return $dom->saveHTML();

        } catch (\Throwable $th)
        {
            // Fallback to return original content if DOM parsing fails
            return $html;
        }
    }

    /**
     * Replaces email addresses with unique hashes using regex pattern matching.
     * Stores the email-hash pairs for later restoration.
     *
     * @param   string  $text  The text content to process
     * 
     * @return  string  The processed text with emails replaced by hashes
     */
    private function replaceEmails($text)
    {
        $pattern = '/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/';
        
        return preg_replace_callback($pattern, function($match)
        {
            $email = $match[0];
            $hash = self::EMAIL_HASH_PREFIX . md5($email . uniqid());
            
            // Store the hash and email pair
            self::$emailHashes[$hash] = $email;
            
            return $hash;
        }, $text);
    }

    /**
     * Restores all protected email addresses by replacing their hashes
     * with the original email addresses.
     *
     * @param   string  $html  The HTML content containing hashed emails
     * 
     * @return  string  The HTML content with restored email addresses
     */
    private function revert($html)
    {
        if (empty(self::$emailHashes))
        {
            return $html;
        }

        // Replace all hashes with their corresponding emails
        return str_replace(array_keys(self::$emailHashes), array_values(self::$emailHashes), $html);
    }
}