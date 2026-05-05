// details.js
document.addEventListener("DOMContentLoaded", function () {
    const assignmentId = getAssignmentIdFromURL();
    if (assignmentId) {
        fetchAssignmentDetails(assignmentId);
    }

    function getAssignmentIdFromURL() {
        const params = new URLSearchParams(window.location.search);
        return params.get('id');
    }

    function fetchAssignmentDetails(id) {
        fetch(`/api/index.php?id=${id}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    renderAssignmentDetails(data.data);
                    loadComments(id);
                } else {
                    alert("Failed to load assignment details.");
                }
            });
    }

    function renderAssignmentDetails(assignment) {
        document.getElementById("assignment-title").textContent = assignment.title;
        document.getElementById("assignment-due-date").textContent = `Due: ${assignment.due_date}`;
        document.getElementById("assignment-description").textContent = assignment.description;

        const filesList = document.getElementById("assignment-files-list");
        filesList.innerHTML = "";
        assignment.files.forEach(file => {
            const li = document.createElement("li");
            const link = document.createElement("a");
            link.href = file;
            link.textContent = file;
            li.appendChild(link);
            filesList.appendChild(li);
        });
    }

    function loadComments(assignmentId) {
        fetch(`/api/index.php?action=comments&assignment_id=${assignmentId}`)
            .then(response => response.json())
            .then(data => {
                renderComments(data.data);
            });
    }

    function renderComments(comments) {
        const commentList = document.getElementById("comment-list");
        commentList.innerHTML = "";
        comments.forEach(comment => {
            const article = createCommentArticle(comment);
            commentList.appendChild(article);
        });
    }

    function createCommentArticle(comment) {
        const article = document.createElement("article");
        article.innerHTML = `
            <p>${comment.text}</p>
            <footer>
                <span>Posted by: ${comment.author}</span>
                <span>${comment.created_at}</span>
            </footer>
        `;
        return article;
    }

    const commentForm = document.getElementById("comment-form");
    commentForm.addEventListener("submit", function (event) {
        event.preventDefault();
        const commentText = document.getElementById("new-comment").value;

        if (commentText) {
            const commentData = {
                assignment_id: assignmentId,
                author: "Student", // Or take this from the current user
                text: commentText,
            };

            fetch("/api/index.php?action=comment", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify(commentData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert("Comment posted successfully!");
                    loadComments(assignmentId);
                    document.getElementById("new-comment").value = ""; // Clear the comment input
                } else {
                    alert("Failed to post comment.");
                }
            });
        }
    });
});