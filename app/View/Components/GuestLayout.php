<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class GuestLayout extends Component
{
    public bool $hideAuthHeader;

    public function __construct(bool $hideAuthHeader = false)
    {
        $this->hideAuthHeader = $hideAuthHeader;
    }

    /**
     * Get the view / contents that represents the component.
     */
    public function render(): View
    {
        return view('layouts.guest', [
            'hideAuthHeader' => $this->hideAuthHeader,
        ]);
    }
}
