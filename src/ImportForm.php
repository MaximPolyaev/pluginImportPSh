<?php


namespace MaximCode\ImportPalmira;


use Module;
use PrestaShop\PrestaShop\Adapter\Entity\Configuration;
use PrestaShop\PrestaShop\Adapter\Entity\HelperForm;
use PrestaShop\PrestaShop\Adapter\Entity\Tools;
use PrestaShop\PrestaShop\Core\Import\EntityField\Provider\ProductFieldsProvider;
use Symfony\Component\VarDumper\VarDumper;

class ImportForm
{
    private const TRANS_DOMAIN = 'Modules.Importpalmira.Importform';

    private $module;
    private $translator;
    private $table;
    private $context;
    private $identifier;

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
            . '&configure=' . $this->module->name . '&tab_module=' . $this->module->tab . '&module_name=' . $this->module->name . '&step=1';

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
    private function getCfgOneForm()
    {
        $cfg[] = ['form' => [
            'title' => $this->module->getTranslator()->trans('Select file to import', [], self::TRANS_DOMAIN),
            'input' => [
                [
                    'type' => 'file',
                    'label' => $this->module->getTranslator()->trans('Choose file', [], self::TRANS_DOMAIN),
                    'desc' => $this->module->getTranslator()->trans('Allowed formats: .csv, .xml. Maximum file size: %s.', [ini_get('upload_max_filesize')], self::TRANS_DOMAIN),
                    'name' => 'IMPORTPALMIRA_FILE_IMPORT',
                    'col' => 9,
                ],
                [
                    'type' => 'history_files',
                    'label' => $this->module->getTranslator()->trans('Choose file from history', [], self::TRANS_DOMAIN),
                    'name' => 'IMPORTPALMIRA_TEST',
                    'col' => 6,
                    'btnlink' => $this->context->link->getAdminLink('AdminModules', false)
                        . '&configure=' . $this->module->name . '&tab_module=' . $this->module->tab . '&module_name=' . $this->module->name . "&token=" . Tools::getAdminTokenLite('AdminModules')
                ],
                [
                    'type' => 'switch',
                    'col' => 3,
                    'label' => $this->module->getTranslator()->trans('Save file after import', [], self::TRANS_DOMAIN),
                    'name' => 'IMPORTPALMIRA_FILE_IMPORT_SAVE',
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
            'line_hr' => true
        ]];

        $cfg[] = ['form' => [
            'title' => $this->module->getTranslator()->trans('Import settings for XML files', [], self::TRANS_DOMAIN),
            'input' => [
                [
                    'type' => 'text',
                    'col' => 3,
                    'label' => $this->module->getTranslator()->trans('Single name product', [], self::TRANS_DOMAIN),
                    'name' => 'IMPORTPALMIRA_XML_SINGLE_NAME',
                ]
            ],
            'line_hr' => true
        ]];

        $cfg[] = ['form' => [
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
                ],
                [
                    'type' => 'select',
                    'col' => 3,
                    'label' => $this->module->getTranslator()->trans('Select the link you want to use as a key', [], self::TRANS_DOMAIN),
                    'desc' => $this->module->getTranslator()->trans('If you select “no link”, then the identifier will be selected as the default key', [], self::TRANS_DOMAIN),
                    'name' => 'IMPORTPALMIRA_REFERENCE_KEY',
                    'options' => [
                        'query' => [
                            [
                                'id_option' => 1,
                                'name' => 'No reference'
                            ],
                            [
                                'id_option' => 2,
                                'name' => 'ID'
                            ],
                            [
                                'id_option' => 3,
                                'name' => 'Name*'
                            ],
                            [
                                'id_option' => 4,
                                'name' => 'EAN13'
                            ],
                            [
                                'id_option' => 5,
                                'name' => 'Reference #'
                            ],
                            [
                                'id_option' => 6,
                                'name' => 'UPC'
                            ],
                            [
                                'id_option' => 7,
                                'name' => 'ISBN'
                            ]
                        ],
                        'id' => 'id_option',
                        'name' => 'name'
                    ],
                ]
            ],
            'submit' => [
                'title' => $this->module->getTranslator()->trans('Next step', [], self::TRANS_DOMAIN)
            ]
        ]];

        return $cfg;
    }

    /**
     * Set values for the inputs.
     */
    private function getCfgOneFormValues()
    {
        return [
            'IMPORTPALMIRA_DELETE_PRODUCTS' => false,
            'IMPORTPALMIRA_ACCOUNT_EMAIL' => Configuration::get('IMPORTPALMIRA_ACCOUNT_EMAIL', 'contact@prestashop.com'),
            'IMPORTPALMIRA_ACCOUNT_PASSWORD' => Configuration::get('IMPORTPALMIRA_ACCOUNT_PASSWORD', null),
            'IMPORTPALMIRA_CSV_SEPARATOR' => ';',
            'IMPORTPALMIRA_FORCE_NUMBERING' => false,
            'IMPORTPALMIRA_REFERENCE_KEY' => 1,
            'IMPORTPALMIRA_XML_SINGLE_NAME' => 'offer',
            'IMPORTPALMIRA_FILE_IMPORT' => '',
            'IMPORTPALMIRA_FILE_IMPORT_SAVE' => false,
            'IMPORTPALMIRA_FILE_TEST' => false,
        ];
    }


