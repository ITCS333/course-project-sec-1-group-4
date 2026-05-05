// src/assignments/details.js

function getAssignmentIdFromURL() {
  const urlParams = new URLSearchParams(window.location.search);
  return urlParams.get('id');
}

function renderAssignmentDetails(assignment) {
  document.getElementById('assignment-title').textContent = assignment.title;
  document.getElementById('assignment-due-date').textContent = `Due: ${assignment.due_date}`;
  document.getElementById('assignment-description').textContent = assignment.description;
  const filesList = document.getElementById('assignment-files-list');
  assignment.files.forEach(file => {
    const li = document.createElement('li');
    const a = document.createElement('a');
    a.href = file;
    a.textContent = file;
    li.appendChild(a);
    filesList.appendChild(li);
  });
}

function initializePage() {
  const id = getAssignmentIdFromURL();
  fetch(`./api/index.php?id=${id}`)
    .then(response => response.json())
    .then(data => renderAssignmentDetails(data.data))
    .catch(error => console.error(error));
}

initializePage();