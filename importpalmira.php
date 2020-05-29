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

require_once _PS_MODULE_DIR_ . 'importpalmira/vendor/autoload.php';

use MaximCode\ImportPalmira\ImportForm as ImportForm;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\VarDumper\VarDumper;

class ImportPalmira extends Module
{
    const _IMPORT_FILES_DIR_ = _PS_MODULE_DIR_ . 'importpalmira/importfiles/';
    private $form;
    private $step;
    private $token;
    private $url;

    public function __construct()
    {
        $this->name = 'importpalmira';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'Maxim Polyaeiv';
        $this->need_instance = 1;

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->trans(
            'Import Palmira',
            [],
            'Modules.Importpalmira.Importpalmira'
        );
        $this->description = $this->trans(
            'Import products of CSV or XML files',
            [],
            'Modules.Importpalmira.Importpalmira'
        );

        $this->ps_versions_compliancy = array('min' => '1.7', 'max' => _PS_VERSION_);

        $this->token = Tools::getAdminTokenLite('AdminModules');

        $this->url = $this->context->link->getAdminLink('AdminModules', false);
        $this->url .= '&token=' . $this->token;
        $this->url .= '&configure=' . $this->name;
        $this->url .= '&tab_module=' . $this->tab;
        $this->url .= '&module_name=' . $this->name;


        $this->step = Tools::getValue('step') ? Tools::getValue('step') : 0;
        $this->form = new ImportForm($this, $this->table, $this->context, $this->identifier, $this->step, $this->token);
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        Configuration::updateValue('IMPORTPALMIRA_DELETE_PRODUCTS', false);

        return parent::install();
    }

    public function uninstall()
    {
        Configuration::deleteByName('IMPORTPALMIRA_DELETE_PRODUCTS');

        return parent::uninstall();
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        /**
         * Delete file from history import files
         */
        if ((bool)Tools::getValue('delete_file_import') && (bool)($file_del = Tools::getValue('file_import_delete_name'))) {
            Tools::deleteFile(self::_IMPORT_FILES_DIR_ . $file_del);
            Tools::redirectAdmin($this->url);
        }

        /**
         * If values have been submitted in the form, process.
         * @todo correct select import file from history
         */
        if ((bool)Tools::isSubmit('submitImportpalmiraModule')) {
            if (isset($_FILES['IMPORTPALMIRA_FILE_IMPORT']) &&
                !empty($_FILES['IMPORTPALMIRA_FILE_IMPORT']) &&
                (bool)Tools::getValue('IMPORTPALMIRA_FILE_IMPORT')
            ) {
                $errors = [];
                $file = $_FILES['IMPORTPALMIRA_FILE_IMPORT'];
                $file_name = Tools::getValue('IMPORTPALMIRA_FILE_IMPORT');
                $file_ext = $this->getFileExt($file_name);
                $file_tmp = $file['tmp_name'];

                $fs = new Filesystem();
                if ($fs->exists(self::_IMPORT_FILES_DIR_ . $file_name)) {
                    $exist_file = new File(self::_IMPORT_FILES_DIR_ . $file_name);
                    if($exist_file->getSize() === $file['size']) {
                        $errors[] = 'Upload file error: file is exist';
                    }
                }

                if ($file_ext !== 'csv' && $file_ext !== 'xml') {
                    $errors[] = "This file type (${file_ext}) is not supported";
                }

                if(empty($errors)) {
                    $current_file = new File($file_tmp);
                    $current_file->move(self::_IMPORT_FILES_DIR_, $file_name);

                    if ($fs->exists(self::_IMPORT_FILES_DIR_ . $file_name)) {
                        VarDumper::dump('File has been successfully uploaded');
                    }
                } else {
                    VarDumper::dump($errors);
                }
            }
        }

        $output = $this->renderView();

        $this->context->controller->addJS($this->_path . 'views/js/back.js');
        $this->context->controller->addCSS($this->_path . 'views/css/back.css');

        return $output;
    }

    public function renderView()
    {
        if (Tools::getValue('controller') != 'AdminModules' && Tools::getValue('configure') != $this->name) {
            return;
        }

        $this->context->smarty->assign('import_step', $this->step);
        if (+$this->step === 0 || +$this->step === 1) {
            $formView = $this->form->getView();
            $this->context->smarty->assign('form_view', $formView);
            return $this->display(__FILE__, 'forms.tpl');
        } elseif (+$this->step === 2) {
            return $this->display(__FILE__, 'final.tpl');
        }

        return new RedirectResponse($this->url .  '&token=' . $this->token . '&step=0');
    }

    public function isUsingNewTranslationSystem()
    {
        return true;
    }

    private function getFileExt($file_name) {
        $file_ext = explode('.', $file_name);
        $file_ext = strtolower(end($file_ext));
        return $file_ext;
    }
}
