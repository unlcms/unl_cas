<?php

class Unl_View_Helper_TextDiff extends Zend_View_Helper_Abstract
{
    static protected $_diffRenderer;

    public function textDiff($from, $to)
    {
        if ($from == $to) {
            return $from;
        }
        if (!self::$_diffRenderer) {
            Zend_Loader_Autoloader::getInstance()->registerNamespace('Horde_');
            self::$_diffRenderer = new Horde_Text_Diff_Renderer_Inline();
        }

        $diff = new Horde_Text_Diff('auto',
            array(
                explode(' ', strtr($from, array("\n" => '*NEWLINE*'))),
                explode(' ', strtr($to,   array("\n" => '*NEWLINE*')))
            )
        );
        $render = new Horde_Text_Diff_Renderer_Inline();
        return strtr($render->render($diff), array('*NEWLINE*' => '<br/>'));
    }
}
