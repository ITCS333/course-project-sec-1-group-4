function renderAssignmentDetails(assignment) {
    document.getElementById('assignment-title').textContent = assignment.title;
    document.getElementById('assignment-due-date').textContent = `Due: ${assignment.due_date}`;
    document.getElementById('assignment-description').textContent = assignment.description;

    const filesList = document.getElementById('assignment-files-list');
    filesList.innerHTML = ''; // Clear the list before adding new files
    assignment.files.forEach(file => {
        const listItem = document.createElement('li');
        const link = document.createElement('a');
        link.href = file;
        link.textContent = file;
        listItem.appendChild(link);
        filesList.appendChild(listItem);
    });
}

function loadAssignmentDetails(id) {
    fetch(`./api/index.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data) {
                renderAssignmentDetails(data.data);
            } else {
                alert('Error fetching assignment details');
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
}

const urlParams = new URLSearchParams(window.location.search);
const assignmentId = urlParams.get('id');
if (assignmentId) {
    loadAssignmentDetails(assignmentId);
}