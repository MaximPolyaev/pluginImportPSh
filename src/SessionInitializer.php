<?php


namespace MaximCode\ImportPalmira;


class SessionInitializer
{
    //Была ли инициализирована сессия при создании класса
    private $session_initialized;

    public function __construct()
    {
        $this->session_initialized = SessionHelper::isStarted();
        SessionHelper::init();
    }

    public function __destruct()
    {
        if(!$this->session_initialized)
            SessionHelper::close();
    }
}
