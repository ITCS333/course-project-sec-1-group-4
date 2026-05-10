const API_URL = './api/index.php';

function createAssignmentArticle(assignment) {
    const article = document.createElement('article');
    article.innerHTML = `
        <h3>${escapeHtml(assignment.title)}</h3>
        <div class="due-date">📅 Due: ${assignment.due_date}</div>
        <div class="description">${escapeHtml(assignment.description.substring(0, 150))}${assignment.description.length > 150 ? '...' : ''}</div>
        <a href="details.html?id=${assignment.id}">View Assignment →</a>
    `;
    return article;
}

async function loadAssignments() {
    const container = document.getElementById('assignment-list-section');
    container.innerHTML = '<div class="loading">Loading assignments...</div>';
    
    try {
        const response = await fetch(API_URL);
        const result = await response.json();
        
        if (result.success && result.data && result.data.length > 0) {
            container.innerHTML = '';
            result.data.forEach(assignment => {
                container.appendChild(createAssignmentArticle(assignment));
            });
        } else {
            container.innerHTML = '<div class="loading">No assignments found.</div>';
        }
    } catch (error) {
        console.error('Error:', error);
        container.innerHTML = '<div class="loading">Error loading assignments. Please try again.</div>';
    }
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

document.addEventListener('DOMContentLoaded', loadAssignments);