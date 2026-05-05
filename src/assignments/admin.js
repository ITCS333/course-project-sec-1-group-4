// src/assignments/admin.js

function createAssignmentRow(assignment) {
  const row = document.createElement('tr');
  row.innerHTML = `
    <td>${assignment.title}</td>
    <td>${assignment.due_date}</td>
    <td>${assignment.description}</td>
    <td>
      <button class="edit-btn" data-id="${assignment.id}">Edit</button>
      <button class="delete-btn" data-id="${assignment.id}">Delete</button>
    </td>
  `;
  return row;
}

function renderTable() {
  const tbody = document.getElementById('assignments-tbody');
  tbody.innerHTML = '';  // Clear the existing content
  assignments.forEach(assignment => {
    tbody.appendChild(createAssignmentRow(assignment));
  });
}

function loadAndInitialize() {
  fetch('./api/index.php')
    .then(response => response.json())
    .then(data => {
      assignments = data.data;
      renderTable();
    })
    .catch(error => console.error(error));
}

loadAndInitialize();