var questionsInput = document.querySelector('#registration-challenge-questions');
const DEFAULT_QUESTION_HEADER = 'New question';

buildRegistrationAccordion();

document.addEventListener('input', function (e) {
    if (e.target.classList.contains('registration-question')) {
        let alert = e.target.parentNode.querySelector('.alert');
        if (!alert.classList.contains('d-none')) {
            alert.classList.add('d-none');
        }
        
        if (questionIsEasyToGuess(e.target.value)) {
            alert.classList.remove('d-none');
        }

        let headerText = e.target.value;
        if (headerText === undefined || headerText === '') {
            headerText = DEFAULT_QUESTION_HEADER;
        }
        e.target.closest('.accordion-question').querySelector('.accordion-button').innerText = headerText;
        buildRegistrationJson();
        return;
    }
    if (e.target.classList.contains('registration-question-answer')) {
        let alert = e.target.closest('.registration-question-answer-container').querySelector('.alert');
        if (!alert.classList.contains('d-none')) {
            alert.classList.add('d-none');
        }
        
        if (answerIsEasyToGuess(e.target.value)) {
            alert.classList.remove('d-none');
        }

        let emptyFound = false;
        let answers = e.target.closest('.registration-question-answers').querySelectorAll('.registration-question-answer');
        for (let answer of answers) {
            if (answer.value === '') {
                emptyFound = true;
                break;
            }
        }

        if (!emptyFound) {
            e.target.closest('.registration-question-answers').appendChild(buildAnswerField());
        }

        buildRegistrationJson();
        return;
    }
});

document.addEventListener('click', function (e) {
    if (
        e.target.classList.contains('btn-registration-question-answer-delete') ||
        e.target.closest('.btn-registration-question-answer-delete')
    ) {
        let registrationQuestionAnswers = e.target.closest('.registration-question-answers');
        e.target.closest('.registration-question-answer-container').remove();
        let answers = registrationQuestionAnswers.querySelectorAll('.registration-question-answer');
        let emptyFound = false;
        for (let answer of answers) {
            if (answer.value === '') {
                emptyFound = true;
                break;
            }
        }
        if (!emptyFound) {
            registrationQuestionAnswers.appendChild(buildAnswerField());
        }

        buildRegistrationJson();
        return;
    }

    if (e.target.classList.contains('btn-registration-question-delete')) {
        e.target.closest('.accordion-question').remove();
        buildRegistrationJson();
        return;
    }

    if (e.target.id === 'registration-challenge-add-question-btn') {
        let accordion = document.querySelector('#questions-accordion');
        let accordionItem = buildAccordionItem(accordion.childElementCount + 1);
        accordion.appendChild(accordionItem);
        expandLastAccordionItem();
        buildRegistrationJson();
        return;
    }
});

function questionIsEasyToGuess(question) {
    question = question.trim().toLowerCase();
    if (question.includes(' number of ')) {
        return true;
    }

    if (question.includes('how many ')) {
        return true;
    }

    if (question.includes(' colour ')) {
        return true;
    }

    if (question.includes(' color ')) {
        return true;
    }

    if (question.startsWith('does ')) {
        return true;
    }

    if (question.startsWith('is ')) {
        return true;
    }

    if (question.startsWith('are ')) {
        return true;
    }

    return false;
}

function answerIsEasyToGuess(answer) {
    answer = answer.trim().toLowerCase();

    if (answer === '') {
        return false;
    }

    if (answer === 'yes' || answer === 'no') {
        return true;
    }

    if (!isNaN(answer)) {
        return true;
    }

    if (['red', 'orange', 'yellow', 'green', 'blue', 'purple', 'pink', 'brown', 'black', 'grey', 'white'].includes(answer)) {
        return true;
    }

    return false;
}

function expandLastAccordionItem() {
    let accordion = document.querySelector('#questions-accordion');
    for (let i = 0; i < accordion.children.length; i++) {
        let accordionItem = accordion.children[i];
        if (i === accordion.children.length - 1) {
            accordionItem.querySelector('.accordion-collapse').classList.add('show');
            accordionItem.querySelector('.accordion-button').classList.remove('collapsed');
            accordionItem.querySelector('.accordion-button').setAttribute('aria-expanded', 'true');
            accordionItem.querySelector('.registration-question').focus();
        } else {
            accordionItem.querySelector('.accordion-collapse').classList.remove('show');
            accordionItem.querySelector('.accordion-button').classList.add('collapsed');
            accordionItem.querySelector('.accordion-button').setAttribute('aria-expanded', 'false');
        }
    }
}

