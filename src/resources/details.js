document.addEventListener("DOMContentLoaded", function () {
    loadResources();
});

function createResourceArticle(resource) {
    const article = document.createElement("article");
    article.className = "border p-3 mb-3";

    article.innerHTML = `
        <h2>${resource.title}</h2>
        <p>${resource.description}</p>
        <a class="btn btn-primary" href="details.html?id=${resource.id}">View Details</a>
    `;

    return article;
}

function loadResources() {
    const container = document.getElementById("resources-container");

    if (!container) {
        return;
    }

    const resources = JSON.parse(localStorage.getItem("resources")) || [];

    container.innerHTML = "";

    if (resources.length === 0) {
        container.innerHTML = "No resources found.";
        return;
    }

    resources.forEach(function (resource) {
        container.appendChild(createResourceArticle(resource));
    });
}