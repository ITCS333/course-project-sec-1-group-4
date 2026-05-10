var resources = JSON.parse(localStorage.getItem("resources")) || [];

document.addEventListener("DOMContentLoaded", function () {
    renderTable(resources);

    const form = document.getElementById("resource-form");
    if (form) {
        form.addEventListener("submit", handleAddResource);
    }
});

function saveResources() {
    localStorage.setItem("resources", JSON.stringify(resources));
}

function createResourceRow(resource) {
    const row = document.createElement("tr");

    row.innerHTML = `
        <td>${resource.title}</td>
        <td>${resource.description}</td>
        <td><a href="${resource.link}" target="_blank">${resource.link}</a></td>
        <td>
            <button type="button" class="btn btn-warning btn-sm edit-btn" data-id="${resource.id}">Edit</button>
            <button type="button" class="btn btn-danger btn-sm delete-btn" data-id="${resource.id}">Delete</button>
        </td>
    `;

    row.querySelector(".delete-btn").addEventListener("click", function () {
        resources = resources.filter(function (item) {
            return item.id !== resource.id;
        });
        saveResources();
        renderTable(resources);
    });

    return row;
}

function renderTable(resourceList) {
    const tbody = document.getElementById("resources-tbody");

    if (!tbody) {
        return;
    }

    tbody.innerHTML = "";

    if (resourceList.length === 0) {
        tbody.innerHTML = `<tr><td colspan="4">No resources found.</td></tr>`;
        return;
    }

    resourceList.forEach(function (resource) {
        tbody.appendChild(createResourceRow(resource));
    });
}

function handleAddResource(event) {
    event.preventDefault();

    const title = document.getElementById("resource-title").value.trim();
    const description = document.getElementById("resource-description").value.trim();
    const link = document.getElementById("resource-link").value.trim();

    if (title === "" || description === "" || link === "") {
        return;
    }

    const newResource = {
        id: Date.now(),
        title: title,
        description: description,
        link: link
    };

    resources.push(newResource);
    saveResources();
    renderTable(resources);

    document.getElementById("resource-form").reset();
}