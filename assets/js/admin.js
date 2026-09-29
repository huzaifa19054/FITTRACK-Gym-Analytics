// FitTrack: File overview
// This file controls page interactions.

(() => {
// Store the element or value needed by this code.
    const side = document.querySelector('.sidebar');
    if (side && !document.querySelector('.mobile-menu')) {
// Store the element or value needed by this code.
        const m = document.createElement('button'); m.className='mobile-menu'; m.type='button'; m.textContent='☰'; m.setAttribute('aria-label','Open menu');
        document.body.appendChild(m); m.addEventListener('click',()=>side.classList.toggle('mobile-open'));
    }
    const key = 'fittrack-theme';
// Store the element or value needed by this code.
    const saved = localStorage.getItem(key) || 'dark';
    document.documentElement.dataset.theme = saved;

    // Keep Contact Messages available from every admin module.
    if (location.pathname.includes('/admin/') && !document.querySelector('.nav[href="contact-messages.php"]')) {
// Store the element or value needed by this code.
        const accountLabel = [...document.querySelectorAll('.nav-label')].find(el => el.textContent.trim() === 'ACCOUNT');
        if (accountLabel) {
// Store the element or value needed by this code.
            const link = document.createElement('a');
            link.className = 'nav';
            link.href = 'contact-messages.php';
            link.innerHTML = '<span class="ico">✉</span>Contact Messages';
            accountLabel.parentNode.insertBefore(link, accountLabel);
        }
    }

// Store the element or value needed by this code.
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'theme-toggle';
    btn.setAttribute('aria-label', 'Toggle theme');
    document.body.appendChild(btn);

// Store the element or value needed by this code.
    const update = () => {
// Store the element or value needed by this code.
        const light = document.documentElement.dataset.theme === 'light';
        btn.innerHTML = light ? '☾ <span>Dark</span>' : '☀ <span>Light</span>';
        btn.title = light ? 'Switch to dark mode' : 'Switch to light mode';
    };
    update();
    btn.addEventListener('click', () => {
// Store the element or value needed by this code.
        const next = document.documentElement.dataset.theme === 'light' ? 'dark' : 'light';
        document.documentElement.dataset.theme = next;
        localStorage.setItem(key, next);
        update();
    });
})();
