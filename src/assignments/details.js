document.addEventListener("DOMContentLoaded", () => {
    const commentForm = document.getElementById("comment-form");
    commentForm.addEventListener("submit", handleAddComment);
    initializePage();
});

function initializePage() {
    const assignmentId = getAssignmentIdFromURL();
    loadAssignmentDetails(assignmentId);
    loadComments(assignmentId);
}

function getAssignmentIdFromURL() {
    const params = new URLSearchParams(window.location.search);
    return params.get("id");
}

function loadAssignmentDetails(assignmentId) {
    fetch(`/api/index.php?id=${assignmentId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderAssignmentDetails(data.data);
            }
        });
}

function renderAssignmentDetails(assignment) {
    document.getElementById("assignment-title").textContent = assignment.title;
    document.getElementById("assignment-due-date").textContent = `Due: ${assignment.due_date}`;
    document.getElementById("assignment-description").textContent = assignment.description;

    const filesList = document.getElementById("assignment-files-list");
    assignment.files.forEach(file => {
        const listItem = document.createElement("li");
        listItem.textContent = file;
        filesList.appendChild(listItem);
    });
}

function loadComments(assignmentId) {
    fetch(`/api/index.php?resource_id=${assignmentId}&action=comments`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderComments(data.data);
            }
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
    article.innerHTML = `<p>${comment.text}</p><footer>By ${comment.author}</footer>`;
    return article;
}

function handleAddComment(event) {
    event.preventDefault();

    const commentText = document.getElementById("new-comment").value;
    const assignmentId = getAssignmentIdFromURL();

    const commentData = {
        resource_id: assignmentId,
        text: commentText,
        author: "Student",
    };

    fetch(`/api/index.php?action=comment`, {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
        },
        body: JSON.stringify(commentData),
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                loadComments(assignmentId);
                document.getElementById("new-comment").value = "";
            }
        });
}