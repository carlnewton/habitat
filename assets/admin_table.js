const checkAll = document.getElementById('check-all');
const rowChecks = document.querySelectorAll('#results-table .row-check');
const checkboxActions = document.querySelectorAll('#actions-menu .checkbox-action');
const actionItemFormInputs = document.querySelectorAll('.action-item-form input[name="items"]')

let selectedItems = [];

checkAll.addEventListener('change', (event) => {
    let check = event.currentTarget.checked;

    for (let i = 0; i < rowChecks.length; i++) {
        rowChecks[i].checked = check;

        if (check) {
            selectedItems.push(rowChecks[i].value);
        }
    }

    if (check) {
        selectedItems = [... new Set(selectedItems)];
        for (let i = 0; i < checkboxActions.length; i++) {
            checkboxActions[i].classList.remove('disabled');
        }
    } else {
        selectedItems = [];
        for (let i = 0; i < checkboxActions.length; i++) {
            checkboxActions[i].classList.add('disabled');
        }
    }

    for (let i = 0; i < actionItemFormInputs.length; i++) {
        actionItemFormInputs[i].value = selectedItems.join(',');
    }
})

window.rowCheckChange = function(checkbox) {
    if (checkbox.checked && !selectedItems.includes(checkbox.value)) {
        selectedItems.push(checkbox.value);
    } else {
        let index = selectedItems.indexOf(checkbox.value);
        if (index > -1) {
            selectedItems.splice(index, 1);
        }
    }

    let allSame = true;
    for (let i = 0; i < rowChecks.length; i++) {
        if (rowChecks[i].checked !== checkbox.checked) {
            checkAll.checked = false;
            allSame = false;
            break;
        }
    }

    if (allSame) {
        checkAll.checked = checkbox.checked;
    }

    if (selectedItems.length > 0) {
        for (let i = 0; i < checkboxActions.length; i++) {
            checkboxActions[i].classList.remove('disabled');
        }
    } else {
        for (let i = 0; i < checkboxActions.length; i++) {
            checkboxActions[i].classList.add('disabled');
        }
    }

    for (let i = 0; i < actionItemFormInputs.length; i++) {
        actionItemFormInputs[i].value = selectedItems.join(',');
    }
}

