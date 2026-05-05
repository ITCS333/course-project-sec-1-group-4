function loadAssignments() {
    fetch('./api/index.php')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data.length > 0) {
                const assignmentList = document.getElementById('assignment-list-section');
                assignmentList.innerHTML = ''; // Clear any existing content

                data.data.forEach(assignment => {
                    const article = createAssignmentArticle(assignment);
                    assignmentList.appendChild(article);
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
}

function createAssignmentArticle(assignment) {
    const article = document.createElement('article');
    const title = document.createElement('h2');
    title.textContent = assignment.title;
    const dueDate = document.createElement('p');
    dueDate.textContent = `Due Date: ${assignment.due_date}`;
    const description = document.createElement('p');
    description.textContent = assignment.description;

    const viewLink = document.createElement('a');
    viewLink.href = `details.html?id=${assignment.id}`;
    viewLink.textContent = 'View Details';

    article.appendChild(title);
    article.appendChild(dueDate);
    article.appendChild(description);
    article.appendChild(viewLink);

    return article;
}

loadAssignments();