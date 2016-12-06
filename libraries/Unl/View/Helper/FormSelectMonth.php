<?php

class Unl_View_Helper_FormSelectMonth extends Zend_View_Helper_FormSelect
{
    public function formSelectMonth($name, $value = null, $attribs = null, $listsep = "<br />\n")
    {
        $options = array(
            -1 => '--Month--',
            1  => 'January',
            2  => 'February',
            3  => 'March',
            4  => 'April',
            5  => 'May',
            6  => 'June',
            7  => 'July',
            8  => 'August',
            9  => 'September',
            10 => 'October',
            11 => 'November',
            12 => 'December'
        );

        return parent::formSelect($name, $value, $attribs, $options, $listsep);
    }
}
