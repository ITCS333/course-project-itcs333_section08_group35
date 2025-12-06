/*
  Requirement: Populate the "Course Assignments" list page.

  Instructions:
  1. Link this file to `list.html` using:
     <script src="list.js" defer></script>

  2. In `list.html`, add an `id="assignment-list-section"` to the
     <section> element that will contain the assignment articles.

  3. Implement the TODOs below.
*/

// --- Element Selections ---
// TODO: Select the section for the assignment list ('#assignment-list-section').
const listSection = document.getElementById("assignment-list-section")

// --- Functions ---

/**
 * TODO: Implement the createAssignmentArticle function.
 * It takes one assignment object {id, title, dueDate, description}.
 * It should return an <article> element matching the structure in `list.html`.
 * The "View Details" link's `href` MUST be set to `details.html?id=${id}`.
 * This is how the detail page will know which assignment to load.
 */
function createAssignmentArticle(assignment) {
  // ... your implementation here ...
  const article = document.createElement("article");
  article.className = "assignment-details";
  article.innerHTML = `
    <h2>${assignment.title}</h2>
    <p>Due: ${assignment.dueDate}</p>
    <p>${assignment.description}</p>
    <a href="details.html?id=${assignment.id}">View Details</a>
  `;
  return article;
}

/**
 * TODO: Implement the loadAssignments function.
 * This function needs to be 'async'.
 * It should:
 * 1. Use `fetch()` to get data from 'assignments.json'.
 * 2. Parse the JSON response into an array.
 * 3. Clear any existing content from `listSection`.
 * 4. Loop through the assignments array. For each assignment:
 * - Call `createAssignmentArticle()`.
 * - Append the returned <article> element to `listSection`.
 */
async function loadAssignments() {
  // ... your implementation here ...
  if (!listSection) {
    console.error("Assignment list section not found !");
  return;
  }
  try {
    const response = await fetch('assignments.json');
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }
    
    const assignments = await response.json();
    listSection.innerHTML = ""; 
    assignments.forEach(assignment => {
      const assignmentArticle = createAssignmentArticle(assignment);
      listSection.appendChild(assignmentArticle);
    });
  } catch (error) {
    console.error("Failed to load assignments:", error);
    listSection.innerHTML = "<p>Error loading assignments. Please try again later.</p>";
  }


}

// --- Initial Page Load ---
// Call the function to populate the page.
loadAssignments();
