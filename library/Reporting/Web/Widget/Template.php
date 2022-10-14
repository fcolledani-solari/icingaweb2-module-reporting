<?php

// Icinga Reporting | (c) 2018 Icinga GmbH | GPLv2

namespace Icinga\Module\Reporting\Web\Widget;

use Icinga\Module\Reporting\Common\Macros;
use Icinga\Module\Reporting\Database;
use ipl\Html\BaseHtmlElement;
use ipl\Orm\Model;
use ipl\Stdlib\Filter;

class Template extends BaseHtmlElement
{
    use Database;
    use Macros;

    protected $tag = 'div';

    protected $defaultAttributes = ['class' => 'template'];

    /** @var CoverPage */
    protected $coverPage;

    /** @var HeaderOrFooter */
    protected $header;

    /** @var HeaderOrFooter */
    protected $footer;

    protected $preview;

    /** @var Template */
    protected $model;

    public static function getDataUrl(array $image = null)
    {
        if (empty($image)) {
            return 'data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==';
        }

        return sprintf('data:%s;base64,%s', $image['mime_type'], $image['content']);
    }

    public static function fromModel(Model $model)
    {
        $template = new static();

        if ($model->settings === null) {
            return null;
        }

        $model->settings = json_decode($model->settings, true);

        $coverPage = (new CoverPage())
            ->setColor($model->settings['color'])
            ->setTitle($model->settings['title']);

        if (isset($model->settings['cover_page_background_image'])) {
            $coverPage->setBackgroundImage($model->settings['cover_page_background_image']);
        }

        if (isset($model->settings['cover_page_logo'])) {
            $coverPage->setLogo($model->settings['cover_page_logo']);
        }

        $template
            ->setCoverPage($coverPage)
            ->setHeader(new HeaderOrFooter(HeaderOrFooter::HEADER, $model->settings))
            ->setFooter(new HeaderOrFooter(HeaderOrFooter::FOOTER, $model->settings));

        return $template;
    }

    /**
     * @return CoverPage
     */
    public function getCoverPage()
    {
        return $this->coverPage;
    }

    /**
     * @param CoverPage $coverPage
     *
     * @return $this
     */
    public function setCoverPage(CoverPage $coverPage)
    {
        $this->coverPage = $coverPage;

        return $this;
    }

    /**
     * @return HeaderOrFooter
     */
    public function getHeader()
    {
        return $this->header;
    }

    /**
     * @param HeaderOrFooter $header
     *
     * @return $this
     */
    public function setHeader($header)
    {
        $this->header = $header;

        return $this;
    }

    /**
     * @return HeaderOrFooter
     */
    public function getFooter()
    {
        return $this->footer;
    }

    /**
     * @param HeaderOrFooter $footer
     *
     * @return $this
     */
    public function setFooter($footer)
    {
        $this->footer = $footer;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getPreview()
    {
        return $this->preview;
    }

    /**
     * @param mixed $preview
     *
     * @return $this
     */
    public function setPreview($preview)
    {
        $this->preview = $preview;

        return $this;
    }

    protected function assemble()
    {
        if ($this->preview) {
            $this->getAttributes()->add('class', 'preview');
        }

        $this->add($this->getCoverPage()->setMacros($this->macros));

//        $page = Html::tag(
//            'div',
//            ['class' => 'main'],
//            Html::tag('div', ['class' => 'page-content'], [
//                $this->header->setMacros($this->macros),
//                Html::tag(
//                    'div',
//                    [
//                        'class' => 'main'
//                    ]
//                ),
//                $this->footer->setMacros($this->macros)
//            ])
//        );
//
//        $this->add($page);
    }
}
