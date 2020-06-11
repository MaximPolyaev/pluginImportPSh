const ajaxErrorCallback  = function (jqXHR, testStatus, errorThrown) {
  console.log(errorThrown);
}

document.addEventListener('DOMContentLoaded', function () {
  if (jQuery('#btntestajax').length) {
    console.log('find #btntestajax');
    testAjax();
  }

  // Идентификаторы завершенных задач
  let finishedTasks = [];

  //Стартовать длительную задачу
  let startLongTask = function (task_id) {
    jQuery.ajax({
      type: 'POST',
      headers: {'cache-control': 'no-cache'},
      url: importpalmira_ajax,
      dataType: 'json',
      data: {
        ajax: true,
        action: 'longprogress',
        long_process: 1,
        task: task_id,
      },
      success: function (data) {
        console.log('start_long_task_new', data);
        finishedTasks.push(task_id);
        console.log('start_long_task_finished_tasks', finishedTasks);
      }
    });
  };

  //Отслеживать прогресс длительной задачи
  let monitorProgress = function (task_id) {
    jQuery.ajax({
      type: 'POST',
      headers: {'cache-control': 'no-cache'},
      url: importpalmira_ajax,
      dataType: 'json',
      data: {
        ajax: true,
        action: 'getprogress',
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
  };

  //Запустить длительную задачу с отслеживанием прогресса
  let runTask = function (task_id) {
    startLongTask(task_id);
    monitorProgress(task_id);
  };

  console.log('test');
  jQuery.ajax({
    type: 'POST',
    headers: {'cache-control': 'no-cache'},
    url: importpalmira_ajax,
    dataType: 'json',
    data: {
      ajax: true,
      action: 'progressnew',
      new_task: 1
    },
    success: function (data) {
      console.log('progress_long', data);
      runTask(data.task);
    }
  });

  function setProgress(value) {
    document.getElementById('importpalmira_progress_view').style.width = value + '%';
    jQuery('#importpalmira_progress_txt')
      .html(value + '%');
  }
});


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
