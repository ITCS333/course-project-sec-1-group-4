var currentComments = [];
var currentResourceId = null;

document.addEventListener("DOMContentLoaded", function () {
    initializePage();

    const form = document.getElementById("comment-form");
    if (form) {
        form.addEventListener("submit", handleAddComment);
    }
});

function getResourceIdFromURL() {
    const params = new URLSearchParams(window.location.search);
    return params.get("id");
}

function renderResourceDetails(resource) {
    document.getElementById("resource-title").textContent = resource.title;
    document.getElementById("resource-description").textContent = resource.description;

    const link = document.getElementById("resource-link");
    link.href = resource.link;
}

function createCommentArticle(comment) {
    const article = document.createElement("article");

    article.innerHTML = `
        <p>${comment.text}</p>
        <footer>${comment.author}</footer>
    `;

    return article;
}

function renderComments() {
    const commentList = document.getElementById("comment-list");

    if (!commentList) {
        return;
    }

    commentList.innerHTML = "";

    currentComments.forEach(function (comment) {
        commentList.appendChild(createCommentArticle(comment));
    });
}

function handleAddComment(event) {
    event.preventDefault();

    const textarea = document.getElementById("new-comment");
    const text = textarea.value.trim();

    if (text === "") {
        return;
    }

    fetch("./api/index.php?action=comment", {
        method: "POST",
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify({
            resource_id: currentResourceId,
            author: "Student",
            text: text
        })
    })
    .then(function (response) {
        return response.json();
    })
    .then(function (result) {
        if (result.data) {
            currentComments.push(result.data);
            renderComments();
        }

        textarea.value = "";
    });
}

async function initializePage() {
    const id = getResourceIdFromURL();
    currentResourceId = id;

    if (!id) {
        return;
    }

    const resourceResponse = await fetch("./api/index.php?id=" + encodeURIComponent(id));
    const resourceResult = await resourceResponse.json();

    const resource = resourceResult.data || resourceResult.resource;

    if (resource && !Array.isArray(resource)) {
        renderResourceDetails(resource);
    }

    const commentsResponse = await fetch("./api/index.php?resource_id=" + encodeURIComponent(id) + "&action=comments");
    const commentsResult = await commentsResponse.json();

    currentComments = commentsResult.data || commentsResult.comments || [];
    renderComments();
}