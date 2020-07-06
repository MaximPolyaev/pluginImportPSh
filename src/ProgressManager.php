<?php
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
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2020 PrestaShop SA
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */

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
    public function __construct($task_id)
    {
        $this->session_initializer = new SessionInitializer;
        $this->task_id = $task_id;
        SessionHelper::set('progress' . $this->task_id, 0);
        SessionHelper::close();
    }

    //Установка количества шагов прогресса
    public function setStepCount($step_count)
    {
        $this->step_count = $step_count;
        $this->current_step = 0;
    }

    //Увеличение прогресса на 1 (переход к следующему шагу)
    public function incrementProgress()
    {
        if (++$this->current_step >= $this->step_count)
            $this->current_step = $this->step_count;

        SessionHelper::init();
        SessionHelper::set('progress' . $this->task_id,
            (int)(($this->current_step * 100.0) / $this->step_count));
        SessionHelper::close();

        header_remove('Set-Cookie');
    }

    public function incrementProgressImportNum($val)
    {
        SessionHelper::init();
        SessionHelper::set('progressimport' . $this->task_id, $val);
        SessionHelper::close();
    }

    public function setProgressMessage($message)
    {
        $messages = self::getProgressMessages();
        $messages[] = $message;

        SessionHelper::init();
        SessionHelper::set('progressdelmessages' . $this->task_id, $messages);
        SessionHelper::close();
    }

    public function setProgressError($error)
    {
        $errors = self::getProgressErrors();
        $errors[] = $error;

        SessionHelper::init();
        SessionHelper::set('progresserrors' . $this->task_id, $errors);
        SessionHelper::close();
    }

    //Завершение подсчета прогресса
    public function __destruct()
    {
        SessionHelper::init();
//       SessionHelper::remove('progress' . $this->task_id);
    }

    //Получение значения прогресса для идентификатора задачи, переданного клиентом
    public static function getProgress()
    {
        $task_id = TaskHelper::getTaskId();
        if ($task_id === null)
            return null;

        $session_initializer = new SessionInitializer;
        $progress = SessionHelper::get('progress' . $task_id, null);

        if ($progress === null)
            return null;

        return (int)$progress;
    }

    public static function getImportProgressNum()
    {
        $task_id = TaskHelper::getTaskId();
        if ($task_id === null)
            return null;

        $session_initializer = new SessionInitializer;
        $progress = SessionHelper::get('progressimport' . $task_id, null);

        if ($progress === null)
            return null;

        return (int)$progress;
    }

    public static function getProgressMessages($delete_messages = false)
    {
        $task_id = TaskHelper::getTaskId();
        if ($task_id === null)
            return null;

        $session_initializer = new SessionInitializer;
        $messages = SessionHelper::get('progressdelmessages' . $task_id, null);
        if ($delete_messages) {
            SessionHelper::remove('progressdelmessages' . $task_id);
        }

        if ($messages === null)
            return [];

        return $messages;
    }

    public static function getProgressErrors($delete_errors = false)
    {
        $task_id = TaskHelper::getTaskId();
        if ($task_id === null) {
            return null;
        }

        $session_initializer = new SessionInitializer;
        $errors = SessionHelper::get('progresserrors' . $task_id, null);

        if ($errors === null) {
            return [];
        }

        if ($delete_errors) {
            SessionHelper::remove('progresserrors' . $task_id);
        }

        return $errors;
    }
}
