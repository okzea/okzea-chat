import { DatePicker } from './date-picker';
import { ChatbotConversation } from './chatbot-conversation';

class MututalysChatbot extends HTMLElement {
  constructor() {
    super();
    this.form = null;
    this.insuredCount = 0;
    this.conversation = null;

    // Create a shadow root
    this.attachShadow({ mode: 'open' });
  }

  connectedCallback() {
    // Load styles first
    this.loadStyles().then(() => {
      // Create container for form in shadow DOM
      const formContainer = document.createElement('div');
      formContainer.id = 'okzea-chatbot-container';
      formContainer.innerHTML = `
        <div class="container">
          <form id="okzea-chatbot-form"></form>
        </div>
      `;
      this.shadowRoot.appendChild(formContainer);

      this.form = this.shadowRoot.getElementById('okzea-chatbot-form');
      if (!this.form) return;

      if (typeof fbq === 'undefined') console.warn('Facebook Pixel not found');

      this.assistantName = 'Lumo';

      // Initialize the conversation
      window.DatePicker = DatePicker; // Make DatePicker globally available for the conversation
      this.conversation = new ChatbotConversation(this.assistantName, this.shadowRoot);
      this.conversation.initConversation(this.form);

      // Listen for question answered events
      this.form.addEventListener('chatbot-question-answered', (event) => {
        this.handleQuestionAnswered(event.detail);
      });

      // Listen for question answered events
      this.form.addEventListener('chatbot-question-answered', (event) => {
        this.handleQuestionAnswered(event.detail);
      });

      // Setup form submission event
      this.form.addEventListener('chatbot-conversation-complete', (event) => {
        this.handleSuccess(event.detail.formData);
      });

      // Add UTM parameters
      this.addUtmParams();
      this.setupTracking();
    });
  }

  loadStyles() {
    return new Promise((resolve) => {
      const linkElem = document.createElement('link');
      linkElem.setAttribute('rel', 'stylesheet');
      linkElem.setAttribute('href', '/wp-content/plugins/okzea-chatbot/dist/css/okzea-chatbot.min.css?v=1.0.1');
      linkElem.onload = resolve;
      this.shadowRoot.appendChild(linkElem);
    });
  }

  handleSuccess(data) {
    if (typeof fbq !== 'undefined') {
      const urlParams = new URLSearchParams(window.location.search);
      const utmSource = urlParams.get('utm_source');

      if (utmSource == 'meta') {
        fbq('track', 'Lead');
        fbq('track', 'CompleteRegistration');
      }
    }
  }

  handleQuestionAnswered(detail) {
    const { fieldName, value } = detail;

    if (typeof fbq !== 'undefined') {
      fbq('trackCustom', 'ChatbotQuestionAnswered', {
        fieldName,
        value
      });
    }
  }

  addUtmParams() {
    const utmParams = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content'];
    const urlParams = new URLSearchParams(window.location.search);

    utmParams.forEach(param => {
      const value = urlParams.get(param);
      if (value) {
        const hiddenField = document.createElement('input');
        hiddenField.type = 'hidden';
        hiddenField.name = param;
        hiddenField.value = value;
        this.form.appendChild(hiddenField);
      }
    });
  }

  setupTracking() {
    const inputFields = this.shadowRoot.querySelectorAll('input');
    inputFields.forEach(input => {
      input.addEventListener('change', (event) => {
        const inputName = event.target.name || event.target.id;
        if (typeof fbq !== 'undefined') {
          fbq('trackCustom', 'InputChanged', {
            inputName: inputName,
            inputValue: event.target.value
          });
        }
      });
    });
  }
}

// Register the custom element
customElements.define('okzea-chatbot', MututalysChatbot);
