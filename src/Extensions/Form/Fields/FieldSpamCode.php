<?php

namespace Simplex\Extensions\Form\Fields;

class FieldSpamCode extends Field
{

    public $v_requied = 1;

    public function html()
    {
        $val = isset($_POST[$this->form][$this->name]) ? $_POST[$this->form][$this->name] : '';
        echo '<span class="plug-form-spamcode">';
//        PlugSpamcode::html($this->form);
        echo '</span>';
    }

    public function &check()
    {
        $err = array();
        $_POST[$this->form][$this->name] = isset($_POST[$this->form][$this->name]) ? $_POST[$this->form][$this->name] : '';
        if ($_POST[$this->form][$this->name] === '' || $_POST[$this->form][$this->name] !== $_SESSION['spamcode']) {
            $err[$this->name][] = "спам-код введен неверно";
        }
        return $err;
    }

}
