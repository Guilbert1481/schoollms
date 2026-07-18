/**
 * Test Settings visibility follows the delivery Mode.
 *
 * Timer, availability window, attempts, passing score and result visibility are all
 * ONLINE-delivery concepts. An F2F test is printed and scanned, so the whole block is
 * hidden and the teacher is never asked for them. This is presentation only — the save
 * controller independently nulls those columns whenever the mode is not online, so a
 * stale value left in a hidden input can never reach a saved F2F test.
 */
(function () {
    document.addEventListener('DOMContentLoaded', function () {
        const mode = document.getElementById('mode-select');
        const section = document.getElementById('test-settings-section');
        const divider = document.getElementById('test-settings-divider');

        if (!mode || !section) {
            return;
        }

        function apply() {
            const show = mode.value === 'online';
            section.style.display = show ? '' : 'none';
            if (divider) {
                divider.style.display = show ? '' : 'none';
            }
        }

        mode.addEventListener('change', apply);

        // Run once on load so a pre-selected mode (editing an existing test) is honoured.
        apply();
    });
})();
