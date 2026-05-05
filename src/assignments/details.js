document.addEventListener("DOMContentLoaded", () => {
    const urlParams = new URLSearchParams(window.location.search);
    const assignmentId = urlParams.get('id');
    fetchAssignmentDetails(assignmentId);
});

function fetchAssignmentDetails(id) {
    fetch(`/api/index.php?id=${id}`)
        .then(response => response.json())
        .then(assignment => {
            const detailDiv = document.querySelector('#assignment-detail');
            detailDiv.innerHTML = `
                <h2>${assignment.title}</h2>
                <p>${assignment.description}</p>
                <p><strong>Due Date:</strong> ${assignment.due_date}</p>
                <a href="${assignment.file_link}">Download File</a>
            `;
        })
        .catch(error => console.error('Error fetching assignment details:', error));
}