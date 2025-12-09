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
    linkEl.href = `details.html?id=${resource.id}`;
    linkEl.setAttribute('role', 'button');
    article.appendChild(linkEl);

    return article;
}

async function loadResources() {
    try {
        const response = await fetch('api/index.php');
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

loadResources();
