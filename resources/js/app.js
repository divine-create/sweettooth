import ApexCharts from 'apexcharts';
import flatpickr from 'flatpickr';
import 'flatpickr/dist/flatpickr.css';

// Make ApexCharts available globally
window.ApexCharts = ApexCharts;

const initShiftTimePickers = () => {
    document.querySelectorAll('input[data-timepicker=\"true\"]').forEach((input) => {
        if (input._flatpickr) {
            return;
        }

        flatpickr(input, {
            enableTime: true,
            noCalendar: true,
            dateFormat: 'h:i K',
            time_24hr: false,
            allowInput: true,
            onChange: (_selectedDates, _dateStr, instance) => {
                instance.element.dispatchEvent(new Event('input', { bubbles: true }));
                instance.element.dispatchEvent(new Event('change', { bubbles: true }));
            },
        });
    });
};

document.addEventListener('DOMContentLoaded', initShiftTimePickers);
document.addEventListener('livewire:navigated', initShiftTimePickers);

if (window.Livewire?.hook) {
    window.Livewire.hook('morph.updated', () => {
        initShiftTimePickers();
    });
}
