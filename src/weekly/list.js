/*
  Requirement: Populate the "Weekly Course Breakdown" list page.
*/

// --- Element Selections ---
// Matches <section id="week-list"> in your HTML
const listSection = document.getElementById('week-list');

// --- Functions ---

function createWeekArticle(week) {
  const article = document.createElement('article');
  article.classList.add('week-card'); 

  const title = document.createElement('h2');
  title.textContent = week.title;
  article.appendChild(title);

  const dateP = document.createElement('p');
  // API returns 'start_date'
  dateP.textContent = `Starts on: ${week.start_date}`;
  article.appendChild(dateP);

  const descP = document.createElement('p');
  descP.textContent = week.description;
  article.appendChild(descP);

  const link = document.createElement('a');
  link.textContent = "View Details & Discussion";
  // Links to details.html with the Database ID
  link.href = `details.html?id=${week.id}`;
  article.appendChild(link);

  return article;
}

async function loadWeeks() {
  if (!listSection) return;

  try {
    // Fetch from API
    const response = await fetch('api/index.php?resource=weeks');

    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }

    const weeks = await response.json();

    // CLEAR HARDCODED HTML HERE
    listSection.innerHTML = '';

    if (weeks.length === 0) {
      listSection.innerHTML = '<p>No course content available yet.</p>';
      return;
    }

    weeks.forEach(week => {
      const articleElement = createWeekArticle(week);
      listSection.appendChild(articleElement);
    });

  } catch (error) {
    console.error("Failed to load weeks:", error);
    // Keep hardcoded data visible if API fails, or show error:
    listSection.innerHTML = '<p style="color:red">Error loading data. Is index.php running?</p>';
  }
}

// --- Initial Page Load ---
loadWeeks();