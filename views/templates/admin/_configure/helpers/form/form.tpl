{*
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
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2020 PrestaShop SA
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*}

{extends file="helpers/form/form.tpl"}
{**
 * 2007-2020 PrestaShop and Contributors
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/OSL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2020 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 * International Registered Trademark & Property of PrestaShop SA
 *}

{block name="defaultForm"}
  {assign var='identifier_bk' value=$identifier scope='parent'}
  {if isset($table_bk) && $table_bk == $table}{capture name='table_count'}{counter name='table_count'}{/capture}{/if}
  {assign var='table_bk' value=$table scope='root'}
  <form id="{if isset($fields.form.form.id_form)}{$fields.form.form.id_form|escape:'html':'UTF-8'}{else}{if $table == null}configuration_form{else}{$table}_form{/if}{if isset($smarty.capture.table_count) && $smarty.capture.table_count}_{$smarty.capture.table_count|intval}{/if}{/if}"
        class="defaultForm form-horizontal{if isset($name_controller) && $name_controller} {$name_controller}{/if}"{if isset($current) && $current}
  action="{$current|escape:'html':'UTF-8'}{if isset($token) && $token}&amp;token={$token|escape:'html':'UTF-8'}{/if}"{/if}
        method="post" enctype="multipart/form-data"{if isset($style)} style="{$style}"{/if} novalidate>
    {if $form_id}
      <input type="hidden" name="{$identifUier}"
             id="{$identifier}{if isset($smarty.capture.identifier_count) && $smarty.capture.identifier_count}_{$smarty.capture.identifier_count|intval}{/if}"
             value="{$form_id}"/>
    {/if}
    {if !empty($submit_action)}
      <input type="hidden" name="{$submit_action}" value="1"/>
    {/if}
    {foreach $fields as $f => $fieldset}
      {block name="fieldset"}
        {capture name='fieldset_name'}{counter name='fieldset_name'}{/capture}
        <div id="fieldset_{$f}{if isset($smarty.capture.identifier_count) && $smarty.capture.identifier_count}_{$smarty.capture.identifier_count|intval}{/if}{if $smarty.capture.fieldset_name > 1}_{($smarty.capture.fieldset_name - 1)|intval}{/if}">
          {foreach $fieldset.form as $key => $field}
            {if $key == 'legend'}
              {block name="legend"}
                <div class="panel-heading">
                  {if isset($field.image) && isset($field.title)}<img src="{$field.image}"
                                                                      alt="{$field.title|escape:'html':'UTF-8'}"/>{/if}
                  {if isset($field.icon)}<i class="{$field.icon}"></i>{/if}
                  {$field.title}
                </div>
              {/block}
            {elseif $key == 'title' && $field}
              <div class="container-fluid">
                <div class="col-lg-6 text-center"><h2>{$field}</h2></div>
              </div>
            {elseif $key == 'line_hr' && $field}
              <hr/>
            {elseif $key == 'description' && $field}
              <div class="alert alert-info">{$field}</div>
            {elseif $key == 'warning' && $field}
              <div class="alert alert-warning">{$field}</div>
            {elseif $key == 'success' && $field}
              <div class="alert alert-success">{$field}</div>
            {elseif $key == 'error' && $field}
              <div class="alert alert-danger">{$field}</div>
            {elseif $key == 'input'}
              <div class="form-wrapper">
                {foreach $field as $input}
                  {block name="input_row"}
                    <div class="form-group{if isset($input.form_group_class)} {$input.form_group_class}{/if}{if $input.type == 'hidden'} hide{/if}"{if $input.name == 'id_state'}
                      id="contains_states"{if !$contains_states} style="display:none;"{/if}{/if}{if $input.name == 'dni'}
                      id="dni_required"{if !$dni_required} style="display:none;"{/if}{/if}{if isset($tabs) && isset($input.tab)}
                    data-tab-id="{$input.tab}"{/if}>
                      {if $input.type == 'hidden'}
                        <input type="hidden" name="{$input.name}" id="{$input.name}"
                               value="{$fields_value[$input.name]|escape:'html':'UTF-8'}"/>
                      {else}
                        {block name="label"}
                          {if isset($input.label)}
                            <label class="control-label col-lg-3{if isset($input.required) && $input.required && $input.type != 'radio'} required{/if}">
                              {if isset($input.hint)}
                              <span class="label-tooltip" data-toggle="tooltip" data-html="true"
                                    title="{if is_array($input.hint)}
                                  {foreach $input.hint as $hint}
                                    {if is_array($hint)}
                                      {$hint.text|escape:'html':'UTF-8'}
                                    {else}
                                      {$hint|escape:'html':'UTF-8'}
                                    {/if}
                                  {/foreach}
                                {else}
                                  {$input.hint|escape:'html':'UTF-8'}
                                {/if}">
										          {/if}
                                {$input.label}
                                {if isset($input.hint)}
                                </span>
                              {/if}
                            </label>
                          {/if}
                        {/block}

                        {block name="field"}
                          <div class="col-lg-{if isset($input.col)}{$input.col|intval}{else}9{/if}{if !isset($input.label)}{/if}">
                            {block name="input"}
                              {if $input.type == 'text'}
                                {assign var='value_text' value=$fields_value[$input.name]}
                                <input type="text"
                                       name="{$input.name}"
                                       id="{if isset($input.id)}{$input.id}{else}{$input.name}{/if}"
                                       value="{if isset($input.string_format) && $input.string_format}{$value_text|string_format:$input.string_format|escape:'html':'UTF-8'}{else}{$value_text|escape:'html':'UTF-8'}{/if}"
                                       class="{if isset($input.class)}{$input.class}{/if}{if $input.type == 'tags'} tagify{/if}"
                                        {if isset($input.size)} size="{$input.size}"{/if}
                                        {if isset($input.maxchar) && $input.maxchar} data-maxchar="{$input.maxchar|intval}"{/if}
                                        {if isset($input.maxlength) && $input.maxlength} maxlength="{$input.maxlength|intval}"{/if}
                                        {if isset($input.readonly) && $input.readonly} readonly="readonly"{/if}
                                        {if isset($input.disabled) && $input.disabled} disabled="disabled"{/if}
                                        {if isset($input.autocomplete) && !$input.autocomplete} autocomplete="off"{/if}
                                        {if isset($input.required) && $input.required } required="required" {/if}
                                        {if isset($input.placeholder) && $input.placeholder } placeholder="{$input.placeholder}"{/if}
                                />
                              {elseif $input.type == 'select'}
                                <select name="{$input.name|escape:'html':'utf-8'}"
                                        class="{if isset($input.class)}{$input.class|escape:'html':'utf-8'}{/if}"
                                        id="{if isset($input.id)}{$input.id|escape:'html':'utf-8'}{else}{$input.name|escape:'html':'utf-8'}{/if}"
                                        {if isset($input.multiple) && $input.multiple} multiple="multiple"{/if}
                                        {if isset($input.size)} size="{$input.size|escape:'html':'utf-8'}"{/if}
                                        {if isset($input.onchange)} onchange="{$input.onchange|escape:'html':'utf-8'}"{/if}
                                        {if isset($input.disabled) && $input.disabled} disabled="disabled"{/if}>
                                  {if isset($input.options.optiongroup)}
                                    {foreach $input.options.optiongroup.query AS $optiongroup}
                                      <optgroup label="{$optiongroup[$input.options.optiongroup.label]}">
                                        {foreach $optiongroup[$input.options.options.query] as $option}
                                          <option value="{$option[$input.options.options.id]}"
                                                  {if isset($input.multiple)}
                                                    {foreach $fields_value[$input.name] as $field_value}
                                                      {if $field_value == $option[$input.options.options.id]}selected="selected"{/if}
                                                    {/foreach}
                                                  {else}
                                                    {if $fields_value[$input.name] == $option[$input.options.options.id]}selected="selected"{/if}
                                                  {/if}
                                          >{$option[$input.options.options.name]}</option>
                                        {/foreach}
                                      </optgroup>
                                    {/foreach}
                                  {else}
                                    {foreach $input.options.query AS $option}
                                      {if is_object($option)}
                                        <option value="{$option->$input.options.id}"
                                                {if isset($input.multiple)}
                                                  {foreach $fields_value[$input.name] as $field_value}
                                                    {if $field_value == $option->$input.options.id}
                                                      selected="selected"
                                                    {/if}
                                                  {/foreach}
                                                {else}
                                                  {if $fields_value[$input.name] == $option->$input.options.id}
                                                    selected="selected"
                                                  {/if}
                                                {/if}
                                        >{$option->$input.options.name}</option>
                                      {elseif $option == "-"}
                                        <option value="">-</option>
                                      {else}
                                        <option value="{$option[$input.options.id]}"
                                                {if isset($input.multiple)}
                                                  {foreach $fields_value[$input.name] as $field_value}
                                                    {if $field_value == $option[$input.options.id]}
                                                      selected="selected"
                                                    {/if}
                                                  {/foreach}
                                                {else}
                                                  {if $fields_value[$input.name] == $option[$input.options.id]}
                                                    selected="selected"
                                                  {/if}
                                                {/if}
                                        >{$option[$input.options.name]}</option>
                                      {/if}
                                    {/foreach}
                                  {/if}
                                </select>
                              {elseif $input.type == 'switch'}
                                <span class="switch prestashop-switch fixed-width-lg">
                                  {foreach $input.values as $value}
                                    <input type="radio"
                                           name="{$input.name}"{if $value.value == 1} id="{$input.name}_on"{else}
                                           id="{$input.name}_off"{/if}
                                           value="{$value.value}"{if $fields_value[$input.name] == $value.value}
                                           checked="checked"{/if}{if (isset($input.disabled) && $input.disabled) or (isset($value.disabled) && $value.disabled)}
                                           disabled="disabled"{/if}/>

