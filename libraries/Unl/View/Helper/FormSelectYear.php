<?php

class Unl_View_Helper_FormSelectYear extends Zend_View_Helper_FormSelect
{
    public function formSelectYear($name, $value = null, $attribs = null, $listsep = "<br />\n")
    {
        $options = array();
        $options[-1] = '--Year--';
        for ($i = 2006; $i <= date('Y') + 1; $i++) {
            $options[$i] = $i;
        }

        return parent::formSelect($name, $value, $attribs, $options, $listsep);
    }
}
