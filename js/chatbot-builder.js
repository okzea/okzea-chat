document.addEventListener('DOMContentLoaded', () => {
    // Check if localized data is available
    if (typeof chatbotBuilderData === 'undefined' || !chatbotBuilderData) {
        console.error('Chatbot Builder data (chatbotBuilderData) is missing.');
        const list = document.getElementById('question-list');
        if(list) {
            list.innerHTML = '<li class="error-placeholder">Error: Builder data missing. Cannot initialize builder.</li>';
        }
        return;
    }

    const { questions: initialQuestions, ajax_url, nonce } = chatbotBuilderData;
    
    // DOM Elements
    const questionList = document.getElementById('question-list');
    const addQuestionBtn = document.getElementById('add-question-btn');
    const saveQuestionsBtn = document.getElementById('save-questions-btn');
    const questionEditorModal = document.getElementById('question-editor');
    const questionEditorOverlay = document.getElementById('question-editor-overlay');
    const questionForm = document.getElementById('question-form');
    const cancelEditBtn = document.getElementById('cancel-edit-btn');
    const deleteQuestionBtn = document.getElementById('delete-question-btn');
    const statusDisplay = document.getElementById('builder-status');
    const fieldTypeSelect = document.getElementById('field-type');

    // --- State ---
    let questions = []; // Holds the current state of questions, including sub-questions
    let isDirty = false; // Flag to track unsaved changes
    let draggedItem = null; // Element being dragged
    let placeholder = null; // Placeholder for drag-and-drop

    // --- Initialization ---
    function initializeBuilder() {
        questions = prepareQuestionsForJs(initialQuestions || []);
        renderQuestionList();
        setupEventListeners();
        updateStatus('Ready.');
        updateSaveButtonState(); // Set initial button state
    }

    // Convert flat DB structure with parent_id to nested JS structure
    function prepareQuestionsForJs(dbQuestions) {
        const questionMap = {}; // id -> question object
        const rootQuestions = [];

        // First pass: create objects and map them
        dbQuestions.forEach(q => {
            const jsQuestion = {
                ...q, // Copy all properties from DB object
                is_required: !!parseInt(q.is_required),
                is_hidden: !!parseInt(q.is_hidden),
                is_group: !!parseInt(q.is_group),
                add_item: !!parseInt(q.add_item),
                skip_typing: !!parseInt(q.skip_typing),
                is_submit: !!parseInt(q.is_submit),
                options: q.options ? tryParseJson(q.options) : null,
                sub_questions: [],
                temp_id: q.id ? `db_${q.id}` : `temp_${Date.now()}_${Math.random().toString(36).substring(2, 7)}`
            };
            if (jsQuestion.options && typeof jsQuestion.options === 'object' && !Array.isArray(jsQuestion.options)) {
                jsQuestion.optionsSets = jsQuestion.options;
                delete jsQuestion.options;
            }
            questionMap[q.id] = jsQuestion;
        });

        // Second pass: build the hierarchy
        dbQuestions.forEach(q => {
            const jsQuestion = questionMap[q.id];
            if (!jsQuestion) return; // Skip if question wasn't mapped (shouldn't happen)

            if (q.parent_id && q.parent_id !== '0' && questionMap[q.parent_id]) {
                questionMap[q.parent_id].sub_questions.push(jsQuestion);
            } else if (!q.parent_id || q.parent_id === '0') {
                rootQuestions.push(jsQuestion);
            }
        });

        // Ensure sub-questions are sorted
        Object.values(questionMap).forEach(q => {
            if (q.sub_questions.length > 0) {
                q.sub_questions.sort((a, b) => parseInt(a.question_order) - parseInt(b.question_order));
            }
        });
        return rootQuestions;
    }

    function tryParseJson(jsonString) {
        try {
            const o = JSON.parse(jsonString);
            if (o && typeof o === "object") {
                return o;
            }
        }
        catch (e) {
            console.warn("Error parsing JSON options:", e, "String:", jsonString);
        }
        return jsonString;
    }

    // --- Rendering ---
    function renderQuestionList() {
        if (!questionList) return;
        questionList.innerHTML = ''; // Clear current list
        if (questions.length === 0) {
            questionList.innerHTML = '<li class="no-questions-placeholder">No questions yet. Click "Add Question" to start.</li>';
            return;
        }
        questions.forEach(question => {
            questionList.appendChild(createQuestionElement(question));
        });
        isDirty = false; // Reset dirty flag after rendering
        updateSaveButtonState();
    }

    function createQuestionElement(question) {
        const li = document.createElement('li');
        li.className = 'question-item';
        li.dataset.questionTempId = question.temp_id;
        li.draggable = true;

        const contentDiv = document.createElement('div');
        contentDiv.className = 'question-content';

        const handleSpan = document.createElement('span');
        handleSpan.className = 'drag-handle';
        handleSpan.innerHTML = '&#x2630;';
        handleSpan.title = 'Drag to reorder';

        const textSpan = document.createElement('span');
        textSpan.className = 'question-text';
        textSpan.textContent = question.question_text || '(No question text)';

        const typeSpan = document.createElement('span');
        let typeLabel = 'Message';
        let typeClass = '';

        if (question.is_group) {
            typeLabel = 'Group';
            typeClass = 'type-group';
        } else if (question.is_submit) {
            typeLabel = 'Submit';
            typeClass = 'type-submit';
        } else if (question.field_type) {
            typeLabel = question.field_type.charAt(0).toUpperCase() + question.field_type.slice(1);
            switch (question.field_type) {
                case 'text':
                case 'textarea':
                case 'email':
                case 'tel':
                case 'number':
                    typeClass = 'type-input';
                    break;
                case 'radio':
                case 'select':
                    typeClass = 'type-choice';
                    break;
                case 'date':
                    typeClass = 'type-date';
                    break;
                case 'range':
                    typeClass = 'type-range';
                    break;
                case 'hidden':
                    typeClass = 'type-hidden';
                    break;
            }
        }

        typeSpan.className = `question-type ${typeClass}`;
        typeSpan.textContent = typeLabel;

        contentDiv.appendChild(handleSpan);
        contentDiv.appendChild(textSpan);
        contentDiv.appendChild(typeSpan);

        const buttonContainer = document.createElement('div');
        buttonContainer.className = 'button-container';

        const editButton = document.createElement('button');
        editButton.type = 'button';
        editButton.className = 'button button-secondary button-small';
        editButton.textContent = 'Edit';
        editButton.onclick = () => openEditor(question.temp_id);
        buttonContainer.appendChild(editButton);

        if (question.is_group) {
            const addSubButton = document.createElement('button');
            addSubButton.type = 'button';
            addSubButton.className = 'button button-secondary button-small';
            addSubButton.textContent = 'Add Sub';
            addSubButton.onclick = () => addSubQuestion(question.temp_id);
            buttonContainer.appendChild(addSubButton);
        }

        contentDiv.appendChild(buttonContainer);
        li.appendChild(contentDiv);

        if (question.is_group) {
            const subList = document.createElement('ul');
            subList.className = 'sub-question-list question-list-area';
            addListDragDropListeners(subList);
            li.appendChild(subList);

            if (question.sub_questions && question.sub_questions.length > 0) {
                question.sub_questions.forEach(subQ => {
                    const subLi = createQuestionElement(subQ);
                    subList.appendChild(subLi);
                });
            }
        }

        li.addEventListener('dragstart', handleDragStart);
        li.addEventListener('dragend', handleDragEnd);

        return li;
    }

    // --- Drag and Drop (Native HTML5) ---
    function handleDragStart(e) {
        draggedItem = e.target.closest('li.question-item');
        if (!draggedItem) return;
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', draggedItem.dataset.questionTempId);

        // Create placeholder
        placeholder = document.createElement('li');
        placeholder.className = 'question-item-placeholder';
        placeholder.style.height = `${draggedItem.offsetHeight}px`;

        // Add class to indicate dragging
        setTimeout(() => draggedItem.classList.add('dragging'), 0);
    }

    function handleDragEnd(e) {
        if (!draggedItem) return;
        draggedItem.classList.remove('dragging');
        if (placeholder && placeholder.parentNode) {
            placeholder.parentNode.removeChild(placeholder);
        }
        draggedItem = null;
        placeholder = null;
    }

    function handleDragOver(e) {
        e.preventDefault(); // Necessary to allow dropping
        e.dataTransfer.dropEffect = 'move';

        const targetList = e.target.closest('.question-list-area');
        if (!targetList || !draggedItem || !placeholder) return;

        const rect = e.target.getBoundingClientRect();
        const offsetY = e.clientY - rect.top;
        const targetItem = e.target.closest('li.question-item');

        if (targetItem && targetItem !== draggedItem) {
            // Determine if inserting before or after the target item
            const targetRect = targetItem.getBoundingClientRect();
            const midY = targetRect.top + targetRect.height / 2;
            if (e.clientY < midY) {
                targetList.insertBefore(placeholder, targetItem);
            } else {
                targetList.insertBefore(placeholder, targetItem.nextSibling);
            }
        } else if (!targetItem && targetList.contains(placeholder)) {
             // If hovering over empty space in list, keep placeholder
             // Might need refinement for placing at the end reliably
             if (!targetList.querySelector('li.question-item:last-child') || e.clientY > targetList.querySelector('li.question-item:last-child').getBoundingClientRect().bottom) {
                targetList.appendChild(placeholder);
             }
        } else if (!targetList.contains(placeholder)) {
            // If entering list, append placeholder
             targetList.appendChild(placeholder);
        }
    }

    function handleDragEnter(e) {
        e.preventDefault();
        const targetList = e.target.closest('.question-list-area');
        if (targetList) {
             // Optional: Add visual feedback on the list
             targetList.classList.add('drag-over');
        }
    }

    function handleDragLeave(e) {
        const targetList = e.target.closest('.question-list-area');
         // Check if the relatedTarget (where the cursor is going) is outside the list
         if (targetList && !targetList.contains(e.relatedTarget)) {
             targetList.classList.remove('drag-over');
             // Remove placeholder only if leaving a list entirely
             // if (placeholder && placeholder.parentNode === targetList) {
             //     targetList.removeChild(placeholder);
             // }
        }
    }

    function handleDrop(e) {
        e.preventDefault();
        const targetList = e.target.closest('.question-list-area');
        if (targetList && draggedItem && placeholder && placeholder.parentNode) {
            targetList.classList.remove('drag-over');
            placeholder.parentNode.insertBefore(draggedItem, placeholder); // Insert item at placeholder position
            updateQuestionOrder();
            markDirty();
        }
        // Clean up handled in dragend
    }

    // Attach drag/drop listeners to list containers
    function addListDragDropListeners(listElement) {
        listElement.addEventListener('dragover', handleDragOver);
        listElement.addEventListener('dragenter', handleDragEnter);
        listElement.addEventListener('dragleave', handleDragLeave);
        listElement.addEventListener('drop', handleDrop);
    }

    // --- Question Data Management ---
    function findQuestionByTempId(tempId, searchList = questions) {
        for (let i = 0; i < searchList.length; i++) {
            const q = searchList[i];
            if (q.temp_id === tempId) {
                return { question: q, list: searchList, index: i };
            }
            if (q.is_group && q.sub_questions && q.sub_questions.length > 0) {
                const foundInSub = findQuestionByTempId(tempId, q.sub_questions);
                if (foundInSub) {
                    return foundInSub;
                }
            }
        }
        return null; // Not found
    }

    function updateQuestionOrder() {
        const updatedQuestions = [];
        const tempIdMap = {}; // temp_id -> question object

        function mapQuestion(q) {
            tempIdMap[q.temp_id] = q;
            if (q.sub_questions) {
                q.sub_questions.forEach(mapQuestion);
            }
        }
        questions.forEach(mapQuestion);

        // Read order from DOM
        document.querySelectorAll('#question-list > li.question-item').forEach(li => {
            const tempId = li.dataset.questionTempId;
            const question = tempIdMap[tempId];

            if (question) {
                question.parent_id = 0; // Reset parent ID, might become sub-question below
                question.sub_questions = []; // Reset sub-questions
                updatedQuestions.push(question);

                // Recursively handle sub-questions
                li.querySelectorAll(':scope > ul.sub-question-list > li.question-item').forEach(subLi => {
                    const subTempId = subLi.dataset.questionTempId;
                    const subQuestion = tempIdMap[subTempId];
                    if (subQuestion) {
                        subQuestion.parent_id = question.id || 0;
                        question.sub_questions.push(subQuestion);
                        // Note: Further nested groups within sub-questions need deeper recursion if supported
                    }
                });
            } else {
                console.warn("Could not find question data for temp ID:", tempId);
            }
        });

        questions = updatedQuestions; // Update the main questions array
        markDirty();
        console.log("Updated question structure:", questions);
    }

    // --- Editor Modal ---
    function openEditor(tempId = null) {
        if (!questionForm || !questionEditorModal || !questionEditorOverlay) return;
        questionForm.reset();
        questionForm.querySelectorAll('.field-specific').forEach(el => el.style.display = 'none');
        questionForm.querySelector('#edit-question-id').value = '';
        questionForm.querySelector('#edit-question-parent-id').value = '0';

        const titleElement = questionEditorModal.querySelector('h2');

        if (tempId) {
            const found = findQuestionByTempId(tempId);
            if (!found) {
                alert('Error: Could not find question to edit.');
                return;
            }
            const question = found.question;

            if(titleElement) titleElement.textContent = 'Edit Question';
            questionForm.querySelector('#edit-question-id').value = question.id || '';
            questionForm.querySelector('#edit-question-parent-id').value = question.parent_id || '0';
            questionForm.dataset.tempId = tempId;

            // Populate form fields
            document.getElementById('question-text').value = question.question_text || '';
            const fieldType = question.is_group ? 'group' : (question.is_submit ? 'submit' : (question.field_type || ''));
            if(fieldTypeSelect) {
                fieldTypeSelect.value = fieldType;
                fieldTypeSelect.dispatchEvent(new Event('change'));
            }
            setInputValue('field-name', question.field_name);
            setInputValue('placeholder', question.placeholder);
            setInputChecked('is-required', question.is_required);
            setInputChecked('is-hidden', question.is_hidden);
            setInputValue('static-value', question.static_value);
            setInputValue('copy-from', question.copy_from);

            // Handle Options/OptionsSets
            const optionsTextarea = document.getElementById('options');
            if (optionsTextarea) {
                if (question.optionsSets) {
                    let optionsText = '';
                    for (const key in question.optionsSets) {
                        optionsText += `${key}:${JSON.stringify(question.optionsSets[key])}\n`;
                    }
                    optionsTextarea.value = optionsText.trim();
                } else if (question.options) {
                    if (Array.isArray(question.options)) {
                        optionsTextarea.value = question.options.map(opt => `${opt.value}:${opt.label}`).join('\n');
                    } else {
                        optionsTextarea.value = JSON.stringify(question.options);
                    }
                } else {
                    optionsTextarea.value = '';
                }
            }

            setInputValue('direction', question.direction || 'default');
            setInputValue('min-value', question.min_value);
            setInputValue('max-value', question.max_value);
            setInputValue('step-value', question.step_value ?? '1');
            setInputValue('default-value', question.default_value);
            setInputValue('date-start-from', question.date_start_from);
            setInputValue('depends-on', question.depends_on);
            setInputValue('starts-with', question.starts_with);
            setInputValue('show-if', question.show_if);
            setInputChecked('add-item', question.add_item);
            setInputChecked('skip-typing', question.skip_typing);
            setInputValue('submit-message', question.submit_message);
            setInputValue('submit-message-failed', question.submit_message_failed);

            if(deleteQuestionBtn) deleteQuestionBtn.style.display = 'inline-block';
        } else {
            if(titleElement) titleElement.textContent = 'Add New Question';
            questionForm.dataset.tempId = '';
            if(fieldTypeSelect) {
                fieldTypeSelect.value = '';
                fieldTypeSelect.dispatchEvent(new Event('change'));
            }
            if(deleteQuestionBtn) deleteQuestionBtn.style.display = 'none';
        }

        // Show modal with animation
        questionEditorOverlay.style.display = 'block';
        questionEditorModal.style.display = 'block';
        // Force reflow
        questionEditorModal.offsetHeight;
        questionEditorOverlay.classList.add('visible');
        questionEditorModal.classList.add('visible');

        // Focus first input after animation
        setTimeout(() => {
            const firstInput = questionEditorModal.querySelector('input, textarea, select');
            if (firstInput) firstInput.focus();
        }, 300);
    }

    function closeEditor() {
        if (!questionEditorModal || !questionEditorOverlay) return;

        // Hide with animation
        questionEditorModal.classList.remove('visible');
        questionEditorOverlay.classList.remove('visible');

        setTimeout(() => {
            questionEditorModal.style.display = 'none';
            questionEditorOverlay.style.display = 'none';
            if(questionForm) {
                questionForm.reset();
                delete questionForm.dataset.tempId;
            }
        }, 300);
    }
    
    // Helper to set input value safely
    function setInputValue(id, value) {
        const el = document.getElementById(id);
        if (el) {
            el.value = value || '';
        }
    }
    // Helper to set checkbox state safely
    function setInputChecked(id, isChecked) {
        const el = document.getElementById(id);
        if (el) {
            el.checked = !!isChecked;
        }
    }

    function handleFieldTypeChange() {
        if(!fieldTypeSelect || !questionForm) return;
        const selectedType = fieldTypeSelect.value;

        // Hide all field-specific rows first
        questionForm.querySelectorAll('.field-specific').forEach(el => el.style.display = 'none');

        // Show rows whose data-depends-on contains the selected type
        if(selectedType) {
            questionForm.querySelectorAll(`.field-specific[data-depends-on*="${selectedType}"]`).forEach(el => el.style.display = '');
        }

        // Function to hide specific rows by their input IDs
        const hideRowsByIds = (ids) => {
             ids.forEach(id => {
                const input = document.getElementById(id);
                if (input) {
                    const row = input.closest('.form-row');
                    if (row) row.style.display = 'none';
                }
             });
        };
        // Function to show specific rows by their input IDs
        const showRowsByIds = (ids) => {
             ids.forEach(id => {
                const input = document.getElementById(id);
                if (input) {
                    const row = input.closest('.form-row');
                    // Check if it was initially targeted by the data-depends-on selector before showing
                    if (row && row.matches(`.field-specific[data-depends-on*="${selectedType}"]`)) {
                        row.style.display = ''; 
                    }
                }
             });
        };

         // Special handling for types
        if (selectedType === 'group') {
             hideRowsByIds(['field-name', 'placeholder', 'is-required', 'is-hidden', 'options', 'direction', 'min-value', 'max-value', 'step-value', 'default-value', 'date-start-from', 'add-item', 'submit-message', 'submit-message-failed', 'static-value', 'copy-from']);
             showRowsByIds(['depends-on', 'starts-with', 'show-if']); // Keep conditional logic
        } else if (selectedType === 'submit') {
             hideRowsByIds(['field-name', 'placeholder', 'is-required', 'is-hidden', 'options', 'direction', 'min-value', 'max-value', 'step-value', 'default-value', 'date-start-from', 'add-item', 'static-value', 'copy-from', 'depends-on', 'starts-with', 'show-if']);
             showRowsByIds(['submit-message', 'submit-message-failed']);
        } else if (selectedType === '') { // Informational
             hideRowsByIds(['field-name', 'placeholder', 'is-required', 'is-hidden', 'options', 'direction', 'min-value', 'max-value', 'step-value', 'default-value', 'date-start-from', 'add-item', 'submit-message', 'submit-message-failed', 'static-value', 'copy-from', 'depends-on', 'starts-with', 'show-if']);
        } else if (selectedType === 'hidden') {
            showRowsByIds(['field-name', 'static-value', 'copy-from']); // Common for hidden
            hideRowsByIds(['placeholder', 'is-required', 'options', 'direction', 'min-value', 'max-value', 'step-value', 'default-value', 'date-start-from', 'add-item', 'submit-message', 'submit-message-failed']); // Hide others
        } else if (!['radio', 'select'].includes(selectedType)) {
            hideRowsByIds(['options', 'direction']); // Hide options/direction if not radio/select
        } else if (selectedType !== 'radio') {
             hideRowsByIds(['direction', 'add-item']); // Hide direction/add-item if not radio
        } else if (selectedType !== 'range') {
            hideRowsByIds(['min-value', 'max-value', 'step-value', 'default-value']);
        } else if (selectedType !== 'date') {
            hideRowsByIds(['date-start-from']);
        }
    }

    // --- Actions ---
    function saveEditorChanges(event) {
        event.preventDefault();
        if (!questionForm) return;

        const tempId = questionForm.dataset.tempId;
        const isNew = !tempId;

        let question;
        let parentList = questions; // Default to root list
        let questionIndex = -1;

        if (isNew) {
            const parentTempId = document.getElementById('edit-question-parent-id').value;
            question = {
                id: null,
                temp_id: `temp_${Date.now()}_${Math.random().toString(36).substring(2, 7)}`,
                sub_questions: [],
                parent_id: 0,
                // Default other fields to null/false/empty
                 question_text: '',
                 field_type: null,
                 is_group: false,
                 is_submit: false
                 // ... add other defaults as needed
            };
            if (parentTempId && parentTempId !== '0') {
                const parentInfo = findQuestionByTempId(parentTempId);
                if (parentInfo && parentInfo.question.is_group) {
                    parentList = parentInfo.question.sub_questions;
                    question.parent_id = parentInfo.question.id || 0;
                } else {
                    console.error("Cannot add sub-question: Parent not found or is not a group.");
                    alert("Error: Cannot add sub-question to the selected parent.");
                    return;
                }
            }
        } else {
            const found = findQuestionByTempId(tempId);
            if (!found) {
                alert('Error: Could not find question data to save.');
                return;
            }
            question = found.question;
            parentList = found.list;
            questionIndex = found.index;
        }

        // Update question object with form data
        const fieldType = document.getElementById('field-type').value;
        question.question_text = document.getElementById('question-text').value;
        question.field_type = (fieldType === 'group' || fieldType === 'submit' || fieldType === '') ? null : fieldType;
        question.is_group = (fieldType === 'group');
        question.is_submit = (fieldType === 'submit');
        
        // Helper function to get value or null
        const getValue = (id) => document.getElementById(id)?.value.trim() || null;
        const isChecked = (id) => document.getElementById(id)?.checked || false;

        // Clear all potentially irrelevant fields first
         const coreFields = ['id', 'temp_id', 'question_text', 'is_group', 'is_submit', 'sub_questions', 'parent_id'];
         Object.keys(question).forEach(key => {
             if (!coreFields.includes(key)) {
                 delete question[key];
             }
         });

        // Only set fields relevant to the type
        if (!question.is_group && !question.is_submit && fieldType !== '') {
            question.field_name = getValue('field-name');
            question.placeholder = getValue('placeholder');
            question.is_required = isChecked('is-required');
            question.is_hidden = isChecked('is-hidden');
            question.static_value = getValue('static-value');
            question.copy_from = getValue('copy-from');

            // Parse options text area
            const optionsText = document.getElementById('options').value.trim();
            if (optionsText) {
                 if (optionsText.startsWith('[')) {
                     question.options = tryParseJson(optionsText);
                     delete question.optionsSets;
                 } else if (optionsText.includes(':') && !optionsText.includes('{')) {
                      question.options = optionsText.split(/\r?\n/).map(line => {
                          const parts = line.split(':');
                          return { value: parts[0]?.trim() || '', label: parts.slice(1).join(':')?.trim() || parts[0]?.trim() || '' };
                      }).filter(opt => opt.value);
                     delete question.optionsSets;
                  } else if (optionsText.includes(':') && optionsText.includes('{')) {
                     question.optionsSets = {};
                     optionsText.split(/\r?\n/).forEach(line => {
                         const parts = line.split(':');
                         const key = parts[0]?.trim();
                         const jsonPart = parts.slice(1).join(':').trim();
                         if (key && jsonPart) {
                             const parsedJson = tryParseJson(jsonPart);
                             if (parsedJson && typeof parsedJson === 'object') {
                                 question.optionsSets[key] = parsedJson;
                             }
                         }
                     });
                      delete question.options;
                 } else {
                      console.warn("Could not parse options format:", optionsText);
                     question.options = null;
                     delete question.optionsSets;
                 }
            } else {
                 delete question.options;
                 delete question.optionsSets;
            }

            question.direction = getValue('direction') || 'default';
            question.min_value = getValue('min-value') ? parseInt(getValue('min-value')) : null;
            question.max_value = getValue('max-value') ? parseInt(getValue('max-value')) : null;
            question.step_value = getValue('step-value') ? parseInt(getValue('step-value')) : null;
            question.default_value = getValue('default-value');
            question.date_start_from = getValue('date-start-from');
            question.depends_on = getValue('depends-on');
            question.starts_with = getValue('starts-with');
            question.show_if = getValue('show-if');
            question.add_item = isChecked('add-item');
            question.skip_typing = isChecked('skip-typing');
        } else if (question.is_submit) {
            question.submit_message = getValue('submit-message');
            question.submit_message_failed = getValue('submit-message-failed');
        } else if (question.is_group) {
            question.depends_on = getValue('depends-on');
            question.starts_with = getValue('starts-with');
            question.show_if = getValue('show-if');
        } else { // Informational
             question.skip_typing = isChecked('skip-typing');
        }

        if (isNew) {
            parentList.push(question);
        } else {
            // Replace existing question in the array/sub-array
            parentList[questionIndex] = question;
        }

        markDirty();
        renderQuestionList(); // Re-render the entire list to reflect changes
        closeEditor();
    }

    function addSubQuestion(parentTempId) {
        const parentInfo = findQuestionByTempId(parentTempId);
        if (!parentInfo || !parentInfo.question.is_group) {
            alert("Error: Cannot add sub-question here. Parent is not a group.");
            return;
        }
        openEditor(); // Open as new
        document.getElementById('edit-question-parent-id').value = parentTempId;
        const titleElement = questionEditorModal.querySelector('h2');
        if(titleElement) titleElement.textContent = `Add Sub-Question to: ${parentInfo.question.question_text}`; // Modify title
    }

    function deleteQuestion() {
        if (!questionForm) return;
        const tempId = questionForm.dataset.tempId;
        if (!tempId) return;

        if (confirm('Are you sure you want to delete this question and all its sub-questions?')) {
            const found = findQuestionByTempId(tempId);
            if (found) {
                found.list.splice(found.index, 1);
                markDirty();
                renderQuestionList();
                closeEditor();
            } else {
                alert('Error: Could not find question to delete.');
            }
        }
    }

    // Prepare questions data for saving (flat structure)
    function prepareQuestionsForSaving() {
        const flatQuestions = [];
        let order = 0;

        function flatten(question, parentDbId = 0) {
            const dataToSave = { ...question }; // Clone
            delete dataToSave.temp_id;
            const subQuestions = dataToSave.sub_questions; // Keep temporarily
            delete dataToSave.sub_questions;

            dataToSave.question_order = order++;
            dataToSave.parent_id = parentDbId;

            if (dataToSave.optionsSets) {
                dataToSave.options = JSON.stringify(dataToSave.optionsSets);
                delete dataToSave.optionsSets;
            } else if (dataToSave.options && Array.isArray(dataToSave.options)) {
                dataToSave.options = JSON.stringify(dataToSave.options);
            } else if (dataToSave.options === null || typeof dataToSave.options === 'undefined') {
                delete dataToSave.options;
            }
            
            // Convert boolean to 1/0 for DB
            Object.keys(dataToSave).forEach(key => {
                if (typeof dataToSave[key] === 'boolean') {
                    dataToSave[key] = dataToSave[key] ? 1 : 0;
                }
            });

            flatQuestions.push(dataToSave);

            if (question.is_group && subQuestions && subQuestions.length > 0) {
                // IMPORTANT: The PHP side currently truncates and re-inserts.
                // Parent ID linking relies purely on the order received.
                // Passing parentDbId here is for future potential update logic.
                 subQuestions.forEach(subQ => flatten(subQ, dataToSave.id || 0));
            }
        }

        questions.forEach(q => flatten(q));
        return flatQuestions;
    }


    function saveQuestions() {
        updateQuestionOrder(); // Ensure structure matches DOM before saving
        const questionsToSave = prepareQuestionsForSaving();

        console.log("Saving questions:", JSON.stringify(questionsToSave, null, 2));

        updateStatus('Saving...', false);
        if(saveQuestionsBtn) saveQuestionsBtn.disabled = true;

        const body = new FormData();
        body.append('action', 'save_chatbot_questions');
        body.append('nonce', nonce);
        body.append('questions', JSON.stringify(questionsToSave));

        fetch(ajax_url, {
            method: 'POST',
            body: body
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                updateStatus('Questions saved successfully!', true);
                isDirty = false;
                updateSaveButtonState();
                 // Optional: Re-fetch or update IDs if needed. For now, just mark as saved.
                 // Since we TRUNCATE, reloading doesn't re-establish temp_id links easily.
                 // We might need to get the saved questions back with IDs and re-prepare.
            } else {
                updateStatus(`Error: ${data.data?.message || 'Unknown error'}`, true, true);
                if(saveQuestionsBtn) saveQuestionsBtn.disabled = false;
            }
        })
        .catch(error => {
            console.error("Fetch Error:", error);
            updateStatus(`AJAX Error: ${error.message}`, true, true);
            if(saveQuestionsBtn) saveQuestionsBtn.disabled = false;
        });
    }

    // --- UI Updates ---
    function markDirty() {
        isDirty = true;
        updateSaveButtonState();
        updateStatus('Unsaved changes.');
    }

    function updateSaveButtonState() {
        if(saveQuestionsBtn) saveQuestionsBtn.disabled = !isDirty;
    }

    function updateStatus(message, fadeOut = true, isError = false) {
        if(!statusDisplay) return;
        statusDisplay.textContent = message;
        statusDisplay.classList.remove('error', 'success');
        statusDisplay.style.display = 'inline';

        if (isError) {
            statusDisplay.classList.add('error');
        } else if (message.toLowerCase().includes('success')) {
            statusDisplay.classList.add('success');
        }

        if (fadeOut) {
            setTimeout(() => {
                 if(statusDisplay) statusDisplay.style.display = 'none';
            }, 3000);
        }
    }

    // --- Event Listeners ---
    function setupEventListeners() {
        if(addQuestionBtn) addQuestionBtn.addEventListener('click', () => openEditor());
        if(saveQuestionsBtn) saveQuestionsBtn.addEventListener('click', saveQuestions);
        if(cancelEditBtn) cancelEditBtn.addEventListener('click', closeEditor);
        if(questionEditorOverlay) questionEditorOverlay.addEventListener('click', closeEditor);
        if(questionForm) questionForm.addEventListener('submit', saveEditorChanges);
        if(deleteQuestionBtn) deleteQuestionBtn.addEventListener('click', deleteQuestion);
        if(fieldTypeSelect) fieldTypeSelect.addEventListener('change', handleFieldTypeChange);
        
        // Prevent closing modal when clicking inside it
        if(questionEditorModal) questionEditorModal.addEventListener('click', (event) => {
            event.stopPropagation();
        });

        // Attach drag/drop listeners to the main list initially
        if(questionList) addListDragDropListeners(questionList);
        // Note: Listeners for sub-lists are added dynamically in createQuestionElement

        // Warn user about unsaved changes before leaving the page
        window.addEventListener('beforeunload', (event) => {
            if (isDirty) {
                event.preventDefault(); // Standard requires prevention
                event.returnValue = ''; // For older browsers
                return ''; // For modern browsers
            }
        });
    }

    // --- Start ---
    initializeBuilder();
}); 
