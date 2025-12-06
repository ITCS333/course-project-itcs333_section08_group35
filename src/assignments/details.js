// --- Global Data Store ---
let currentAssignmentId = null;
let currentComments = [];

// --- Element Selections ---
const assignmentTitle = document.getElementById("assignment-title");
const assignmentDueDate = document.getElementById("assignment-due-date");
const assignmentDescription = document.getElementById("assignment-description");
const assignmentFilesList = document.getElementById("assignment-files-list");
const commentList = document.getElementById("comment-list");
const commentForm = document.getElementById("comment-form");
const newCommentText = document.getElementById("new-comment-text");

// --- Functions ---

function getAssignmentIdFromURL() {
  const queryString = window.location.search;
  const urlParams = new URLSearchParams(queryString);
  return urlParams.get("id");
}

function renderAssignmentDetails(assignment) {
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

function createCommentArticle(comment) {
  const article = document.createElement("article");
  article.className = "comment";
  article.innerHTML = `
    <p>${comment.text}</p>
    <footer>Posted by: ${comment.author}</footer>
  `;
  return article;
}

function renderComments() {
  if (!commentList) return;
  commentList.innerHTML = "";

  currentComments.forEach(comment => {
    const commentArticle = createCommentArticle(comment);
    commentList.appendChild(commentArticle);
  });
}

function handleAddComment(event) {
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


async function initializePage() {
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
