<?php

namespace App\View\Components;

use App\Support\Briefings;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\Component;

/**
 * The site navigation. This class decides what to show (which links, which button, whether there
 * is an account menu); the view only draws it, once for desktop and once for the phone menu.
 */
class Navbar extends Component
{
    /** @var list<array{label: string, url: string, current: bool}> */
    public array $links = [];

    /**
     * The single primary button: "Log in" for guests, "Write a briefing" for people who may write.
     * `hidden` means "keep the space but don't show it" (on the write form).
     *
     * @var array{label: string, url: string, hidden: bool}|null
     */
    public ?array $cta = null;

    /** @var array{email: string, logout: string}|null */
    public ?array $account = null;

    public function __construct()
    {
        $request = request();
        $signedIn = auth()->check();

        $this->links[] = [
            'label' => 'Briefings',
            'url' => route('briefings.index'),
            // The list and each briefing page, but not the "create" form that lives under the same path.
            'current' => $request->is('briefings') || ($request->is('briefings/*') && ! $request->is('briefings/create')),
        ];

        if ($signedIn) {
            $this->links[] = [
                'label' => 'My drafts',
                'url' => route('drafts.index'),
                'current' => $request->is('my-drafts', 'my-drafts/*'),
            ];
        }

        if (! $signedIn) {
            $this->cta = ['label' => 'Log in', 'url' => route('login'), 'hidden' => false];
        } elseif (Gate::allows(Briefings::CAN_CREATE)) {
            // On the write form itself you are already there, so the button is hidden. It is still rendered
            // (invisible) so it keeps its place and the links beside it don't shift.
            $this->cta = ['label' => 'Write a briefing', 'url' => route('briefings.create'), 'hidden' => $request->is('briefings/create')];
        }

        if ($signedIn) {
            $email = (string) auth()->user()->email();

            $this->account = [
                'email' => $email,
                // Statamic's own logout route; sends people back to the public list afterwards.
                'logout' => cp_route('logout', ['redirect' => '/briefings']),
            ];
        }
    }

    public function render(): View|Closure|string
    {
        return view('components.navbar');
    }
}
