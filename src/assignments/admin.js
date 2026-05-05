document.addEventListener("DOMContentLoaded", () => {
    fetchAssignments();
    document.getElementById('add-assignment-form').addEventListener('submit', addAssignment);
});

function fetchAssignments() {
    fetch('/api/index.php')
        .then(response => response.json())
        .then(assignments => {
            const tableBody = document.querySelector('#assignments-list tbody');
            tableBody.innerHTML = '';
            assignments.forEach(assignment => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${assignment.title}</td>
                    <td>${assignment.description}</td>
                    <td>${assignment.due_date}</td>
                    <td>
                        <button onclick="editAssignment(${assignment.id})">Edit</button>
                        <button onclick="deleteAssignment(${assignment.id})">Delete</button>
                    </td>
                `;
                tableBody.appendChild(row);
            });
        });
}

function addAssignment(e) {
    e.preventDefault();
    const title = document.getElementById('title').value;
    const description = document.getElementById('description').value;
    const fileLink = document.getElementById('file_link').value;
    const dueDate = document.getElementById('due_date').value;

    const data = { title, description, file_link: fileLink, due_date: dueDate };

    fetch('/api/index.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(() => fetchAssignments());
}

function deleteAssignment(id) {
    fetch(`/api/index.php?id=${id}`, { method: 'DELETE' })
        .then(() => fetchAssignments());
}