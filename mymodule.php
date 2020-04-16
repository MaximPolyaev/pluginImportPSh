<?php
if (!defined('_PS_VERSION_')) {
    exit;
}



class MyModule extends Module
{
    public function __construct()
    {
        $this->name = 'mymodule';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'Maxim Polyaev';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = [
            'min' => '1.6',
            'max' => _PS_VERSION_
        ];
        $this->bootstrap = true;



        parent::__construct();

        $this->displayName = $this->l('IMPORT CSV');
        $this->description = $this->l('Import price and number products');

        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');

        // Проверка ну существование имя;
        if (!Configuration::get('MYMODULE_NAME')) {
            $this->warning = $this->l('No name provided');
        }
    }

    public function install()
    {
        echo 'install';
        if (Shop::isFeatureActive()) {
            Shop::setContext(Shop::CONTEXT_ALL);
        }

        if (!parent::install() ||
            !$this->registerHook('leftColumn') ||
            !$this->registerHook('header') ||
            !Configuration::updateValue('MYMODULE_NAME', 'my friendd')
        ) {
            return false;
        }

        return true;
    }

    public function uninstall()
    {
        if (!parent::uninstall() ||
            !Configuration::deleteByName('MYMODULE_NAME')
        ) {
            return false;
        }

        return true;
    }

    public function getContent()
    {
        $output = null;


        if (Tools::isSubmit('submit'.$this->name)) {
            $myModuleName = strval(Tools::getValue('MYMODULE_NAME'));
            $value2 = strval(Tools::getValue('MYMODULE_NAME2'));

            \Symfony\Component\VarDumper\VarDumper::dump($_FILES);
            if (
                !$myModuleName ||
                empty($myModuleName) ||
                !Validate::isGenericName($myModuleName) ||
                !$value2 ||
                empty($value2) ||
                !Validate::isGenericName($value2)
            ) {
                $output .= $this->displayError($this->l('Invalid Configuration value'));
            } else {
                Configuration::updateValue('MYMODULE_NAME', $myModuleName);
                Configuration::updateValue('MYMODULE_NAME2', $value2);
                $output .= $this->displayConfirmation($this->l('Settings updated'));
            }
        }

        return $output.$this->displayForm() . $output.$this->displayForm() ;
    }

    public function displayForm()
    {
        // Get default language
        $defaultLang = (int)Configuration::get('PS_LANG_DEFAULT');

        // Init Fields form array
        $fieldsForm[0]['form'] = [
            'legend' => [
                'title' => $this->l('Настройки'),
            ],
            'input' => [
                [
                    'type' => 'text',
                    'label' => $this->l('Тестовое значение'),
                    'name' => 'MYMODULE_NAME',
                    'size' => 20,
                    'required' => true
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('Тестовое значение2'),
                    'name' => 'MYMODULE_NAME2',
                    'size' => 20,
                    'required' => true
                ],
                [
                    'type' => 'file',
                    'label' => $this->l('Файл'),
                    'name' => 'MY_CSV_FILE',
                    'required' => true,
                    'download_url' => './assets/'
                ]
            ],
            'submit' => [
                'title' => $this->l('Сохранить'),
                'class' => 'btn btn-default pull-right'
            ]
        ];

        $helper = new HelperForm();

        // Module, token and currentIndex
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;

        // Language
        $helper->default_form_language = $defaultLang;
        $helper->allow_employee_form_lang = $defaultLang;

        // Title and toolbar
        $helper->title = $this->displayName;
        $helper->show_toolbar = false;        // false -> remove toolbar
        $helper->toolbar_scroll = false;      // yes - > Toolbar is always visible on the top of the screen.
        $helper->submit_action = 'submit'.$this->name;
//        $helper->toolbar_btn = [
//            'save' => [
//                'desc' => $this->l('Save'),
//                'href' => AdminController::$currentIndex.'&configure='.$this->name.'&save'.$this->name.
//                    '&token='.Tools::getAdminTokenLite('AdminModules'),
//            ],
//            'back' => [
//                'href' => AdminController::$currentIndex.'&token='.Tools::getAdminTokenLite('AdminModules'),
//                'desc' => $this->l('Back to list')
//            ]
//        ];

        // Load current value
        $helper->fields_value['MYMODULE_NAME'] = Configuration::get('MYMODULE_NAME');
        $helper->fields_value['MYMODULE_NAME2'] = Configuration::get('MYMODULE_NAME2');

        return $helper->generateForm($fieldsForm);
    }
}
