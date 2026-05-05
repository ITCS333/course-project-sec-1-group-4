document.getElementById('add-assignment').addEventListener('click', function(event) {
    event.preventDefault();

    const title = document.getElementById('assignment-title').value;
    const description = document.getElementById('assignment-description').value;
    const due_date = document.getElementById('assignment-due-date').value;
    const files = document.getElementById('assignment-files').value.split('\n');

    if (!title || !description || !due_date) {
        alert('Please fill in all required fields');
        return;
    }

    const newAssignment = { title, description, due_date, files };

    fetch('./api/index.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(newAssignment),
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Assignment added successfully');
            location.reload(); // Reload to display new assignment
        } else {
            alert('Error adding assignment');
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
});