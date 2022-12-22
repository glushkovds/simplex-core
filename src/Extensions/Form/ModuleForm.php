<?php

namespace Simplex\Extensions\Form;

use Simplex\Extensions\Form\Fields\Field;
use Simplex\Extensions\Form\Fields\FieldCheckbox;
use Simplex\Extensions\Form\Fields\FieldEmail;
use Simplex\Extensions\Form\Fields\FieldPassword;
use Simplex\Extensions\Form\Fields\FieldSpamCode;
use Simplex\Extensions\Form\Fields\FieldString;
use Simplex\Extensions\Form\Fields\FieldText;
use Simplex\Core\Models\ModelSettings;
use Simplex\Core\Alert\Site\Alert;
use Simplex\Core\Container;
use Simplex\Core\Helpers\Str;
use Simplex\Core\Mail;
use Simplex\Core\ModuleBase;

class ModuleForm extends ModuleBase
{

    protected $name;
    protected $submitButtonText;
    /** @var Field[] */
    protected $fields = [];
    protected static $formTplDefault = 'form.tpl';
    protected $formTpl;
    protected $errors;
    protected $action = '';
    protected $method = 'post';

    protected const TYPE_MAP = [
        'string' => FieldString::class,
        'checkbox' => FieldCheckbox::class,
        'email' => FieldEmail::class,
        'password' => FieldPassword::class,
        'spamCode' => FieldSpamCode::class,
        'text' => FieldText::class,
    ];

    public function __construct($module)
    {
        parent::__construct($module);
        $this->name = Str::translite($this->title);
        $this->submitButtonText = $this->params['submit_button_text'] ?? 'Отправить';
        $this->fillFields(explode("\n", $this->params['fields'] ?? ''));
        $this->formTpl = $this->params['form_tpl'] ?? self::$formTplDefault;
        $this->maybeSubmit();
    }

    protected function maybeSubmit()
    {
        if ($this->submitted()) {
            if ($this->validate()) {
                $to = ModelSettings::get('form_email');
                if (empty($to)) {
                    Alert::warning('Сообщение не может быть отправлено по техническим причинам, попробуйте позднее [0xfe]');
                    return;
                }
                $subject = 'Заполненная форма ' . $this->title;

                $rows = [];
                $rows[] = "Отправлено со страницы " . Container::getRequest()->getFullUrl();
                $rows[] = "";
                foreach ($this->fields as $field) {
                    $rows[] = $field->getLabel() . ': ' . $field->getPOST();
                }
                $rows[] = "";
                $rows[] = "---";
                $rows[] = 'Simplex Framework';
                if (Mail::create($to, $subject, join("\r\n", $rows))->setAsPlain()->send()) {
                    Alert::success('Ваше сообщение отправлено, Мы ответим Вам в ближайшее время');
                } else {
                    Alert::warning('Сообщение не может быть отправлено по техническим причинам, попробуйте позднее');
                }
            } else {
                Alert::warning(
                    'При заполнении формы возникли ошибки<span style="display:none">' . $this->errorsStr() . '</span>'
                );
            }
        }
    }

    protected function errorsStr(): string
    {
        $ar = [];
        foreach ($this->errors as $field => $errors) {
            $ar[] = "$field: " . implode(', ', $errors);
        }
        return implode('; ', $ar);
    }

    protected function submitted(): bool
    {
        return isset($_POST[$this->name]['submit']);
    }

    public function validate(): bool
    {
        $errors = array();
        foreach ($this->fields as $field) {
            $errors = array_merge($errors, $field->check());
        }
        $this->errors = $errors;
        return !count($this->errors);
    }

    protected function fillFields(array $fields)
    {
        $this->fields = [];
        foreach ($fields as $fieldRaw) {
            $ar = explode(':', $fieldRaw);
            $title = $ar[0];
            $name = Str::translite($title);
            $type = $ar[1] ?? 'string';
            $required = $ar[2] ?? false;
            $fieldClass = self::TYPE_MAP[$type] ?? FieldString::class;
            /** @var Field $field */
            $field = new $fieldClass($this, $name, $title, '', 0, '', $required);
            $this->fields[$name] = $field;
        }
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    protected function content()
    {
        include static::findTemplateFile($this->formTpl);
    }

    protected static function findTemplateFile(string $name): string
    {
        if (is_file($path = SF_ROOT_PATH . '/Extensions/Form/tpl/' . $name)) {
            return $path;
        }
        if (is_file($path = __DIR__ . '/tpl/' . $name)) {
            return $path;
        }
        return __DIR__ . '/tpl/' . self::$formTplDefault;
    }
}