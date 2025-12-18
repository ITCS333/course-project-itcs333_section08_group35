const listSection = document.querySelector('#resource-list-section');

function createResourceArticle(resource) {
    const article = document.createElement('article');

    const titleEl = document.createElement('h3');
    titleEl.textContent = resource.title;
    article.appendChild(titleEl);

    const descEl = document.createElement('p');
    descEl.textContent = resource.description;
    article.appendChild(descEl);

    const linkEl = document.createElement('a');
    linkEl.textContent = 'View Details';
    // FIXED: Used backticks (`) for template literal to make the ID dynamic
    linkEl.href = `details.html?id=${resource.id}`;
    linkEl.setAttribute('role', 'button');
    article.appendChild(linkEl);

    return article;
}

async function loadResources() {
    // Safety check: ensure the element exists before running logic
    if (!listSection) {
        console.error('Error: #resource-list-section not found in the DOM.');
        return;
    }

    try {
        const response = await fetch('api/index.php');

        // FIXED: Check if the server actually returned a valid page (status 200-299)
        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
        }

        const json = await response.json();

        if (!json.success) {
            throw new Error(json.message || 'Unable to retrieve materials');
        }

        const resources = json.data || [];

        listSection.innerHTML = '';

        if (resources.length === 0) {
            listSection.innerHTML = '<p>No materials found.</p>';
            return;
        }

        resources.forEach(resource => {
            const article = createResourceArticle(resource);
            listSection.appendChild(article);
        });
    } catch (error) {
        console.error('Error loading resources:', error);
        listSection.innerHTML = '<p>Cannot display materials at this time.</p>';
    }
}

// Run the function
loadResources();