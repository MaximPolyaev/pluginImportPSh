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
 *  @author    PrestaShop SA <contact@prestashop.com>
 *  @copyright 2007-2020 PrestaShop SA
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 *
 * Don't forget to prefix your containers with your own identifier
 * to avoid any conflicts with others containers.
 */

document.addEventListener('DOMContentLoaded', () => {
  const saveBtnMatches = jQuery('#IMPORTPALMIRA_BTN_SAVE_MATCHES');
  const inputNameCfg = jQuery('#IMPORTPALMIRA_NAME_CFG');
  const selectTypeFields = Array.from(jQuery('select[name=IMPORTPALMIRA_TYPE_VALUE\\[\\]]'));
  const tableCfg = jQuery('#IMPORTPALMIRA_TABLE_CFG');
  if(saveBtnMatches.length) {
    saveBtnMatches.on('click', () => {
      const nameCfg = inputNameCfg.val();
      if (nameCfg !== '') {
        const isAllSelectNo = selectTypeFields.every(node => jQuery(node).val() === 'no');
        if(isAllSelectNo) {
          jQuery.growl.error({
            title: 'Ошибка сохраниния конфигурации',
            size: 'large',
            message: 'Не выбраны поля для настройка сопостовления данных'
          });
          return;
        }
        inputNameCfg.val('');

        const newDataCfg = getNewDataCfg(selectTypeFields);

        console.log('Отправление данных для новой конфигурации', newDataCfg);
        jQuery.ajax({
          type: 'POST',
          url: importpalmira_ajax,
          dataType: 'json',
          data: {
            ajax: true,
            action: 'savejsoncfg',
            new_json_data: newDataCfg,
            name_cfg: nameCfg
          },
          success: function (data) {
            if (data.save_json) {
              jQuery.growl.notice({
                title: 'Успех!',
                size: 'large',
                message: 'Конфигурация успешно сохранена'
              });
              addCfgMatchesToHistory(nameCfg);
            } else {
              data.save_json_errors.forEach(error => {
                jQuery.growl.error({
                  title: 'Ошибка сохраниния конфигурации',
                  size: 'large',
                  message: error
                });
              });
            }
          }
        });
      } else {
        jQuery.growl.error({
          title: 'Ошибка сохраниния конфигурации',
          size: 'large',
          message: 'Введите имя конфигурации'
        });
      }
    });
  }

  tableCfg.on('click', e => {
    const target = e.target;

    if (target.classList.contains('IMPORTPALMIRA_USE_CFG')) {
      useCfg(target);
    }

    if (target.classList.contains('IMPORTPALMIRA_DELETE_CFG')) {
      deleteCfg(target);
      console.log('delete click');
    }
  });
});

const addCfgMatchesToHistory = (name = 'test') => {
  const tableCfg = jQuery('#IMPORTPALMIRA_TABLE_CFG');
  if (!tableCfg.length) {
    return;
  }
  const key = tableCfg.data('num');
  if (key === 0) {
    jQuery('.IMPORTPALMIRA_LABEL_SELECT_CFG').show();
  }

  tableCfg.data('num', key + 1);

  const newCfgHtml = `
    <tr>
      <td id="IMPORTPALMIRA_CFG_KEY_${key}">${name}</td>
      <td style="width: 40px">
        <button type="button" data-id="IMPORTPALMIRA_CFG_KEY_${key}"
                class="btn btn-primary IMPORTPALMIRA_USE_CFG">
          Использовать
        </button>
      </td>
      <td style="width: 40px">
        <button type="button" data-id="IMPORTPALMIRA_CFG_KEY_${key}"
                class="btn btn-danger IMPORTPALMIRA_DELETE_CFG">
          Удалить
        </button>
      </td>
    </tr>
  `;

  tableCfg.find('tbody').prepend(newCfgHtml);
};

const getNewDataCfg = fields => {
  let data = [];
  fields.forEach(node => {
    data.push(jQuery(node).val())
  });

  return data;
};

const useCfg = btn => {
  const nameCfg = jQuery('#' + jQuery(btn).data('id')).text();

  jQuery.ajax({
    type: 'POST',
    url: importpalmira_ajax,
    dataType: 'json',
    data: {
      ajax: true,
      action: 'usejsoncfg',
      name_cfg: nameCfg
    },
    success: function (data) {
      console.log(data);
      if (data.json_cfg_data === null) {
        data.use_json_errors.forEach(error => {
          jQuery.growl.error({
            title: 'Ошибка использования конфигурации',
            size: 'large',
            message: error
          });
        });
      } else if (Array.isArray(data.json_cfg_data)) {
        const selectTypeFields = jQuery('select[name=IMPORTPALMIRA_TYPE_VALUE\\[\\]]');

        for(let i in data.json_cfg_data) {
          if (selectTypeFields[+i] === undefined) {
            break;
          }
          selectTypeFields[+i].value = data.json_cfg_data[+i];
        }
        jQuery.growl.notice({
          title: '',
          size: 'large',
          message: 'Конфигурация установлена'
        });
      }
    }
  });
};

const deleteCfg = btn => {
  const tableCfg = jQuery('#IMPORTPALMIRA_TABLE_CFG');
  const nameCfgElement = jQuery('#' + jQuery(btn).data('id'));
  const nameCfg = nameCfgElement.text();

  jQuery.ajax({
    type: 'POST',
    url: importpalmira_ajax,
    dataType: 'json',
    data: {
      ajax: true,
      action: 'deletejsoncfg',
      delete_name_cfg: nameCfg
    },
    success: function (data) {
      if (data.delete_json) {
        jQuery.growl.notice({
          title: 'Успех!',
          size: 'large',
          message: 'Конфигурация успешно удалена'
        });

        const num_configurations = tableCfg.data('num') - 1;
        tableCfg.data('num', num_configurations);
        nameCfgElement.parent().remove();
        if (num_configurations === 0) {
          jQuery('.IMPORTPALMIRA_LABEL_SELECT_CFG').hide();
        }
      } else {
        data.delete_json_errors.forEach(error => {
          jQuery.growl.error({
            title: 'Ошибка удления конфигурации',
            size: 'large',
            message: error
          });
        });
      }
    }
  });
};
