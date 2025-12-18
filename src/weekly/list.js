/*
  Requirement: Populate the "Weekly Course Breakdown" list page.
*/

// --- Element Selections ---
// Select the section for the week list.
const listSection = document.querySelector('#week-list');

// --- Functions ---

/**
 * Creates the HTML article for a specific week.
 * @param {Object} week - The week object {id, title, startDate, description}.
 * @returns {HTMLElement} - The <article> element ready to be appended.
 */
function createWeekArticle(week) {
  // 1. Create the article container
  const article = document.createElement('article');
  // Optional: Add a class for styling if you have CSS (e.g., from the HTML template)
  // article.classList.add('week-card'); 

  // 2. Create the Title (h2)
  const title = document.createElement('h2');
  title.textContent = week.title;
  article.appendChild(title);

  // 3. Create the Start Date paragraph (p)
  const dateP = document.createElement('p');
  // Note: We check for 'startDate' (from comments) or 'date' (from admin.js consistency)
  dateP.textContent = `Starts on: ${week.startDate || week.date}`;
  article.appendChild(dateP);

  // 4. Create the Description paragraph (p)
  const descP = document.createElement('p');
  descP.textContent = week.description;
  article.appendChild(descP);

  // 5. Create the Link (a)
  const link = document.createElement('a');
  link.textContent = "View Details & Discussion";
  // CRITICAL: Set the href to include the ID parameter
  link.href = `details.html?id=${week.id}`;
  article.appendChild(link);

  return article;
}

/**
 * Fetches the weekly data and populates the list section.
 */
async function loadWeeks() {
  try {
    // 1. Fetch data from 'weeks.json'
    const response = await fetch('../api/weeks.json')

    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }

    // 2. Parse the JSON response
    const weeks = await response.json();

    // 3. Clear any existing content
    listSection.innerHTML = '';

    // Check if there is data
    if (weeks.length === 0) {
      listSection.innerHTML = '<p>No course content available yet.</p>';
      return;
    }

    // 4. Loop through the weeks array and append articles
    weeks.forEach(week => {
      const articleElement = createWeekArticle(week);
      listSection.appendChild(articleElement);
    });

  } catch (error) {
    console.error("Failed to load weeks:", error);
    listSection.innerHTML = '<p>Error loading course schedule. Please try again later.</p>';
  }
}

// --- Initial Page Load ---
loadWeeks();