// --- Global Data Store ---
let currentAssignmentId = null;
let currentComments = [];

// --- Element Selections (Matching your HTML IDs) ---
const assignmentTitle = document.getElementById("assignment-title");
const assignmentDueDate = document.getElementById("assignment-due-date");
const assignmentDescription = document.getElementById("assignment-description");
const assignmentFilesList = document.getElementById("assignment-files-list");
const commentList = document.getElementById("comment-list");
const commentForm = document.getElementById("comment-form");
const newCommentText = document.getElementById("new-comment-text");

// --- Functions ---

/**
 * Extract 'id' parameter from URL (e.g., details.html?id=5)
 */
function getAssignmentIdFromURL() {
  const queryString = window.location.search;
  const urlParams = new URLSearchParams(queryString);
  return urlParams.get("id");
}

/**
 * Render the Assignment Data into the HTML elements
 */
function renderAssignmentDetails(assignment) {
    if (assignmentTitle) assignmentTitle.textContent = assignment.title;
    
    // Note: API uses snake_case 'due_date'
    if (assignmentDueDate) assignmentDueDate.textContent = "Due: " + assignment.due_date;
    
    if (assignmentDescription) assignmentDescription.textContent = assignment.description;

    if (assignmentFilesList) {
        // Clear the dummy HTML example <li>
        assignmentFilesList.innerHTML = "";
        
        // Ensure files is an array. API returns JSON parsed array or null.
        const files = Array.isArray(assignment.files) ? assignment.files : [];
        
        if (files.length === 0) {
            assignmentFilesList.innerHTML = "<li>No files attached.</li>";
        } else {
            files.forEach(fileName => {
                const li = document.createElement("li");
                const a = document.createElement("a");
                a.href = "#"; // In a real app, this would be a path like 'uploads/' + fileName
                a.textContent = fileName;
                a.setAttribute("download", ""); // Attribute to trigger download
                li.appendChild(a);
                assignmentFilesList.appendChild(li);
            });
        }
    }
}

/**
 * Create HTML structure for a single comment
 * Matches: <article class="comment"><p>...</p><footer>...</footer></article>
 */
function createCommentArticle(comment) {
  const article = document.createElement("article");
  article.className = "comment";
  
  // Format date if available
  const dateStr = comment.created_at ? new Date(comment.created_at).toLocaleString() : 'Just now';
  
  article.innerHTML = `
    <p>${comment.text}</p>
    <footer>
        Posted by: ${comment.author} 
        <br><small style="color:#666;">${dateStr}</small>
    </footer>
  `;
  return article;
}

/**
 * Render the list of comments
 */
function renderComments() {
  if (!commentList) return;
  
  // Clear any existing content (including the dummy comments in HTML)
  commentList.innerHTML = "";

  if (currentComments.length === 0) {
      commentList.innerHTML = "<p>No comments yet. Be the first to post!</p>";
      return;
  }

  currentComments.forEach(comment => {
    const commentArticle = createCommentArticle(comment);
    commentList.appendChild(commentArticle);
  });
}

/**
 * Handle Form Submission (POST to API)
 */
async function handleAddComment(event) {
  event.preventDefault(); // Stop page reload
  
  const commentText = newCommentText.value.trim();
  if (commentText === "") return;

  // Prepare data for API
  // Note: Your HTML form doesn't have an "Author" input, so we hardcode "Student"
  const newCommentPayload = {
    assignment_id: currentAssignmentId,
    author: 'Student', 
    text: commentText
  };

  try {
      // Send POST request to PHP API
      const response = await fetch('api/index.php?resource=comments', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(newCommentPayload)
      });

      const result = await response.json();

      if (response.ok) {
          // Add the returned comment (with real DB ID) to local list
          currentComments.push(result.data);
          
          // Re-render list
          renderComments();
          
          // Clear input field
          newCommentText.value = "";
      } else {
          alert("Error: " + (result.message || "Failed to post comment"));
      }

  } catch (error) {
      console.error("Error submitting comment:", error);
      alert("Network error. Check console.");
  }
}

/**
 * Main Initialization Function
 */
async function initializePage() {
  currentAssignmentId = getAssignmentIdFromURL();
  
  if (!currentAssignmentId) {
    if(assignmentTitle) assignmentTitle.textContent = "Error: No Assignment Selected";
    if(assignmentDescription) assignmentDescription.textContent = "Please go back to the list and select an assignment.";
    // Hide form if no ID
    if(document.getElementById("discussion-forum")) {
        document.getElementById("discussion-forum").style.display = "none";
    }
    return;
  }

  try {
    // 1. Fetch Assignment Details from API
    const assignmentResp = await fetch(`api/index.php?resource=assignments&id=${currentAssignmentId}`);
    
    // 2. Fetch Comments for this assignment from API
    const commentsResp = await fetch(`api/index.php?resource=comments&assignment_id=${currentAssignmentId}`);

    if (!assignmentResp.ok) {
        throw new Error("Assignment not found in database.");
    }

    const assignmentData = await assignmentResp.json();
    
    // Check if comments exist (API might return error if none found, or empty array)
    if (commentsResp.ok) {
        currentComments = await commentsResp.json();
    } else {
        currentComments = [];
    }

    // Render Data to HTML
    renderAssignmentDetails(assignmentData);
    renderComments();

    // Attach Form Event Listener
    if (commentForm) {
      commentForm.addEventListener("submit", handleAddComment);
    }

  } catch (error) {
    document.body.innerHTML = `<h2 style="color:red; text-align:center; margin-top:20px;">Error Loading Data</h2><p style="text-align:center;">${error.message}</p>`;
    console.error("Initialization error:", error);
  }
}

// --- Start the App ---
initializePage();