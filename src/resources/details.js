let currentResourceId = null;
let currentComments = [];


const resourceTitle = document.querySelector('#resource-title');
const resourceDescription = document.querySelector('#resource-description');
const resourceLink = document.querySelector('#resource-link');
const commentList = document.querySelector('#comment-list');
const commentForm = document.querySelector('#comment-form');
const newComment = document.querySelector('#new-comment');

function getResourceIdFromURL() {
    const params = new URLSearchParams(window.location.search);
    return params.get('id');
}

function renderResourceDetails(resource) {
    resourceTitle.textContent = resource.title;
    resourceDescription.textContent = resource.description;
    resourceLink.href = resource.link;
}

function createCommentArticle(comment) {
    const article = document.createElement('article');

    const p = document.createElement('p');
    p.textContent = comment.text;

    const footer = document.createElement('footer');
    footer.innerHTML = `<small>By <strong>${comment.author}</strong> - ${new Date(comment.created_at).toLocaleDateString()}</small>`;

    article.appendChild(p);
    article.appendChild(footer);
    return article;
}

function renderComments() {
    commentList.innerHTML = '';
    currentComments.forEach(comment => {
        const article = createCommentArticle(comment);
        commentList.appendChild(article);
    });
}

async function handleAddComment(event) {
    event.preventDefault();
    const commentText = newComment.value.trim();
    if (!commentText) return;

    try {
        const response = await fetch('api/index.php?action=comment', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                resource_id: currentResourceId,
                author: 'Student',
                text: commentText
            })
        });

        const json = await response.json();

        if (!json.success) {
            alert('Cannot post comment: ' + (json.message || 'Operation failed'));
            return;
        }

        await loadComments();
        newComment.value = '';
        alert('Comment added!');
    } catch (error) {
        console.error('Error posting comment:', error);
        alert('Unable to submit comment. Try again.');
    }
}

async function loadComments() {
    try {
        const response = await fetch(`api/index.php?action=comments&resource_id=${currentResourceId}`);
        const json = await response.json();

        if (!json.success) {
            throw new Error(json.message || 'Cannot retrieve comments');
        }

        currentComments = json.data || [];
        renderComments();
    } catch (error) {
        console.error('Error loading comments:', error);
        commentList.innerHTML = '<p>Comments unavailable.</p>';
    }
}

async function initializePage() {
    currentResourceId = getResourceIdFromURL();

    if (!currentResourceId) {
        resourceTitle.textContent = "Material not available.";
        return;
    }

    try {
        const response = await fetch(`api/index.php?id=${currentResourceId}`);
        const json = await response.json();

        if (!json.success) {
            resourceTitle.textContent = "Material not found.";
            return;
        }

        const resource = json.data;
        renderResourceDetails(resource);

        await loadComments();
        commentForm.addEventListener('submit', handleAddComment);
    } catch (error) {
        console.error('Error initializing page:', error);
        resourceTitle.textContent = "Cannot load material.";
    }
}

initializePage();
