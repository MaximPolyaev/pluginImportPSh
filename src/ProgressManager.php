<?php


namespace MaximCode\ImportPalmira;


class ProgressManager
{
    //Идентификатор задачи
    private $task_id = 0;
    //Количество шагов в задаче
    private $step_count = 1;
    //Текущий шаг
    private $current_step = 0;
    //Инициализатор сессии на время работы менеджера
    private $session_initializer;

    //Создание менеджера прогресса для задачи с идентификатором $task_id
    public function __construct($task_id, $current_step = -1)
    {
        $this->session_initializer = new SessionInitializer;
        $this->task_id = $task_id;
        SessionHelper::set('progress' . $this->task_id, $current_step < 0 ? 0 : $current_step);
        SessionHelper::close();
    }

    //Установка количества шагов прогресса
    public function setStepCount($step_count, $current_step = -1)
    {
        $this->step_count = $step_count;
        $this->current_step = $current_step < 0 ? 0 : $current_step;
    }

    //Увеличение прогресса на 1 (переход к следующему шагу)
    public function incrementProgress()
    {
        if(++$this->current_step >= $this->step_count)
            $this->current_step = $this->step_count;

        SessionHelper::init();
        SessionHelper::set('progress' . $this->task_id,
            (int)(($this->current_step * 100.0) / $this->step_count));
        SessionHelper::close();

        header_remove('Set-Cookie');
    }

    //Завершение подсчета прогресса
    public function __destruct()
    {
        SessionHelper::init();
//        SessionHelper::remove('progress' . $this->task_id);
    }

    //Получение значения прогресса для идентификатора задачи, переданного клиентом
    public static function getProgress()
    {
        $task_id = TaskHelper::getTaskId();
        if($task_id === null)
            return null;

        $session_initializer = new SessionInitializer;
        $progress = SessionHelper::get('progress' . $task_id, null);

        if($progress === null)
            return null;

        return (int)$progress;
    }
}
