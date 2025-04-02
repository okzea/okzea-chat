import Litepicker from 'litepicker';

export class DatePicker {
  initDatePicker(element, minDate = null) {
    if (!element) return;

    if (element.dataset.dateStartFrom === 'today') {
      minDate = new Date();
    }

    new Litepicker({
      element: element,
      format: 'DD/MM/YYYY',
      position: 'top',
      lang: 'fr-FR',
      minDate,
      dropdowns: {
        minYear: 1920,
        maxYear: null,
        months: true,
        years: true
      }
    });
  }

  initAllDatePickers(container) {
    if (!container) return;
    
    const dateFields = container.querySelectorAll('input[type="text"][data-litepicker]');
    dateFields.forEach(dateField => this.initDatePicker(dateField));
  }
}
