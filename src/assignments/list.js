document.addEventListener('DOMContentLoaded', () => {
    loadAssignments();
});

function loadAssignments() {
    fetch('/api/index.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderAssignments(data.data);
            }
        });
}

function renderAssignments(assignments) {
    const assignmentsList = document.getElementById('assignments-list');
    assignmentsList.innerHTML = '';
    assignments.forEach(assignment => {
        const article = createAssignmentArticle(assignment);
        assignmentsList.appendChild(article);
    });
}

function createAssignmentArticle(assignment) {
    const article = document.createElement('article');
    article.innerHTML = `
        <h3>${assignment.title}</h3>
        <p>${assignment.due_date}</p>
        <p>${assignment.description}</p>
        <a href="details.html?id=${assignment.id}">View Details</a>
    `;
    return article;
}