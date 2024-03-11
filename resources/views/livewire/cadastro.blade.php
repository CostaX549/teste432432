<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\WithFileUploads; // Importe a trait WithFileUploads

new  #[Layout('layouts.app')] class extends Component {
    use WithFileUploads; // Use a trait WithFileUploads

    public $amount;
    public $days = ['Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado', 'Domingo'];
    public $item = [];
    public $myDate = [];
    public $photo;

    public function mount()
    {
        foreach ($this->days as $day) {
            $this->item[$day] = '';
            $this->myDate[$day] = '';
        }
    }

}; ?>

<div>

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Cadastro') }}
        </h2>
    </x-slot>

    <x-mary-form wire:submit="save" class="mx-auto max-w-[500px] pt-5">
        <x-mary-input label="Nome" wire:model="name" class="mb-4" />

        @foreach($days as $day)

            <x-mary-checkbox label="{{ ucfirst($day) }}" wire:model="item.{{ $day }}" />
            <div class="flex items-center gap-6">
                <x-mary-datetime label="Horário Inicial" wire:model="myDate.{{ $day }}" icon="o-calendar" type="time" class="w-[225px]" />
                <x-mary-datetime label="Horário Final" wire:model="myDateFinal.{{ $day }}" icon="o-calendar" type="time" class="w-[225px]" />
            </div>
        @endforeach
        <x-mary-file wire:model="photo" accept="image/png, image/jpeg" crop-after-change>
            <img src="{{ auth()->user()->profile_photo_url ?? '/empty-user.jpg' }}" class="h-40 rounded-lg" />
        </x-mary-file>
        <x-slot:actions>
            <x-mary-button label="Cancel" />
            <x-mary-button label="Click me!" class="btn-primary" type="submit" spinner="save" />
        </x-slot:actions>
    </x-mary-form>
</div>
