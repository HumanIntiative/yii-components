<?php

interface EmailNotifInterface
{
    // public function getQueue();
    public function notifyEmail($type);
    public function queueFactory($subject);
}
