{*
* 2020-2020 PrestaShop
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
*  @author MaximPolyaev MP <polyaev.maks@ya.ru>
*  @copyright  2020-2020 MaximPolyaev MP
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*}

<div class="panel">
  <div class="panel-heading"><i class="material-icons">import_export</i>
    {l s='Import products from CSV and XML files' d='Modules.Importpalmira.Step'}</div>
  <div class="mp-stepper-horizontal">
    <div class="mp-step active">
      <div class="mp-step-circle"><span>1</span></div>
      <div class="mp-step-title">{l s='Upload your file' d='Modules.Importpalmira.Step'}</div>
      <div class="mp-step-bar-left"></div>
      <div class="mp-step-bar-right"></div>
    </div>
    <div class="mp-step active">
      <div class="mp-step-circle"><span>2</span></div>
      <div class="mp-step-title">{l s='Match your data' d='Modules.Importpalmira.Step'}</div>
      <div class="mp-step-optional mp-done-optional">{l s='Success' d='Modules.Importpalmira.Step'}<i
                class="material-icons">done</i></div>
      <div class="mp-step-bar-left"></div>
      <div class="mp-step-bar-right"></div>
    </div>
    <div class="mp-step active">
      <div class="mp-step-circle"><span>3</span></div>
      <div class="mp-step-title">{l s='Products loaded' d='Modules.Importpalmira.Step'}</div>
      <div class="mp-step-bar-left"></div>
      <div class="mp-step-bar-right"></div>
    </div>
  </div>
  <hr>
  <div>
    <div>
      <button id="btntestajax">btn-test-ajax</button>
      <button id="btnstartprogress">Btn start progress</button>
    </div>
    <div id="importpalmira-progress_div">
      <h2 style="font-size: 4rem; text-align: center"><span id="importpalmira_progress_msg">Загрзка товаров...</span><span id="importpalmira_progress_txt">0 %</span></h2>
      <div class="importpalmira-progress">
        <div class="importpalmira-progress_view" id="importpalmira_progress_view" style="width: 0"></div>
      </div>
    </div>

    <div id="importpalmira-checkmark" style="display: none">
      <svg class="checkmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52">
        <circle class="checkmark__circle" cx="26" cy="26" r="25" fill="none"/>
        <path class="checkmark__check" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8"/>
      </svg>
    </div>
  </div>
</div>
