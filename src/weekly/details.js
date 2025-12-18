/*
  Requirement: Populate the weekly detail page and discussion forum.
*/

// --- Element Selections ---
const weekTitle = document.querySelector('header h1');
const weekContentArticle = document.getElementById('week-content');
const commentContainer = document.getElementById('comments-container');
const commentForm = document.getElementById('comment-form');
const newCommentInput = document.getElementById('new-comment'); // Matches HTML ID

// --- Global Vars ---
let currentWeekId = null;

// --- Functions ---

function getWeekIdFromURL() {
  const params = new URLSearchParams(window.location.search);
  return params.get('id');
}

function renderWeekDetails(week) {
  // Update Title
  if(weekTitle) weekTitle.textContent = week.title;

  // Rebuild Content Article
  if(weekContentArticle) {
    weekContentArticle.innerHTML = `
      <p><strong>Starts on:</strong> ${week.start_date}</p>
      <h2>Description & Notes</h2>
      <p>${week.description}</p>
      <h2>Exercises & Resources</h2>
      <ul>
        ${(week.links && week.links.length) 
           ? week.links.map(link => `<li><a href="${link}" target="_blank">${link}</a></li>`).join('')
           : '<li>No resources listed.</li>'}
      </ul>
    `;
  }
}

function createCommentArticle(comment) {
  const article = document.createElement('article');
  article.classList.add('comment');

  const p = document.createElement('p');
  p.textContent = comment.text;

  const footer = document.createElement('footer');
  footer.textContent = `Posted by: ${comment.author}`;

  article.appendChild(p);
  article.appendChild(footer);
  return article;
}

function renderComments(comments) {
  if (!commentContainer) return;
  commentContainer.innerHTML = ''; // Clear hardcoded comments

  if (!comments || comments.length === 0) {
    commentContainer.innerHTML = '<p>No comments yet. Be the first!</p>';
    return;
  }

  comments.forEach(c => {
    commentContainer.appendChild(createCommentArticle(c));
  });
}

async function handleAddComment(e) {
  e.preventDefault(); // Stop page reload

  const text = newCommentInput.value.trim();
  if (!text) return;

  const payload = {
    week_id: currentWeekId,
    author: 'Student User', // You can change this or get from session
    text: text
  };

  try {
    const response = await fetch('api/index.php?resource=comments', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });

    if (response.ok) {
      newCommentInput.value = ''; // Clear box
      loadComments(currentWeekId); // Reload list
    } else {
      alert("Failed to post comment.");
    }
  } catch (error) {
    console.error(error);
  }
}

async function loadComments(id) {
  try {
    const res = await fetch(`api/index.php?resource=comments&week_id=${id}`);
    if (res.ok) {
      const comments = await res.json();
      renderComments(comments);
    }
  } catch(e) { console.error(e); }
}

async function initializePage() {
  currentWeekId = getWeekIdFromURL();
  if (!currentWeekId) return; // Or handle error

  try {
    // Fetch Week Data
    const weekRes = await fetch(`api/index.php?resource=weeks&id=${currentWeekId}`);
    if (weekRes.ok) {
      const week = await weekRes.json();
      renderWeekDetails(week);
    }

    // Fetch Comments
    loadComments(currentWeekId);

    // Attach Form Listener
    if (commentForm) {
      commentForm.addEventListener('submit', handleAddComment);
    }

  } catch (error) {
    console.error("Init failed", error);
  }
}

// Start
initializePage();