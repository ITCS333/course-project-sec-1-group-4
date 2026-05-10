const API_URL = './api/index.php';
let currentAssignmentId = null;
let currentComments = [];

function getAssignmentIdFromURL() {
    const params = new URLSearchParams(window.location.search);
    return params.get('id');
}

function renderAssignmentDetails(assignment) {
    document.getElementById('assignment-title').textContent = assignment.title;
    document.getElementById('assignment-due-date').textContent = `Due: ${assignment.due_date}`;
    document.getElementById('assignment-description').textContent = assignment.description;
    
    const filesList = document.getElementById('assignment-files-list');
    filesList.innerHTML = '';
    
    if (assignment.files && assignment.files.length > 0) {
        assignment.files.forEach(file => {
            const li = document.createElement('li');
            const a = document.createElement('a');
            a.href = file;
            a.target = '_blank';
            a.textContent = file.split('/').pop() || 'Download File';
            li.appendChild(a);
            filesList.appendChild(li);
        });
    } else {
        filesList.innerHTML = '<li>No files attached to this assignment.</li>';
    }
}

function createCommentArticle(comment) {
    const article = document.createElement('article');
    article.className = 'comment';
    article.innerHTML = `
        <p>${escapeHtml(comment.text)}</p>
        <footer>— ${escapeHtml(comment.author)} • ${comment.created_at || new Date().toLocaleString()}</footer>
    `;
    return article;
}

function renderComments() {
    const container = document.getElementById('comment-list');
    container.innerHTML = '';
    
    if (!currentComments || currentComments.length === 0) {
        container.innerHTML = '<p style="color: #999; text-align: center;">No comments yet. Be the first to comment!</p>';
        return;
    }
    
    currentComments.forEach(comment => {
        container.appendChild(createCommentArticle(comment));
    });
}

async function handleAddComment(event) {
    event.preventDefault();
    
    const textarea = document.getElementById('new-comment');
    const text = textarea.value.trim();
    
    if (!text) return;
    
    try {
        const response = await fetch(`${API_URL}?action=comment`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                assignment_id: parseInt(currentAssignmentId),
                author: 'Student User',
                text: text
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            textarea.value = '';
            await loadComments();
        } else {
            alert('Failed to add comment: ' + (result.error || 'Unknown error'));
        }
    } catch (error) {
        console.error('Error adding comment:', error);
        alert('Failed to add comment. Please try again.');
    }
}

async function loadComments() {
    if (!currentAssignmentId) return;
    
    try {
        const response = await fetch(`${API_URL}?action=comments&assignment_id=${currentAssignmentId}`);
        const result = await response.json();
        
        if (result.success) {
            currentComments = result.data || [];
            renderComments();
        }
    } catch (error) {
        console.error('Error loading comments:', error);
        document.getElementById('comment-list').innerHTML = '<p style="color: red;">Error loading comments.</p>';
    }
}

async function loadAssignment() {
    const id = getAssignmentIdFromURL();
    if (!id) {
        document.getElementById('assignment-title').textContent = 'No assignment ID specified';
        return;
    }
    
    currentAssignmentId = id;
    
    try {
        const response = await fetch(`${API_URL}?id=${id}`);
        const result = await response.json();
        
        if (result.success && result.data) {
            renderAssignmentDetails(result.data);
        } else {
            document.getElementById('assignment-title').textContent = 'Assignment not found';
            document.getElementById('assignment-description').textContent = 'The requested assignment could not be found.';
        }
    } catch (error) {
        console.error('Error loading assignment:', error);
        document.getElementById('assignment-title').textContent = 'Error loading assignment';
        document.getElementById('assignment-description').textContent = 'There was an error loading this assignment. Please try again.';
    }
}

async function initializePage() {
    await loadAssignment();
    await loadComments();
    
    const form = document.getElementById('comment-form');
    if (form) {
        form.addEventListener('submit', handleAddComment);
    }
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

document.addEventListener('DOMContentLoaded', initializePage);