{strip}
                                    <label {if $value.value == 1} for="{$input.name}_on"{else} for="{$input.name}_off"{/if}>
                                        {if $value.value == 1}
                                          {l s='Yes' d='Admin.Global'}
                                        {else}
                                          {l s='No' d='Admin.Global'}
                                        {/if}
                                      </label>
                                  {/strip}
                                  {/foreach}
										              <a class="slide-button btn"></a>
									              </span>
                              {elseif $input.type == 'file'}
                                <div class="form-group">
                                  <div class="col-sm-6">
                                    <input id="{$input.name}" type="file" name="{$input.name}" accept=".xml, .csv"
                                           class="hide"/>
                                    <div class="dummyfile input-group">
                                      <span class="input-group-addon"><i class="icon-file"></i></span>
                                      <input id="{$input.name}-name" type="text" name="{$input.name}" readonly/>
                                      <span class="input-group-btn">
                                        <button id="{$input.name}-selectbutton" type="button"
                                                name="submitAddAttachments" class="btn btn-default">
                                          <i class="icon-folder-open"></i> {l s='Add files'}
                                        </button>
                                      </span>
                                    </div>
                                  </div>
                                </div>
                                <script type="text/javascript">
                                  $(document)
                                          .ready(function () {
                                            $('#{$input.name}-selectbutton')
                                                    .click(function (e) {
                                                      $('#{$input.name}')
                                                              .trigger('click');
                                                    });

                                            $('#{$input.name}-name')
                                                    .click(function (e) {
                                                      $('#{$input.name}')
                                                              .trigger('click');
                                                    });

                                            $('#{$input.name}-name')
                                                    .on('dragenter', function (e) {
                                                      e.stopPropagation();
                                                      e.preventDefault();
                                                    });

                                            $('#{$input.name}-name')
                                                    .on('dragover', function (e) {
                                                      e.stopPropagation();
                                                      e.preventDefault();
                                                    });

                                            $('#{$input.name}-name')
                                                    .on('drop', function (e) {
                                                      e.preventDefault();
                                                      var files = e.originalEvent.dataTransfer.files;
                                                      $('#{$input.name}')[0].files = files;
                                                      $(this)
                                                              .val(files[0].name);
                                                    });

                                            $('#{$input.name}')
                                                    .change(function (e) {
                                                      if ($(this)[0].files !== undefined) {
                                                        var files = $(this)[0].files;
                                                        var name = '';

                                                        $.each(files, function (index, value) {
                                                          name += value.name + ', ';
                                                        });

                                                        $('#{$input.name}-name')
                                                                .val(name.slice(0, -2));
                                                      } else // Internet Explorer 9 Compatibility
                                                      {
                                                        var name = $(this)
                                                                .val()
                                                                .split(/[\\/]/);
                                                        $('#{$input.name}-name')
                                                                .val(name[name.length - 1]);
                                                      }
                                                    });

                                          });
                                </script>
                                {*                                {$input.file}*}
                              {elseif $input.type == 'history_files'}
                                {if isset($input.files_name) && !empty($input.files_name)}
                                  <table class="table" id="history_files">
                                    <tbody>
                                    {foreach from=$input.files_name key=$key item=$name}
                                      <tr>
                                        <td id="{$input.name}_{$key}">{$name}</td>
                                        <td style="width: 40px">
                                          <button type="button" data-id="{$input.name}_{$key}"
                                                  class="btn btn-primary btn-use-file">
                                            {l s='Use' d='Admin.Actions'}
                                          </button>
                                        </td>
                                        <td style="width: 40px">
                                          <a href="{$input.btnlink}&file_import_delete_name={$name}&delete_file_import=1" type="button" class="btn btn-danger">
                                            {l s='Delete' d='Admin.Actions'}
                                          </a>
                                        </td>
                                      </tr>
                                    {/foreach}
                                    </tbody>
                                  </table>
                                  <script type="text/javascript">
                                    $(document)
                                            .ready(function () {
                                              $('#history_files .btn-use-file')
                                                      .click(function (e) {
                                                        $('#IMPORTPALMIRA_FILE_IMPORT-name')
                                                                .val($('#' + $(e.target)
                                                                        .data('id'))
                                                                        .text());
                                                        $('#IMPORTPALMIRA_FILE_IMPORT').val('');
                                                      });

                                            });
                                  </script>
                                {/if}
                              {elseif $input.type == 'text_save'}
                                <div class="col-sm-10">
                                  <input type="text" name="{$input.name}" id="{$input.name}">
                                </div>
                                <div class="col-sm-2">
                                  <button type="button" style="width: 100%"
                                          class="btn btn-default">{l s='Save' d='Admin.Actions'}</button>
                                </div>
                              {elseif $input.type == 'election_table'}
                                <div class="table-responsive" style="width: 100%; display: block; overflow-x: auto">
                                  <table class="table table-bordered mp-table">
                                    <thead>
                                    <tr>
                                      {for $num=1 to $input.product_arr_import.header|@count}
                                        <th>
                                          <select name="{$input.name}[]">
                                            <option value="no">{l s='Ignore this column' d='Modules.Importpalmira.Form'}</option>
                                            {foreach from=$input.product_fields item=$field}
                                              <option value="{$field->getName()}">{$field->getLabel()}</option>
                                            {/foreach}
                                          </select>
                                        </th>
                                      {/for}
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <tr>
                                      {foreach from=$input.product_arr_import.header item=$value}
                                        <td>{$value}</td>
                                      {/foreach}
                                    </tr>
                                    {foreach from=$input.product_arr_import.products item=$product}
                                      <tr>
                                        {foreach from=$product item=$value}
                                          <td>{$value}</td>
                                        {/foreach}
                                      </tr>
                                    {/foreach}
                                    </tbody>
                                  </table>
                                </div>
                              {/if}
                            {/block}{* end block input *}

                            {block name="description"}
                              {if isset($input.desc) && !empty($input.desc)}
                                <p class="help-block">
                                  {if is_array($input.desc)}
                                    {foreach $input.desc as $p}
                                      {if is_array($p)}
                                        <span id="{$p.id}">{$p.text}</span>
                                        <br/>
                                      {else}
                                        {$p}
                                        <br/>
                                      {/if}
                                    {/foreach}
                                  {else}
                                    {$input.desc}
                                  {/if}
                                </p>
                              {/if}
                            {/block}
                          </div>
                        {/block}{* end block field *}
                      {/if}
                    </div>
                  {/block}
                {/foreach}
              </div>
              <!-- /.form-wrapper -->
            {/if}
          {/foreach}
          {block name="footer"}
            {if isset($fieldset['form']['submit']) || isset($fieldset['form']['buttons'])}
              <div class="panel-footer">
                {if isset($fieldset['form']['submit']) && !empty($fieldset['form']['submit'])}
                  <button type="submit" value="1"
                          id="{if isset($fieldset['form']['submit']['id'])}{$fieldset['form']['submit']['id']}{else}{$table}_form_submit_btn{/if}{if $smarty.capture.form_submit_btn > 1}_{($smarty.capture.form_submit_btn - 1)|intval}{/if}"
                          name="{if isset($fieldset['form']['submit']['name'])}{$fieldset['form']['submit']['name']}{else}{$submit_action}{/if}{if isset($fieldset['form']['submit']['stay']) && $fieldset['form']['submit']['stay']}AndStay{/if}"
                          class="{if isset($fieldset['form']['submit']['class'])}{$fieldset['form']['submit']['class']}{else}btn btn-default pull-right{/if}">
                    <i
                            class="{if isset($fieldset['form']['submit']['icon'])}{$fieldset['form']['submit']['icon']}{else}process-icon-save{/if}"></i> {$fieldset['form']['submit']['title']}
                  </button>
                {/if}
                {if isset($show_cancel_button) && $show_cancel_button}
                  <a class="btn btn-default" {if $table}id="{$table}_form_cancel_btn"{/if}
                     onclick="javascript:window.history.back();">
                    <i class="process-icon-cancel"></i> {l s='Cancel' d='Admin.Actions'}
                  </a>
                {/if}
              </div>
            {/if}
          {/block}
        </div>
      {/block}
    {/foreach}
  </form>
{/block}
