document.addEventListener("DOMContentLoaded", function () {
    loadResourceDetails();

    const form = document.getElementById("comment-form");

    if (form) {
        form.addEventListener("submit", function (event) {
            event.preventDefault();
            addComment();
        });
    }
});

function getResourceId() {
    const params = new URLSearchParams(window.location.search);
    return params.get("id");
}

async function loadResourceDetails() {
    const resourceId = getResourceId();
    const detailsContainer = document.getElementById("resource-details");
    const commentsContainer = document.getElementById("comments-container");

    if (!resourceId || !detailsContainer) {
        return;
    }

    try {
        const response = await fetch("api/index.php?action=details&id=" + encodeURIComponent(resourceId));
        const data = await response.json();

        if (!data.success) {
            detailsContainer.innerHTML = "<p>Resource not found.</p>";
            return;

        }

        const resource = data.resource;

        document.getElementById("resource-title").textContent = resource.title;

        detailsContainer.innerHTML = `
            <h2>${resource.title}</h2>
            <p>${resource.description}</p>
            <a href="${resource.link}" target="_blank">Open Resource</a>
        `;

        commentsContainer.innerHTML = "";

        if (!data.comments || data.comments.length === 0) {
            commentsContainer.innerHTML = "<p>No comments yet.</p>";
            return;
        }

        data.comments.forEach(function (comment) {
            const div = document.createElement("div");

            div.innerHTML = `
                <p>${comment.comment}</p>
                <small>User ID: ${comment.user_id}</small>
                <hr>
            `;

            commentsContainer.appendChild(div);
        });

    } catch (error) {
        detailsContainer.innerHTML = "<p>Error loading resource details.</p>";
    }
}

async function addComment() {
    const resourceId = getResourceId();
    const commentInput = document.getElementById("comment-input");

    if (!resourceId || !commentInput) {
        return;

    }

    const comment = commentInput.value.trim();

    if (comment === "") {
        alert("Please write a comment.");
        return;
    }

    try {
        const response = await fetch("api/index.php?action=comment", {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({
                resource_id: resourceId,
                comment: comment
            })
        });

        const data = await response.json();

        if (data.success) {
            commentInput.value = "";
            loadResourceDetails();
        } else {
            alert(data.message || "Could not add comment.");
        }

    } catch (error) {
        alert("Error adding comment.");
    }
}
