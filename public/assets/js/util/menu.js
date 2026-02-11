export const setHereClassForMenu = (menuContainerSelector, menuSelector) => {
  const menuContainer = document.querySelector(menuContainerSelector);
  if (!menuContainer) return;

  // Precompute current path once
  const currentPath = (() => {
    try {
      // Use location.pathname directly (fast + already normalized by browser)
      const p = window.location.pathname || '/';
      return p.replace(/\/+$/, '') || '/';
    } catch {
      return '/';
    }
  })();

  // Normalize href -> pathname (handles relative + absolute). Cheap in hot path.
  const normalizePath = (href) => {
    if (!href || href === '#') return null;
    try {
      const p = new URL(href, window.location.origin).pathname || '/';
      return p.replace(/\/+$/, '') || '/';
    } catch {
      return null;
    }
  };

  const menuItems = menuContainer.querySelectorAll(menuSelector);

  for (let i = 0; i < menuItems.length; i++) {
    const menuItem = menuItems[i];

    // Fast path: if there are submenu links, check them and stop.
    const childLinks = menuItem.querySelectorAll('.menu-sub .menu-link[href]');
    if (childLinks.length) {
      for (let j = 0; j < childLinks.length; j++) {
        const link = childLinks[j];
        const linkPath = normalizePath(link.getAttribute('href'));
        if (linkPath && linkPath === currentPath) {
          link.classList.add('active');
          menuItem.classList.add('here', 'show');
          break;
        }
      }
      continue;
    }

    // Otherwise, check the primary link for this item
    const anchor =
      menuItem.querySelector(':scope > a[href]') ||
      menuItem.querySelector(':scope .menu-link[href]');
    if (!anchor) continue;

    const anchorPath = normalizePath(anchor.getAttribute('href'));
    if (anchorPath && anchorPath === currentPath) {
      menuItem.classList.add('here');
    }
  }
};
