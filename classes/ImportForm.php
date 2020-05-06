<?php
/**
 * 2007-2020 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2020 PrestaShop SA
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class ImportForm
{
    private const TRANS_DOMAIN = 'Modules.Importpalmira.Importform';

    private $module;
    private $translator;
    private $table;
    private $context;
    private $identifier;

    /**
     * ImportForm constructor.
     * @param Module $module
     * @param $table
     * @param $context
     * @param $identifier
     */
    public function __construct(Module $module, $table, $context, $identifier)
    {
        $this->module = $module;
        $this->translator = $this->module->getTranslator();
        $this->table = $table;
        $this->context = $context;
        $this->identifier = $identifier;
    }

    /**
     * Create the form that will be displayed in the configuration of your module.
     */
    public function step_one()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;

        $helper->table = $this->table;

        $helper->module = $this->module;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitImportpalmiraModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->module->name . '&tab_module=' . $this->module->tab . '&module_name=' . $this->module->name;

        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getCfgOneFormValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm($this->getCfgOneForm());
    }

    /**
     * Create the structure of your form.
     */
    private function getCfgOneForm() {
        return [
            0 => ['form' => [
                'title' => $this->module->getTranslator()->trans('Select file to import', [],self::TRANS_DOMAIN),
                'input' => [
                    [
                        'type' => 'text',
                        'col' => 3,
                        'label' => $this->module->getTranslator()->trans('Choose file', [], self::TRANS_DOMAIN),
                        'name' => 'IMPORTPALMIRA_CSV_SEPARATOR'
                    ]
                ],
                'line_hr' => true
            ]],
            1 => ['form' => [
                'title' => $this->module->getTranslator()->trans('Import settings for CSV files', [], self::TRANS_DOMAIN),
                'input' => [
                    [
                        'type' => 'text',
                        'col' => 3,
                        'label' => $this->module->getTranslator()->trans('Field separator', [], self::TRANS_DOMAIN),
                        'name' => 'IMPORTPALMIRA_CSV_SEPARATOR',
                        'desc' => $this->module->getTranslator()->trans('e.g. vendorCode; price; ean13', [], self::TRANS_DOMAIN)
                    ]
                ],
                'line_hr' => true
            ]],
            2 => ['form' => [
                'title' => $this->module->getTranslator()->trans('Other import settings', [], self::TRANS_DOMAIN),
                'input' => [
                    [
                        'type' => 'switch',
                        'label' => $this->module->getTranslator()->trans('Delete all products before import', [], self::TRANS_DOMAIN),
                        'name' => 'IMPORTPALMIRA_DELETE_PRODUCTS',
                        'is_bool' => true,
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->module->getTranslator()->trans('Enabled', [], self::TRANS_DOMAIN)
                            ],
                            [
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->module->getTranslator()->trans('Disabled', [], self::TRANS_DOMAIN)
                            ]
                        ]
                    ],
                    [
                        'type' => 'switch',
                        'col' => 3,
                        'label' => $this->module->getTranslator()->trans('Force ALL ID numbers', [], self::TRANS_DOMAIN),
                        'name' => 'IMPORTPALMIRA_FORCE_NUMBERING',
                        'desc' => $this->module->getTranslator()->trans(
                            'If you enable this option, your imported items ID number will be used as-is. If you do
                            not enable this option, the imported ID numbers will be ignored, and PrestaShop will instead
                            create auto-incremented ID numbers for all the imported items.',
                            [],
                            self::TRANS_DOMAIN),
                        'is_bool' => true,
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->module->getTranslator()->trans('Enabled', [], self::TRANS_DOMAIN)
                            ],
                            [
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->module->getTranslator()->trans('Disabled', [], self::TRANS_DOMAIN)
                            ]
                        ]
                    ]
                ],
                'submit' => [
                    'title' => $this->module->getTranslator()->trans('Save', [], self::TRANS_DOMAIN)
                ]
            ]]
        ];
    }

    /**
     * Set values for the inputs.
     */
    private function getCfgOneFormValues() {
        return [
            'IMPORTPALMIRA_DELETE_PRODUCTS' => false,
            'IMPORTPALMIRA_ACCOUNT_EMAIL' => Configuration::get('IMPORTPALMIRA_ACCOUNT_EMAIL', 'contact@prestashop.com'),
            'IMPORTPALMIRA_ACCOUNT_PASSWORD' => Configuration::get('IMPORTPALMIRA_ACCOUNT_PASSWORD', null),
            'IMPORTPALMIRA_CSV_SEPARATOR' => ';',
            'IMPORTPALMIRA_FORCE_NUMBERING' => false
        ];
    }
}
