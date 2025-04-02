export const select = (name, options, required = false) => {
  let opts;

  if (Array.isArray(options)) {
    // Handle array of objects with value and label properties
    opts = options.map(option => 
      `<option value="${option.value}">${option.label}</option>`
    ).join('');
  } else if (typeof options === 'object') {
    // Handle plain object with key-value pairs
    opts = Object.entries(options).map(([key, value]) => 
      `<option value="${key}">${value}</option>`
    ).join('');
  } else {
    // Handle simple array of strings
    opts = options.map(option => 
      `<option value="${option}">${option}</option>`
    ).join('');
  }

  return `<select name="${name}" ${required ? 'required' : ''}>${opts}</select>`;
};

export const text = (name, placeholder, required = false) => {
  return `<input type="text" name="${name}" placeholder="${placeholder}" ${required ? 'required' : ''}/>`;
};

export const email = (name, placeholder, required = false) => {
  return `<input type="email" name="${name}" placeholder="${placeholder}" pattern="[^@]+@[^@]+\.[a-zA-Z]{2,6}" title="Adresse mail non valide" ${required ? 'required' : ''}/>`;
};

export const tel = (name, placeholder, required = false) => {
  return `<input type="tel" name="${name}" placeholder="${placeholder}" pattern="0[0-9]{9}" ${required ? 'required' : ''}/>`;
};

export const textarea = (name, placeholder, required = false) => {
  return `<textarea name="${name}" placeholder="${placeholder}" ${required ? 'required' : ''}></textarea>`;
};

export const checkbox = (name, value, required = false) => {
  return `<input type="checkbox" name="${name}" value="${value}" ${required ? 'required' : ''}/>`;
};

export const radio = (name, value, id, label, required = false) => {
  return `
    <input type="radio" name="${name}" id="${id}" value="${value}" ${required ? 'required' : ''}/>
    <label for="${id}">${label}</label>
  `;
};

export const range = (name, min = 0, max = 5, step = 1, value = 0) => {
  return `
    <div class="range">
      <div class="range-slider">
        <input
          name="${name}"
          type="range"
          autocomplete="off"
          min="${min}"
          max="${max}"
          step="${step}"
          value="${value}"
          class="range-input"
        />
        <div class="sliderticks">
          <span class="small">Minimum</span>
          <span></span>
          <span></span>
          <span></span>
          <span></span>
          <span class="small">Maximum</span>
        </div>
      </div>
    </div>
  `;
};

export const date = (name, placeholder, required = false, dateStartFrom = null) => {
  return `
    <input type="text" data-litepicker data-date-start-from="${dateStartFrom || ''}" name="${name}" placeholder="${placeholder}" ${required ? 'required' : ''} autocomplete="off"/>
  `;
};

export const validateField = (field) => {
  const isValid = field.checkValidity();
  field.classList.toggle('field-invalid', !isValid);
  return isValid;
};

export const validateTab = (tabElement) => {
  const fields = tabElement.querySelectorAll('input:not([type="hidden"]), select, textarea');
  let isValid = true;
  fields.forEach(field => {
    if (!validateField(field)) {
      isValid = false;
    }
  });
  return isValid;
};

export const scrollTo = (el) => {
  el.scrollIntoView({ behavior: 'smooth', block: 'start' });
};

export const updateProgress = (activeTab) => {
  const tabs = document.querySelectorAll('[data-tab]');
  const progress = (Array.from(tabs).indexOf(activeTab) + 1) * 25;
  const progressBar = document.querySelector('.progress-bar');

  progressBar.style.width = `${progress}%`;
  progressBar.textContent = `${progress}%`;
};
