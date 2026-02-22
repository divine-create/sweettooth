<?php

namespace App\View\Components;

use Illuminate\View\Component;

class Breadcrumb extends Component
{
    public array $items;

    public ?string $title;

    public bool $compact;

    public bool $withIcons;

    /**
     * Create a new component instance.
     */
    public function __construct(array $items = [], ?string $title = null, bool $compact = false, bool $withIcons = false)
    {
        $this->items = $items;
        $this->title = $title;
        $this->compact = $compact;
        $this->withIcons = $withIcons;
    }

    public function render()
    {
        return view('components.breadcrumb');
    }
}
