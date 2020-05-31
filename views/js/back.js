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

window.onload = function () {

  // Идентификаторы завершенных задач
  var finishedTasks = [];

  //Стартовать длительную задачу
  let startLongTask = function(task_id)
  {
    jQuery.ajax({
      type: 'POST',
      headers: { "cache-control": "no-cache" },
      url: importpalmira_ajax,
      dataType: 'json',
      data: {
        ajax: true,
        action: 'longprogress',
        long_process: 1,
        task: task_id
      },
      success: function (data) {
        console.log('start_long_task_new', data);
        finishedTasks.push(task_id);
        console.log('start_long_task_finished_tasks', finishedTasks);
      }
    });
  }

  //Отслеживать прогресс длительной задачи
  let monitorProgress = function(task_id)
  {
    jQuery.ajax({
      type: 'POST',
      headers: { "cache-control": "no-cache" },
      url: importpalmira_ajax,
      dataType: 'json',
      data: {
        ajax: true,
        action: 'changeconfyear',
        get_progress: 1,
        task: task_id
      },
      success: function (data) {
        if (jQuery.inArray(task_id, finishedTasks) != -1) {
          setProgress(100);
          return;
        }

        if (data.progress !== undefined) {
          console.log('data.progress', data.progress);
          setProgress(data.progress);
        }
        setTimeout(function () {
          monitorProgress(task_id);
        }, 1000);
      }
    });
  }

  //Запустить длительную задачу с отслеживанием прогресса
  let runTask = function(task_id)
  {
    startLongTask(task_id);
    monitorProgress(task_id);
  }

  const btnStart = document.querySelector('#btnstartprogress');
  btnStart.addEventListener('click', () => {
    console.log('btnStart click');

    jQuery.ajax({
      type: 'POST',
      headers: { "cache-control": "no-cache" },
      url: importpalmira_ajax,
      dataType: 'json',
      data: {
        ajax: true,
        action: 'progressnew',
        new_task: 1
      },
      success: function (data) {
        console.log('progress_long', data);
        runTask(data.task)
      }
    });
  })

  function setProgress(value) {
    document.getElementById('importpalmira_progress_view').style.width = value + '%';
    jQuery('#importpalmira_progress_txt')
      .html(value + '%');
  }
};
