document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('assignment-form');
    form.addEventListener('submit', handleAddAssignment);
});

function handleAddAssignment(event) {
    event.preventDefault();

    const title = document.getElementById('assignment-title').value;
    const description = document.getElementById('assignment-description').value;
    const dueDate = document.getElementById('assignment-due-date').value;
    const files = document.getElementById('assignment-files').value;

    const newAssignment = {
        title,
        description,
        dueDate,
        files,
    };

    renderAssignment(newAssignment);
}

function renderAssignment(assignment) {
    const tbody = document.getElementById('assignments-tbody');
    const row = document.createElement('tr');
    row.innerHTML = `
        <td>${assignment.title}</td>
        <td>${assignment.dueDate}</td>
        <td>${assignment.description}</td>
        <td>
            <button class="edit-btn">Edit</button>
            <button class="delete-btn">Delete</button>
        </td>
    `;
    tbody.appendChild(row);
}