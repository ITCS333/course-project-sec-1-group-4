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

async function loadResources() {

    const container = document.getElementById("resources-container");

    if (!container) {

        return;

    }

    try {

        const response = await fetch("./api/index.php");

        const result = await response.json();

        const resources = result.data || result.resources || [];

        container.innerHTML = "";

        resources.forEach(function (resource) {

            container.appendChild(createResourceArticle(resource));

        });

    } catch (error) {

        container.innerHTML = "Failed to load resources.";

    }

}