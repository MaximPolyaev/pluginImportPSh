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
  is_scroll_errors = true;
  is_scroll_log = true;
  finishedTasks = [];

  startLongTask = (task_id, progress_num = 'none') => {
    jQuery.ajax({
      type: 'POST',
      headers: {'cache-control': 'no-cache'},
      url: importpalmira_ajax + '&long=long',
      dataType: 'json',
      data: {
        ajax: true,
        action: 'longprogress',
        type_task: this.type_task,
        importpalmira_import_file_path,
        importpalmira_type_value,
        importpalmira_num_skip_rows,
        importpalmira_force_id,
        importpalmira_only_update,
        importpalmira_reference_key,
        progress_num: progress_num,
        task: task_id
      },
      success: (data) => {
        if (data.messages !== undefined && Array.isArray(data.messages)) {
          this.viewDebugMessages(data.messages);
        }

        if (data.errors !== undefined && Array.isArray(data.errors)) {
          this.viewDebugErrors(data.errors);
        }

        if (data.status_progress !== undefined) {
          if (data.status_progress === 'next') {
            if (this.type_task === 'import_products' && data.progress_num !== undefined) {
              this.startLongTask(task_id, data.progress_num);
            } else {
              this.startLongTask(task_id);
            }
          }

          if (data.status_progress === 'end') {
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
        type_task: this.type_task,
        task: task_id
      },
      success: (data) => {
        if (data === null) {
          setTimeout( () => {
            this.monitorProgress(task_id);
          }, 1000);
          return;
        }
        if (data.messages !== undefined) {
          if (Array.isArray(data.messages))
            this.viewDebugMessages(data.messages);
        }

        if (data.errors !== undefined) {
          if (Array.isArray(data.errors))
            this.viewDebugErrors(data.errors);
        }

        if (jQuery.inArray(task_id, this.finishedTasks) != -1) {
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

        if (data.progress !== undefined && data.remaining_progress_num !== undefined) {
          this.setProgress(data.remaining_progress_num);
        }

        if (data.progress !== undefined && data.progress_num !== undefined) {
          if (data.progress !== null) {
            this.setProgress(data.progress_num);
          }
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
        importpalmira_import_file_path,
        importpalmira_num_skip_rows,
        type_task: this.type_task
      },
      success: (data) => {

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
      if (this.type_task === 'delete_all_products') {
        progress_value = (this.full_progress_count - value) / (this.full_progress_count * 0.01);
      } else if (this.type_task === 'import_products') {
        progress_value = value / (this.full_progress_count * 0.01);
      }
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

  viewDebugMessages(messages) {
    const debugLog = document.getElementById('importpalmira-debug_log');
    messages.forEach(message => {
      const p = document.createElement('p');
      p.innerHTML = message.trim();
      debugLog.appendChild(p);
    });

    if (this.is_scroll_log) {
      debugLog.scrollTop = debugLog.scrollHeight;
    }
  }

  viewDebugErrors(errors) {
    const errorLog = document.getElementById('importpalmira-debug_errors');
    errors.forEach(error => {
      const p = document.createElement('p');
      p.innerHTML = error.trim();
      errorLog.appendChild(p);
    });

    if (this.is_scroll_errors) {
      errorLog.scrollTop = errorLog.scrollHeight;
    }
  }

  ajaxErrorCallback = (jqXHR, testStatus, errorThrown) => {
    console.log(errorThrown);
  };

  setScrollErrors(status) {
    this.is_scroll_errors = status;
  }

  setScrollLog(status) {
    this.is_scroll_log = status;
  }
}

document.addEventListener('DOMContentLoaded', function () {
  if (jQuery('#btntestajax').length) {
    testAjax();
  }

  // const btnStart = document.querySelector('#btnstartprogress');
  const longTask = new LongTask();
  // btnStart.addEventListener('click', () => {
  //   if (longTask.is_progress_end && importpalmira_delete_products !== undefined) {
  //     if (importpalmira_delete_products) {
  //       longTask.runTask('delete_all_products');
  //     } else {
  //       longTask.runTask('import_products');
  //     }
  //   }
  // });

  if (longTask.is_progress_end && importpalmira_delete_products !== undefined) {
    if (importpalmira_delete_products) {
      longTask.runTask('delete_all_products');
    } else {
      longTask.runTask('import_products');
    }
  }

  const debugErrors = document.getElementById('importpalmira-debug_errors');
  debugErrors.onmouseover = debugErrors.onmouseout = event => {
    if (event.type === 'mouseover') {
      longTask.setScrollErrors(false)
    }
    if (event.type === 'mouseout') {
      longTask.setScrollErrors(true);
    }
  };

  const debugLog = document.getElementById('importpalmira-debug_log');
  debugLog.onmouseover = debugLog.onmouseout = event => {
    if (event.type === 'mouseover') {
      longTask.setScrollLog(false)
    }
    if (event.type === 'mouseout') {
      longTask.setScrollLog(true);
    }
  };
});

const ajaxErrorCallback  = function (jqXHR, testStatus, errorThrown) {
  console.log(errorThrown);
}

const testAjax = function () {
  const testBtnAjax = document.querySelector('#btntestajax');
  testBtnAjax.addEventListener('click', () => {
    jQuery.ajax({
      type: 'POST',
      url: importpalmira_ajax + "&testtask=testtask",
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

  const btnTestImportOne = document.querySelector('#btntestimportone');
  btnTestImportOne.addEventListener('click', () => {
    jQuery.ajax({
      type: 'POST',
      url: importpalmira_ajax,
      // dataType: 'json',
      data: {
        ajax: true,
        action: 'importone',
        type_task: 'import_products',
        importpalmira_import_file_path,
        importpalmira_type_value,
        importpalmira_num_skip_rows,
        progress_num: 1
      },
      success: (data) => {
        console.log(data);
      },
      error: ajaxErrorCallback
    })
  });
};
