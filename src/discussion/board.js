let topics = [];

const newTopicForm = document.getElementById('new-topic-form');


const topicListContainer = document.getElementById('topic-list-container');

// --- Functions ---


function createTopicArticle(topic) {
    const article = document.createElement('article');
    
    article.className= "list-group-item list-group-item-action p-4 mb-2 shadow-sm border-0";
    article.innerHTML = 
       ` <h3 class="h5 text-primary"> <a href="topic.html?id=${topic.id}" class="text-decoration-none">${topic.subject}</a></h3>
        <footer class="text-muted small">Posted by: ${topic.author} on ${topic.created_at}</footer>
        <div class="mt-3">
            <button class="edit-btn btn btn-outline-info btn-sm me-2" data-id="${topic.id}">Edit</button>
            <button class="delete-btn btn btn-outline-danger btn-sm" data-id="${topic.id}">Delete</button>
        </div> `
    ;
    return article;
}


function renderTopics() {
    topicListContainer.innerHTML = "";

    topics.forEach(topic => {

        const article = createTopicArticle(topic);
        topicListContainer.appendChild(article);
    });
}


async function handleCreateTopic(event) {
    event.preventDefault();

    const subject = document.getElementById('topic-subject').value;
    const message = document.getElementById('topic-message').value;
    const submitBtn = document.getElementById('create-topic');
    const editId = submitBtn.getAttribute('data-edit-id');


    if (editId) {
        await handleUpdateTopic(parseInt(editId), { subject, message });

        submitBtn.textContent = "Create Topic";
        submitBtn.removeAttribute('data-edit-id');
        newTopicForm.reset();
        return;
    }


    try {
        const response = await fetch('./api/index.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ subject, message, author: "Student" })
        });
        const result = await response.json();

        if (result.success) {

            topics.push({
                id: result.id,
                subject: subject,
                message: message,
                author: "Student",
                created_at: new Date().toISOString().slice(0, 19).replace('T', ' ')
            });
            renderTopics();
            newTopicForm.reset();
        }
    } catch (error) {
        console.error("Error creating topic:", error);
    }
}


async function handleUpdateTopic(id, fields) {
    try {
        const response = await fetch('./api/index.php', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id, ...fields })
        });
        const result = await response.json();

        if (result.success) {

            const index = topics.findIndex(t => t.id === id);
            if (index !== -1) {
                topics[index].subject = fields.subject;
                topics[index].message = fields.message;
            }
            renderTopics();
        }
    } catch (error) {
        console.error("Error updating topic:", error);
    }
}


async function handleTopicListClick(event) {
    const target = event.target;
    const id = target.dataset.id;

    if (!id) return;


    if (target.classList.contains('delete-btn')) {
        if (!confirm("Are you sure you want to delete this topic?")) return;

        try {
            const response = await fetch(`./api/index.php?id=${id}`, { method: 'DELETE' });
            const result = await response.json();

            if (result.success) {
                topics = topics.filter(t => t.id != id);
                renderTopics();
            }
        } catch (error) {
            console.error("Error deleting topic:", error);
        }
    }


    if (target.classList.contains('edit-btn')) {
        const topic = topics.find(t => t.id == id);
        if (topic) {
            document.getElementById('topic-subject').value = topic.subject;
            document.getElementById('topic-message').value = topic.message;
            
            const submitBtn = document.getElementById('create-topic');
            submitBtn.textContent = "Update Topic";
            submitBtn.setAttribute('data-edit-id', id);
            

            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    }
}


async function loadAndInitialize() {
    try {

        const response = await fetch('./api/index.php');
        const result = await response.json();

        if (result.success) {
            topics = result.data;
            renderTopics();
        }
        newTopicForm.addEventListener('submit', handleCreateTopic);
        topicListContainer.addEventListener('click', handleTopicListClick);

    } catch (error) {
        console.error("Error initializing board:", error);
    }
}

loadAndInitialize();