function buildRegistrationJson() {
    let accordion = document.querySelector('#questions-accordion');
    questionItems = [];
    for (const accordionItem of accordion.children) {
        let question = accordionItem.querySelector('input.registration-question').value;
        let answers = [];
        let answerFields = accordionItem.querySelectorAll('input.registration-question-answer');
        for (const answerField of answerFields) {
            answers.push(answerField.value);
        }
        questionItems.push({
            'question': question,
            'answers': answers
        });
    }
    questionsInput.value = JSON.stringify(questionItems);
}

function buildAnswerField(answer = '') {

    const answerField = document.createElement('div');
    answerField.classList.add('input-group');
    answerField.innerHTML = `
        <input type="text" class="registration-question-answer form-control" value="${answer}" placeholder="Add a correct answer" maxlength="255">
        <button class="btn btn-outline-danger btn-registration-question-answer-delete" type="button"><i class="bi bi-x-lg"></i></button>
    `;


    const alert = document.createElement('div');
    alert.classList.add('alert', 'alert-warning', 'd-none', 'mt-2');
    alert.innerText = 'This answer looks like it might be easy to brute-force. It is recommended to avoid yes or no questions, questions which have numeric answers, or answers from a known list.';
    const answerFieldWithAlert = document.createElement('div');
    answerFieldWithAlert.classList.add('mb-3', 'registration-question-answer-container');
    answerFieldWithAlert.appendChild(answerField);
    answerFieldWithAlert.appendChild(alert);

    return answerFieldWithAlert;
}

function buildAccordionItem(id, question) {
    let answerFields = '';
    let questionText = '';
    let questionHeader = DEFAULT_QUESTION_HEADER;
    if (question !== undefined) {
        questionText = question.question;
        questionHeader = question.question;
        if (question.answers.length) {
            for (let answer = 0; answer < question.answers.length; answer++) {
                answerFields += buildAnswerField(question.answers[answer]).outerHTML;
            }
        }
    }
    answerFields += buildAnswerField().outerHTML;
    const accordionItem = document.createElement('div');
    accordionItem.classList.add('accordion-question', 'accordion-item');
    accordionItem.innerHTML = `
        <h2 class="accordion-header">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#question-${id}" aria-expanded="false" aria-controls="question-${id}">
                ${questionHeader}
            </button>
        </h2>
        <div id="question-${id}" class="accordion-collapse collapse" data-bs-parent="#questions-accordion">
            <div class="accordion-body">
                <div class="mb-3">
                    <label for="question-${id}-question" class="form-label">Question</label>
                    <div class="form-text">
                        <p>
                            It is recommended to try to ask a question which would be easy for somebody local to
                            answer, but difficult for anyone else, or a bot to guess.
                        </p>
                    </div>
                    <input type="text" class="form-control registration-question" id="question-${id}-question" value="${questionText}" placeholder="Add a question" maxlength="255">
                    <div class="mt-2 alert alert-warning d-none">
                        This question looks like it might be easy to brute-force. It is recommended to avoid yes or no questions, questions which have numeric answers, or answers from a known list.
                    </div>
                </div>
                <div class="mb-3">
                    <label for="question-${id}-answers" class="form-label">Correct answers</label>
                    <div class="form-text">
                        <p>
                            Answers are not cases sensitive and white space before and after the answer will be ignored.
                        </p>
                        <p>
                            For instance, <kbd>&nbsp;&nbsp;Example answer</kbd>, <kbd>example ANSWER&nbsp&nbsp</kbd> and
                            <kbd>ExAmPlE aNsWeR</kbd> will all be considered as variations of the same answer.
                        </p>
                    </div>
                    <div class="registration-question-answers">
                        ${answerFields}
                    </div>
                    <button class="btn btn-outline-danger btn-registration-question-delete">Remove question</button>
                </div>
            </div>
        </div>
    `;

    return accordionItem;
}

function buildRegistrationAccordion() {
    let questions = questionsInput.value;
    let questionsJson = JSON.parse(questions);

    let accordion = document.querySelector('#questions-accordion');
    accordion.innerHTML = '';
    for (let question = 0; question < questionsJson.length; question++) {
        accordion.appendChild(buildAccordionItem(question, questionsJson[question]));
    }
}
