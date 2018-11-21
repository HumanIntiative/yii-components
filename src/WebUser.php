<?php

class WebUser extends PRights
{
    public $attributesConfig = array();
    public $returnUrl = array('site/index');

    /**
     * @inheritdoc
     */
    public function login($identity, $duration=0)
    {
        $validLogin = parent::login($identity, $duration);
        // Set state
        foreach ($this->attributesConfig as $key => $value) {
            if (is_string($value)) {
                $this->setState($key, $identity->model->$value);
            } else {
                $this->setState($key, $value);
            }
        }
    }
}
