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
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\VarDumper\VarDumper;

class ImportPalmira extends Module
{
    const _IMPORT_FILES_DIR_ = _PS_MODULE_DIR_ . 'importpalmira/importfiles/';
    private $form;
    private $step;
    private $token;
    private $url;
    private $flash;
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
        $this->token = Tools::getAdminTokenLite('AdminModules');

        $this->url = $this->context->link->getAdminLink('AdminModules', false);
        $this->url .= '&token=' . $this->token;
        $this->url .= '&configure=' . $this->name;
        $this->url .= '&tab_module=' . $this->tab;
        $this->url .= '&module_name=' . $this->name;

        $this->flash = Flash::getInstance();

        $this->step = Tools::getValue('step') ? Tools::getValue('step') : 0;
        $this->form = new ImportForm($this, $this->table, $this->context, $this->identifier, $this->step, $this->token);

        /**
         * Delete file from history import files
         */
        if ((bool)Tools::getValue('delete_file_import') && (bool)($file_del = Tools::getValue('file_import_delete_name'))) {
            Tools::deleteFile(self::_IMPORT_FILES_DIR_ . $file_del);
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
            if(empty($errors)) {
                $errors[] = 'Возможно вы забыли загрузить файл';
            }

            $this->flash->add('file_load_errors', $errors);
            $this->flash->add('step_one_is_error', 1);
            Tools::redirectAdmin($this->url);
        }
        $file_name = $fileUploader->getFileName();
        $this->context->smarty->assign('import_file_name', $file_name);
        $this->context->smarty->assign('file_success_msg', $fileUploader->getSuccess());
    }

    public function renderStepThree() {
        VarDumper::dump(Tools::getAllValues());
    }

    public function renderView()
    {
        if (Tools::getValue('controller') != 'AdminModules' && Tools::getValue('configure') != $this->name) {
            return;
        }
        Media::addJsDef([
            'importpalmira_ajax' => $this->context->link->getAdminLink('AdminImportpalmira'),
            'importpalmira_step' => $this->step,
            'importpalmira_module_url' => $this->url
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
}
