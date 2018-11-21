<?php

class LocationDataBehavior extends CActiveRecordBehavior
{
    // For Model (Owner)
    protected $_attribute   = 'location_id';
    protected $_attribute1  = 'location_id_1';
    protected $_attribute2  = 'location_id_2';
    protected $_attribute3  = 'location_id_3';
    protected $_attribute4  = 'location_id_4';
    protected $_attrAddress = 'address';
    protected $_attrCountry = 'country_id';

    // For Location Model
    protected $_locationModel   = 'Location';
    protected $_countryModel    = 'Country';
    protected $_attributeId     = 'id';
    protected $_attributeName   = 'location_name';
    protected $_attributeLevel  = 'lev';
    protected $_attributeParent = 'parent_id';

    // Attributes
    protected $_negara;
    protected $_provinsi;
    protected $_kabupaten;
    protected $_kecamatan;
    protected $_kelurahan;

    public function beforeValidate($event)
    {
        if (!empty($this->owner->{$this->_attribute4})) {
            $this->owner->{$this->_attribute} = $this->owner->{$this->_attribute4};
        } elseif (!empty($this->owner->{$this->_attribute3})) {
            $this->owner->{$this->_attribute} = $this->owner->{$this->_attribute3};
        } elseif (!empty($this->owner->{$this->_attribute2})) {
            $this->owner->{$this->_attribute} = $this->owner->{$this->_attribute2};
        } elseif (!empty($this->owner->{$this->_attribute1})) {
            $this->owner->{$this->_attribute} = $this->owner->{$this->_attribute1};
        } else {
            $this->owner->{$this->_attribute} = null;
        }
    }

    public function loadLocation()
    {
        $modelName = $this->_locationModel;
        $modelCountry = $this->_countryModel;

        if (isset($this->owner->{$this->_attrCountry})) {
            if (null !== ($country = $modelCountry::model()->findByPk($this->owner->{$this->_attrCountry}))) {
                $this->_negara = $country->country_name;
            }
        }

        if (empty($this->owner->{$this->_attribute})) {
            return;
        }
        if (null === ($location = $modelName::model()->findByPk($this->owner->{$this->_attribute}))) {
            return;
        }

        $parent = $location;
        while ($parent) {
            switch ($parent->{$this->_attributeLevel}) {
                case 5: //Kelurahan
                    $this->owner->{$this->_attribute4} = $parent->{$this->_attributeId};
                    $this->_kelurahan = $parent->{$this->_attributeName};
                    break;
                case 4: // Kecamatan
                    $this->owner->{$this->_attribute3} = $parent->{$this->_attributeId};
                    $this->_kecamatan = $parent->{$this->_attributeName};
                    break;
                case 3: // Kabupaten
                    $this->owner->{$this->_attribute2} = $parent->{$this->_attributeId};
                    $this->_kabupaten = $parent->{$this->_attributeName};
                    break;
                case 2: // Provinsi
                    $this->owner->{$this->_attribute1} = $parent->{$this->_attributeId};
                    $this->_provinsi = $parent->{$this->_attributeName};
            }
            $parent = $modelName::model()->findByPk($parent->{$this->_attributeParent});
        }
    }

    public function getLocation()
    {
        $modelName = $this->_locationModel;

        if (empty($this->owner->{$this->_attribute})) {
            return;
        }
        if (null === ($location = $modelName::model()->findByPk($this->owner->{$this->_attribute}))) {
            return;
        }

        $parent = $location;
        while ($parent) {
            switch ($parent->{$this->_attributeLevel}) {
                case 5:
                    $kelurahan = $parent->{$this->_attributeName};
                    if (empty($kelurahan)) {
                        break;
                    }
                    // no break
                case 4:
                    $kecamatan = $parent->{$this->_attributeName};
                    if (empty($kecamatan)) {
                        break;
                    }
                    // no break
                case 3:
                    $kabupaten = $parent->{$this->_attributeName};
                    if (empty($kabupaten)) {
                        break;
                    }
                    // no break
                case 2:
                    $provinsi = $parent->{$this->_attributeName};
            }
            $parent = $modelName::model()->findByPk($parent->{$this->_attributeParent});
        }

        switch ($location->{$this->_attributeLevel}) {
            case 5:
                return $lokasi = "Kel. ".$kelurahan." Kec. ".$kecamatan." Kab. ".$kabupaten.", ".$provinsi;
                break;
            case 4:
                return $lokasi = " Kec. ".$kecamatan." Kab. ".$kabupaten.", ".$provinsi;
                break;
            case 3:
                return $lokasi = " Kab. ".$kabupaten.", ".$provinsi;
                break;
            case 2:
                return $lokasi = $provinsi;
        }
    }

    public function getAddressComplete()
    {
        $address = $this->getNegara();
        $address .= '<br />';
        $address .= $this->owner->{$this->_attrAddress};
        $address .= '<br />';
        $address .= $this->getLocation();

        return $address;
    }

    public function getNegara()
    {
        return $this->_negara ? $this->_negara : 'Indonesia'; //"Negara "
    }

    public function getProvinsi()
    {
        return $this->_provinsi;
    }

    public function getKabupaten()
    {
        return $this->_kabupaten;
    }

    public function getKecamatan()
    {
        return $this->_kecamatan;
    }

    public function getKelurahan()
    {
        return $this->_kelurahan;
    }
}
