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

namespace MaximCode\ImportPalmira;

use PrestaShop\PrestaShop\Adapter\Entity\HelperForm;
use PrestaShop\PrestaShop\Core\Foundation\Filesystem\FileSystem;
use PrestaShop\PrestaShop\Core\Import\EntityField\Provider\ProductFieldsProvider;
use Symfony\Component\Finder\Finder;
use Symfony\Component\VarDumper\VarDumper;

class ImportForm
{
    private $module;
    private $translator;
    private $context;
    private $identifier;
    private $step;

    /**
     * ImportForm constructor.
     * @param $module
     */
    public function __construct($module)
    {
        $this->module = $module;
        $this->translator = $this->module->getTranslator();
        $this->context = $this->module->getContext();
        $this->identifier = $this->module->getIdentifier();
        $this->step = $this->module->step;
    }

    /**
     * Create the form that will be displayed in the configuration of your module.
     */
    public function getView()
    {
        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->module = $this->module;
        $helper->submit_action = 'submitImportpalmiraModule';
        $helper->show_cancel_button = true;

        $helper->currentIndex = $this->module->url . '&step=' . ($this->step + 1);
        $helper->tpl_vars = array(
            'fields_value' => $this->getCfgValues(),
        );

        return $helper->generateForm($this->step === 0 ? $this->getCfgOneForm() : $this->getCfgTwoForm());
    }

    /**
     * Set values for the inputs.
     */
    private function getCfgValues()
    {
        return [
            'IMPORTPALMIRA_DELETE_PRODUCTS' => false,
            'IMPORTPALMIRA_CSV_SEPARATOR' => ';',
            'IMPORTPALMIRA_FORCE_NUMBERING' => false,
            'IMPORTPALMIRA_REFERENCE_KEY' => 1,
            'IMPORTPALMIRA_XML_SINGLE_NAME' => 'offer',
            'IMPORTPALMIRA_FILE_IMPORT' => '',
            'IMPORTPALMIRA_FILE_IMPORT_SAVE' => false,
            'IMPORTPALMIRA_NAME_CFG' => '',
            'IMPORTPALMIRA_NUM_SKIP_ROWS' => 1,
            'IMPORTPALMIRA_IMPORT_FILE_PATH' => $this->module->import_file_path ?? 'error'
        ];
    }

