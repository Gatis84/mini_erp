(function ($) {

    function initDualSelect(config) {

        const $available = $(config.available);
        const $selected  = $(config.selected);
        const existing   = config.existing || [];

        $available.find('option').each(function () {
            if (existing.includes(this.value)) {
                $(this).appendTo($selected).prop('selected', true);
            }
        });

        const move = (from, to, onlySelected = false) => {
            const $opts = onlySelected
                ? from.find('option:selected')
                : from.find('option');

            $opts.appendTo(to)
                 .prop('selected', to.is($selected));
        };

        $(config.addOne).on('click',    () => move($available, $selected, true));
        $(config.addAll).on('click',    () => move($available, $selected));
        $(config.removeOne).on('click', () => move($selected, $available, true));
        $(config.removeAll).on('click', () => move($selected, $available));

        $(config.form).on('submit', () =>
            $selected.find('option').prop('selected', true)
        );
    }

    window.initDualSelect = initDualSelect;

})(jQuery);
