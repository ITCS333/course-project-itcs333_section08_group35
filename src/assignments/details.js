/*
  Requirement: Populate the assignment detail page and discussion forum.

  Instructions:
  1. Link this file to `details.html` using:
     <script src="details.js" defer></script>

  2. In `details.html`, add the following IDs:
     - To the <h1>: `id="assignment-title"`
     - To the "Due" <p>: `id="assignment-due-date"`
     - To the "Description" <p>: `id="assignment-description"`
     - To the "Attached Files" <ul>: `id="assignment-files-list"`
     - To the <div> for comments: `id="comment-list"`
     - To the "Add a Comment" <form>: `id="comment-form"`
     - To the <textarea>: `id="new-comment-text"`

  3. Implement the TODOs below.
*/

// --- Global Data Store ---
// These will hold the data related to *this* assignment.
let currentAssignmentId = null;
let currentComments = [];

// --- Element Selections ---
// TODO: Select all the elements you added IDs for in step 2.

const assignmentTitle = document.getElementById("assignment-title");
const assignmentDueDate = document.getElementById("assignment-due-date");
const assignmentDescription = document.getElementById("assignment-description");
const assignmentFilesList = document.getElementById("assignment-files-list");
const commentList = document.getElementById("comment-list");
const commentForm = document.getElementById("comment-form");
const newCommentText = document.getElementById("new-comment-text");

// --- Functions ---


/**
 * TODO: Implement the getAssignmentIdFromURL function.
 * It should:
 * 1. Get the query string from `window.location.search`.
 * 2. Use the `URLSearchParams` object to get the value of the 'id' parameter.
 * 3. Return the id.
 */
function getAssignmentIdFromURL() {
  const queryString = window.location.search;
  const urlParams = new URLSearchParams(queryString);
  return urlParams.get("id");
}

/**
 * TODO: Implement the renderAssignmentDetails function.
 * It takes one assignment object.
 * It should:
 * 1. Set the `textContent` of `assignmentTitle` to the assignment's title.
 * 2. Set the `textContent` of `assignmentDueDate` to "Due: " + assignment's dueDate.
 * 3. Set the `textContent` of `assignmentDescription`.
 * 4. Clear `assignmentFilesList` and then create and append
 * `<li><a href="#">...</a></li>` for each file in the assignment's 'files' array.
 */
function renderAssignmentDetails(assignment) {
  // ... your implementation here ...
    if (assignmentTitle) assignmentTitle.textContent = assignment.title;
    if (assignmentDueDate) assignmentDueDate.textContent = "Due: " + assignment.dueDate;
    if (assignmentDescription) assignmentDescription.textContent = assignment.description;

    if (assignmentFilesList) {
        assignmentFilesList.innerHTML = "";
        const files = assignment.files || [];
        
        files.forEach(file => {
            const li = document.createElement("li");
            const a = document.createElement("a");
            a.href = "#";
            a.textContent = fileName;
            a.setAttribute("Download", "");
            li.appendChild(a);
            assignmentFilesList.appendChild(li);
        });
    }
}

/**
 * TODO: Implement the createCommentArticle function.
 * It takes one comment object {author, text}.
 * It should return an <article> element matching the structure in `details.html`.
 */
function createCommentArticle(comment) {
  // ... your implementation here ...
  const article = document.createElement("article");
  article.className = "comment";
  article.innerHTML = `
    <p>${comment.text}</p>
    <footer>Posted by: ${comment.author}</footer>
  `;
  return article;
}

/**
 * TODO: Implement the renderComments function.
 * It should:
 * 1. Clear the `commentList`.
 * 2. Loop through the global `currentComments` array.
 * 3. For each comment, call `createCommentArticle()`, and
 * append the resulting <article> to `commentList`.
 */
function renderComments() {
  // ... your implementation here ...
  if (!commentList) return;
  commentList.innerHTML = "";

  currentComments.forEach(comment => {
    const commentArticle = createCommentArticle(comment);
    commentList.appendChild(commentArticle);
  });
}

/**
 * TODO: Implement the handleAddComment function.
 * This is the event handler for the `commentForm` 'submit' event.
 * It should:
 * 1. Prevent the form's default submission.
 * 2. Get the text from `newCommentText.value`.
 * 3. If the text is empty, return.
 * 4. Create a new comment object: { author: 'Student', text: commentText }
 * (For this exercise, 'Student' is a fine hardcoded author).
 * 5. Add the new comment to the global `currentComments` array (in-memory only).
 * 6. Call `renderComments()` to refresh the list.
 * 7. Clear the `newCommentText` textarea.
 */
function handleAddComment(event) {
  // ... your implementation here ...
  event.preventDefault();
  const commentText = newCommentText.value.trim();
  if (commentText === "") return;

  const newComment = {
    author: 'Student',
    text: commentText
  };
  currentComments.push(newComment);
  renderComments();
  newCommentText.value = "";
}

/**
 * TODO: Implement an `initializePage` function.
 * This function needs to be 'async'.
 * It should:
 * 1. Get the `currentAssignmentId` by calling `getAssignmentIdFromURL()`.
 * 2. If no ID is found, display an error and stop.
 * 3. `fetch` both 'assignments.json' and 'comments.json' (you can use `Promise.all`).
 * 4. Find the correct assignment from the assignments array using the `currentAssignmentId`.
 * 5. Get the correct comments array from the comments object using the `currentAssignmentId`.
 * Store this in the global `currentComments` variable.
 * 6. If the assignment is found:
 * - Call `renderAssignmentDetails()` with the assignment object.
 * - Call `renderComments()` to show the initial comments.
 * - Add the 'submit' event listener to `commentForm` (calls `handleAddComment`).
 * 7. If the assignment is not found, display an error.
 */
async function initializePage() {
  // ... your implementation here ...
  currentAssignmentId = getAssignmentIdFromURL();
  if (!currentAssignmentId) {
    document.body.innerHTML = "<h2>Error: No assignment ID provided in URL.</h2>";
    return;
  }
  try{
    const [assignmentsResponse, commentsResponse] = await Promise.all([
      fetch('assignments.json'),
      fetch('comments.json')
    ]);
    if(!assignmentsResponse.ok || !commentsResponse.ok){
      throw new Error("Failed to fetch data.");
    }
    const assignmentsData = await assignmentsResponse.json();
    const commentsData = await commentsResponse.json();
    
    const assignment = assignmentsData.find(a => a.id === currentAssignmentId);
    if(commentsData[currentAssignmentId]){
      currentComments = commentsData[currentAssignmentId];
    }
    else {
     currentComments = [];
    }
    if(assignment){
      renderAssignmentDetails(assignment);
      renderComments();
    }
    if(commentForm){
      commentForm.addEventListener("submit", handleAddComment);
    }
    else {
      document.body.innerHTML = "<h2>Error: Assignment not found !</h2>";
    }
  } catch (error) {
    document.body.innerHTML = `<h2>Error: ${error.message}</h2>`;
    console.error("Initialization error:", error);
  }

    

  }


// --- Initial Page Load ---
initializePage();
