// --- Element Selections ---
const listSection = document.getElementById("assignment-list-section")

// --- Functions ---
function createAssignmentArticle(assignment) {
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

async function loadAssignments() {
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