    /**
     * Create the structure of your form.
     */
    private function getCfgOneForm()
    {
        $cfg = [];

        $import_files_name = $this->getImportFilesName();

        $cfg[] = ['form' => [
            'title' => $this->translate('Select file to import'),
            'input' => [
                [
                    'type' => 'file',
                    'label' => $this->translate('Choose file'),
                    'desc' => $this->translate(
                        'Allowed formats: .csv, .xml. Maximum file size: %s.',
                        [ini_get('upload_max_filesize')]
                    ),
                    'name' => 'IMPORTPALMIRA_FILE_IMPORT',
                    'col' => 9,
                ],
                [
                    'type' => 'history_files',
                    'label' => !empty($import_files_name) ? $this->translate('Choose file from history') : '',
                    'name' => 'IMPORTPALMIRA_FILE_ID',
                    'col' => 6,
                    'btnlink' => $this->module->url . "&token=" . $this->module->token,
                    'files_name' => $import_files_name
                ],
                $this->getSwitchCfg(
                    'IMPORTPALMIRA_FILE_IMPORT_SAVE',
                    'Save file after import'
                ),
            ],
            'line_hr' => true
        ]];

        $cfg[] = ['form' => [
            'title' => $this->translate('Import settings for XML files'),
            'input' => [
                $this->getInputTextCfg(
                    'IMPORTPALMIRA_XML_SINGLE_NAME',
                    'Single name product'
                )
            ],
            'line_hr' => true
        ]];

        $cfg[] = ['form' => [
            'title' => $this->translate('Import settings for СSV files'),
            'input' => [
                $this->getInputTextCfg(
                    'IMPORTPALMIRA_CSV_SEPARATOR',
                    'Field separator',
                    'e.g. vendorCode; price; ean13'
                )
            ],
            'line_hr' => true
        ]];

        $cfg[] = ['form' => [
            'title' => $this->translate('Other import settings'),
            'input' => [
                $this->getSwitchCfg(
                    'IMPORTPALMIRA_DELETE_PRODUCTS',
                    'Delete all products before import'
                ),
                $this->getSwitchCfg(
                    'IMPORTPALMIRA_FORCE_NUMBERING',
                    'Force ALL ID numbers',
                    'If you enable this option, your imported items ID number will be used as-is. If you do
                            not enable this option, the imported ID numbers will be ignored, and PrestaShop will
                            instead create auto-incremented ID numbers for all the imported items.'
                ),
                [
                    'type' => 'select',
                    'col' => 3,
                    'label' => $this->translate('Select the link you want to use as a key'),
                    'desc' => $this->translate(
                        'If you select “no reference”, then the identifier will be selected as the default key'
                    ),
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
                'title' => $this->translate('Next step')
            ]
        ]];

        return $cfg;
    }

    /**
     * Create the structure of your form.
     * @return mixed
     */
    private function getCfgTwoForm()
    {
        $productFieldsProvider = new ProductFieldsProvider($this->translator);
        $productFieldsCollection = $productFieldsProvider->getCollection();

        $productArrImport = $this->getImportMatchingData();

        $cfg = [];

        $cfg[] = ['form' => [
            'title' => $this->translate('Match your data'),
            'description' => $this->translate(
                'Please match each column of your source file to one of the destination columns.'
            ),
            'input' => [
                [
                    'type' => 'hidden',
                    'name' => 'IMPORTPALMIRA_IMPORT_FILE_PATH'
                ],
                [
                    'type' => 'text_save',
                    'label' => $this->translate('Save your data matching configuration'),
                    'name' => 'IMPORTPALMIRA_NAME_CFG',
                    'col' => 6,
                ],
                [
                    'type' => 'history_files',
                    'label' => $this->translate('Choose file from history'),
                    'name' => 'IMPORTPALMIRA_TEST',
                    'col' => 6,
                    'btnlink' => $this->module->url . "&token=" . $this->module->token
                ],
                $this->getInputTextCfg(
                    'IMPORTPALMIRA_NUM_SKIP_ROWS',
                    'Rows to skip'
                )
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
                'title' => $this->translate('Next step')
            ]
        ]];

        return $cfg;
    }

    private function getInputTextCfg($name, $label = '', $desc = '')
    {
        $cfg = [
            'type' => 'text',
            'col' => 3,
            'name' => $name
        ];

        if ($label !== '') {
            $cfg['label'] = $this->translate($label);
        }

        if ($desc !== '') {
            $cfg['desc'] = $this->translate($desc);
        }

        return $cfg;
    }

    private function getSwitchCfg($name, $label = '', $desc = '')
    {
        $cfg = [
            'type' => 'switch',
            'col' => 3,
            'name' => $name,
            'is_bool' => true,
            'values' => [
                [
                    'id' => 'active_on',
                    'value' => true,
                    'label' => $this->translate('Enabled')
                ],
                [
                    'id' => 'active_off',
                    'value' => false,
                    'label' => $this->translate('Disabled')
                ]
            ]
        ];

        if ($label !== '') {
            $cfg['label'] = $this->translate($label);
        }

        if ($desc !== '') {
            $cfg['desc'] = $this->translate($desc);
        }

        return $cfg;
    }

    private function translate($string, $arr = [])
    {
        return $this->module->getTranslator()->trans($string, $arr, 'Modules.Importpalmira.Importpalmira');
    }


    /**
     * Get array files name from "importfiles" folder
     * @return array
     */
    private function getImportFilesName(): array
    {
        $import_dir = \ImportPalmira::_IMPORT_FILES_DIR_;
        $finder = new Finder();
        $finder->files()->in($import_dir);

        $files_names = [];

        if ($finder->hasResults()) {
            foreach ($finder as $file) {
                $file_ext = $file->getExtension();

                if ($file_ext === 'csv' || $file_ext === 'xml') {
                    $files_names[] = $file->getFilename();
                }
            }
        }

        return $files_names;
    }

    /**
     * Get data matching types from import file
     * @return array|bool
     */
    private function getImportMatchingData()
    {
        $file_path = $this->module->getImportFilePath();
        $fileReader = (new FileReader($file_path))->init();
        $headers = $fileReader->getHeaders();
        if (!$headers) {
            return false;
        }

        $products = $fileReader->getData(0, 7);
        if (!$products) {
            return false;
        }

        return [
            'header' => $headers,
            'products' => $products
        ];
    }
}
