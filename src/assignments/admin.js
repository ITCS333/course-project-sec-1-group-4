// admin.js
document.addEventListener("DOMContentLoaded", function () {
    const form = document.getElementById("assignment-form");
    form.addEventListener("submit", handleAddAssignment);

    function handleAddAssignment(event) {
        event.preventDefault();

        const title = document.getElementById("assignment-title").value;
        const description = document.getElementById("assignment-description").value;
        const dueDate = document.getElementById("assignment-due-date").value;
        const files = document.getElementById("assignment-files").value.split("\n");

        const newAssignment = {
            title,
            description,
            due_date: dueDate,
            files: files.map(file => file.trim()).filter(file => file),
        };

        fetch("/api/index.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify(newAssignment)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert("Assignment added successfully!");
                loadAssignments();
            } else {
                alert("Error adding assignment.");
            }
        })
        .catch(error => alert("Failed to add assignment: " + error));
    }

    function loadAssignments() {
        fetch("/api/index.php")
            .then(response => response.json())
            .then(data => {
                const tbody = document.getElementById("assignments-tbody");
                tbody.innerHTML = '';
                data.data.forEach(assignment => {
                    const row = createAssignmentRow(assignment);
                    tbody.appendChild(row);
                });
            });
    }

    function createAssignmentRow(assignment) {
        const row = document.createElement("tr");
        row.innerHTML = `
            <td>${assignment.title}</td>
            <td>${assignment.due_date}</td>
            <td>${assignment.description}</td>
            <td>
                <button class="edit-btn" data-id="${assignment.id}">Edit</button>
                <button class="delete-btn" data-id="${assignment.id}">Delete</button>
            </td>
        `;
        return row;
    }

    loadAssignments();
});