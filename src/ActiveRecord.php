<?php
/**
 * ActiveRecord Class
 */

/**
 * Modifikasi AR dengan konfigurasi nama sequence tabel secara manual
 *
 * @category Components
 * @package  Yii.Components
 * @author   Bagus P. Priyantono <bagus@pkpu.org>
 * @license  https://opensource.org/licenses/Apache-2.0 Apache License 2.0
 * @link     https://github.com/pkpudev/yii-components
 */
class ActiveRecord extends CActiveRecord
{
    /**
     * Sequence Name
     *
     * @var $tableSequenceName
     */
    protected $tableSequenceName;

    /**
     * Bugfix Insert to Model with Postgres connection
     *
     * @param array $attributes Attributes
     *
     * @return bool
     */
    public function insert($attributes=null)
    {
        if (!$this->getIsNewRecord()) {
            throw new CDbException(
                Yii::t('yii', 'The AR cannot be inserted because it is not new.')
            );
        }
        if (!$this->beforeSave()) return false;
        
        Yii::trace(get_class($this).'.insert()', 'system.db.ar.CActiveRecord');

        $builder = $this->getCommandBuilder();
        $table = $this->getMetaData()->tableSchema;
        if (isset($this->tableSequenceName)
            && !empty($this->tableSequenceName)
            && is_string($this->tableSequenceName)
        ) {
            $table->sequenceName = $this->tableSequenceName;
        }
        $command = $builder->createInsertCommand(
            $table,
            $this->getAttributes($attributes)
        );
        if ($command->execute()) {
            $primaryKey = $table->primaryKey;
            if ($table->sequenceName !== null) {
                if (is_string($primaryKey) && $this->$primaryKey === null) {
                    $this->$primaryKey = $builder->getLastInsertID($table);
                } elseif (is_array($primaryKey)) {
                    foreach ($primaryKey as $pk) {
                        if ($this->$pk === null) {
                            $this->$pk = $builder->getLastInsertID($table);
                            break;
                        }
                    }
                }
            }
            $this->_pk = $this->getPrimaryKey();
            $this->afterSave();
            $this->setIsNewRecord(false);
            $this->setScenario('update');
            return true;
        }
    }
}
