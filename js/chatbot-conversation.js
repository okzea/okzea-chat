import * as FormHelpers from './form-helpers';
import Questions from './chatbot-questions';
import { FormSubmission } from './form-submission';

export class ChatbotConversation {
  constructor(assistantName, shadowRoot) {
    this.assistantName = assistantName;
    this.shadowRoot = shadowRoot;
    this.chatContainer = null;
    this.responseContainer = null;
    this.typingSpeed = 29; // milliseconds per character
    this.currentQuestion = 0;
    this.formData = {};
    this.conversationComplete = false;
    this.subQuestions = []; // Array to store sub-questions
    this.insuredIndex = 0; // Counter for insureds
    this.questionCount = 0; // Counter for answered questions
  }

  initConversation(containerElement) {
    this.chatContainer = document.createElement('div');
    this.chatContainer.className = 'chatbot-conversation';

    const avatarContainer = document.createElement('div');
    avatarContainer.className = 'avatar-container';
    avatarContainer.innerHTML = `
      <div class="avatar">
        <img src="/wp-content/plugins/okzea-chatbot/images/assistant-avatar-${this.assistantName.toLowerCase()}.png" alt="${this.assistantName.toLowerCase()}" width="40" height="40"/>
      </div>
      <div>
        <div class="avatar-name">${this.assistantName}</div>
        <div class="avatar-status">En ligne</div>
      </div>
    `;

    const chatMessageContainer = document.createElement('div');
    chatMessageContainer.className = 'chat-messages';
    chatMessageContainer.id = 'chat-messages';

    this.chatContainer.appendChild(avatarContainer);
    this.chatContainer.appendChild(chatMessageContainer);

    // Create response container but don't add it to the DOM yet
    this.responseContainer = document.createElement('div');
    this.responseContainer.className = 'response-container';
    this.responseContainer.id = 'response-container';

    containerElement.appendChild(this.chatContainer);

    // Start the conversation
    this.askNextQuestion();
  }

  handleNonInteractiveQuestion(question) {
    const delay = Math.floor(Math.random() * (700 - 200 + 1)) + 200;
    setTimeout(() => {
      if (question.break) return;

      // If this is a sub-question, just move to the next one
      if (this.subQuestions.length > 0) {
        this.askNextQuestion();
      } else {
        // If this is a main question, increment and move to next
        this.currentQuestion++;
        this.askNextQuestion();
      }
    }, delay);
  }

  handleInteractiveQuestion(question) {
    const messagesContainer = this.shadowRoot.getElementById('chat-messages');
    messagesContainer.appendChild(this.responseContainer);
    this.renderInputField(question);
    this.scrollToBottom();
  }

  askNextQuestion() {
    // If we have sub-questions queued up, ask those first
    if (this.subQuestions.length > 0) {
      const subQuestion = this.subQuestions.shift();

      // Handle conditional visibility
      if (subQuestion.showIf || subQuestion.hidden) {
        const condition = (subQuestion.showIf || "false").replace('{index}', this.insuredIndex);
        const shouldShow = eval(condition);

        if (!shouldShow && !subQuestion.hidden) {
          // Skip this question if it shouldn't be shown
          this.askNextQuestion();
          return;
        }

        if (subQuestion.hidden) {
          // For hidden fields, just store the value and continue
          const fieldName = subQuestion.fieldName.replace('{index}', this.insuredIndex);
          if (subQuestion.copyFrom) {
            // If copyFrom is specified, get the value from the target field
            const sourceFieldName = subQuestion.copyFrom.replace('{index}', this.insuredIndex);
            this.formData[fieldName] = this.formData[sourceFieldName] || '';
          } else {
            // Otherwise use the static value or evaluate the function
            this.formData[fieldName] = typeof subQuestion.value === 'function' ? 
              subQuestion.value(this.formData) :
              subQuestion.value;
          }
          this.askNextQuestion();
          return;
        }
      }

      // Handle conditional question text
      let questionText = subQuestion.question;
      if (typeof questionText === 'object') {
        questionText = questionText[this.insuredIndex] || questionText.other;
      }

      this.displayMessage(questionText.replace('{assistant}', this.assistantName), () => {
        if (!subQuestion.fieldType) {
          this.handleNonInteractiveQuestion(subQuestion);
        } else {
          this.handleInteractiveQuestion(subQuestion);
        }
      });
      return;
    }

    const question = Questions[this.currentQuestion];

    // Check if this question is conditional based on a startsWith condition
    if (question.dependsOn && question.startsWith) {
      const dependentValue = this.getDependentValue(question.dependsOn);
      // Skip this question if the dependent value doesn't match the startsWith condition
      if (!dependentValue || !dependentValue.toString().startsWith(question.startsWith.toString())) {
        this.currentQuestion++;
        this.askNextQuestion();
        return;
      }
    }

    // Check if the question has an array of questions
    if (question.questions) {
      // Add all sub-questions to the queue
      this.subQuestions = [...question.questions];
      this.currentQuestion++;
      this.askNextQuestion();
      return;
    }

    if (question.submit) {
      // Create a FormData object
      const formData = new FormData();

      // Add all collected data to the FormData object
      for (const [name, value] of Object.entries(this.formData)) {
        formData.append(name, value);
      }

      // Create success callback
      const successCallback = (data) => {
        this.conversationComplete = true;

        // Display success message
        if (data.submission_id) {
          this.displayMessage(question.submitMessage, () => {});
        }

        // Dispatch a custom event to notify the parent that the conversation is complete
        const event = new CustomEvent('chatbot-conversation-complete', {
          detail: {
            formData: this.formData
          }
        });
        document.dispatchEvent(event);
      };

      // Submit the form data
      try {
        const formSubmission = new FormSubmission();
        formSubmission.submit(formData, successCallback);
      } catch (error) {
        console.error('Error submitting form:', error);
        this.displayMessage(question.submitMessageFailed, () => {});
      }
      return;
    }

    // Display the question
    this.displayMessage(question.question.replace('{assistant}', this.assistantName), () => {
      if (!question.fieldType) {
        this.handleNonInteractiveQuestion(question);
      } else {
        this.handleInteractiveQuestion(question);
      }
    });
  }

