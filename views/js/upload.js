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

class LongTask {
  is_progress_end = true;
  finishedTasks = [];

  startLongTask = (task_id) => {
    jQuery.ajax({
      type: 'POST',
      headers: {'cache-control': 'no-cache'},
      url: importpalmira_ajax,
      dataType: 'json',
      data: {
        ajax: true,
        action: 'longprogress',
        task: task_id
      },
      success: (data) => {
        if (data.status_progress !== undefined) {
          if (data.status_progress === 'next') {
            console.log('start long task next, id:', task_id);
            this.startLongTask(task_id);
          }

          if (data.status_progress === 'end') {
            console.log('start long task end, id', task_id);
            this.finishedTasks.push(task_id);
          }
        }
      }
    });
  };

  monitorProgress = (task_id) => {
    jQuery.ajax({
      type: 'POST',
      headers: {'cache-control': 'no-cache'},
      url: importpalmira_ajax,
      dataType: 'json',
      data: {
        ajax: true,
        action: 'getprogress',
        task: task_id
      },
      success: (data) => {
        if (data.count_products !== undefined) {
          jQuery('#importpalmira-num-products').text(data.count_products);
        }

        if (jQuery.inArray(task_id, this.finishedTasks) != -1) {
          console.log('monitor progress, finish task id:', task_id);
          if (this.type_task === 'delete_all_products') {
            this.is_progress_end = true;
            this.setProgress(100, false);
            setTimeout(() => {
              this.runTask('import_products');
            }, 1500)
          }

          if (this.type_task === 'import_products') {
            this.is_progress_end = true;
            this.setProgress(100, false);
            this.viewStatusImport();
          }
          return;
        }

        if (data.progress !== undefined && data.remaining_progress_num) {
          console.log('monitor progress, set progress', data.progress);
          console.log('monitor progress, set progress for task id:', task_id);
          this.setProgress(data.remaining_progress_num);
        }

        setTimeout( () => {
          this.monitorProgress(task_id);
        }, 1000);
      }
    });
  };

  /*
    type_task = delete_all_products or import_products
  */
  runTask = (type_task = 'import_products') => {
    this.is_progress_end = false;
    this.type_task = type_task;

    this.setProgress(0, false);
    this.setTypeTaskMode();

    jQuery.ajax({
      type: 'POST',
      headers: {'cache-control': 'no-cache'},
      url: importpalmira_ajax,
      dataType: 'json',
      data: {
        ajax: true,
        action: 'progressnew',
      },
      success: (data) => {
        console.log('run task, data:', data);

        if (data.full_progress_count !== undefined) {
          this.full_progress_count = data.full_progress_count;
        }

        if (data.task !== undefined) {
          this.startLongTask(data.task);
          this.monitorProgress(data.task);
        }
      }
    });
  };

  setProgress = (value, is_remaining_pgs_num = true) => {
    const progressTxt = jQuery('#importpalmira_progress_txt');

    let progress_value = value;

    if (this.full_progress_count === value) {
      progress_value = 0;
    } else if (is_remaining_pgs_num) {
      progress_value = (this.full_progress_count - value) / (this.full_progress_count * 0.01);
    }

    document.getElementById('importpalmira_progress_view').style.width = progress_value + '%';
    progressTxt.html(Math.round(progress_value) + '%');
  }

  setTypeTaskMode = () => {
    if (this.type_task === 'delete_all_products') {
      jQuery('#importpalmira_progress_view').parent().addClass('importpalmira-progress__red');
      jQuery('#importpalmira_progress_msg').text(importpalmira_msg_delete_products);
    }

    if (this.type_task === 'import_products') {
      if (jQuery('#importpalmira_progress_view').parent().hasClass('importpalmira-progress__red')) {
        jQuery('#importpalmira_progress_view').parent().removeClass('importpalmira-progress__red');
        jQuery('#importpalmira_progress_msg').text(importpalmira_msg_import_products);
      }
    }
  };

  viewStatusImport = () => {
    setTimeout(() => {
      // jQuery('#importpalmira-progress_div').hide('slow');
      jQuery('#importpalmira-checkmark').show('slow');
    }, 1500);
    console.log('status import its OKAY');
  };

  ajaxErrorCallback = (jqXHR, testStatus, errorThrown) => {
    console.log(errorThrown);
  };
}

document.addEventListener('DOMContentLoaded', function () {
  if (jQuery('#btntestajax').length) {
    testAjax();
  }

  const btnStart = document.querySelector('#btnstartprogress');
  const longTask = new LongTask();
  btnStart.addEventListener('click', () => {
    if (longTask.is_progress_end) {
      longTask.runTask('delete_all_products');
    }
  });
});

const ajaxErrorCallback  = function (jqXHR, testStatus, errorThrown) {
  console.log(errorThrown);
}

const testAjax = function () {
  const testBtnAjax = document.querySelector('#btntestajax');
  testBtnAjax.addEventListener('click', () => {
    jQuery.ajax({
      type: 'POST',
      url: importpalmira_ajax,
      // dataType: 'json',
      data: {
        ajax: true,
        action: 'testajax',
        importpalmira_import_file_path,
        importpalmira_type_value
      },
      success: function (data) {
        console.log('testAjaxSuccess', data);
      },
      error: ajaxErrorCallback
    });
  });
};
