@props(['name', 'value' => null, 'dir' => null, 'rows' => 12])

@php
    $dir = $dir ?? (app()->getLocale() === 'en' ? 'ltr' : 'rtl');
@endphp

<div class="overflow-hidden rounded-xl border border-ink-300 bg-white {{ $name && $errors->has($name) ? 'border-danger-500' : '' }}"
     x-data="{
        sync() {
            this.$refs.input.value = this.$refs.editor.innerHTML;
        },
        command(name, value = null) {
            this.$refs.editor.focus();
            document.execCommand(name, false, value);
            this.sync();
        },
        setFont(event) {
            this.command('fontName', event.target.value);
            event.target.selectedIndex = 0;
        },
        setSize(event) {
            this.command('fontSize', event.target.value);
            event.target.selectedIndex = 0;
        },
        setColor(event) {
            this.command('foreColor', event.target.value);
        },
        link() {
            const url = window.prompt(@js(__('admin.editor.link_prompt')), 'https://');
            if (url) {
                this.command('createLink', url);
            }
        }
     }"
     x-init="$refs.editor.innerHTML = $refs.input.value">
    <div class="flex flex-wrap items-center gap-1 border-b border-ink-200 bg-ink-50 px-2 py-1.5" dir="ltr">
        <button type="button" class="rich-toolbar-btn" @click="command('bold')" title="{{ __('admin.editor.bold') }}"><span class="font-bold">B</span></button>
        <button type="button" class="rich-toolbar-btn" @click="command('italic')" title="{{ __('admin.editor.italic') }}"><span class="italic">I</span></button>
        <button type="button" class="rich-toolbar-btn" @click="command('underline')" title="{{ __('admin.editor.underline') }}"><span class="underline">U</span></button>
        <span class="mx-1 h-4 w-px bg-ink-200"></span>
        <button type="button" class="rich-toolbar-btn text-xs font-semibold" @click="command('formatBlock', 'h2')">H2</button>
        <button type="button" class="rich-toolbar-btn text-xs font-semibold" @click="command('formatBlock', 'h3')">H3</button>
        <button type="button" class="rich-toolbar-btn text-xs" @click="command('formatBlock', 'p')">P</button>
        <span class="mx-1 h-4 w-px bg-ink-200"></span>
        <button type="button" class="rich-toolbar-btn" @click="command('insertUnorderedList')" title="{{ __('admin.editor.list') }}">•</button>
        <button type="button" class="rich-toolbar-btn" @click="command('insertOrderedList')" title="{{ __('admin.editor.numbered') }}">1.</button>
        <button type="button" class="rich-toolbar-btn text-xs" @click="command('justifyRight')" title="{{ __('admin.editor.align_right') }}">R</button>
        <button type="button" class="rich-toolbar-btn text-xs" @click="command('justifyCenter')" title="{{ __('admin.editor.align_center') }}">C</button>
        <button type="button" class="rich-toolbar-btn text-xs" @click="command('justifyLeft')" title="{{ __('admin.editor.align_left') }}">L</button>
        <span class="mx-1 h-4 w-px bg-ink-200"></span>
        <select class="h-8 rounded-md border-0 bg-white text-xs text-ink-700 ring-1 ring-ink-200" @change="setFont($event)">
            <option value="">{{ __('admin.editor.font') }}</option>
            <option value="Almarai">Almarai</option>
            <option value="IBM Plex Sans Arabic">IBM Plex</option>
            <option value="Inter">Inter</option>
            <option value="Georgia">Georgia</option>
            <option value="Arial">Arial</option>
        </select>
        <select class="h-8 rounded-md border-0 bg-white text-xs text-ink-700 ring-1 ring-ink-200" @change="setSize($event)">
            <option value="">{{ __('admin.editor.size') }}</option>
            <option value="2">{{ __('admin.editor.size_small') }}</option>
            <option value="3">{{ __('admin.editor.size_normal') }}</option>
            <option value="5">{{ __('admin.editor.size_large') }}</option>
            <option value="7">{{ __('admin.editor.size_xl') }}</option>
        </select>
        <label class="rich-toolbar-btn cursor-pointer" title="{{ __('admin.editor.color') }}">
            A
            <input type="color" class="sr-only" value="#1e3a5f" @input="setColor($event)">
        </label>
        <button type="button" class="rich-toolbar-btn text-xs" @click="link()">{{ __('admin.editor.link') }}</button>
        <button type="button" class="rich-toolbar-btn text-xs" @click="command('removeFormat')" title="{{ __('admin.editor.clear') }}">Tx</button>
    </div>

    <div x-ref="editor"
         class="rich-editor min-h-48 px-3 py-2 text-sm leading-7 text-ink-800 outline-none"
         contenteditable="true"
         dir="{{ $dir }}"
         @input="sync()"
         @blur="sync()"></div>

    <textarea x-ref="input" name="{{ $name }}" id="{{ $name }}" class="hidden" rows="{{ $rows }}">{{ old($name, $value) }}</textarea>
</div>
