document.addEventListener("DOMContentLoaded", function () {
    loadResources();
});

async function loadResources() {
    const container = document.getElementById("resources-container");

    if (!container) {
        return;
    }

    try {
        const response = await fetch("api/index.php?action=list");
        const data = await response.json();

        if (!data.success || data.resources.length === 0) {
            container.innerHTML = "<p>No resources found.</p>";
            return;
        }

        container.innerHTML = "";

        data.resources.forEach(function (resource) {
            const article = document.createElement("article");

            article.innerHTML = `
                <h3>${resource.title}</h3>
                <p>${resource.description}</p>
                <a href="${resource.link}" target="_blank">Open Resource</a>
                <br>
                <a href="details.html?id=${resource.id}">View Details</a>
                <hr>

            `;

            container.appendChild(article);
        });

    } catch (error) {
        container.innerHTML = "<p>Error loading resources.</p>";
    }
}  