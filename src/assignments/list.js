// list.js
document.addEventListener("DOMContentLoaded", function () {
    loadAssignments();

    function loadAssignments() {
        fetch("/api/index.php")
            .then(response => response.json())
            .then(data => {
                const listSection = document.getElementById("assignment-list-section");
                listSection.innerHTML = ''; // Clear existing content
                data.data.forEach(assignment => {
                    const article = createAssignmentArticle(assignment);
                    listSection.appendChild(article);
                });
            });
    }

    function createAssignmentArticle(assignment) {
        const article = document.createElement("article");
        article.innerHTML = `
            <h2>${assignment.title}</h2>
            <p>Due: ${assignment.due_date}</p>
            <p>${assignment.description}</p>
            <a href="details.html?id=${assignment.id}">View Details</a>
        `;
        return article;
    }
});