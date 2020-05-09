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
use Symfony\Component\HttpFoundation\RedirectResponse;

class ImportPalmira extends Module
{
    private $form;
    private $step;

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

        $this->displayName = $this->trans('Import Palmira', [], 'Modules.Importpalmira.Importpalmira');
        $this->description = $this->getTranslator()->trans('Import products of CSV or XML files', [], 'Modules.Importpalmira.Importpalmira');

        $this->ps_versions_compliancy = array('min' => '1.7', 'max' => _PS_VERSION_);

        $this->step = Tools::getValue('step') ? Tools::getValue('step') : 0;
        $this->form = new ImportForm($this, $this->table, $this->context, $this->identifier, $this->step);
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

        if (+$this->step === 0 || +$this->step === 1) {
            $formView = $this->form->getView();
            $this->context->smarty->assign('form_view', $formView);
            return $this->display(__FILE__, 'forms.tpl');
        } elseif (+$this->step === 2) {
            return $this->display(__FILE__, 'final.tpl');
        }

        return new RedirectResponse($this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name .  '&token='. Tools::getAdminTokenLite('AdminModules') . '&step=0');
    }

    public function isUsingNewTranslationSystem()
    {
        return true;
    }
}