  displayMessage(message, callback) {
    const messagesContainer = this.shadowRoot.getElementById('chat-messages');
    const messageElement = document.createElement('div');
    messageElement.className = 'chat-message';

    // Add typing indicator
    const typingIndicator = document.createElement('div');
    typingIndicator.className = 'typing-indicator';
    typingIndicator.innerHTML = '<span></span><span></span><span></span>';
    messageElement.appendChild(typingIndicator);

    messagesContainer.appendChild(messageElement);
    this.scrollToBottom();

    // Check if we should skip typing effect
    const currentQuestion = this.subQuestions.length > 0 ?
      this.subQuestions[0] : 
      Questions[this.currentQuestion];

    if (currentQuestion?.skipTyping) {
      messageElement.innerHTML = message;
      if (callback) callback();
      return;
    }

    // Simulate typing
    let displayText = '';
    let charIndex = 0;

    const typeChar = () => {
      if (charIndex < message.length) {
        displayText += message[charIndex];
        messageElement.innerHTML = displayText;
        charIndex++;
        setTimeout(typeChar, this.typingSpeed);
      } else {
        // Typing complete
        if (callback) callback();
      }
    };

    // Start typing effect after a small delay
    setTimeout(() => {
      messageElement.innerHTML = '';
      if (document.location.hostname === 'localhost') {
        messageElement.innerHTML = message;
        if (callback) callback();
      } else {
        typeChar();
      }
    }, 500);
  }

  scrollToBottom() {
    const chatbotContainer = this.shadowRoot.getElementById('okzea-chatbot-container');
    setTimeout(() => {
      chatbotContainer.scrollTop = chatbotContainer.scrollHeight;
    }, 10);
  }

  renderInputField(question) {
    this.responseContainer.innerHTML = '';

    const inputWrapper = document.createElement('div');
    inputWrapper.classList.add('input-field-wrapper', question.direction || 'default');

    // Handle conditional placeholders
    let placeholder = question.placeholder;
    if (typeof placeholder === 'object') {
      placeholder = placeholder[this.insuredIndex] || placeholder.other;
    }

    if (question.fieldName) {
      question = {
        ...question,
        fieldName: question.fieldName.replace('{index}', this.insuredIndex),
        placeholder
      };
    }

    let inputField;

    switch (question.fieldType) {
      case 'text':
        inputField = FormHelpers.text(question.fieldName, question.placeholder, question.required);
        break;
      case 'email':
        inputField = FormHelpers.email(question.fieldName, question.placeholder, question.required);
        break;
      case 'tel':
        inputField = FormHelpers.tel(question.fieldName, question.placeholder, question.required);
        break;
      case 'select':
        inputField = FormHelpers.select(question.fieldName, question.options, question.required);
        break;
      case 'date':
        inputField = FormHelpers.date(question.fieldName, question.placeholder, question.required, question.dateStartFrom);
        break;
      case 'range':
        inputField = FormHelpers.range(question.fieldName, question.min, question.max, question.step, question.value);
        break;
      case 'textarea':
        inputField = FormHelpers.textarea(question.fieldName, question.placeholder, question.required);
        break;
      case 'radio':
        let options = question.options;
        
        // Handle dynamic options based on previous answer
        if (question.dependsOn && question.optionsSets) {
          const dependentValue = this.getDependentValue(question.dependsOn);
          if (dependentValue && question.optionsSets[dependentValue]) {
            options = question.optionsSets[dependentValue];
          }
        }
        
        inputField = options.map(option => 
          FormHelpers.radio(question.fieldName, option.value, `${question.fieldName}[${option.value}]`, option.label, question.required)
        ).join('');
        break;
      default:
        inputField = FormHelpers.text(question.fieldName, question.placeholder, question.required);
    }

    inputWrapper.innerHTML = inputField;
    this.responseContainer.appendChild(inputWrapper);

    if (question.fieldType !== 'radio') {
      const submitButton = document.createElement('button');
      submitButton.type = 'button';
      submitButton.className = 'next-button';
      submitButton.setAttribute('aria-label', 'Suivant');
      submitButton.addEventListener('click', () => this.processAnswer(question));
      this.responseContainer.appendChild(submitButton);

      // Add event listener for enter key
      const input = this.responseContainer.querySelector('input, select, textarea');
      if (input) {
        input.focus();
        input.addEventListener('keypress', (event) => {
          if (event.key === 'Enter' && question.fieldType !== 'textarea') {
            event.preventDefault();
            submitButton.click();
          }
        });
      }
    }

    // Initialize any special fields like date pickers
    if (question.fieldType === 'date') {
      const datePicker = new window.DatePicker();
      datePicker.initAllDatePickers(this.responseContainer);
    }

    if (question.fieldType === 'radio') {
      const radios = this.responseContainer.querySelectorAll(`input[name="${question.fieldName}"]`);
      radios.forEach(radio => {
        radio.addEventListener('change', () => {
          this.processAnswer(question);
        });
      });
    }
  }

