<?php

class Unl_View_Helper_FormSelectDay extends Zend_View_Helper_FormSelect
{
    public function formSelectDay($name, $value = null, $attribs = null, $listsep = "<br />\n")
    {
        $options = array();
        $options[-1] = '--Day--';
        for ($i = 1; $i <= 31; $i++) {
            $options[$i] = $i;
        }

        return parent::formSelect($name, $value, $attribs, $options, $listsep);
    }
}
