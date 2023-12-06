<?php

namespace Cmf\Update\Controller;

use Cmf\Http\Controller\AbstractHtmlController;
use Illuminate\Contracts\View\Factory;
use Psr\Http\Message\ServerRequestInterface as Request;

class IndexController extends AbstractHtmlController
{
    /**
     * @var Factory
     */
    protected $view;

    /**
     * @param Factory $view
     */
    public function __construct(Factory $view)
    {
        $this->view = $view;
    }

    public function render(Request $request)
    {
        $view = $this->view->make('cmf.update::app')->with('title', 'Update Cmf');

        $view->with('content', $this->view->make('cmf.update::update'));

        return $view;
    }
}
