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
      {if isset($step_one_is_error)}
        <div class="mp-step-optional mp-error-optional">
          {l s='Error' d='Modules.Importpalmira.Step'}
          <i class="material-icons">error_outline</i>
        </div>
      {/if}
      <div class="mp-step-bar-left"></div>
      <div class="mp-step-bar-right"></div>
    </div>
    <div class="mp-step {if $import_step == 1}active{/if}">
      <div class="mp-step-circle"><span>2</span></div>
      <div class="mp-step-title">{l s='Match your data' d='Modules.Importpalmira.Step'}</div>
      <div class="mp-step-optional mp-done-optional">{l s='Success' d='Modules.Importpalmira.Step'}<i
                class="material-icons">done</i></div>
      <div class="mp-step-bar-left"></div>
      <div class="mp-step-bar-right"></div>
    </div>
    <div class="mp-step">
      <div class="mp-step-circle"><span>3</span></div>
      <div class="mp-step-title">{l s='Products loaded' d='Modules.Importpalmira.Step'}</div>
      <div class="mp-step-bar-left"></div>
      <div class="mp-step-bar-right"></div>
    </div>
  </div>
  <hr>
  <div id="importpalmira_view">
    <div>
      <button id="devbtn">dev-btn</button>
    </div>
    {if isset($step_one_errors)}
      {foreach from=$step_one_errors item=error}
        <div class="alert alert-danger" role="alert">
          <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
          <p class="alert-text">{$error}</p>
        </div>
      {/foreach}
    {/if}

    {if isset($file_success_msg)}
      {foreach from=$file_success_msg item=msg}
        <div class="alert alert-success" role="alert">
          <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
          <p class="alert-text">{$msg}</p>
        </div>
      {/foreach}
    {/if}
    {if isset($import_file_name)}
      <div class="alert alert-success" role="alert">
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
        <p class="alert-text">{l s='Select file import: ' d='Modules.Importpalmira.Step'} {$import_file_name}</p>
      </div>
    {/if}

    {$form_view}
  </div>
</div>
