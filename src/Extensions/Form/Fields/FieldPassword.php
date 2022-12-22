<?php

namespace Simplex\Extensions\Form\Fields;

class FieldPassword extends Field
{

    public function html()
    {
        $val = isset($_POST[$this->form][$this->name]) ? $_POST[$this->form][$this->name] : '';
        echo '<span class="plug-form-field"><input type="password" name="', $this->form, '[', $this->name, ']', '" value="', htmlspecialchars(
            $val
        ), '" /></span>';
    }

}