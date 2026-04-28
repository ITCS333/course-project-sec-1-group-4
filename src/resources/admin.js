document.addEventListener("DOMContentLoaded", function () {
    loadAdminResources();

    const form = document.getElementById("resource-form");
    const cancelButton = document.getElementById("cancel-edit-btn");

    if (form) {
        form.addEventListener("submit", function (event) {
            event.preventDefault();
            saveResource();
        });
    }

    if (cancelButton) {
        cancelButton.addEventListener("click", function () {
            resetForm();
        });
    }
});

async function loadAdminResources() {
    const container = document.getElementById("admin-resources-container");

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
            const div = document.createElement("div");

            div.innerHTML = `
                <h3>${resource.title}</h3>
                <p>${resource.description}</p>
                <a href="${resource.link}" target="_blank">Open Resource</a>
                <br>
                <button onclick="editResource(${resource.id})">Edit</button>
                <button onclick="deleteResource(${resource.id})">Delete</button>
                <hr>
            `;

            div.dataset.title = resource.title;
            div.dataset.description = resource.description;
            div.dataset.link = resource.link;
            div.dataset.id = resource.id;

            container.appendChild(div);
        });

    } catch (error) {
        container.innerHTML = "<p>Error loading resources.</p>";
    }

}

function editResource(id) {
    const cards = document.querySelectorAll("#admin-resources-container div");
    let selectedCard = null;

    cards.forEach(function (card) {
        if (card.dataset.id == id) {
            selectedCard = card;
        }
    });

    if (!selectedCard) {
        return;
    }

    document.getElementById("resource-id").value = id;
    document.getElementById("resource-title").value = selectedCard.dataset.title;
    document.getElementById("resource-description").value = selectedCard.dataset.description;
    document.getElementById("resource-link").value = selectedCard.dataset.link;
    document.getElementById("form-title").textContent = "Edit Resource";

}

function resetForm() {
    document.getElementById("resource-id").value = "";
    document.getElementById("resource-title").value = "";
    document.getElementById("resource-description").value = "";
    document.getElementById("resource-link").value = "";
    document.getElementById("form-title").textContent = "Add Resource";
}

async function saveResource() {
    const id = document.getElementById("resource-id").value;
    const title = document.getElementById("resource-title").value.trim();
    const description = document.getElementById("resource-description").value.trim();
    const link = document.getElementById("resource-link").value.trim();

    if (title === "" || description === "" || link === "") {
        alert("Please fill in all fields.");
        return;
    }

    const url = id ? "api/index.php?action=update" : "api/index.php?action=create";

    try {
        const response = await fetch(url, {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({
                id: id,
                title: title,
                description: description,
                link: link
            })
        });

        const data = await response.json();

        if (data.success) {
            resetForm();
            loadAdminResources();
        } else {
            alert(data.message || "Could not save resource.");
        }

    } catch (error) {
        alert("Error saving resource.");
    }
}

async function deleteResource(id) {
    const confirmed = confirm("Are you sure you want to delete this resource?");

    if (!confirmed) {
        return;
    }

    try {
        const response = await fetch("api/index.php?action=delete", {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({
                id: id
            })
        });

        const data = await response.json();

        if (data.success) {
            loadAdminResources();
        } else {
            alert(data.message || "Could not delete resource.");
        }

    } catch (error) {
        alert("Error deleting resource.");
    }
}