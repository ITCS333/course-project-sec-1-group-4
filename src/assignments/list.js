document.addEventListener("DOMContentLoaded", () => {
    fetchAssignments();
});

function fetchAssignments() {
    fetch('/api/index.php')
        .then(response => response.json())
        .then(assignments => {
            const tableBody = document.querySelector('#assignments-table tbody');
            assignments.forEach(assignment => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${assignment.title}</td>
                    <td>${assignment.description}</td>
                    <td>${assignment.due_date}</td>
                    <td><a href="details.html?id=${assignment.id}">View</a></td>
                `;
                tableBody.appendChild(row);
            });
        })
        .catch(error => console.error('Error fetching assignments:', error));
}