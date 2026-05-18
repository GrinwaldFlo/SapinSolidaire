@props([
    'suggestions' => [],
    'model' => '',
    'blur' => null,
    'disabled' => false,
    'hasError' => false,
])

{{--
    Combobox
    ────────
    An Alpine.js-powered combobox that:
    - Filters suggestions as the user types (accent-insensitive)
    - Allows completely free-form input (no restriction)
    - Supports keyboard navigation (↑ ↓ Enter Escape)
    - Closes when clicking outside
    Props:
      model       – Livewire wire:model path (string)
      blur        – wire:blur action string (optional)
      suggestions – array of suggestion strings
      disabled    – boolean
      hasError    – boolean (switches to error styling)
--}}
<div
    x-data="{
        open: false,
        activeIndex: -1,
        query: '',
        suggestions: {{ Js::from($suggestions) }},
        normalize(str) {
            return str.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
        },
        get filtered() {
            const results = this.query
                ? this.suggestions.filter(s => this.normalize(s).includes(this.normalize(this.query)))
                : [...this.suggestions];
            return results.sort((a, b) => this.normalize(a).localeCompare(this.normalize(b)));
        },
        select(s) {
            this.query = s;
            this.$refs.input.value = s;
            this.$refs.input.dispatchEvent(new Event('input', { bubbles: true }));
            this.$refs.input.dispatchEvent(new Event('change', { bubbles: true }));
            this.open = false;
            this.activeIndex = -1;
        },
        onInput(e) {
            this.query = e.target.value;
            this.activeIndex = -1;
            this.open = this.filtered.length > 0;
        },
        onFocus(e) {
            this.query = e.target.value;
            if (this.filtered.length > 0) this.open = true;
        },
        onArrowDown() {
            if (!this.open) {
                this.open = this.filtered.length > 0;
                return;
            }
            this.activeIndex = Math.min(this.activeIndex + 1, this.filtered.length - 1);
            this.scrollActive();
        },
        onArrowUp() {
            this.activeIndex = Math.max(this.activeIndex - 1, -1);
            if (this.activeIndex < 0) this.open = false;
            else this.scrollActive();
        },
        onEnter() {
            if (this.open && this.activeIndex >= 0 && this.filtered[this.activeIndex]) {
                this.select(this.filtered[this.activeIndex]);
            } else {
                this.open = false;
            }
        },
        scrollActive() {
            this.$nextTick(() => {
                const list = this.$refs.list;
                const item = list?.children[this.activeIndex];
                item?.scrollIntoView({ block: 'nearest' });
            });
        }
    }"
    x-on:click.outside="open = false; activeIndex = -1"
    class="relative"
>
    <input
        x-ref="input"
        type="text"
        wire:model="{{ $model }}"
        @if($blur) wire:blur="{{ $blur }}" @endif
        x-on:input="onInput($event)"
        x-on:focus="onFocus($event)"
        x-on:keydown.escape.prevent="open = false; activeIndex = -1"
        x-on:keydown.enter.prevent="onEnter()"
        x-on:keydown.arrow-down.prevent="onArrowDown()"
        x-on:keydown.arrow-up.prevent="onArrowUp()"
        autocomplete="off"
        role="combobox"
        :aria-expanded="open.toString()"
        aria-haspopup="listbox"
        aria-autocomplete="list"
        :aria-controls="$id('combobox-list')"
        :aria-activedescendant="open && activeIndex >= 0 ? $id('combobox-option', activeIndex) : null"
        class="{{ $hasError ? 'field-input-error' : 'field-input' }}"
        @if($disabled) disabled @endif
    />

    <ul
        x-ref="list"
        x-show="open && filtered.length > 0"
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="opacity-0 -translate-y-1"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 -translate-y-1"
        :id="$id('combobox-list')"
        class="absolute z-30 left-0 right-0 mt-1 bg-white dark:bg-zinc-800 border border-gray-200 dark:border-zinc-600 rounded-lg shadow-lg max-h-52 overflow-y-auto"
        role="listbox"
    >
        <template x-for="(s, i) in filtered" :key="s">
            <li
                :id="$id('combobox-option', i)"
                x-text="s"
                x-on:mousedown.prevent="select(s)"
                :class="i === activeIndex
                    ? 'bg-green-100 dark:bg-green-900/30 text-green-900 dark:text-green-100'
                    : 'text-gray-800 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-zinc-700'"
                class="px-4 py-2.5 cursor-pointer text-sm transition-colors"
                role="option"
                :aria-selected="(i === activeIndex).toString()"
            ></li>
        </template>
    </ul>
</div>
