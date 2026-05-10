const API_URL = './api/index.php';
let assignments = [];

// إنشاء صف جدول
function createAssignmentRow(assignment) {
    const row = document.createElement('tr');
    row.innerHTML = `
        <td>${escapeHtml(assignment.title)}</td>
        <td>${assignment.due_date}</td>
        <td>${escapeHtml(assignment.description.substring(0, 100))}${assignment.description.length > 100 ? '...' : ''}</td>
        <td>
            <button class="edit-btn" data-id="${assignment.id}">Edit</button>
            <button class="delete-btn" data-id="${assignment.id}">Delete</button>
        </td>
    `;
    return row;
}

// عرض الجدول
function renderTable() {
    const tbody = document.getElementById('assignments-tbody');
    tbody.innerHTML = '';
    
    if (assignments.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4">No assignments found.</td></tr>';
        return;
    }
    
    assignments.forEach(assignment => {
        tbody.appendChild(createAssignmentRow(assignment));
    });
}

// إضافة واجب جديد
async function handleAddAssignment(event) {
    event.preventDefault();
    
    const title = document.getElementById('assignment-title').value.trim();
    const description = document.getElementById('assignment-description').value.trim();
    const due_date = document.getElementById('assignment-due-date').value;
    const filesText = document.getElementById('assignment-files').value.trim();
    
    if (!title || !description || !due_date) {
        alert('Please fill in all required fields.');
        return;
    }
    
    const files = filesText ? filesText.split('\n').filter(f => f.trim()) : [];
    
    try {
        const response = await fetch(API_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ title, description, due_date, files })
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('Assignment added successfully!');
            document.getElementById('assignment-form').reset();
            await loadAndInitialize();
        } else {
            alert('Failed to add assignment.');
        }
    } catch (error) {
        console.error('Error adding assignment:', error);
        alert('Error adding assignment.');
    }
}

// حذف واجب
async function deleteAssignment(id) {
    try {
        const response = await fetch(`${API_URL}?id=${id}`, {
            method: 'DELETE'
        });
        const result = await response.json();
        
        if (result.success) {
            alert('Assignment deleted successfully!');
            await loadAndInitialize();
        } else {
            alert('Failed to delete assignment.');
        }
    } catch (error) {
        console.error('Error deleting assignment:', error);
        alert('Error deleting assignment.');
    }
}

// تعديل واجب - تعبئة النموذج
function editAssignment(assignment) {
    document.getElementById('assignment-title').value = assignment.title;
    document.getElementById('assignment-description').value = assignment.description;
    document.getElementById('assignment-due-date').value = assignment.due_date;
    document.getElementById('assignment-files').value = assignment.files ? assignment.files.join('\n') : '';
    
    // تغيير زر الإضافة إلى تحديث
    const submitBtn = document.getElementById('add-assignment');
    submitBtn.textContent = 'Update Assignment';
    
    // إزالة المستمع القديم وإضافة مستمع للتحديث
    const form = document.getElementById('assignment-form');
    const oldSubmit = form.onsubmit;
    form.onsubmit = async (e) => {
        e.preventDefault();
        await updateAssignment(assignment.id);
    };
}

// تحديث واجب
async function updateAssignment(id) {
    const title = document.getElementById('assignment-title').value.trim();
    const description = document.getElementById('assignment-description').value.trim();
    const due_date = document.getElementById('assignment-due-date').value;
    const filesText = document.getElementById('assignment-files').value.trim();
    const files = filesText ? filesText.split('\n').filter(f => f.trim()) : [];
    
    try {
        const response = await fetch(API_URL, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id, title, description, due_date, files })
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('Assignment updated successfully!');
            document.getElementById('assignment-form').reset();
            const submitBtn = document.getElementById('add-assignment');
            submitBtn.textContent = 'Add Assignment';
            document.getElementById('assignment-form').onsubmit = handleAddAssignment;
            await loadAndInitialize();
        } else {
            alert('Failed to update assignment.');
        }
    } catch (error) {
        console.error('Error updating assignment:', error);
        alert('Error updating assignment.');
    }
}

// معالج النقر على الجدول
function handleTableClick(event) {
    const target = event.target;
    
    if (target.classList.contains('delete-btn')) {
        const id = parseInt(target.dataset.id);
        if (confirm('Are you sure you want to delete this assignment?')) {
            deleteAssignment(id);
        }
    } else if (target.classList.contains('edit-btn')) {
        const id = parseInt(target.dataset.id);
        const assignment = assignments.find(a => a.id === id);
        if (assignment) {
            editAssignment(assignment);
        }
    }
}

// تحميل البيانات وتهيئة الصفحة
async function loadAndInitialize() {
    try {
        const response = await fetch(API_URL);
        const result = await response.json();
        
        if (result.success && result.data) {
            assignments = result.data;
            renderTable();
        }
    } catch (error) {
        console.error('Error loading assignments:', error);
        document.getElementById('assignments-tbody').innerHTML = '<tr><td colspan="4">Error loading data.</td></tr>';
    }
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// تهيئة الصفحة
document.addEventListener('DOMContentLoaded', async () => {
    await loadAndInitialize();
    
    const form = document.getElementById('assignment-form');
    if (form) {
        form.addEventListener('submit', handleAddAssignment);
    }
    
    const table = document.getElementById('assignments-table');
    if (table) {
        table.addEventListener('click', handleTableClick);
    }
});