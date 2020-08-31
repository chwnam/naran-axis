<?php


namespace Naran\Axis\View\Admin;


use Naran\Axis\View\Admin\FieldWidgets\FieldWidget;
use WP_Post;


abstract class PropertyMetaBoxView extends MetaBoxView
{
    private $template = 'generics/property-meta-box.php';

    /**
     * Return list of field widgets
     *
     * @param $post
     *
     * @return FieldWidget[]
     */
    abstract protected function getFieldWidgets($post);

    /**
     * Render meta box.
     *
     * @param WP_Post $post
     *
     * @return void
     */
    public function renderMetabox($post)
    {
        $nonce = $this->getNonce();

        $this->plainRender(
            $this->getTemplate(),
            [
                'content_header' => $this->getContentHeader(),
                'content_footer' => $this->getContentFooter(),
                'table_header'   => $this->getTableHeader(),
                'table_footer'   => $this->getTableFooter(),
                'nonce_arg'      => $nonce->getQueryArg(),
                'nonce_action'   => $nonce->getAction(),
                'table_attrs'    => $this->getTableAttrs(),
                'widgets'        => $this->getFieldWidgets($post),
            ]
        );
    }

    /**
     * Return template name.
     *
     * @return string
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * Set template.
     *
     * @param string $template
     *
     * @return $this
     */
    public function setTemplate($template)
    {
        $this->template = $template;

        return $this;
    }

    /**
     * Return HTML code above table.
     *
     * @return string
     */
    protected function getContentHeader()
    {
        return '';
    }

    /**
     * Return HTML code below table.
     *
     * @return string
     */
    protected function getContentFooter()
    {
        return '';
    }

    /**
     * Return table header HTML code.
     *
     * @return string
     */
    protected function getTableHeader()
    {
        return '';
    }

    /**
     * Return table footer HTML code.
     *
     * @return string
     */
    protected function getTableFooter()
    {
        return '';
    }

    /**
     * Table tag extra class.
     *
     * @return array
     */
    protected function getTableAttrs()
    {
        return [
            'class' => 'form-table',
        ];
    }
}
