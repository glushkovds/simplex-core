<?php
namespace Simplex\Core\Html;

use Simplex\Core\Response;

class HtmlResponse extends Response
{
    protected $content = '';

    public function __construct()
    {
        parent::__construct();
        $this->setContentType('text/html');
    }

    public function setContent(string $content)
    {
        $this->content = $content;
    }

    public function addContent(string $content)
    {
        $this->content .= $content;
    }

    public function addContentFromTemplate(string $templatePath, array $params)
    {
        if (file_exists($templatePath)) {
            extract($params, EXTR_SKIP);

            ob_start();
            include $templatePath;
            $this->addContent(ob_get_clean());
        }
    }

    protected function makeBody(): string
    {
        return $this->content;
    }
}