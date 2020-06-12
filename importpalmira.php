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

use MaximCode\ImportPalmira\FileUploader;
use MaximCode\ImportPalmira\Flash;
use MaximCode\ImportPalmira\ImportForm;
use MaximCode\ImportPalmira\ImportHelper;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\VarDumper\VarDumper;

class ImportPalmira extends Module
{
    const _IMPORT_FILES_DIR_ = _PS_MODULE_DIR_ . 'importpalmira/importfiles/';
    private $form;
    private $flash;
    public $url;
    public $token;
    public $step;
    public $import_file_path;

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
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        return parent::install();
    }

    public function uninstall()
    {
        return parent::uninstall();
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        $this->token = Tools::getAdminTokenLite('AdminModules');

        $this->url = $this->context->link->getAdminLink('AdminModules', false);
        $this->url .= '&configure=' . $this->name;
        $this->url .= '&token=' . $this->token;

        $this->flash = Flash::getInstance();

        $this->step = Tools::getValue('step') ? Tools::getValue('step') : 0;
        $this->form = new ImportForm($this);

        /**
         * Delete file from history import files
         */
        if ((bool)Tools::getValue('delete_file_import') && (bool)($file_del = Tools::getValue('file_import_delete_name'))) {
            Tools::deleteFile(self::_IMPORT_FILES_DIR_ . $file_del);
            Tools::redirectAdmin($this->url);
        } else if ((bool)Tools::getValue('delete_file_import') && (bool)Tools::getValue('delete_file_import_all')) {
            $files_name = FileUploader::getImportFilesName();
            foreach ($files_name as $name) {
                Tools::deleteFile(self::_IMPORT_FILES_DIR_ . $name);
            }
            Tools::redirectAdmin($this->url);
        }

        switch (+$this->step) {
            case 0:
                $this->renderStepOne();
                break;
            case 1:
                $this->renderStepTwo();
                break;
            case 2:
                $this->renderStepThree();
                break;
            default:
                die;
        }

        $output = $this->renderView();

        $this->context->controller->addJS($this->_path . 'views/js/back.js');
        $this->context->controller->addCSS($this->_path . 'views/css/back.css');

        return $output;
    }

    public function renderStepOne()
    {
        ImportHelper::cleanUnsavedFiles();

        $isError = $this->flash->get('step_one_is_error');
        if ($isError) {
            $errors = $this->flash->get('file_load_errors');

            $this->context->smarty->assign('step_one_is_error', true);
            $this->context->smarty->assign('step_one_errors', $errors);
        }
    }

    public function renderStepTwo()
    {
        $fileUploader = new FileUploader($this);
        $this->import_file_path = $fileUploader->getPath();
        if ($this->import_file_path === 'error') {
            $errors = $fileUploader->getErrors();
            if (empty($errors)) {
                $errors[] = 'Возможно вы забыли загрузить файл';
            }

            $this->flash->add('file_load_errors', $errors);
            $this->flash->add('step_one_is_error', 1);
            Tools::redirectAdmin($this->url);
        }

        if(!Tools::getValue('IMPORTPALMIRA_FILE_IMPORT_SAVE')) {
            ImportHelper::addUnsavedFile($this->import_file_path);
        }

        $file_name = $fileUploader->getFileName();
        $this->context->smarty->assign('import_file_name', $file_name);
        $this->context->smarty->assign('file_success_msg', $fileUploader->getSuccess());
        $this->context->controller->addJS($this->_path . 'views/js/matching.js');
    }

    public function renderStepThree()
    {
        $is_delete_products = Tools::getValue('IMPORTPALMIRA_DELETE_PRODUCTS');
        Media::addJsDef([
            'importpalmira_type_value' => Tools::getValue('IMPORTPALMIRA_TYPE_VALUE'),
            'importpalmira_delete_products' => $is_delete_products,
            'importpalmira_force_id' => Tools::getValue('IMPORTPALMIRA_FORCE_ID'),
            'importpalmira_msg_delete_products' => 'Удаление товаров...',
            'importpalmira_msg_import_products' => 'Импорт товаров...'
        ]);
        $this->context->smarty->assign('is_delete_products', $is_delete_products);
        $this->context->controller->addJS($this->_path . 'views/js/upload.js');
    }

    public function renderView()
    {
        if (Tools::getValue('controller') != 'AdminModules' && Tools::getValue('configure') != $this->name) {
            return;
        }
        Media::addJsDef([
            'importpalmira_ajax' => $this->context->link->getAdminLink('AdminImportpalmira'),
            'importpalmira_step' => $this->step,
            'importpalmira_module_url' => $this->url,
            'importpalmira_import_file_path' => Tools::getValue('IMPORTPALMIRA_IMPORT_FILE_PATH')
        ]);


        $this->context->smarty->assign('import_step', $this->step);
        if (+$this->step === 0 || +$this->step === 1) {
            $formView = $this->form->getView();
            $this->context->smarty->assign('form_view', $formView);
            return $this->display(__FILE__, 'forms.tpl');
        } elseif (+$this->step === 2) {
            return $this->display(__FILE__, 'final.tpl');
        }

        return new RedirectResponse($this->url . '&token=' . $this->token . '&step=0');
    }

    public function isUsingNewTranslationSystem()
    {
        return true;
    }

    public function getContext()
    {
        return $this->context;
    }

    public function getIdentifier()
    {
        return $this->identifier;
    }

    public function getImportFilePath() {
        return $this->import_file_path;
    }
}
