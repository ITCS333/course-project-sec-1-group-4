// src/assignments/list.js

function createAssignmentArticle(assignment) {
  const article = document.createElement('article');
  article.innerHTML = `
    <h2>${assignment.title}</h2>
    <p>Due: ${assignment.due_date}</p>
    <p>${assignment.description}</p>
    <a href="details.html?id=${assignment.id}">View</a>
  `;
  return article;
}

function loadAssignments() {
  fetch('./api/index.php')
    .then(response => response.json())
    .then(data => {
      const assignments = data.data;
      const section = document.getElementById('assignment-list-section');
      section.innerHTML = '';  // Clear the existing content
      assignments.forEach(assignment => {
        section.appendChild(createAssignmentArticle(assignment));
      });
    })
    .catch(error => console.error(error));
}

loadAssignments();