    public function step_two()
    {
        $productFieldsProvider = new ProductFieldsProvider($this->translator);
        $productFieldsCollection = $productFieldsProvider->getCollection();

        $productArrImport = [
            'header' => ['ean13', 'name'],
            'products' => [
                [
                    'ean13' => '234324234',
                    'name' => 'pen'
                ],
                [
                    'ean13' => '234324234',
                    'name' => 'pen'
                ],
                [
                    'ean13' => '234324234',
                    'name' => 'pen'
                ],
                [
                    'ean13' => '234324234',
                    'name' => 'pen'
                ],
                [
                    'ean13' => '234324234',
                    'name' => 'pen'
                ],
                [
                    'ean13' => '234324234',
                    'name' => 'pen'
                ],
                [
                    'ean13' => '234324234',
                    'name' => 'pen'
                ],
                [
                    'ean13' => '234324234',
                    'name' => 'pen'
                ],
                [
                    'ean13' => '234324234',
                    'name' => 'pen'
                ],
                [
                    'ean13' => '234324234',
                    'name' => 'pen'
                ]
            ]
        ];

        VarDumper::dump(count($productArrImport['header']));

        $helper = new HelperForm();

        $helper->show_toolbar = false;

        $helper->table = $this->table;

        $helper->module = $this->module;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitImportpalmiraModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->module->name . '&tab_module=' . $this->module->tab . '&module_name=' . $this->module->name . '&step=2';

        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getCfgTwoFormValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm($this->getCfgTwoForm($productFieldsCollection, $productArrImport));
    }

    public function getCfgTwoFormValues()
    {
        return [
            'IMPORTPALMIRA_DELETE_PRODUCTS' => false,
            'IMPORTPALMIRA_ACCOUNT_EMAIL' => Configuration::get('IMPORTPALMIRA_ACCOUNT_EMAIL', 'contact@prestashop.com'),
            'IMPORTPALMIRA_ACCOUNT_PASSWORD' => Configuration::get('IMPORTPALMIRA_ACCOUNT_PASSWORD', null),
            'IMPORTPALMIRA_CSV_SEPARATOR' => ';',
            'IMPORTPALMIRA_FORCE_NUMBERING' => false,
            'IMPORTPALMIRA_REFERENCE_KEY' => 1,
            'IMPORTPALMIRA_XML_SINGLE_NAME' => 'offer',
            'IMPORTPALMIRA_FILE_IMPORT' => '',
            'IMPORTPALMIRA_FILE_IMPORT_SAVE' => false,
            'IMPORTPALMIRA_FILE_TEST' => false,
            'IMPORTPALMIRA_NAME_CFG' => false,
            'IMPORTPALMIRA_NUM_SKIP_ROWS' => 1,
        ];
    }

    /**
     * Create the structure of your form.
     * @param $productFieldsCollection
     * @param $productArrImport
     * @return mixed
     */
    private function getCfgTwoForm($productFieldsCollection, $productArrImport)
    {
        $cfg[] = ['form' => [
            'title' => $this->module->getTranslator()->trans('Совпадение ваших данных', [], self::TRANS_DOMAIN),
            'input' => [
                [
                    'type' => 'text_save',
                    'label' => $this->module->getTranslator()->trans('Save your data matching configuration', [], self::TRANS_DOMAIN),
                    'name' => 'IMPORTPALMIRA_NAME_CFG',
                    'col' => 6,
                ],
                [
                    'type' => 'history_files',
                    'label' => $this->module->getTranslator()->trans('Choose file from history', [], self::TRANS_DOMAIN),
                    'name' => 'IMPORTPALMIRA_TEST',
                    'col' => 6,
                    'btnlink' => $this->context->link->getAdminLink('AdminModules', false)
                        . '&configure=' . $this->module->name . '&tab_module=' . $this->module->tab . '&module_name=' . $this->module->name . "&token=" . Tools::getAdminTokenLite('AdminModules')
                ],
                [
                    'type' => 'text',
                    'col' => 3,
                    'label' => $this->module->getTranslator()->trans('Rows to skip', [], self::TRANS_DOMAIN),
                    'name' => 'IMPORTPALMIRA_NUM_SKIP_ROWS',
                ]
            ],
            'line_hr' => true
        ]];

        $cfg[] = ['form' => [
            'input' => [
                [
                    'type' => 'election_table',
                    'name' => 'IMPORTPALMIRA_TYPE_VALUE',
                    'col' => 12,
                    'product_fields' => $productFieldsCollection,
                    'product_arr_import' => $productArrImport
                ]
            ],
            'submit' => [
                'title' => $this->module->getTranslator()->trans('Next step', [], self::TRANS_DOMAIN)
            ]
        ]];

        return $cfg;
    }
}
