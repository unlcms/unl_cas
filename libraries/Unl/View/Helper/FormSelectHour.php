<?php

class Unl_View_Helper_FormSelectHour extends Zend_View_Helper_FormSelect
{
    public function formSelectHour($name, $value = null, $attribs = null, $listsep = "<br />\n")
    {
        $options = array();
        $options[-1] = '--';
        for ($i = 0; $i < 24; $i++) {
            $options[$i] = str_pad($i, 2, '0', STR_PAD_LEFT);
        }
        if ($value !== null) {
            $value = intval($value);
        }
        return parent::formSelect($name, $value, $attribs, $options, $listsep);
    }
}
