<?php


namespace MaximCode\ImportPalmira;


final class TaskHelper
{
    private function __construct()
    {
    }

    //Создать уникальный в пределах сессии идентификатор задачи
    static public function generateTaskId()
    {
        $session_initializer = new SessionInitializer;
        $id = SessionHelper::get('max_task', 0) + 1;
        SessionHelper::set('max_task', $id);
        return $id;
    }

    //Получить идентификатор задачи, переданный клиентом
    static public function getTaskId()
    {
        $task_id = WebHelpers::request('task');

        if(!preg_match('/^\d{1,9}$/', $task_id))
            return null;

        return (int)$_REQUEST['task'];
    }
}
