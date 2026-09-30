/**
 * Front-end behaviour for the Mosaic Gallery block: opens a collection in an
 * accessible modal, fetching its content from the plugin's REST endpoint.
 */

const cache = new Map();
const inFlight = new Map();

let modal = null;
let dialog = null;
let body = null;
let closeButton = null;
let lastTrigger = null;
let currentId = null;

const FOCUSABLE =
    'a[href], button:not([disabled]), input, select, textarea, [tabindex]:not([tabindex="-1"])';

function ensureModal() {
    if (modal) {
        return;
    }

    modal = document.createElement('div');
    modal.id = 'collection-modal';

    dialog = document.createElement('div');
    dialog.id = 'modal-content-container';
    dialog.className = 'modal-inner';
    dialog.setAttribute('role', 'dialog');
    dialog.setAttribute('aria-modal', 'true');
    dialog.setAttribute('aria-labelledby', 'modal-title');

    closeButton = document.createElement('button');
    closeButton.type = 'button';
    closeButton.className = 'close-modal';
    closeButton.setAttribute('aria-label', 'Close');
    closeButton.textContent = '×';

    body = document.createElement('div');
    body.className = 'modal-body';

    dialog.append(closeButton, body);
    modal.append(dialog);
    document.body.append(modal);

    closeButton.addEventListener('click', closeModal);

    // Close on backdrop click, but not on clicks inside the dialog.
    modal.addEventListener('click', (event) => {
        if (event.target === modal) {
            closeModal();
        }
    });

    modal.addEventListener('keydown', onKeydown);
}

function onKeydown(event) {
    if (event.key === 'Escape') {
        event.preventDefault();
        closeModal();
        return;
    }

    if (event.key !== 'Tab') {
        return;
    }

    // Keep focus inside the dialog.
    const focusable = Array.from(dialog.querySelectorAll(FOCUSABLE));
    if (!focusable.length) {
        return;
    }
    const first = focusable[0];
    const last = focusable[focusable.length - 1];

    if (event.shiftKey && document.activeElement === first) {
        event.preventDefault();
        last.focus();
    } else if (!event.shiftKey && document.activeElement === last) {
        event.preventDefault();
        first.focus();
    }
}

function setLoading() {
    body.setAttribute('aria-busy', 'true');
    body.innerHTML = '<p class="modal-status">Loading…</p>';
}

function setContent(html) {
    body.removeAttribute('aria-busy');
    body.innerHTML = html;
}

function setError(postId, endpoint) {
    body.removeAttribute('aria-busy');
    body.innerHTML =
        '<div class="modal-status modal-error"><p>Sorry, this item could not be loaded.</p>' +
        '<button type="button" class="modal-retry">Try again</button></div>';
    body.querySelector('.modal-retry').addEventListener('click', () => {
        loadInto(postId, endpoint);
    });
}

function fetchCollection(postId, endpoint) {
    if (cache.has(postId)) {
        return Promise.resolve(cache.get(postId));
    }
    if (inFlight.has(postId)) {
        return inFlight.get(postId);
    }

    const request = fetch(endpoint + encodeURIComponent(postId), {
        headers: { Accept: 'application/json' },
    })
        .then((response) => {
            if (!response.ok) {
                throw new Error(`Request failed with status ${response.status}`);
            }
            return response.json();
        })
        .then((data) => {
            cache.set(postId, data.html);
            return data.html;
        })
        .finally(() => {
            inFlight.delete(postId);
        });

    inFlight.set(postId, request);
    return request;
}

function loadInto(postId, endpoint) {
    currentId = postId;

    if (cache.has(postId)) {
        setContent(cache.get(postId));
        return;
    }

    setLoading();

    fetchCollection(postId, endpoint)
        .then((html) => {
            // Ignore stale responses if the modal was closed or switched meanwhile.
            if (currentId === postId) {
                setContent(html);
            }
        })
        .catch((error) => {
            // eslint-disable-next-line no-console
            console.error('Failed to load collection:', error);
            if (currentId === postId) {
                setError(postId, endpoint);
            }
        });
}

function openModal(postId, endpoint, trigger) {
    ensureModal();

    lastTrigger = trigger;
    loadInto(postId, endpoint);

    modal.classList.add('is-open');
    document.documentElement.classList.add('roduza-modal-open');
    closeButton.focus();
}

function closeModal() {
    if (!modal || !modal.classList.contains('is-open')) {
        return;
    }

    currentId = null;
    modal.classList.remove('is-open');
    document.documentElement.classList.remove('roduza-modal-open');
    body.innerHTML = '';

    if (lastTrigger && document.contains(lastTrigger)) {
        lastTrigger.focus();
    }
    lastTrigger = null;
}

document.addEventListener('click', (event) => {
    const trigger = event.target.closest('.open-collection-modal');
    if (!trigger) {
        return;
    }

    const gallery = trigger.closest('[data-endpoint]');
    const postId = trigger.dataset.postid;
    if (!gallery || !postId) {
        return;
    }

    event.preventDefault();
    openModal(postId, gallery.dataset.endpoint, trigger);
});
