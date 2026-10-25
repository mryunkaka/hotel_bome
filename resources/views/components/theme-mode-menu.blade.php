<flux:menu.item
    data-theme-mode="light"
    icon="sun"
    x-on:click="$flux.appearance = 'light'"
>
    {{ __('Light') }}
</flux:menu.item>
<flux:menu.item
    data-theme-mode="dark"
    icon="moon"
    x-on:click="$flux.appearance = 'dark'"
>
    {{ __('Dark') }}
</flux:menu.item>
<flux:menu.item
    data-theme-mode="system"
    icon="computer-desktop"
    x-on:click="$flux.appearance = 'system'"
>
    {{ __('System default') }}
</flux:menu.item>
