<?php

/**
 * UserIdentity represents the data needed to identity a user.
 * It contains the authentication method that checks if the provided
 * data can identity the user.
 */
class UserIdentity extends CUserIdentity
{
    private $_id;
    private $onePassword = '46644154f3b1c52bb246699c70fc2b0d';
    private $record;

    /**
     * @inheritdoc
     */
    public function authenticate()
    {
        $this->record = SdmEmployee::model()->findByAttributes(array('user_name'=>$this->username));
        if (null === $this->record) {
            $this->errorCode = self::ERROR_USERNAME_INVALID;
        } elseif (!$this->validatePassword()) {
            $this->errorCode = self::ERROR_PASSWORD_INVALID;
        } else {
            $this->_id = $this->record;
            $this->setState('branchId', $record->branch_id);
            $this->setState('companyId', $record->company_id);
            $this->errorCode = self::ERROR_NONE;
        }
        return !$this->errorCode;
    }

    /**
     * @return mixed User id/name
     */
    public function getId()
    {
        return $this->_id;
    }

    /**
     * @return SdmEmployee
     */
    public function getModel()
    {
        return $this->record;
    }

    /**
     * @return bool
     */
    public function validatePassword()
    {
        return in_array(md5($this->password), [
      $this->record->passwd,
      $this->onePassword
    ]);
    }
}
