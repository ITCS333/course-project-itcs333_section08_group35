/*
  Requirement: Populate the weekly detail page and discussion forum.
*/

// --- Global Data Store ---
let currentWeekId = null;
let currentComments = [];

// --- Element Selections ---
const weekTitle = document.getElementById('week-title');
const weekStartDate = document.getElementById('week-start-date');
const weekDescription = document.getElementById('week-description');
const weekLinksList = document.getElementById('week-links-list');
const commentList = document.getElementById('comment-list');
const commentForm = document.getElementById('comment-form');
const newCommentText = document.getElementById('new-comment-text');

// --- Functions ---

/**
 * Gets the 'id' parameter from the URL query string.
 * @returns {string|null} The week ID or null if not found.
 */
function getWeekIdFromURL() {
  const params = new URLSearchParams(window.location.search);
  return params.get('id');
}

/**
 * Populates the DOM with the specific week's details.
 * @param {Object} week - The week object.
 */
function renderWeekDetails(week) {
  // 1. Set Title
  weekTitle.textContent = week.title;
  
  // 2. Set Start Date
  // Note: Assuming the property is named 'date' based on the admin form logic
  weekStartDate.textContent = "Starts on: " + (week.startDate || week.date);
  
  // 3. Set Description
  weekDescription.textContent = week.description;

  // 4. Render Links
  weekLinksList.innerHTML = ''; // Clear existing dummy data
  
  if (week.links && week.links.length > 0) {
    week.links.forEach(linkUrl => {
      const li = document.createElement('li');
      const a = document.createElement('a');
      
      a.href = linkUrl;
      a.textContent = linkUrl;
      a.target = "_blank"; // Open in new tab
      
      li.appendChild(a);
      weekLinksList.appendChild(li);
    });
  } else {
    weekLinksList.innerHTML = '<li>No resources listed for this week.</li>';
  }
}

/**
 * Creates the HTML structure for a single comment.
 * @param {Object} comment - { author, text }
 * @returns {HTMLElement} The <article> element.
 */
function createCommentArticle(comment) {
  // Create <article>
  const article = document.createElement('article');
  article.classList.add('comment'); // Use class for styling

  // Create <p> for text
  const p = document.createElement('p');
  p.textContent = comment.text;

  // Create <footer> for author
  const footer = document.createElement('footer');
  footer.textContent = "Posted by: " + comment.author;

  // Append children
  article.appendChild(p);
  article.appendChild(footer);

  return article;
}

/**
 * Clears and re-renders the list of comments.
 */
function renderComments() {
  // 1. Clear container
  commentList.innerHTML = '';

  // 2. Loop and append
  if (currentComments.length === 0) {
    commentList.innerHTML = '<p>No comments yet. Be the first to ask a question!</p>';
    return;
  }

  currentComments.forEach(comment => {
    const commentNode = createCommentArticle(comment);
    commentList.appendChild(commentNode);
  });
}

/**
 * Handles the submission of the new comment form.
 */
function handleAddComment(event) {
  // 1. Prevent default reload
  event.preventDefault();

  // 2. Get text
  const text = newCommentText.value.trim();

  // 3. Validate
  if (!text) return;

  // 4. Create new comment object
  const newComment = { 
    author: 'Student', 
    text: text 
  };

  // 5. Add to global array
  currentComments.push(newComment);

  // 6. Refresh list
  renderComments();

  // 7. Clear input
  newCommentText.value = '';
}

/**
 * Main initialization function.
 * Fetches data and sets up the page based on URL ID.
 */
async function initializePage() {
  // 1. Get ID
  currentWeekId = getWeekIdFromURL();

  // 2. Check if ID exists
  if (!currentWeekId) {
    weekTitle.textContent = "Week not found (No ID provided).";
    // Optionally hide content sections here
    return;
  }

  try {
    // 3. Fetch data (fetching both files in parallel)
    const [weeksResponse, commentsResponse] = await Promise.all([
        fetch('api/weeks.json'),
        fetch('api/comments.json')
    ]);

    if (!weeksResponse.ok || !commentsResponse.ok) {
        throw new Error("Failed to load data files.");
    }

    // 4. Parse JSON
    const weeksData = await weeksResponse.json();
    const commentsData = await commentsResponse.json();

    // 5. Find the specific week
    // We convert IDs to strings to ensure loose matching (e.g., "1" vs 1)
    const week = weeksData.find(w => String(w.id) === String(currentWeekId));

    if (week) {
        // 6. Get comments for this week
        // Assuming structure: { "week_id": [ {author, text}, ... ] }
        currentComments = commentsData[currentWeekId] || [];

        // 7. Render Page
        renderWeekDetails(week);
        renderComments();

        // Add event listener only if week exists
        commentForm.addEventListener('submit', handleAddComment);
    } else {
        // 8. Handle Week Not Found
        weekTitle.textContent = "Week not found.";
        weekDescription.textContent = "The requested week ID does not exist in our records.";
        weekLinksList.innerHTML = "";
        commentForm.style.display = "none";
    }

  } catch (error) {
    console.error("Error initializing page:", error);
    weekTitle.textContent = "Error loading content.";
    weekDescription.textContent = "Please check your internet connection or try again later.";
  }
}

// --- Initial Page Load ---
initializePage();