  getDependentValue(fieldName) {
    // Replace {index} with current insured index if present
    fieldName = fieldName.replace('{index}', this.insuredIndex);
    return this.formData[fieldName] || null;
  }

  processAnswer(question) {
    let input, value, displayValue;

    if (question.fieldType === 'radio') {
      // Handle radio button groups
      const radios = this.responseContainer.querySelectorAll(`input[name="${question.fieldName}"]`);
      const selectedRadio = Array.from(radios).find(radio => radio.checked);

      if (!selectedRadio) return;

      input = selectedRadio;
      value = selectedRadio.value;

      // Get the label text for display
      const label = this.responseContainer.querySelector(`label[for="${selectedRadio.id}"]`);
      displayValue = label ? label.textContent.trim() : value;
    } else {
      // Handle other input types
      input = this.responseContainer.querySelector(`[name="${question.fieldName}"]`);
      
      if (!input) return;

      // Validate input if required
      if (question.required && !FormHelpers.validateField(input)) {
        return;
      }

      value = input.value;

      // Format the displayed answer
      if (question.fieldType === 'select') {
        const selectedOption = input.options[input.selectedIndex];
        displayValue = selectedOption ? selectedOption.text : value;
      } else if (question.fieldType === 'range') {
        displayValue = `Niveau ${value} sur ${question.max}`;
      } else {
        displayValue = value;
      }
    }

    // Store the answer
    const fieldName = question.fieldName.replace('{index}', this.insuredIndex);
    this.formData[fieldName] = value;

    // Dispatch custom event for question answer
    const answerEvent = new CustomEvent('chatbot-question-answered', {
      detail: {
        questionNumber: this.questionCount,
        fieldName,
        value,
        displayValue,
        totalQuestions: Questions.length + this.subQuestions.length
      },
      bubbles: true,
      composed: true
    });
    this.chatContainer.dispatchEvent(answerEvent);

    // Remove response container from the DOM
    if (this.responseContainer.parentNode) {
      this.responseContainer.parentNode.removeChild(this.responseContainer);
    }

    // Add user message to chat
    const messagesContainer = this.shadowRoot.getElementById('chat-messages');
    const userMessage = document.createElement('div');
    userMessage.className = 'chat-message user-message';
    userMessage.textContent = displayValue;
    messagesContainer.appendChild(userMessage);
    this.scrollToBottom();

    // Check if this question should trigger adding another item
    if (question.addItem) {
      if (value === 'true') {
        // Increment insured index and go back to the parent question block
        this.insuredIndex++;
        // Find the index of the parent question block (the one with sub-questions)
        const parentQuestionIndex = Questions.findIndex(q => q.questions && q.questions.some(sq => sq.addItem));
        if (parentQuestionIndex !== -1) {
          this.currentQuestion = parentQuestionIndex;
          this.askNextQuestion();
          return;
        }
      } else {
        // If user selected "false", move to the next main question
        this.subQuestions = [];
        this.askNextQuestion();
        return;
      }
    }

    // Move to next question
    if (this.subQuestions.length > 0) {
      this.askNextQuestion();
    } else {
      this.currentQuestion++;
      this.askNextQuestion();
    }
  }

  getFormData() {
    return this.formData;
  }

  isComplete() {
    return this.conversationComplete;
  }
}
