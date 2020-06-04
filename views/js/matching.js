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
          console.log('Не выбраны поля для настройка сопостовления данных')
          return;
        }
        // inputNameCfg.val('');

        const newDataCfg = getNewDataCfg(selectTypeFields);

        console.log('Отправление данных для новой конфигурации', newDataCfg);
        jQuery.ajax({
          type: 'POST',
          headers: {'cache-control': 'no-cache'},
          url: importpalmira_ajax,
          dataType: 'json',
          data: {
            ajax: true,
            action: 'savejsoncfg',
            new_json_data: newDataCfg,
            name_cfg: nameCfg
          },
          success: function (data) {
            console.log('Ответ после отправления данных', data);
            addCfgMatchesToHistory(nameCfg)
          }
        });
      } else {
        console.log('Введите имя кофигурации');
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
    }
  });
});

const addCfgMatchesToHistory = (name = 'test') => {
  const tableCfg = jQuery('#IMPORTPALMIRA_TABLE_CFG');
  if (!tableCfg.length) {
    return;
  }
  const key = tableCfg.data('num');

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
  const cfgName = jQuery('#' + jQuery(btn).data('id')).text();
  console.log('useCfg', cfgName);
};

const deleteCfg = btn => {
  const tableCfg = jQuery('#IMPORTPALMIRA_TABLE_CFG');
  const nameCfgElement = jQuery('#' + jQuery(btn).data('id'));
  const nameCfg = nameCfgElement.text();

  jQuery.ajax({
    type: 'POST',
    headers: {'cache-control': 'no-cache'},
    url: importpalmira_ajax,
    dataType: 'json',
    data: {
      ajax: true,
      action: 'deletejsoncfg',
      delete_name_cfg: nameCfg
    },
    success: function (data) {
      console.log('deleteCfg', nameCfg);
      console.log('delete cfg data', data);
      tableCfg.data('num', tableCfg.data('num') - 1);
      nameCfgElement.parent().remove();
    }
  });
};
