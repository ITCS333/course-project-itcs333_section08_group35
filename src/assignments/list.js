// --- Element Selections ---
const listSection = document.getElementById("assignment-list-section");

// --- Functions ---
function createAssignmentArticle(assignment) {
  const article = document.createElement("article");
  article.className = "assignment-details";
  
  // FIX: Database uses 'due_date' (snake_case), not 'dueDate'
  article.innerHTML = `
    <h2>${assignment.title}</h2>
    <p>Due: ${assignment.due_date}</p>
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
    // FIX: Fetch from the PHP API instead of the static JSON file
    const response = await fetch('api/index.php?resource=assignments');
    
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }
    
    const result = await response.json();

    // FIX: Ensure we have an array (API might return {data: [...]})
    const assignments = Array.isArray(result) ? result : (result.data || []);
    
    listSection.innerHTML = ""; 

    if (assignments.length === 0) {
        listSection.innerHTML = "<p>No assignments found.</p>";
        return;
    }

    assignments.forEach(assignment => {
      const assignmentArticle = createAssignmentArticle(assignment);
      listSection.appendChild(assignmentArticle);
    });

  } catch (error) {
    console.error("Failed to load assignments:", error);
    listSection.innerHTML = "<p>Error loading assignments. Please check the console for details.</p>";
  }
}

// --- Initial Page Load ---
// Call the function to populate the page.
loadAssignments();